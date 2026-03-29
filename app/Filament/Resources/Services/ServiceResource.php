<?php

namespace App\Filament\Resources\Services;

use App\Enums\ServiceCheckType;
use App\Enums\ServiceStatus;
use App\Filament\Resources\Services\Pages\ManageServices;
use App\Models\Service;
use App\Services\Status\ServiceCheckRunner;
use Filament\Actions\Action;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static string|UnitEnum|null $navigationGroup = 'Statuskonfiguration';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'Dienst';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Dienste';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('group_id')
                    ->relationship('group', 'name')
                    ->label('Dienstgruppe')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label('Slug')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Optional. Wenn leer, wird automatisch ein eindeutiger Slug aus dem Dienstnamen erzeugt.'),
                Select::make('status')
                    ->label('Angezeigter Status')
                    ->options(self::serviceStatusOptions())
                    ->required()
                    ->default(ServiceStatus::Operational->value)
                    ->helperText('Wird nach erfolgreichen Checks automatisch mit dem letzten Prüfergebnis aktualisiert.'),
                Toggle::make('check_enabled')
                    ->label('Automatische Überwachung aktiv')
                    ->default(true)
                    ->inline(false),
                Select::make('check_type')
                    ->label('Check-Typ')
                    ->options(self::checkTypeOptions())
                    ->default(ServiceCheckType::Website->value)
                    ->required()
                    ->live(),
                TextInput::make('check_interval_seconds')
                    ->label('Prüfintervall in Sekunden')
                    ->numeric()
                    ->default(60)
                    ->minValue(30)
                    ->required(),
                TextInput::make('timeout_seconds')
                    ->label('Timeout in Sekunden')
                    ->numeric()
                    ->default(5)
                    ->minValue(1)
                    ->required(),
                TextInput::make('latency_degraded_ms')
                    ->label('Schwelle für Beeinträchtigung in ms')
                    ->numeric()
                    ->nullable()
                    ->helperText('Ab diesem Wert wird ein erfolgreicher Check als beeinträchtigt markiert.'),
                TextInput::make('latency_down_ms')
                    ->label('Schwelle für Ausfall in ms')
                    ->numeric()
                    ->nullable()
                    ->helperText('Ab diesem Wert wird ein erfolgreicher Check als Ausfall markiert.'),
                TextInput::make('target_url')
                    ->label('Ziel-URL')
                    ->url()
                    ->visible(fn (Get $get): bool => $get('check_type') === ServiceCheckType::Website->value)
                    ->required(fn (Get $get): bool => $get('check_type') === ServiceCheckType::Website->value)
                    ->helperText('Beispiel: https://status.nebuliton.de/health'),
                TextInput::make('expected_status_code')
                    ->label('Erwarteter HTTP-Status')
                    ->numeric()
                    ->visible(fn (Get $get): bool => $get('check_type') === ServiceCheckType::Website->value)
                    ->nullable()
                    ->helperText('Leer lassen, um alle 2xx- und 3xx-Antworten zu akzeptieren.'),
                Toggle::make('verify_ssl')
                    ->label('SSL-Zertifikat prüfen')
                    ->visible(fn (Get $get): bool => $get('check_type') === ServiceCheckType::Website->value)
                    ->default(true)
                    ->inline(false),
                TextInput::make('target_host')
                    ->label('Zielhost')
                    ->visible(fn (Get $get): bool => in_array($get('check_type'), [ServiceCheckType::Tcp->value, ServiceCheckType::Ping->value], true))
                    ->required(fn (Get $get): bool => in_array($get('check_type'), [ServiceCheckType::Tcp->value, ServiceCheckType::Ping->value], true))
                    ->helperText('IP-Adresse oder Hostname des Zielsystems.'),
                TextInput::make('target_port')
                    ->label('TCP-Port')
                    ->numeric()
                    ->visible(fn (Get $get): bool => $get('check_type') === ServiceCheckType::Tcp->value)
                    ->required(fn (Get $get): bool => $get('check_type') === ServiceCheckType::Tcp->value),
                Select::make('database_driver')
                    ->label('Datenbank-Treiber')
                    ->options(self::databaseDriverOptions())
                    ->default('mysql')
                    ->visible(fn (Get $get): bool => $get('check_type') === ServiceCheckType::Database->value)
                    ->required(fn (Get $get): bool => $get('check_type') === ServiceCheckType::Database->value),
                TextInput::make('database_host')
                    ->label('Datenbank-Host')
                    ->visible(fn (Get $get): bool => $get('check_type') === ServiceCheckType::Database->value)
                    ->required(fn (Get $get): bool => $get('check_type') === ServiceCheckType::Database->value),
                TextInput::make('database_port')
                    ->label('Datenbank-Port')
                    ->numeric()
                    ->visible(fn (Get $get): bool => $get('check_type') === ServiceCheckType::Database->value)
                    ->nullable(),
                TextInput::make('database_name')
                    ->label('Datenbankname')
                    ->visible(fn (Get $get): bool => $get('check_type') === ServiceCheckType::Database->value)
                    ->required(fn (Get $get): bool => $get('check_type') === ServiceCheckType::Database->value),
                TextInput::make('database_username')
                    ->label('Benutzername')
                    ->visible(fn (Get $get): bool => $get('check_type') === ServiceCheckType::Database->value)
                    ->nullable(),
                TextInput::make('database_password')
                    ->label('Passwort')
                    ->password()
                    ->revealable()
                    ->visible(fn (Get $get): bool => $get('check_type') === ServiceCheckType::Database->value)
                    ->nullable(),
                Textarea::make('database_query')
                    ->label('Testabfrage')
                    ->visible(fn (Get $get): bool => $get('check_type') === ServiceCheckType::Database->value)
                    ->rows(3)
                    ->placeholder('SELECT 1')
                    ->helperText('Standardmäßig wird `SELECT 1` verwendet.'),
                Placeholder::make('last_checked_at')
                    ->label('Letzter Check')
                    ->content(fn (?Service $record): string => $record?->last_checked_at?->diffForHumans() ?? 'Noch nicht ausgeführt'),
                Placeholder::make('last_response_time_ms')
                    ->label('Letzte Antwortzeit')
                    ->content(fn (?Service $record): string => $record?->last_response_time_ms !== null ? "{$record->last_response_time_ms} ms" : 'Noch kein Wert'),
                Placeholder::make('last_check_message')
                    ->label('Letzte Rückmeldung')
                    ->content(fn (?Service $record): string => $record?->last_check_message ?? 'Noch keine Meldung'),
                Placeholder::make('uptime_percentage')
                    ->label('Berechnete Verfügbarkeit')
                    ->content(fn (?Service $record): string => $record
                        ? number_format((float) $record->uptime_percentage, 2).' %'
                        : 'Wird automatisch berechnet, sobald Uptime-Logs gespeichert wurden.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('group_id')
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('group.name')
                    ->label('Dienstgruppe')
                    ->badge()
                    ->sortable(),
                TextColumn::make('check_type')
                    ->label('Typ')
                    ->badge()
                    ->formatStateUsing(fn (ServiceCheckType|string|null $state): string => $state instanceof ServiceCheckType ? $state->label() : ServiceCheckType::tryFrom((string) $state)?->label() ?? 'Unbekannt')
                    ->sortable(),
                TextColumn::make('monitor_target')
                    ->label('Ziel')
                    ->getStateUsing(fn (Service $record): string => $record->monitorTarget())
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (ServiceStatus $state): string => $state->label())
                    ->color(fn (ServiceStatus $state): string => $state->filamentColor())
                    ->sortable(),
                TextColumn::make('last_response_time_ms')
                    ->label('Antwortzeit')
                    ->formatStateUsing(fn ($state): string => $state !== null ? "{$state} ms" : '—')
                    ->sortable(),
                TextColumn::make('uptime_percentage')
                    ->label('Verfügbarkeit')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2).' %')
                    ->sortable(),
                TextColumn::make('last_checked_at')
                    ->label('Zuletzt geprüft')
                    ->since()
                    ->sortable(),
                TextColumn::make('uptime_logs_count')
                    ->counts('uptimeLogs')
                    ->label('Prüfungen')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('check_now')
                    ->label('Jetzt prüfen')
                    ->icon(Heroicon::OutlinedBolt)
                    ->visible(fn (Service $record): bool => $record->check_enabled)
                    ->action(function (Service $record, ServiceCheckRunner $serviceCheckRunner): void {
                        $serviceCheckRunner->run($record);
                    })
                    ->successNotificationTitle('Check erfolgreich ausgeführt'),
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
            'index' => ManageServices::route('/'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function serviceStatusOptions(): array
    {
        return collect(ServiceStatus::cases())
            ->mapWithKeys(fn (ServiceStatus $status) => [$status->value => $status->label()])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected static function checkTypeOptions(): array
    {
        return collect(ServiceCheckType::cases())
            ->mapWithKeys(fn (ServiceCheckType $type) => [$type->value => $type->label()])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected static function databaseDriverOptions(): array
    {
        return [
            'mysql' => 'MySQL',
            'pgsql' => 'PostgreSQL',
            'sqlsrv' => 'SQL Server',
        ];
    }
}
