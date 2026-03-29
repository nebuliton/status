<?php

namespace App\Services\Status\Checks;

use App\Enums\ServiceStatus;
use App\Models\Service;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Throwable;

class PingHealthChecker implements HealthChecker
{
    public function __construct(
        protected LatencyStatusResolver $latencyStatusResolver,
    ) {}

    public function check(Service $service): CheckResult
    {
        if (blank($service->target_host)) {
            return new CheckResult(ServiceStatus::Down, 'Es ist kein Zielhost für den Ping konfiguriert.');
        }

        $timeoutSeconds = max(1, $service->timeout_seconds);
        $timeoutMilliseconds = $timeoutSeconds * 1000;
        $startedAt = hrtime(true);

        try {
            $process = Process::timeout($timeoutSeconds + 2)->run(
                $this->commandForHost($service->target_host, $timeoutSeconds, $timeoutMilliseconds),
            );
        } catch (Throwable $exception) {
            return new CheckResult(
                ServiceStatus::Down,
                'Ping-Check konnte nicht ausgeführt werden: '.$exception->getMessage(),
                $this->elapsedMilliseconds($startedAt),
            );
        }

        $fallbackTimeMs = $this->elapsedMilliseconds($startedAt);
        $responseTimeMs = $this->extractLatency($process->output()) ?? $fallbackTimeMs;

        if (! $process->successful()) {
            $output = Str::of($process->errorOutput().' '.$process->output())->squish()->limit(180)->value();

            return new CheckResult(
                ServiceStatus::Down,
                filled($output) ? "Ping fehlgeschlagen: {$output}" : 'Ping fehlgeschlagen.',
                $responseTimeMs,
            );
        }

        $status = $this->latencyStatusResolver->fromResponseTime($service, $responseTimeMs);

        return new CheckResult(
            $status,
            "Ping-Antwort von {$service->target_host} in {$responseTimeMs} ms.",
            $responseTimeMs,
        );
    }

    /**
     * @return array<int, string>
     */
    protected function commandForHost(string $host, int $timeoutSeconds, int $timeoutMilliseconds): array
    {
        return match (PHP_OS_FAMILY) {
            'Windows' => ['ping', '-n', '1', '-w', (string) $timeoutMilliseconds, $host],
            default => ['ping', '-c', '1', '-W', (string) $timeoutSeconds, $host],
        };
    }

    protected function extractLatency(string $output): ?int
    {
        if (preg_match('/(?:time|zeit)[=<]\s*(\d+(?:[.,]\d+)?)\s*ms/i', $output, $matches) === 1) {
            return max(1, (int) round((float) str_replace(',', '.', $matches[1])));
        }

        if (preg_match('/(?:Average|Mittelwert)\s*=\s*(\d+)ms/i', $output, $matches) === 1) {
            return max(1, (int) $matches[1]);
        }

        return null;
    }

    protected function elapsedMilliseconds(int $startedAt): int
    {
        return max(1, (int) round((hrtime(true) - $startedAt) / 1_000_000));
    }
}
