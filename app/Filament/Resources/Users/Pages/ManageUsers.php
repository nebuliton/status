<?php

namespace App\Filament\Resources\Users\Pages;

use App\Actions\Users\CreateUserAccount;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Benutzer anlegen')
                ->using(fn (array $data, CreateUserAccount $createUserAccount) => $createUserAccount->handle($data))
                ->successNotificationTitle('Benutzerkonto erfolgreich erstellt'),
        ];
    }
}
