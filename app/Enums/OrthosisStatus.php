<?php

namespace App\Enums;

/**
 * Stato di lavorazione di un ortesi / plantare su misura.
 */
enum OrthosisStatus: string
{
    case PRESCRIBED = 'prescribed';
    case IN_PRODUCTION = 'in_production';
    case READY = 'ready';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PRESCRIBED => 'Prescritto',
            self::IN_PRODUCTION => 'In lavorazione',
            self::READY => 'Pronto',
            self::DELIVERED => 'Consegnato',
            self::CANCELLED => 'Annullato',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PRESCRIBED => '#64748b',
            self::IN_PRODUCTION => '#d97706',
            self::READY => '#2563eb',
            self::DELIVERED => '#16a34a',
            self::CANCELLED => '#dc2626',
        };
    }
}
