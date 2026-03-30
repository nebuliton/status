<?php

namespace App\Console\Commands;

use App\Services\ApplicationUpdateService;
use Illuminate\Console\Command;

class AppUpdateCommand extends Command
{
    protected $signature = 'app:update
        {--check : Prüft nur den Update-Status}
        {--auto : Führt einen automatischen Lauf aus}
        {--json : Gibt das Ergebnis als JSON aus}';

    protected $description = 'Prüft und installiert freigegebene App-Updates';

    public function handle(ApplicationUpdateService $updateService): int
    {
        if ((bool) $this->option('check')) {
            $status = $updateService->status();

            if ((bool) $this->option('json')) {
                $this->line(json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                return $status['healthy'] ? self::SUCCESS : self::FAILURE;
            }

            $this->table(
                ['Feld', 'Wert'],
                [
                    ['Lokale Version', (string) data_get($status, 'local.version', 'unbekannt')],
                    ['Remote Version', (string) data_get($status, 'remote.version', 'unbekannt')],
                    ['Branch', (string) ($status['branch'] ?? 'unbekannt')],
                    ['Repository', (string) ($status['repository_url'] ?? 'unbekannt')],
                    ['Update verfügbar', ($status['update_available'] ?? false) ? 'ja' : 'nein'],
                    ['Update möglich', ($status['can_update'] ?? false) ? 'ja' : 'nein'],
                    ['Auto-Update', ($status['auto_update_enabled'] ?? false) ? 'ja' : 'nein'],
                    ['Fehler', (string) (($status['error'] ?? '-') ?: '-')],
                ],
            );

            return $status['healthy'] ? self::SUCCESS : self::FAILURE;
        }

        $result = $updateService->run(
            actorUserId: null,
            automatic: (bool) $this->option('auto'),
            output: fn (string $line) => $this->line($line),
        );

        if ((bool) $this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        return in_array($result['status'], ['succeeded', 'skipped'], true)
            ? self::SUCCESS
            : self::FAILURE;
    }
}
