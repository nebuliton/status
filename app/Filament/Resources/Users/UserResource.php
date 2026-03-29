<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Zugänge & Teams';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Benutzer';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Benutzer';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('E-Mail-Adresse')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('password')
                    ->label('Passwort')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->afterStateHydrated(fn (TextInput $component): TextInput => $component->state(''))
                    ->helperText('Beim Bearbeiten leer lassen, um das bestehende Passwort beizubehalten.'),
                DateTimePicker::make('email_verified_at')
                    ->label('E-Mail bestätigt am')
                    ->seconds(false)
                    ->default(now())
                    ->helperText('Nur bestätigte Benutzer können das Adminpanel öffnen.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-Mail-Adresse')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('currentTeam.name')
                    ->label('Aktuelles Team')
                    ->placeholder('—'),
                TextColumn::make('teams_count')
                    ->counts('teams')
                    ->label('Teams')
                    ->badge(),
                TextColumn::make('email_verified_at')
                    ->label('Bestätigt')
                    ->since()
                    ->placeholder('Nicht bestätigt')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
