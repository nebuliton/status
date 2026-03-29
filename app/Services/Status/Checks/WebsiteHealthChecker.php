<?php

namespace App\Services\Status\Checks;

use App\Enums\ServiceStatus;
use App\Models\Service;
use Illuminate\Http\Client\PendingRequest;
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
            $response = $this->makeRequest($service)->get($service->target_url);
            $usedInsecureFallback = false;
        } catch (ConnectionException $exception) {
            if ($this->shouldRetryWithoutVerifying($service, $exception)) {
                try {
                    $response = $this->makeRequest($service, forceWithoutVerifying: true)->get($service->target_url);
                    $usedInsecureFallback = true;
                } catch (ConnectionException $retryException) {
                    return new CheckResult(
                        ServiceStatus::Down,
                        'HTTP-Check fehlgeschlagen: '.$retryException->getMessage(),
                        $this->elapsedMilliseconds($startedAt),
                    );
                } catch (Throwable $retryException) {
                    return new CheckResult(
                        ServiceStatus::Down,
                        'HTTP-Check konnte nicht ausgeführt werden: '.$retryException->getMessage(),
                        $this->elapsedMilliseconds($startedAt),
                    );
                }
            } else {
                return new CheckResult(
                    ServiceStatus::Down,
                    'HTTP-Check fehlgeschlagen: '.$exception->getMessage(),
                    $this->elapsedMilliseconds($startedAt),
                );
            }
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
        $message = "HTTP {$actualStatusCode} in {$responseTimeMs} ms.";

        if ($usedInsecureFallback ?? false) {
            $message .= ' Lokale Zertifikatsprüfung wurde automatisch übersprungen.';
        }

        return new CheckResult(
            $status,
            $message,
            $responseTimeMs,
        );
    }

    protected function makeRequest(Service $service, bool $forceWithoutVerifying = false): PendingRequest
    {
        $request = Http::timeout(max(1, $service->timeout_seconds));

        if ($forceWithoutVerifying || (! $service->verify_ssl)) {
            $request = $request->withoutVerifying();
        }

        return $request;
    }

    protected function shouldRetryWithoutVerifying(Service $service, ConnectionException $exception): bool
    {
        if (! app()->isLocal()) {
            return false;
        }

        if (! $service->verify_ssl) {
            return false;
        }

        $message = strtolower($exception->getMessage());

        return str_contains($message, 'curl error 60')
            || str_contains($message, 'ssl certificate problem')
            || str_contains($message, 'unable to get local issuer certificate');
    }

    protected function elapsedMilliseconds(int $startedAt): int
    {
        return max(1, (int) round((hrtime(true) - $startedAt) / 1_000_000));
    }
}
