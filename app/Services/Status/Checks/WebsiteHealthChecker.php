<?php

namespace App\Services\Status\Checks;

use App\Enums\ServiceStatus;
use App\Models\Service;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class WebsiteHealthChecker implements HealthChecker
{
    public function __construct(
        protected LatencyStatusResolver $latencyStatusResolver,
    ) {}

    public function check(Service $service): CheckResult
    {
        if (blank($service->target_url)) {
            return new CheckResult(ServiceStatus::Down, 'Es ist keine Ziel-URL hinterlegt.');
        }

        $startedAt = hrtime(true);

        try {
            $request = Http::timeout(max(1, $service->timeout_seconds));

            if (! $service->verify_ssl) {
                $request = $request->withoutVerifying();
            }

            $response = $request->get($service->target_url);
        } catch (ConnectionException $exception) {
            return new CheckResult(
                ServiceStatus::Down,
                'HTTP-Check fehlgeschlagen: '.$exception->getMessage(),
                $this->elapsedMilliseconds($startedAt),
            );
        } catch (Throwable $exception) {
            return new CheckResult(
                ServiceStatus::Down,
                'HTTP-Check konnte nicht ausgeführt werden: '.$exception->getMessage(),
                $this->elapsedMilliseconds($startedAt),
            );
        }

        $responseTimeMs = $this->elapsedMilliseconds($startedAt);
        $expectedStatusCode = $service->expected_status_code;
        $actualStatusCode = $response->status();

        $isExpectedStatus = $expectedStatusCode !== null
            ? $actualStatusCode === $expectedStatusCode
            : (($actualStatusCode >= 200) && ($actualStatusCode < 400));

        if (! $isExpectedStatus) {
            $message = $expectedStatusCode !== null
                ? "HTTP {$actualStatusCode} statt erwartetem Status {$expectedStatusCode}."
                : "HTTP {$actualStatusCode} zurückgegeben.";

            return new CheckResult(ServiceStatus::Down, $message, $responseTimeMs);
        }

        $status = $this->latencyStatusResolver->fromResponseTime($service, $responseTimeMs);

        return new CheckResult(
            $status,
            "HTTP {$actualStatusCode} in {$responseTimeMs} ms.",
            $responseTimeMs,
        );
    }

    protected function elapsedMilliseconds(int $startedAt): int
    {
        return max(1, (int) round((hrtime(true) - $startedAt) / 1_000_000));
    }
}
