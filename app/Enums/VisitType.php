<?php

namespace App\Enums;

/**
 * Tipi di visita clinica (rispecchiano i moduli di SmartPodos).
 */
enum VisitType: string
{
    case PODOLOGICA = 'podologica';
    case ONICOPATIE = 'onicopatie';
    case VERRUCA = 'verruca';
    case DIABETICO = 'diabetico';
    case EXTRA = 'extra';

    public function label(): string
    {
        return match ($this) {
            self::PODOLOGICA => 'Visita podologica',
            self::ONICOPATIE => 'Onicopatie',
            self::VERRUCA => 'Verruca plantare',
            self::DIABETICO => 'Paziente diabetico',
            self::EXTRA => 'Extra',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PODOLOGICA => '#2563eb',
            self::ONICOPATIE => '#7c3aed',
            self::VERRUCA => '#db2777',
            self::DIABETICO => '#ea580c',
            self::EXTRA => '#64748b',
        };
    }
}
