<?php

namespace App\Console\Commands;

use App\Models\Service;
use App\Services\Status\ServiceCheckRunner;
use Illuminate\Console\Command;

class RunServiceChecks extends Command
{
    protected $signature = 'status:check-services
        {--service= : ID oder Slug eines einzelnen Dienstes}
        {--force : Erzwingt einen Check unabhängig vom Intervall}';

    protected $description = 'Führt die konfigurierten Health-Checks für Dienste aus.';

    public function handle(ServiceCheckRunner $serviceCheckRunner): int
    {
        $serviceOption = $this->option('service');

        if (filled($serviceOption)) {
            $service = Service::query()
                ->whereKey($serviceOption)
                ->orWhere('slug', $serviceOption)
                ->first();

            if ($service === null) {
                $this->error('Der angegebene Dienst wurde nicht gefunden.');

                return self::FAILURE;
            }

            if (! $service->check_enabled) {
                $this->warn('Für diesen Dienst ist die Überwachung deaktiviert.');

                return self::INVALID;
            }

            $result = $serviceCheckRunner->run($service);

            $this->line(sprintf(
                '%s [%s] %s',
                $service->name,
                $result->status->label(),
                $result->message,
            ));

            return self::SUCCESS;
        }

        $results = $serviceCheckRunner->runDueChecks((bool) $this->option('force'));

        if ($results->isEmpty()) {
            $this->info('Keine Dienste waren für einen Check fällig.');

            return self::SUCCESS;
        }

        $results->each(function (array $payload): void {
            $this->line(sprintf(
                '%s [%s] %s',
                $payload['service']->name,
                $payload['result']->status->label(),
                $payload['result']->message,
            ));
        });

        $this->newLine();
        $this->info("Checks ausgeführt: {$results->count()}");

        return self::SUCCESS;
    }
}
