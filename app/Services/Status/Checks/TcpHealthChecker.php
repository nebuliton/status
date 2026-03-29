<?php

namespace App\Services\Status\Checks;

use App\Enums\ServiceStatus;
use App\Models\Service;

class TcpHealthChecker implements HealthChecker
{
    public function __construct(
        protected LatencyStatusResolver $latencyStatusResolver,
    ) {}

    public function check(Service $service): CheckResult
    {
        if (blank($service->target_host) || blank($service->target_port)) {
            return new CheckResult(ServiceStatus::Down, 'Host oder Port für den TCP-Check fehlen.');
        }

        $startedAt = hrtime(true);
        $errorCode = 0;
        $errorMessage = '';

        set_error_handler(static function () {
            return true;
        });

        try {
            $connection = stream_socket_client(
                "tcp://{$service->target_host}:{$service->target_port}",
                $errorCode,
                $errorMessage,
                max(1, $service->timeout_seconds),
            );
        } finally {
            restore_error_handler();
        }

        $responseTimeMs = $this->elapsedMilliseconds($startedAt);

        if ($connection === false) {
            $message = filled($errorMessage)
                ? "TCP-Verbindung fehlgeschlagen: {$errorMessage}"
                : 'TCP-Verbindung konnte nicht aufgebaut werden.';

            return new CheckResult(ServiceStatus::Down, $message, $responseTimeMs);
        }

        fclose($connection);

        $status = $this->latencyStatusResolver->fromResponseTime($service, $responseTimeMs);

        return new CheckResult(
            $status,
            "TCP-Port {$service->target_host}:{$service->target_port} in {$responseTimeMs} ms erreichbar.",
            $responseTimeMs,
        );
    }

    protected function elapsedMilliseconds(int $startedAt): int
    {
        return max(1, (int) round((hrtime(true) - $startedAt) / 1_000_000));
    }
}
