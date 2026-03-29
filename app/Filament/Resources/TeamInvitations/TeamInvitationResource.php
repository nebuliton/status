<?php

namespace App\Filament\Resources\TeamInvitations;

use App\Actions\Teams\SendTeamInvitation;
use App\Enums\TeamRole;
use App\Filament\Resources\TeamInvitations\Pages\ManageTeamInvitations;
use App\Models\Team;
use App\Models\TeamInvitation;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class TeamInvitationResource extends Resource
{
    protected static ?string $model = TeamInvitation::class;

    protected static ?string $recordTitleAttribute = 'email';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'Zugänge & Teams';

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return 'Team-Einladung';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Team-Einladungen';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('team_id')
                    ->label('Team')
                    ->options(fn (): array => Team::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('email')
                    ->label('E-Mail-Adresse')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Select::make('role')
                    ->label('Rolle')
                    ->options(self::roleOptions())
                    ->default(TeamRole::Member->value)
                    ->required(),
                DateTimePicker::make('expires_at')
                    ->label('Gültig bis')
                    ->seconds(false)
                    ->default(now()->addDays(7)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('team.name')
                    ->label('Team')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-Mail-Adresse')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->label('Rolle')
                    ->badge()
                    ->formatStateUsing(fn (TeamRole|string $state): string => $state instanceof TeamRole ? $state->label() : TeamRole::from($state)->label()),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (TeamInvitation $record): string => self::statusLabel($record))
                    ->color(fn (TeamInvitation $record): string => self::statusColor($record)),
                TextColumn::make('inviter.name')
                    ->label('Eingeladen von')
                    ->placeholder('—'),
                TextColumn::make('expires_at')
                    ->label('Gültig bis')
                    ->since()
                    ->placeholder('Ohne Ablauf'),
                TextColumn::make('accepted_at')
                    ->label('Angenommen')
                    ->since()
                    ->placeholder('Noch offen'),
            ])
            ->recordActions([
                Action::make('resend')
                    ->label('Erneut senden')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->visible(fn (TeamInvitation $record): bool => ! $record->isAccepted())
                    ->action(fn (TeamInvitation $record, SendTeamInvitation $sendTeamInvitation): bool => tap(true, fn () => $sendTeamInvitation->resend($record)))
                    ->successNotificationTitle('Einladung erneut versendet'),
                EditAction::make(),
                DeleteAction::make()
                    ->label('Einladung löschen')
                    ->visible(fn (TeamInvitation $record): bool => ! $record->isAccepted()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTeamInvitations::route('/'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function roleOptions(): array
    {
        return collect(TeamRole::assignable())
            ->mapWithKeys(fn (array $role): array => [$role['value'] => $role['label']])
            ->all();
    }

    protected static function statusLabel(TeamInvitation $record): string
    {
        if ($record->isAccepted()) {
            return 'Angenommen';
        }

        if ($record->isExpired()) {
            return 'Abgelaufen';
        }

        return 'Offen';
    }

    protected static function statusColor(TeamInvitation $record): string
    {
        if ($record->isAccepted()) {
            return 'success';
        }

        if ($record->isExpired()) {
            return 'danger';
        }

        return 'warning';
    }
}
