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
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StatusPageService
{
    public function snapshot(int $days = 90): array
    {
        $windowStart = now()->subDays($days - 1)->startOfDay();

        $groups = ServiceGroup::query()
            ->with([
                'services' => fn ($query) => $query
                    ->orderBy('name'),
            ])
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        $services = $groups->pluck('services')->flatten()->values();
        $historyLogs = $this->dailyHistoryLogs($services, $windowStart);
        $globalStatus = $this->resolveGlobalStatus($services);

        return [
            'days' => $days,
            'generatedAt' => now(),
            'globalStatus' => $globalStatus,
            'globalMessage' => $this->globalMessage($globalStatus, $services),
            'statusBreakdown' => $this->statusBreakdown($services),
            'averageUptime' => round($services->avg('uptime_percentage') ?? 100, 2),
            'groups' => $groups->map(
                fn (ServiceGroup $group) => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'services' => $group->services->map(
                        fn (Service $service) => [
                            'id' => $service->id,
                            'name' => $service->name,
                            'slug' => $service->slug,
                            'status' => $service->status,
                            'check_type' => $service->check_type,
                            'check_enabled' => $service->check_enabled,
                            'target' => $service->monitorTarget(),
                            'last_checked_at' => $service->last_checked_at,
                            'last_response_time_ms' => $service->last_response_time_ms,
                            'last_check_message' => $service->last_check_message,
                            'uptime_percentage' => (float) $service->uptime_percentage,
                            'history' => $this->historyForService(
                                $historyLogs->get($service->id, collect()),
                                $windowStart,
                                $days,
                            ),
                        ],
                    ),
                ],
            ),
            'incidents' => [
                'active' => $this->incidentCollection(false),
                'resolved' => $this->incidentCollection(true),
            ],
            'maintenances' => [
                'upcoming' => $this->maintenanceCollection(false),
                'completed' => $this->maintenanceCollection(true),
            ],
            'announcements' => Announcement::query()
                ->published()
                ->orderByDesc('is_pinned')
                ->orderByDesc('published_at')
                ->get(),
        ];
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
}
