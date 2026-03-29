<?php

namespace App\Services\Status\Checks;

use App\Enums\ServiceStatus;
use App\Models\Service;

class LatencyStatusResolver
{
    public function fromResponseTime(Service $service, ?int $responseTimeMs): ServiceStatus
    {
        if ($responseTimeMs === null) {
            return ServiceStatus::Operational;
        }

        if (($service->latency_down_ms !== null) && ($responseTimeMs >= $service->latency_down_ms)) {
            return ServiceStatus::Down;
        }

        if (($service->latency_degraded_ms !== null) && ($responseTimeMs >= $service->latency_degraded_ms)) {
            return ServiceStatus::Degraded;
        }

        return ServiceStatus::Operational;
    }
}
