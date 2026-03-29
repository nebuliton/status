<?php

namespace App\Filament\Resources\TeamInvitations\Pages;

use App\Actions\Teams\SendTeamInvitation;
use App\Filament\Resources\TeamInvitations\TeamInvitationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;

class ManageTeamInvitations extends ManageRecords
{
    protected static string $resource = TeamInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Einladung versenden')
                ->using(fn (array $data, SendTeamInvitation $sendTeamInvitation) => $sendTeamInvitation->handle($data, Auth::user()))
                ->successNotificationTitle('Einladung erfolgreich versendet'),
        ];
    }
}
