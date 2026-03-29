<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\TeamInvitations\TeamInvitationResource;
use App\Filament\Resources\Teams\TeamResource;
use App\Filament\Resources\Users\UserResource;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;

class AdminQuickActions extends Widget
{
    protected static ?int $sort = -1;

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.admin-quick-actions';

    protected function getViewData(): array
    {
        return [
            'actions' => [
                [
                    'title' => 'Benutzer anlegen',
                    'description' => 'Neue Konten anlegen und bestehende Zugänge verwalten.',
                    'url' => UserResource::getUrl(),
                    'icon' => Heroicon::OutlinedUserPlus,
                ],
                [
                    'title' => 'Team anlegen',
                    'description' => 'Neue Teams mit einem festen Besitzer anlegen.',
                    'url' => TeamResource::getUrl(),
                    'icon' => Heroicon::OutlinedUserGroup,
                ],
                [
                    'title' => 'Mitglied einladen',
                    'description' => 'Einladungen per E-Mail direkt aus dem Admin versenden.',
                    'url' => TeamInvitationResource::getUrl(),
                    'icon' => Heroicon::OutlinedEnvelope,
                ],
            ],
        ];
    }
}
