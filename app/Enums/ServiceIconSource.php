<?php

namespace App\Enums;

enum ServiceIconSource: string
{
    case Auto = 'auto';
    case Icon = 'icon';
    case Upload = 'upload';

    public function label(): string
    {
        return match ($this) {
            self::Auto => 'Automatisch',
            self::Icon => 'Symbol auswählen',
            self::Upload => 'Eigenes Bild hochladen',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Auto => 'Bei Webseiten wird automatisch das Favicon genutzt, sonst das Standard-Symbol des Typs.',
            self::Icon => 'Verwendet ein manuell ausgewähltes Symbol.',
            self::Upload => 'Verwendet ein eigenes hochgeladenes Bild.',
        };
    }
}
