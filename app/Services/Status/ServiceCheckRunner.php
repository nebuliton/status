<?php

namespace App\Services\Status;

use App\Models\Service;
use App\Models\UptimeLog;
use App\Services\Status\Checks\CheckResult;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ServiceCheckRunner
{
    public function __construct(
        protected ServiceCheckManager $serviceCheckManager,
    ) {}

    /**
     * @return Collection<int, array{service: Service, result: CheckResult}>
     */
    public function runDueChecks(bool $force = false): Collection
    {
        $now = CarbonImmutable::now();

        return Service::query()
            ->where('check_enabled', true)
            ->orderBy('name')
            ->get()
            ->filter(fn (Service $service) => $force || $service->isDueForCheck($now))
            ->values()
            ->map(fn (Service $service) => [
                'service' => $service,
                'result' => $this->run($service),
            ]);
    }

    public function run(Service $service): CheckResult
    {
        $result = $this->serviceCheckManager->check($service);

        DB::transaction(function () use ($service, $result): void {
            $service->forceFill([
                'status' => $result->status,
                'last_checked_at' => $result->checkedAt,
                'last_response_time_ms' => $result->responseTimeMs,
                'last_check_message' => $result->message,
            ])->save();

            UptimeLog::query()->create([
                'service_id' => $service->id,
                'status' => $result->status->value,
                'recorded_at' => $result->checkedAt,
            ]);
        });

        return $result;
    }
}
