<?php

namespace App\Services\Status\Checks;

use App\Enums\ServiceStatus;
use App\Models\Service;
use PDO;
use PDOException;
use Throwable;

class DatabaseHealthChecker implements HealthChecker
{
    public function __construct(
        protected LatencyStatusResolver $latencyStatusResolver,
    ) {}

    public function check(Service $service): CheckResult
    {
        if (blank($service->database_driver) || blank($service->database_host) || blank($service->database_name)) {
            return new CheckResult(ServiceStatus::Down, 'Die Datenbank-Konfiguration ist unvollständig.');
        }

        $startedAt = hrtime(true);

        try {
            $pdo = new PDO(
                $this->dsn($service),
                $service->database_username,
                $service->database_password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => max(1, $service->timeout_seconds),
                ],
            );

            $statement = $pdo->query($service->database_query ?: 'SELECT 1');

            if ($statement !== false) {
                $statement->fetch();
            }
        } catch (PDOException $exception) {
            return new CheckResult(
                ServiceStatus::Down,
                'Datenbank-Check fehlgeschlagen: '.$exception->getMessage(),
                $this->elapsedMilliseconds($startedAt),
            );
        } catch (Throwable $exception) {
            return new CheckResult(
                ServiceStatus::Down,
                'Datenbank-Check konnte nicht ausgeführt werden: '.$exception->getMessage(),
                $this->elapsedMilliseconds($startedAt),
            );
        }

        $responseTimeMs = $this->elapsedMilliseconds($startedAt);
        $status = $this->latencyStatusResolver->fromResponseTime($service, $responseTimeMs);

        return new CheckResult(
            $status,
            "Datenbank-Verbindung und Testabfrage in {$responseTimeMs} ms erfolgreich.",
            $responseTimeMs,
        );
    }

    protected function dsn(Service $service): string
    {
        return match ($service->database_driver) {
            'pgsql' => sprintf(
                'pgsql:host=%s;port=%d;dbname=%s',
                $service->database_host,
                $service->database_port ?? 5432,
                $service->database_name,
            ),
            'sqlsrv' => sprintf(
                'sqlsrv:Server=%s,%d;Database=%s',
                $service->database_host,
                $service->database_port ?? 1433,
                $service->database_name,
            ),
            default => sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $service->database_host,
                $service->database_port ?? 3306,
                $service->database_name,
            ),
        };
    }

    protected function elapsedMilliseconds(int $startedAt): int
    {
        return max(1, (int) round((hrtime(true) - $startedAt) / 1_000_000));
    }
}
