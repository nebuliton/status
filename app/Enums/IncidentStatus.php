<?php

namespace App\Enums;

enum IncidentStatus: string
{
    case Investigating = 'investigating';
    case Identified = 'identified';
    case Monitoring = 'monitoring';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Investigating => 'Untersuchung',
            self::Identified => 'Identifiziert',
            self::Monitoring => 'Beobachtung',
            self::Resolved => 'Gelöst',
        };
    }

    public function filamentColor(): string
    {
        return match ($this) {
            self::Investigating => 'danger',
            self::Identified => 'warning',
            self::Monitoring => 'info',
            self::Resolved => 'success',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Investigating => 'border-rose-200/80 bg-rose-50 text-rose-700',
            self::Identified => 'border-amber-200/80 bg-amber-50 text-amber-700',
            self::Monitoring => 'border-sky-200/80 bg-sky-50 text-sky-700',
            self::Resolved => 'border-emerald-200/80 bg-emerald-50 text-emerald-700',
        };
    }

    public function isResolved(): bool
    {
        return $this === self::Resolved;
    }
}
