<?php

namespace App\Enums;

enum ServiceStatus: string
{
    case Operational = 'operational';
    case Degraded = 'degraded';
    case Down = 'down';

    public function label(): string
    {
        return match ($this) {
            self::Operational => 'Betriebsbereit',
            self::Degraded => 'Beeinträchtigt',
            self::Down => 'Ausfall',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Operational => 'Alle Prüfungen sind erfolgreich und der Dienst arbeitet stabil.',
            self::Degraded => 'Der Dienst ist erreichbar, aber Leistung oder Stabilität sind eingeschränkt.',
            self::Down => 'Der Dienst ist derzeit nicht verfügbar.',
        };
    }

    public function filamentColor(): string
    {
        return match ($this) {
            self::Operational => 'success',
            self::Degraded => 'warning',
            self::Down => 'danger',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Operational => 'border-emerald-200/80 bg-emerald-50 text-emerald-700',
            self::Degraded => 'border-amber-200/80 bg-amber-50 text-amber-700',
            self::Down => 'border-rose-200/80 bg-rose-50 text-rose-700',
        };
    }

    public function segmentClasses(): string
    {
        return match ($this) {
            self::Operational => 'bg-emerald-400/90',
            self::Degraded => 'bg-amber-400/90',
            self::Down => 'bg-rose-400/90',
        };
    }

    public function severity(): int
    {
        return match ($this) {
            self::Operational => 0,
            self::Degraded => 1,
            self::Down => 2,
        };
    }

    public function uptimeWeight(): float
    {
        return match ($this) {
            self::Operational => 1.0,
            self::Degraded => 0.5,
            self::Down => 0.0,
        };
    }
}
