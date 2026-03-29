<?php

namespace App\Filament\Resources\IncidentUpdates\Pages;

use App\Filament\Resources\IncidentUpdates\IncidentUpdateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageIncidentUpdates extends ManageRecords
{
    protected static string $resource = IncidentUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
