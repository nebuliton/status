<?php

namespace App\Filament\Resources\Maintenances;

use App\Enums\MaintenanceStatus;
use App\Filament\Resources\Maintenances\Pages\ManageMaintenances;
use App\Models\Maintenance;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class MaintenanceResource extends Resource
{
    protected static ?string $model = Maintenance::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = 'Betrieb';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'Wartung';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Wartungen';
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
                    ->options(self::maintenanceStatusOptions())
                    ->required()
                    ->default(MaintenanceStatus::Scheduled->value),
                DateTimePicker::make('scheduled_at')
                    ->label('Geplant für')
                    ->required()
                    ->seconds(false),
                Textarea::make('description')
                    ->label('Beschreibung')
                    ->required()
                    ->rows(6)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('scheduled_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (MaintenanceStatus $state): string => $state->label())
                    ->color(fn (MaintenanceStatus $state): string => $state->filamentColor())
                    ->sortable(),
                TextColumn::make('scheduled_at')
                    ->label('Geplant für')
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
            'index' => ManageMaintenances::route('/'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function maintenanceStatusOptions(): array
    {
        return collect(MaintenanceStatus::cases())
            ->mapWithKeys(fn (MaintenanceStatus $status) => [$status->value => $status->label()])
            ->all();
    }
}
