<?php

namespace App\Filament\Resources\Teams;

use App\Actions\Teams\DeleteTeam;
use App\Filament\Resources\Teams\Pages\ManageTeams;
use App\Models\Team;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Zugänge & Teams';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'Team';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Teams';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Teamname')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label('Slug')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Optional. Wenn leer, wird automatisch ein eindeutiger Slug erzeugt.'),
                Select::make('owner_id')
                    ->label('Teambesitzer')
                    ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->visibleOn('create')
                    ->helperText('Der Besitzer erhält automatisch die Owner-Rolle im Team.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Team')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                TextColumn::make('owner_name')
                    ->label('Besitzer')
                    ->getStateUsing(fn (Team $record): string => $record->owner()?->name ?? '—'),
                TextColumn::make('members_count')
                    ->counts('members')
                    ->label('Mitglieder')
                    ->badge(),
                TextColumn::make('invitations_count')
                    ->counts('invitations')
                    ->label('Einladungen')
                    ->badge(),
                IconColumn::make('is_personal')
                    ->label('Persönlich')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->label('Team löschen')
                    ->visible(fn (Team $record): bool => ! $record->is_personal)
                    ->action(fn (Team $record, DeleteTeam $deleteTeam): bool => tap(true, fn () => $deleteTeam->handle($record)))
                    ->successNotificationTitle('Team erfolgreich gelöscht'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTeams::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function canCreate(): bool
    {
        return auth()->check();
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->check();
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->check() && ! $record->is_personal;
    }
}
