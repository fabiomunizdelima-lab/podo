<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Anagrafica paziente.
 *
 * GDPR: contiene dati personali e sanitari (categoria particolare, art. 9).
 * I campi sensibili sono cifrati a riposo (AES-256) e ogni accesso/modifica
 * è tracciato nell'audit log (spatie/activitylog).
 */
class Patient extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'first_name',
        'last_name',
        'fiscal_code',
        'birth_date',
        'gender',
        'email',
        'phone',
        'whatsapp_phone',
        'address',
        'city',
        'postal_code',
        'province',
        'notes',
        'clinical_notes',
        'consent_privacy',
        'consent_whatsapp',
        'consent_marketing',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'clinical_notes' => 'encrypted',
            'consent_privacy' => 'boolean',
            'consent_whatsapp' => 'boolean',
            'consent_marketing' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function clinicalRecord(): HasOne
    {
        return $this->hasOne(ClinicalRecord::class);
    }

    public function clinicalVisits(): HasMany
    {
        return $this->hasMany(ClinicalVisit::class)->latest('visited_at');
    }

    public function clinicalPhotos(): HasMany
    {
        return $this->hasMany(ClinicalPhoto::class)->latest('taken_at');
    }

    /** Ortesi / plantari su misura. */
    public function orthoses(): HasMany
    {
        return $this->hasMany(Orthosis::class)->latest('prescribed_at');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function whatsappE164(): ?string
    {
        $raw = $this->whatsapp_phone ?: $this->phone;
        if (! $raw) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $raw);
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '3') && strlen($digits) <= 11) {
            $digits = '39'.$digits;
        }
        return $digits ?: null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['first_name', 'last_name', 'fiscal_code', 'phone', 'email', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('paziente');
    }
}
