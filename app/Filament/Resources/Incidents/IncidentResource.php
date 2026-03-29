<?php

namespace App\Filament\Resources\Incidents;

use App\Enums\IncidentStatus;
use App\Filament\Resources\Incidents\Pages\ManageIncidents;
use App\Models\Incident;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class IncidentResource extends Resource
{
    protected static ?string $model = Incident::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|UnitEnum|null $navigationGroup = 'Betrieb';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Vorfall';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Vorfälle';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Titel')
                    ->required()
                    ->maxLength(255),
                Select::make('status')
                    ->label('Status')
                    ->options(self::incidentStatusOptions())
                    ->required()
                    ->default(IncidentStatus::Investigating->value),
                Textarea::make('description')
                    ->label('Beschreibung')
                    ->required()
                    ->rows(6)
                    ->columnSpanFull(),
                Repeater::make('updates')
                    ->relationship()
                    ->defaultItems(1)
                    ->columnSpanFull()
                    ->collapsed()
                    ->itemLabel(fn (array $state): ?string => filled($state['status'] ?? null)
                        ? IncidentStatus::from($state['status'])->label()
                        : 'Update')
                    ->schema([
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
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (IncidentStatus $state): string => $state->label())
                    ->color(fn (IncidentStatus $state): string => $state->filamentColor())
                    ->sortable(),
                TextColumn::make('updates_count')
                    ->counts('updates')
                    ->label('Updates')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Aktualisiert')
                    ->since()
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
            'index' => ManageIncidents::route('/'),
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
