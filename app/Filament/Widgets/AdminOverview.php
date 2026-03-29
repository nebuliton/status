<?php

namespace App\Filament\Widgets;

use App\Models\Service;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Schnellüberblick';

    protected ?string $description = 'Die wichtigsten Bereiche für dein Nebuliton-Control-Center.';

    protected function getStats(): array
    {
        return [
            Stat::make('Benutzer', User::query()->count())
                ->description('Verifizierte Zugänge und interne Konten')
                ->descriptionIcon(Heroicon::OutlinedUsers)
                ->icon(Heroicon::OutlinedUsers)
                ->color('primary'),
            Stat::make('Teams', Team::query()->count())
                ->description('Persönliche und gemeinsame Teams')
                ->descriptionIcon(Heroicon::OutlinedUserGroup)
                ->icon(Heroicon::OutlinedUserGroup)
                ->color('info'),
            Stat::make(
                'Offene Einladungen',
                TeamInvitation::query()
                    ->whereNull('accepted_at')
                    ->where(function ($query): void {
                        $query
                            ->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    })
                    ->count(),
            )
                ->description('Noch nicht angenommene Team-Einladungen')
                ->descriptionIcon(Heroicon::OutlinedEnvelope)
                ->icon(Heroicon::OutlinedEnvelope)
                ->color('warning'),
            Stat::make('Dienste', Service::query()->count())
                ->description('Aktive Status- und Monitoring-Einträge')
                ->descriptionIcon(Heroicon::OutlinedServerStack)
                ->icon(Heroicon::OutlinedServerStack)
                ->color('success'),
        ];
    }
}
