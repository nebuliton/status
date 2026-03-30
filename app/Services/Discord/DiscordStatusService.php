<?php

namespace App\Services\Discord;

use App\Services\AppSettingsService;
use App\Services\Status\StatusPageService;
use App\Services\VersionManifestService;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DiscordStatusService
{
    public function __construct(
        protected AppSettingsService $appSettingsService,
        protected StatusPageService $statusPageService,
        protected VersionManifestService $versionManifestService,
    ) {}

    public function settings(): array
    {
        return [
            'webhook_url' => $this->appSettingsService->get('discord_status_webhook_url', config('services.discord.status_webhook_url')),
            'auto_enabled' => $this->appSettingsService->boolean('discord_status_auto_enabled'),
            'include_image' => $this->appSettingsService->boolean('discord_status_include_image', true),
            'include_service_links' => $this->appSettingsService->boolean('discord_status_include_service_links', true),
            'last_sent_at' => $this->appSettingsService->get('discord_status_last_sent_at'),
            'last_hash' => $this->appSettingsService->get('discord_status_last_hash'),
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function updateSettings(array $settings): void
    {
        $this->appSettingsService->update([
            'discord_status_webhook_url' => trim((string) ($settings['webhook_url'] ?? '')),
            'discord_status_auto_enabled' => ! empty($settings['auto_enabled']) ? '1' : '0',
            'discord_status_include_image' => ! empty($settings['include_image']) ? '1' : '0',
            'discord_status_include_service_links' => ! empty($settings['include_service_links']) ? '1' : '0',
        ]);
    }

    public function sendSnapshot(bool $automatic = false, bool $force = false): array
    {
        $settings = $this->settings();

        if ($automatic && ! $settings['auto_enabled']) {
            return [
                'status' => 'skipped',
                'message' => 'Automatische Discord-Snapshots sind deaktiviert.',
            ];
        }

        if (blank($settings['webhook_url'])) {
            return [
                'status' => 'skipped',
                'message' => 'Es ist keine Discord-Webhook-URL konfiguriert.',
            ];
        }

        $snapshot = $this->statusPageService->overviewShareSnapshot();
        $hash = $snapshot['shareHash'];

        if (! $force && filled($settings['last_hash']) && $settings['last_hash'] === $hash) {
            return [
                'status' => 'skipped',
                'message' => 'Seit dem letzten Discord-Snapshot gab es keine Statusänderung.',
            ];
        }

        $payload = $this->buildPayload($snapshot, $settings, $hash);
        $response = Http::timeout(20)
            ->asJson()
            ->post($settings['webhook_url'], $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Discord hat das Embed nicht akzeptiert: '.$response->status());
        }

        $timestamp = now()->toIso8601String();

        $this->appSettingsService->update([
            'discord_status_last_hash' => $hash,
            'discord_status_last_sent_at' => $timestamp,
        ]);

        return [
            'status' => 'sent',
            'message' => 'Discord-Snapshot erfolgreich gesendet.',
            'last_sent_at' => $timestamp,
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    protected function buildPayload(array $snapshot, array $settings, string $hash): array
    {
        $version = $this->versionManifestService->safeReadLocal()['version'];
        $embed = [
            'title' => 'Nebuliton Status',
            'description' => $snapshot['globalMessage']."\nStand ".$snapshot['lastUpdatedLabel'],
            'url' => route('status.index'),
            'color' => $this->discordColor($snapshot['globalStatus']->value),
            'fields' => collect($snapshot['services'])->take(7)->map(function (array $service) use ($settings): array {
                $value = $service['status']->label().' · '.number_format((float) $service['uptime_percentage'], 2, ',', '.')." %";

                if (! empty($settings['include_service_links'])) {
                    $value .= "\n".route('status.service.show', ['service' => $service['slug']]);
                }

                return [
                    'name' => $service['name'],
                    'value' => $value,
                    'inline' => false,
                ];
            })->all(),
            'footer' => [
                'text' => 'Nebuliton Status · Version '.$version,
                'icon_url' => config('services.nebuliton.logo_url'),
            ],
            'timestamp' => ($snapshot['lastUpdatedAt'] ?? now())->toIso8601String(),
        ];

        if (! empty($settings['include_image'])) {
            $embed['image'] = [
                'url' => route('status.overview.image', ['v' => substr($hash, 0, 12)]),
            ];
        }

        return [
            'username' => 'Nebuliton Status',
            'avatar_url' => config('services.nebuliton.logo_url'),
            'embeds' => [$embed],
        ];
    }

    protected function discordColor(string $status): int
    {
        return match ($status) {
            'operational' => hexdec('10B981'),
            'degraded' => hexdec('F59E0B'),
            'down' => hexdec('F43F5E'),
            default => hexdec('6366F1'),
        };
    }
}
