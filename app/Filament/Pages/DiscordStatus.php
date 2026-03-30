<?php

namespace App\Filament\Pages;

use App\Services\Discord\DiscordStatusService;
use App\Services\Status\StatusPageService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class DiscordStatus extends Page
{
    protected static ?string $title = 'Discord Status';

    protected static ?string $navigationLabel = 'Discord Status';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static string|UnitEnum|null $navigationGroup = 'Kommunikation';

    protected static ?int $navigationSort = 3;

    protected string|Width|null $maxContentWidth = 'full';

    protected ?string $subheading = 'Webhook, Snapshot-Versand und Embed-Vorschau für Discord.';

    protected string $view = 'filament.pages.discord-status';

    public string $webhookUrl = '';

    public bool $autoEnabled = false;

    public bool $includeImage = true;

    public bool $includeServiceLinks = true;

    public ?string $lastSentAt = null;

    public array $preview = [];

    public function mount(DiscordStatusService $discordStatusService, StatusPageService $statusPageService): void
    {
        $this->refreshData($discordStatusService, $statusPageService);
    }

    public function refreshData(DiscordStatusService $discordStatusService, StatusPageService $statusPageService): void
    {
        $settings = $discordStatusService->settings();

        $this->webhookUrl = (string) ($settings['webhook_url'] ?? '');
        $this->autoEnabled = (bool) ($settings['auto_enabled'] ?? false);
        $this->includeImage = (bool) ($settings['include_image'] ?? true);
        $this->includeServiceLinks = (bool) ($settings['include_service_links'] ?? true);
        $this->lastSentAt = $settings['last_sent_at'] ?? null;
        $this->preview = $statusPageService->overviewShareSnapshot();
    }

    public function saveSettings(DiscordStatusService $discordStatusService, StatusPageService $statusPageService): void
    {
        $discordStatusService->updateSettings([
            'webhook_url' => $this->webhookUrl,
            'auto_enabled' => $this->autoEnabled,
            'include_image' => $this->includeImage,
            'include_service_links' => $this->includeServiceLinks,
        ]);

        $this->refreshData($discordStatusService, $statusPageService);

        Notification::make()
            ->title('Discord-Einstellungen gespeichert')
            ->color('success')
            ->send();
    }

    public function sendNow(DiscordStatusService $discordStatusService, StatusPageService $statusPageService): void
    {
        try {
            $result = $discordStatusService->sendSnapshot(
                automatic: false,
                force: true,
            );
        } catch (\Throwable $exception) {
            Notification::make()
                ->title('Discord-Versand fehlgeschlagen')
                ->body($exception->getMessage())
                ->color('danger')
                ->send();

            return;
        }

        $this->refreshData($discordStatusService, $statusPageService);

        Notification::make()
            ->title($result['status'] === 'sent' ? 'Discord-Snapshot gesendet' : 'Discord-Snapshot übersprungen')
            ->body($result['message'])
            ->color($result['status'] === 'sent' ? 'success' : 'gray')
            ->send();
    }
}
