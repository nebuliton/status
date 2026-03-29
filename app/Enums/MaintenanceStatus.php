<?php

namespace App\Enums;

enum MaintenanceStatus: string
{
    case Scheduled = 'scheduled';
    case Ongoing = 'ongoing';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Geplant',
            self::Ongoing => 'Laufend',
            self::Completed => 'Abgeschlossen',
        };
    }

    public function filamentColor(): string
    {
        return match ($this) {
            self::Scheduled => 'info',
            self::Ongoing => 'warning',
            self::Completed => 'success',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Scheduled => 'border-sky-200/80 bg-sky-50 text-sky-700',
            self::Ongoing => 'border-amber-200/80 bg-amber-50 text-amber-700',
            self::Completed => 'border-emerald-200/80 bg-emerald-50 text-emerald-700',
        };
    }
}
