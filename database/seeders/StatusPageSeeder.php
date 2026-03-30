<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Incident;
use App\Models\Maintenance;
use App\Models\Service;
use App\Models\ServiceGroup;
use App\Models\Subscriber;
use App\Models\UptimeLog;
use Illuminate\Database\Seeder;

class StatusPageSeeder extends Seeder
{
    public function run(): void
    {
        $serviceSlugs = [
            'edge-network',
            'queue-workers',
            'object-storage',
            'public-api',
            'auth-api',
            'billing-api',
            'status-website',
            'customer-dashboard',
            'checkout-flow',
        ];

        $groupNames = [
            'Infrastruktur',
            'Infrastructure',
            'API',
            'Frontend',
        ];

        $incidentTitles = [
            'Elevated latency on object storage',
            'Erhöhte Latenz im Objektspeicher',
            'Public API timeout spike',
            'Erhöhte Timeouts in der öffentlichen API',
        ];

        $maintenanceTitles = [
            'Planned database engine patching',
            'Geplante Patches für die Datenbank-Engine',
            'Regional cache fleet refresh',
            'Erneuerung des regionalen Cache-Clusters',
        ];

        $announcementTitles = [
            'Status notifications are now backed by the central platform database',
            'Statusbenachrichtigungen laufen jetzt über die zentrale Plattformdatenbank',
            'Improved uptime visibility for customer-facing services',
            'Verbesserte Sichtbarkeit der Verfügbarkeit für kundennahe Dienste',
        ];

        $subscriberEmails = [
            'ops@nebuliton.test',
            'support@nebuliton.test',
        ];

        $serviceIds = Service::query()
            ->whereIn('slug', $serviceSlugs)
            ->pluck('id');

        if ($serviceIds->isNotEmpty()) {
            UptimeLog::query()->whereIn('service_id', $serviceIds)->delete();
            Service::query()->whereKey($serviceIds)->delete();
        }

        Incident::query()->whereIn('title', $incidentTitles)->delete();
        Maintenance::query()->whereIn('title', $maintenanceTitles)->delete();
        Announcement::query()->whereIn('title', $announcementTitles)->delete();
        Subscriber::query()->whereIn('email', $subscriberEmails)->delete();

        ServiceGroup::query()
            ->whereIn('name', $groupNames)
            ->doesntHave('services')
            ->delete();

        $this->command?->info('StatusPageSeeder legt absichtlich keine Demo-Daten mehr an.');
        $this->command?->info('Frühere Beispiel-Daten aus den alten Status-Seeds wurden, soweit vorhanden, entfernt.');
    }
}
