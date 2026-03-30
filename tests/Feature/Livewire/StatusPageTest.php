<?php

use App\Enums\IncidentStatus;
use App\Enums\MaintenanceStatus;
use App\Enums\ServiceStatus;
use App\Models\Announcement;
use App\Models\Incident;
use App\Models\Maintenance;
use App\Models\Service;
use App\Models\ServiceGroup;
use App\Models\UptimeLog;
use Livewire\Volt\Volt;

it('renders the public status page with grouped services', function () {
    $group = ServiceGroup::factory()->create([
        'name' => 'Infrastruktur',
        'order' => 1,
    ]);

    $service = Service::factory()->create([
        'group_id' => $group->id,
        'name' => 'Edge-Netzwerk',
        'slug' => 'edge-network',
        'status' => ServiceStatus::Operational,
        'uptime_percentage' => 99.95,
    ]);

    UptimeLog::factory()->for($service)->count(3)->sequence(
        ['status' => ServiceStatus::Operational, 'recorded_at' => now()->subDays(2)],
        ['status' => ServiceStatus::Operational, 'recorded_at' => now()->subDay()],
        ['status' => ServiceStatus::Operational, 'recorded_at' => now()],
    )->create();

    $incident = Incident::factory()->create([
        'title' => 'Speicherlatenz',
        'status' => IncidentStatus::Monitoring,
    ]);

    $incident->updates()->create([
        'message' => 'Beobachtung nach der Gegenmaßnahme.',
        'status' => IncidentStatus::Monitoring,
        'created_at' => now(),
    ]);

    Maintenance::factory()->create([
        'title' => 'Datenbank-Patching',
        'status' => MaintenanceStatus::Scheduled,
        'scheduled_at' => now()->addDay(),
    ]);

    Announcement::factory()->create([
        'title' => 'Status-Plattform überarbeitet',
    ]);

    Volt::test('status-page')
        ->assertSee('Nebuliton')
        ->assertSee('Infrastruktur')
        ->assertSee('Edge-Netzwerk')
        ->assertSee('1 aktive Vorfälle')
        ->call('setTab', 'incidents')
        ->assertSee('Speicherlatenz');
});

it('stores subscribers from the subscribe action', function () {
    Volt::test('status-page')
        ->call('setTab', 'subscribe')
        ->set('subscriberEmail', 'ops@example.com')
        ->call('subscribe')
        ->assertSet('subscriberEmail', '')
        ->assertSee('Du bist eingetragen');

    $this->assertDatabaseHas('subscribers', [
        'email' => 'ops@example.com',
        'is_active' => true,
    ]);
});
