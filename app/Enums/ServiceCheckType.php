<?php

namespace App\Enums;

enum ServiceCheckType: string
{
    case Website = 'website';
    case Tcp = 'tcp';
    case Ping = 'ping';
    case Database = 'database';

    public function label(): string
    {
        return match ($this) {
            self::Website => 'Webseite',
            self::Tcp => 'TCP',
            self::Ping => 'Ping',
            self::Database => 'Datenbank',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Website => 'Prüft eine URL per HTTP oder HTTPS.',
            self::Tcp => 'Prüft, ob ein TCP-Port erreichbar ist.',
            self::Ping => 'Prüft die Erreichbarkeit per Ping.',
            self::Database => 'Prüft eine Datenbankverbindung samt Testabfrage.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Website => 'globe',
            self::Tcp => 'plug',
            self::Ping => 'signal',
            self::Database => 'database',
        };
    }
}
