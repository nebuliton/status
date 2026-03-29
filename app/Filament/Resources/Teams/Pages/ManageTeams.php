<?php

namespace App\Filament\Resources\Teams\Pages;

use App\Actions\Teams\CreateTeamWithOwner;
use App\Filament\Resources\Teams\TeamResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTeams extends ManageRecords
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Team anlegen')
                ->using(function (array $data, CreateTeamWithOwner $createTeamWithOwner) {
                    $owner = User::query()->findOrFail($data['owner_id']);

                    return $createTeamWithOwner->handle(
                        owner: $owner,
                        name: $data['name'],
                        isPersonal: false,
                        switchOwnerToTeam: false,
                    );
                })
                ->successNotificationTitle('Team erfolgreich erstellt'),
        ];
    }
}
