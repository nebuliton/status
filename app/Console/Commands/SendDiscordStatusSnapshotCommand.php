<?php

namespace App\Console\Commands;

use App\Services\Discord\DiscordStatusService;
use Illuminate\Console\Command;

class SendDiscordStatusSnapshotCommand extends Command
{
    protected $signature = 'status:discord-snapshot
        {--auto : Führt nur den automatischen Versandpfad aus}
        {--force : Erzwingt den Versand auch ohne Statusänderung}';

    protected $description = 'Sendet eine aktuelle Statusübersicht als Discord-Embed';

    public function handle(DiscordStatusService $discordStatusService): int
    {
        try {
            $result = $discordStatusService->sendSnapshot(
                automatic: (bool) $this->option('auto'),
                force: (bool) $this->option('force'),
            );
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        match ($result['status']) {
            'sent' => $this->info($result['message']),
            default => $this->warn($result['message']),
        };

        return $result['status'] === 'sent'
            ? self::SUCCESS
            : self::FAILURE;
    }
}
