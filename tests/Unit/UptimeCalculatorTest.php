<?php

use App\Enums\ServiceStatus;
use App\Models\Service;
use App\Models\ServiceGroup;
use App\Models\UptimeLog;
use App\Services\Status\UptimeCalculator;

it('recalculates uptime percentage using weighted service states', function () {
    $group = ServiceGroup::factory()->create();

    $service = Service::factory()->create([
        'group_id' => $group->id,
        'status' => ServiceStatus::Operational,
        'uptime_percentage' => 100,
    ]);

    UptimeLog::withoutEvents(function () use ($service): void {
        UptimeLog::query()->create([
            'service_id' => $service->id,
            'status' => ServiceStatus::Operational->value,
            'recorded_at' => now()->subDays(2),
        ]);

        UptimeLog::query()->create([
            'service_id' => $service->id,
            'status' => ServiceStatus::Degraded->value,
            'recorded_at' => now()->subDay(),
        ]);

        UptimeLog::query()->create([
            'service_id' => $service->id,
            'status' => ServiceStatus::Down->value,
            'recorded_at' => now(),
        ]);
    });

    $percentage = app(UptimeCalculator::class)->recalculateForService($service);

    expect($percentage)->toBe(50.0);
    expect((float) $service->fresh()->uptime_percentage)->toBe(50.0);
});
