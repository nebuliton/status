<?php

namespace App\Filament\Resources\Announcements;

use App\Filament\Resources\Announcements\Pages\ManageAnnouncements;
use App\Models\Announcement;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Kommunikation';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Ankündigung';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Ankündigungen';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Titel')
                    ->required()
                    ->maxLength(255),
                Textarea::make('excerpt')
                    ->label('Kurztext')
                    ->rows(3)
                    ->maxLength(280)
                    ->columnSpanFull(),
                Textarea::make('content')
                    ->label('Inhalt')
                    ->required()
                    ->rows(8)
                    ->columnSpanFull(),
                Toggle::make('is_pinned')
                    ->label('Angepinnt')
                    ->inline(false),
                DateTimePicker::make('published_at')
                    ->label('Veröffentlicht am')
                    ->seconds(false)
                    ->helperText('Leer lassen, um die Ankündigung im Entwurfsstatus zu belassen.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_pinned')
                    ->boolean()
                    ->label('Angepinnt'),
                TextColumn::make('published_at')
                    ->label('Veröffentlicht am')
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
            'index' => ManageAnnouncements::route('/'),
        ];
    }
}
