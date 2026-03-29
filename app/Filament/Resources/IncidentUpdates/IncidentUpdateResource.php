<?php

namespace App\Filament\Resources\IncidentUpdates;

use App\Enums\IncidentStatus;
use App\Filament\Resources\IncidentUpdates\Pages\ManageIncidentUpdates;
use App\Models\IncidentUpdate;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class IncidentUpdateResource extends Resource
{
    protected static ?string $model = IncidentUpdate::class;

    protected static ?string $recordTitleAttribute = 'message';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Betrieb';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'Vorfallsupdate';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Vorfallsupdates';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('incident_id')
                    ->relationship('incident', 'title')
                    ->label('Vorfall')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('status')
                    ->label('Status')
                    ->options(self::incidentStatusOptions())
                    ->required()
                    ->default(IncidentStatus::Investigating->value),
                DateTimePicker::make('created_at')
                    ->label('Erstellt am')
                    ->required()
                    ->seconds(false)
                    ->default(now()),
                Textarea::make('message')
                    ->label('Nachricht')
                    ->required()
                    ->rows(5)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('incident.title')
                    ->label('Vorfall')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (IncidentStatus $state): string => $state->label())
                    ->color(fn (IncidentStatus $state): string => $state->filamentColor())
                    ->sortable(),
                TextColumn::make('message')
                    ->label('Nachricht')
                    ->limit(80)
                    ->wrap(),
                TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageIncidentUpdates::route('/'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function incidentStatusOptions(): array
    {
        return collect(IncidentStatus::cases())
            ->mapWithKeys(fn (IncidentStatus $status) => [$status->value => $status->label()])
            ->all();
    }
}
