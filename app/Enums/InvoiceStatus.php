<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case ISSUED = 'issued';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Bozza',
            self::ISSUED => 'Emessa',
            self::PAID => 'Pagata',
            self::CANCELLED => 'Annullata',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => '#64748b',
            self::ISSUED => '#2563eb',
            self::PAID => '#16a34a',
            self::CANCELLED => '#dc2626',
        };
    }
}
