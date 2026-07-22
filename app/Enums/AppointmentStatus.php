<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case SCHEDULED = 'scheduled';
    case CONFIRMED = 'confirmed';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::SCHEDULED => 'Programmato',
            self::CONFIRMED => 'Confermato',
            self::COMPLETED => 'Completato',
            self::CANCELLED => 'Annullato',
            self::NO_SHOW => 'Non presentato',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SCHEDULED => '#64748b',
            self::CONFIRMED => '#0ea5e9',
            self::COMPLETED => '#16a34a',
            self::CANCELLED => '#dc2626',
            self::NO_SHOW => '#d97706',
        };
    }
}
