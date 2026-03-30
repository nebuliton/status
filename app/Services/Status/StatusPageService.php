<?php

namespace App\Services\Status;

use App\Enums\IncidentStatus;
use App\Enums\MaintenanceStatus;
use App\Enums\ServiceStatus;
use App\Models\Announcement;
use App\Models\Incident;
use App\Models\Maintenance;
use App\Models\Service;
use App\Models\ServiceGroup;
use App\Models\UptimeLog;
use Carbon\CarbonInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatusPageService
{
    public function snapshot(int $days = 90): array
    {
        $snapshot = $this->rememberSnapshot(
            "status-page:snapshot:{$days}",
            fn (): array => $this->buildSnapshot($days),
        );

        return $this->withRelativeMeta($snapshot);
    }

    public function serviceSnapshot(Service $service, int $days = 90): array
    {
        $service->loadMissing('group');

        $snapshot = $this->rememberSnapshot(
            "status-page:service:{$service->getKey()}:{$days}",
            fn (): array => $this->buildServiceSnapshot($service, $days),
        );

        return $this->withRelativeMeta($snapshot);
    }

    public function overviewShareSnapshot(int $days = 90, int $limit = 8): array
    {
        $snapshot = $this->snapshot($days);
        $services = collect($snapshot['groups'])
            ->pluck('services')
            ->flatten(1)
            ->sortByDesc(fn (array $service) => $service['status']->severity())
            ->values();

        return [
            'days' => $snapshot['days'],
            'generatedAt' => $snapshot['generatedAt'],
            'lastUpdatedAt' => $snapshot['lastUpdatedAt'],
            'lastUpdatedLabel' => $snapshot['lastUpdatedLabel'],
            'globalStatus' => $snapshot['globalStatus'],
            'globalMessage' => $snapshot['globalMessage'],
            'statusBreakdown' => $snapshot['statusBreakdown'],
            'averageUptime' => $snapshot['averageUptime'],
            'services' => $services->take($limit)->values()->all(),
            'serviceCount' => $services->count(),
            'shareHash' => sha1(json_encode([
                'global_status' => $snapshot['globalStatus']->value,
                'last_updated_at' => $snapshot['lastUpdatedAt']?->toIso8601String(),
                'services' => $services->map(fn (array $service) => [
                    'slug' => Arr::get($service, 'slug'),
                    'status' => Arr::get($service, 'status')?->value,
                    'uptime' => Arr::get($service, 'uptime_percentage'),
                ])->all(),
            ])),
        ];
    }

    protected function buildSnapshot(int $days): array
    {
        $windowStart = now()->subDays($days - 1)->startOfDay();

        $groups = ServiceGroup::query()
            ->select(['id', 'name', 'order'])
            ->with([
                'services' => fn ($query) => $query
                    ->select([
                        'id',
                        'name',
                        'slug',
                        'status',
                        'uptime_percentage',
                        'group_id',
                        'check_type',
                        'icon_source',
                        'icon_name',
                        'icon_path',
                        'check_enabled',
                        'target_url',
                        'target_host',
                        'target_port',
                        'database_driver',
                        'database_host',
                        'database_name',
                        'last_checked_at',
                        'last_response_time_ms',
                        'last_check_message',
                    ])
                    ->orderBy('name'),
            ])
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        $services = $groups->pluck('services')->flatten()->values();
        $historyLogs = $this->dailyHistoryLogs($services, $windowStart);
        $globalStatus = $this->resolveGlobalStatus($services);
        $activeIncidents = $this->incidentCollection(false);
        $resolvedIncidents = $this->incidentCollection(true);
        $upcomingMaintenances = $this->maintenanceCollection(false);
        $completedMaintenances = $this->maintenanceCollection(true);
        $announcements = Announcement::query()
            ->published()
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->get();
        $lastUpdatedAt = $this->resolveLastUpdatedAt(
            $services,
            $activeIncidents,
            $resolvedIncidents,
            $upcomingMaintenances,
            $completedMaintenances,
            $announcements,
        );

        return [
            'days' => $days,
            'lastUpdatedAt' => $lastUpdatedAt,
            'globalStatus' => $globalStatus,
            'globalMessage' => $this->globalMessage($globalStatus, $services),
            'statusBreakdown' => $this->statusBreakdown($services),
            'averageUptime' => round($services->avg('uptime_percentage') ?? 100, 2),
            'groups' => $groups->map(
                fn (ServiceGroup $group) => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'services' => $group->services->map(
                        fn (Service $service) => $this->servicePayload(
                            $service,
                            $historyLogs->get($service->id, collect()),
                            $windowStart,
                            $days,
                        ),
                    ),
                ],
            ),
            'incidents' => [
                'active' => $activeIncidents,
                'resolved' => $resolvedIncidents,
            ],
            'maintenances' => [
                'upcoming' => $upcomingMaintenances,
                'completed' => $completedMaintenances,
            ],
            'announcements' => $announcements,
        ];
    }

    protected function buildServiceSnapshot(Service $service, int $days): array
    {
        $windowStart = now()->subDays($days - 1)->startOfDay();
        $historyLogs = $this->dailyHistoryLogs(collect([$service]), $windowStart)->get($service->id, collect());
        $payload = $this->servicePayload($service, $historyLogs, $windowStart, $days);
        $lastUpdatedAt = $service->last_checked_at instanceof CarbonInterface
            ? CarbonImmutable::instance($service->last_checked_at)
            : null;

        return [
            'days' => $days,
            'lastUpdatedAt' => $lastUpdatedAt,
            'service' => $payload,
            'description' => $this->serviceShareDescription($payload),
            'shareHash' => sha1(json_encode([
                'status' => $service->status->value,
                'uptime' => $payload['uptime_percentage'],
                'last_checked_at' => $service->last_checked_at?->toIso8601String(),
                'last_check_message' => $service->last_check_message,
            ])),
        ];
    }

    protected function withRelativeMeta(array $snapshot): array
    {
        $snapshot['generatedAt'] = now();
        $snapshot['lastUpdatedLabel'] = $this->lastUpdatedLabel($snapshot['lastUpdatedAt'] ?? null);

        return $snapshot;
    }

    protected function rememberSnapshot(string $key, callable $resolver): array
    {
        $ttl = (int) config('services.nebuliton.status_snapshot_cache_seconds', 15);
        $store = (string) config('services.nebuliton.status_snapshot_cache_store', 'file');

        if (app()->isLocal() || app()->runningUnitTests() || ($ttl < 1)) {
            return $resolver();
        }

        try {
            return Cache::store($store)->remember($key, now()->addSeconds($ttl), $resolver);
        } catch (\Throwable) {
            return Cache::remember($key, now()->addSeconds($ttl), $resolver);
        }
    }

    protected function resolveLastUpdatedAt(
        Collection $services,
        Collection $activeIncidents,
        Collection $resolvedIncidents,
        Collection $upcomingMaintenances,
        Collection $completedMaintenances,
        Collection $announcements,
    ): ?CarbonImmutable {
        return collect()
            ->merge($services->pluck('last_checked_at'))
            ->merge($activeIncidents->pluck('updated_at'))
            ->merge($resolvedIncidents->pluck('updated_at'))
            ->merge($upcomingMaintenances->pluck('updated_at'))
            ->merge($completedMaintenances->pluck('updated_at'))
            ->merge($announcements->pluck('updated_at'))
            ->filter(fn ($timestamp): bool => $timestamp instanceof CarbonInterface)
            ->map(fn (CarbonInterface $timestamp): CarbonImmutable => CarbonImmutable::instance($timestamp))
            ->sortDesc()
            ->first();
    }

    protected function serviceShareDescription(array $service): string
    {
        $statusLabel = $service['status']->label();
        $uptime = number_format((float) $service['uptime_percentage'], 2, ',', '.');
        $message = trim((string) ($service['last_check_message'] ?: $service['status']->description()));
        $message = mb_strimwidth($message, 0, 140, '…');

        return "{$service['name']} ist aktuell {$statusLabel}. Verfügbarkeit {$uptime} %. {$message}";
    }

    protected function lastUpdatedLabel(?CarbonImmutable $lastUpdatedAt): string
    {
        if (! $lastUpdatedAt) {
            return 'Noch keine Aktualisierung';
        }

        if ($lastUpdatedAt->diffInSeconds(now()) < 10) {
            return 'gerade eben';
        }

        return $lastUpdatedAt->diffForHumans();
    }

    protected function resolveGlobalStatus(Collection $services): ServiceStatus
    {
        $highestSeverity = $services
            ->map(fn (Service $service) => $service->status->severity())
            ->max();

        return match ($highestSeverity) {
            2 => ServiceStatus::Down,
            1 => ServiceStatus::Degraded,
            default => ServiceStatus::Operational,
        };
    }

    protected function globalMessage(ServiceStatus $globalStatus, Collection $services): string
    {
        if ($services->isEmpty()) {
            return 'Statusdaten werden vorbereitet.';
        }

        return match ($globalStatus) {
            ServiceStatus::Operational => 'Alle Systeme betriebsbereit',
            ServiceStatus::Degraded => 'Teilweise Beeinträchtigungen',
            ServiceStatus::Down => 'Größere Dienststörung',
        };
    }

    protected function statusBreakdown(Collection $services): array
    {
        return [
            'operational' => $services->filter(fn (Service $service) => $service->status === ServiceStatus::Operational)->count(),
            'degraded' => $services->filter(fn (Service $service) => $service->status === ServiceStatus::Degraded)->count(),
            'down' => $services->filter(fn (Service $service) => $service->status === ServiceStatus::Down)->count(),
        ];
    }

    protected function historyForService(Collection $logs, CarbonImmutable $windowStart, int $days): array
    {
        $logsByDay = $logs
            ->groupBy(fn ($log) => $log->recorded_at->toDateString())
            ->map(fn (Collection $logs) => $logs->sortBy('recorded_at')->last());

        $history = [];
        $lastKnownStatus = null;

        for ($offset = 0; $offset < $days; $offset++) {
            $date = $windowStart->addDays($offset);
            $log = $logsByDay->get($date->toDateString());

            if ($log !== null) {
                $lastKnownStatus = $log->status;
            }

            $history[] = [
                'date' => $date,
                'status' => $lastKnownStatus,
            ];
        }

        return $history;
    }

    protected function dailyHistoryLogs(Collection $services, CarbonImmutable $windowStart): Collection
    {
        if ($services->isEmpty()) {
            return collect();
        }

        $dateExpression = match (DB::getDriverName()) {
            'sqlite' => "strftime('%Y-%m-%d', recorded_at)",
            default => 'DATE(recorded_at)',
        };

        $latestLogsPerDay = UptimeLog::query()
            ->selectRaw("service_id, {$dateExpression} as recorded_day, MAX(recorded_at) as latest_recorded_at")
            ->whereIn('service_id', $services->pluck('id')->all())
            ->where('recorded_at', '>=', $windowStart)
            ->groupBy('service_id', DB::raw($dateExpression));

        return UptimeLog::query()
            ->joinSub($latestLogsPerDay, 'latest_daily_logs', function ($join): void {
                $join->on('uptime_logs.service_id', '=', 'latest_daily_logs.service_id')
                    ->on('uptime_logs.recorded_at', '=', 'latest_daily_logs.latest_recorded_at');
            })
            ->select('uptime_logs.id', 'uptime_logs.service_id', 'uptime_logs.status', 'uptime_logs.recorded_at')
            ->orderBy('uptime_logs.recorded_at')
            ->get()
            ->groupBy('service_id');
    }

    protected function incidentCollection(bool $resolved): Collection
    {
        return Incident::query()
            ->with('updates')
            ->when(
                $resolved,
                fn ($query) => $query->where('status', IncidentStatus::Resolved),
                fn ($query) => $query->where('status', '!=', IncidentStatus::Resolved),
            )
            ->latest()
            ->get();
    }

    protected function maintenanceCollection(bool $completed): Collection
    {
        return Maintenance::query()
            ->when(
                $completed,
                fn ($query) => $query->where('status', MaintenanceStatus::Completed),
                fn ($query) => $query->where('status', '!=', MaintenanceStatus::Completed),
            )
            ->orderByDesc('scheduled_at')
            ->get();
    }

    protected function servicePayload(
        Service $service,
        Collection $historyLogs,
        CarbonImmutable $windowStart,
        int $days,
    ): array {
        return [
            'id' => $service->id,
            'name' => $service->name,
            'slug' => $service->slug,
            'group_name' => $service->group?->name,
            'status' => $service->status,
            'check_type' => $service->check_type,
            'check_enabled' => $service->check_enabled,
            'target' => $service->monitorTarget(),
            'icon' => $service->resolvedIcon(),
            'last_checked_at' => $service->last_checked_at,
            'last_response_time_ms' => $service->last_response_time_ms,
            'last_check_message' => $service->last_check_message,
            'uptime_percentage' => (float) $service->uptime_percentage,
            'history' => $this->historyForService($historyLogs, $windowStart, $days),
        ];
    }
}
