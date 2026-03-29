<?php

namespace App\Filament\Pages;

use App\Filament\Resources\TeamInvitations\TeamInvitationResource;
use App\Filament\Resources\Teams\TeamResource;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Admin-Übersicht';

    protected ?string $subheading = 'Benutzer, Teams und Einladungen zentral verwalten.';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_user')
                ->label('Benutzer anlegen')
                ->icon(Heroicon::OutlinedUserPlus)
                ->url(UserResource::getUrl()),
            Action::make('create_team')
                ->label('Team anlegen')
                ->icon(Heroicon::OutlinedUserGroup)
                ->url(TeamResource::getUrl()),
            Action::make('invite_team')
                ->label('Team einladen')
                ->icon(Heroicon::OutlinedEnvelope)
                ->url(TeamInvitationResource::getUrl()),
        ];
    }
}
