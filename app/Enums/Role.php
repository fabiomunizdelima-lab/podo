<?php

namespace App\Enums;

/**
 * Ruoli applicativi (RBAC - checklist sicurezza).
 *
 * - SUPERADMIN: gestione completa, incluse impostazioni di sistema e utenti admin.
 * - ADMIN:      gestione clinica completa (pazienti, agenda, fatture) + utenti "user".
 * - USER:       operatività quotidiana (agenda, pazienti) senza impostazioni sensibili.
 */
enum Role: string
{
    case SUPERADMIN = 'superadmin';
    case ADMIN = 'admin';
    case USER = 'user';

    public function label(): string
    {
        return match ($this) {
            self::SUPERADMIN => 'Super Admin',
            self::ADMIN => 'Amministratore',
            self::USER => 'Utente',
        };
    }

    /** Ruoli con privilegi elevati: MFA obbligatoria. */
    public function isPrivileged(): bool
    {
        return in_array($this, [self::SUPERADMIN, self::ADMIN], true);
    }

    /** Gerarchia: livello numerico crescente = più privilegi. */
    public function level(): int
    {
        return match ($this) {
            self::USER => 1,
            self::ADMIN => 2,
            self::SUPERADMIN => 3,
        };
    }

    public function atLeast(Role $other): bool
    {
        return $this->level() >= $other->level();
    }
}
