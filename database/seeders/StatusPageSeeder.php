<?php

namespace Database\Seeders;

use App\Enums\IncidentStatus;
use App\Enums\MaintenanceStatus;
use App\Enums\ServiceCheckType;
use App\Enums\ServiceStatus;
use App\Models\Announcement;
use App\Models\Incident;
use App\Models\Maintenance;
use App\Models\Service;
use App\Models\ServiceGroup;
use App\Models\Subscriber;
use App\Models\UptimeLog;
use App\Services\Status\UptimeCalculator;
use Illuminate\Database\Seeder;

class StatusPageSeeder extends Seeder
{
    public function run(): void
    {
        $calculator = app(UptimeCalculator::class);

        collect($this->serviceBlueprints())
            ->each(function (array $groupData, int $index) use ($calculator): void {
                $group = ServiceGroup::query()
                    ->whereIn('name', [
                        $groupData['name'],
                        ...($groupData['legacy_names'] ?? []),
                    ])
                    ->firstOrNew();

                $group->fill([
                    'name' => $groupData['name'],
                    'order' => $index + 1,
                ])->save();

                foreach ($groupData['services'] as $serviceData) {
                    $service = Service::query()->updateOrCreate(
                        ['slug' => $serviceData['slug']],
                        [
                            'group_id' => $group->id,
                            'name' => $serviceData['name'],
                            'status' => $serviceData['status'],
                            'check_type' => $serviceData['check_type'],
                            'check_enabled' => $serviceData['check_enabled'] ?? false,
                            'check_interval_seconds' => $serviceData['check_interval_seconds'] ?? 60,
                            'timeout_seconds' => $serviceData['timeout_seconds'] ?? 5,
                            'target_url' => $serviceData['target_url'] ?? null,
                            'target_host' => $serviceData['target_host'] ?? null,
                            'target_port' => $serviceData['target_port'] ?? null,
                            'expected_status_code' => $serviceData['expected_status_code'] ?? null,
                            'verify_ssl' => $serviceData['verify_ssl'] ?? true,
                            'latency_degraded_ms' => $serviceData['latency_degraded_ms'] ?? 800,
                            'latency_down_ms' => $serviceData['latency_down_ms'] ?? 2500,
                            'database_driver' => $serviceData['database_driver'] ?? null,
                            'database_host' => $serviceData['database_host'] ?? null,
                            'database_port' => $serviceData['database_port'] ?? null,
                            'database_name' => $serviceData['database_name'] ?? null,
                            'database_username' => $serviceData['database_username'] ?? null,
                            'database_password' => $serviceData['database_password'] ?? null,
                            'database_query' => $serviceData['database_query'] ?? null,
                        ],
                    );

                    UptimeLog::query()->whereBelongsTo($service)->delete();

                    UptimeLog::withoutEvents(function () use ($service, $serviceData): void {
                        foreach (range(89, 0) as $daysAgo) {
                            UptimeLog::query()->create([
                                'service_id' => $service->id,
                                'status' => $this->historyStatus($serviceData['status'], $daysAgo)->value,
                                'recorded_at' => now()->subDays($daysAgo)->setTime(12, 0),
                            ]);
                        }
                    });

                    $calculator->recalculateForService($service);
                }
            });

        $this->seedIncidents();
        $this->seedMaintenances();
        $this->seedAnnouncements();
        $this->seedSubscribers();
    }

    /**
     * @return array<int, array{name: string, legacy_names: array<int, string>, services: array<int, array<string, mixed>>}>
     */
    protected function serviceBlueprints(): array
    {
        return [
            [
                'name' => 'Infrastruktur',
                'legacy_names' => ['Infrastructure'],
                'services' => [
                    [
                        'name' => 'Edge-Netzwerk',
                        'slug' => 'edge-network',
                        'status' => ServiceStatus::Operational,
                        'check_type' => ServiceCheckType::Ping,
                        'target_host' => '1.1.1.1',
                    ],
                    [
                        'name' => 'Warteschlangen-Worker',
                        'slug' => 'queue-workers',
                        'status' => ServiceStatus::Operational,
                        'check_type' => ServiceCheckType::Tcp,
                        'target_host' => '1.1.1.1',
                        'target_port' => 443,
                    ],
                    [
                        'name' => 'Objektspeicher',
                        'slug' => 'object-storage',
                        'status' => ServiceStatus::Degraded,
                        'check_type' => ServiceCheckType::Website,
                        'target_url' => 'https://example.com',
                    ],
                ],
            ],
            [
                'name' => 'API',
                'legacy_names' => [],
                'services' => [
                    [
                        'name' => 'Öffentliche API',
                        'slug' => 'public-api',
                        'status' => ServiceStatus::Operational,
                        'check_type' => ServiceCheckType::Website,
                        'target_url' => 'https://example.com',
                    ],
                    [
                        'name' => 'Authentifizierungs-API',
                        'slug' => 'auth-api',
                        'status' => ServiceStatus::Operational,
                        'check_type' => ServiceCheckType::Tcp,
                        'target_host' => '1.1.1.1',
                        'target_port' => 443,
                    ],
                    [
                        'name' => 'Abrechnungs-API',
                        'slug' => 'billing-api',
                        'status' => ServiceStatus::Operational,
                        'check_type' => ServiceCheckType::Ping,
                        'target_host' => '8.8.8.8',
                    ],
                ],
            ],
            [
                'name' => 'Frontend',
                'legacy_names' => [],
                'services' => [
                    [
                        'name' => 'Status-Webseite',
                        'slug' => 'status-website',
                        'status' => ServiceStatus::Operational,
                        'check_type' => ServiceCheckType::Website,
                        'target_url' => 'https://example.com',
                    ],
                    [
                        'name' => 'Kunden-Dashboard',
                        'slug' => 'customer-dashboard',
                        'status' => ServiceStatus::Operational,
                        'check_type' => ServiceCheckType::Website,
                        'target_url' => 'https://example.com',
                    ],
                    [
                        'name' => 'Bezahlvorgang',
                        'slug' => 'checkout-flow',
                        'status' => ServiceStatus::Operational,
                        'check_type' => ServiceCheckType::Database,
                        'database_driver' => 'mysql',
                        'database_host' => null,
                        'database_port' => 3306,
                        'database_name' => null,
                        'database_query' => 'SELECT 1',
                    ],
                ],
            ],
        ];
    }

    protected function historyStatus(ServiceStatus $currentStatus, int $daysAgo): ServiceStatus
    {
        if ($currentStatus === ServiceStatus::Degraded) {
            return match (true) {
                $daysAgo <= 3 => ServiceStatus::Degraded,
                in_array($daysAgo, [14, 15, 16, 31], true) => ServiceStatus::Degraded,
                $daysAgo === 32 => ServiceStatus::Down,
                default => ServiceStatus::Operational,
            };
        }

        return match (true) {
            in_array($daysAgo, [10, 44], true) => ServiceStatus::Degraded,
            default => ServiceStatus::Operational,
        };
    }

    protected function seedIncidents(): void
    {
        $activeIncident = Incident::query()
            ->whereIn('title', [
                'Elevated latency on object storage',
                'Erhöhte Latenz im Objektspeicher',
            ])
            ->firstOrNew();

        $activeIncident->fill([
            'title' => 'Erhöhte Latenz im Objektspeicher',
            'description' => 'Dateioperationen über den Objektspeicher reagieren langsamer als üblich, während Gegenmaßnahmen ausgerollt werden.',
            'status' => IncidentStatus::Monitoring,
        ])->save();

        $activeIncident->updates()->delete();
        $activeIncident->updates()->createMany([
            [
                'message' => 'Wir haben erhöhte Datenträgerlatenzen in einem Speicher-Node-Pool identifiziert und Last von den am stärksten ausgelasteten Replikaten verlagert.',
                'status' => IncidentStatus::Identified,
                'created_at' => now()->subHours(3),
            ],
            [
                'message' => 'Nach der Neuverteilung ist der Datenverkehr stabil. Wir beobachten die Antwortzeiten, während die verbleibenden Nodes ihre Kompaktierung abschließen.',
                'status' => IncidentStatus::Monitoring,
                'created_at' => now()->subHour(),
            ],
        ]);

        $resolvedIncident = Incident::query()
            ->whereIn('title', [
                'Public API timeout spike',
                'Erhöhte Timeouts in der öffentlichen API',
            ])
            ->firstOrNew();

        $resolvedIncident->fill([
            'title' => 'Erhöhte Timeouts in der öffentlichen API',
            'description' => 'Eine kurzfristige Sättigung im Upstream führte vorübergehend zu erhöhten 504-Antworten.',
            'status' => IncidentStatus::Resolved,
        ])->save();

        $resolvedIncident->updates()->delete();
        $resolvedIncident->updates()->createMany([
            [
                'message' => 'Wir haben eine Spitze in der Upstream-Sättigung beobachtet und nicht kritischen Hintergrundverkehr reduziert.',
                'status' => IncidentStatus::Investigating,
                'created_at' => now()->subDays(5)->subHours(3),
            ],
            [
                'message' => 'Die Timeouts haben sich nach Traffic-Shaping und dem Zurücksetzen der Verbindungspools normalisiert. Es wurde kein Datenverlust festgestellt.',
                'status' => IncidentStatus::Resolved,
                'created_at' => now()->subDays(5)->subHour(),
            ],
        ]);
    }

    protected function seedMaintenances(): void
    {
        $plannedMaintenance = Maintenance::query()
            ->whereIn('title', [
                'Planned database engine patching',
                'Geplante Patches für die Datenbank-Engine',
            ])
            ->firstOrNew();

        $plannedMaintenance->fill([
            'title' => 'Geplante Patches für die Datenbank-Engine',
            'description' => 'Die primären Datenbank-Hosts erhalten während des geplanten Wartungsfensters Sicherheitsupdates.',
            'scheduled_at' => now()->addDays(2)->setTime(23, 0),
            'status' => MaintenanceStatus::Scheduled,
        ])->save();

        $completedMaintenance = Maintenance::query()
            ->whereIn('title', [
                'Regional cache fleet refresh',
                'Erneuerung des regionalen Cache-Clusters',
            ])
            ->firstOrNew();

        $completedMaintenance->fill([
            'title' => 'Erneuerung des regionalen Cache-Clusters',
            'description' => 'Der rollierende Austausch der Cache-Nodes wurde abgeschlossen, um Kaltstarts und Konsistenz zu verbessern.',
            'scheduled_at' => now()->subDays(7)->setTime(1, 0),
            'status' => MaintenanceStatus::Completed,
        ])->save();
    }

    protected function seedAnnouncements(): void
    {
        $databaseAnnouncement = Announcement::query()
            ->whereIn('title', [
                'Status notifications are now backed by the central platform database',
                'Statusbenachrichtigungen laufen jetzt über die zentrale Plattformdatenbank',
            ])
            ->firstOrNew();

        $databaseAnnouncement->fill([
            'title' => 'Statusbenachrichtigungen laufen jetzt über die zentrale Plattformdatenbank',
            'excerpt' => 'Die Nebuliton-Statusseite speichert Vorfälle, Wartungsfenster und Abonnements jetzt vollständig in MySQL-Tabellen.',
            'content' => 'Mit diesem Rollout entfällt die dateibasierte Statuspflege. Die gesamte öffentliche Seite wird nun von Eloquent-Modellen, Livewire und über Filament verwalteten Inhalten gesteuert.',
            'is_pinned' => true,
            'published_at' => now()->subDays(1),
        ])->save();

        $visibilityAnnouncement = Announcement::query()
            ->whereIn('title', [
                'Improved uptime visibility for customer-facing services',
                'Verbesserte Sichtbarkeit der Verfügbarkeit für kundennahe Dienste',
            ])
            ->firstOrNew();

        $visibilityAnnouncement->fill([
            'title' => 'Verbesserte Sichtbarkeit der Verfügbarkeit für kundennahe Dienste',
            'excerpt' => 'Jeder überwachte Dienst zeigt auf der öffentlichen Statusseite jetzt eine rollierende 90-Tage-Historie an.',
            'content' => 'Die neue Darstellung hebt Dienstgruppen, aktuellen Zustand, Verfügbarkeitswerte und historische Segmente hervor, ohne dass ein Seitenreload nötig ist.',
            'is_pinned' => false,
            'published_at' => now()->subDays(6),
        ])->save();
    }

    protected function seedSubscribers(): void
    {
        Subscriber::query()->updateOrCreate(
            ['email' => 'ops@nebuliton.test'],
            ['verified_at' => now(), 'is_active' => true],
        );

        Subscriber::query()->updateOrCreate(
            ['email' => 'support@nebuliton.test'],
            ['verified_at' => now(), 'is_active' => true],
        );
    }
}
