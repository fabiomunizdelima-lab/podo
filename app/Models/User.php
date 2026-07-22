<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            // Segreto TOTP e recovery codes cifrati a riposo (AES-256)
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'last_login_at' => 'datetime',
        ];
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'created_by');
    }

    // ---- Helper RBAC ----

    public function hasRole(Role $role): bool
    {
        return $this->role === $role;
    }

    public function atLeast(Role $role): bool
    {
        return $this->role?->atLeast($role) ?? false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === Role::SUPERADMIN;
    }

    public function requiresMfa(): bool
    {
        return config('podo.security.mfa_required_for_admins')
            && ($this->role?->isPrivileged() ?? false);
    }

    public function hasMfaEnabled(): bool
    {
        return ! is_null($this->two_factor_confirmed_at)
            && ! is_null($this->two_factor_secret);
    }
}
