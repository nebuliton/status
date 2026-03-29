<?php

namespace App\Services\Status;

use App\Models\Service;
use App\Models\UptimeLog;

class UptimeCalculator
{
    public function recalculateForService(Service $service, int $windowDays = 90): float
    {
        $logs = UptimeLog::query()
            ->whereBelongsTo($service)
            ->where('recorded_at', '>=', now()->subDays($windowDays))
            ->get();

        if ($logs->isEmpty()) {
            $service->forceFill([
                'uptime_percentage' => 100,
            ])->saveQuietly();

            return 100.0;
        }

        $weightedAvailability = $logs
            ->sum(fn (UptimeLog $log) => $log->status->uptimeWeight());

        $percentage = round(($weightedAvailability / $logs->count()) * 100, 2);

        $service->forceFill([
            'uptime_percentage' => $percentage,
        ])->saveQuietly();

        return $percentage;
    }
}
