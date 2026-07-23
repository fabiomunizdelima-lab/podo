<?php

namespace App\Models;

use App\Enums\VisitType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Visita / trattamento clinico datato.
 * I campi di testo clinico sono cifrati a riposo (dati art.9 GDPR).
 */
class ClinicalVisit extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'patient_id', 'appointment_id', 'created_by',
        'visited_at', 'reason', 'visit_type',
        'objective_exam', 'diagnosis', 'treatment_performed', 'recommendations',
        'next_visit_at',
    ];

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
            'next_visit_at' => 'date',
            'visit_type' => VisitType::class,
            'objective_exam' => 'encrypted',
            'diagnosis' => 'encrypted',
            'treatment_performed' => 'encrypted',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function treatments(): BelongsToMany
    {
        return $this->belongsToMany(Treatment::class, 'clinical_visit_treatment')
            ->withPivot(['id', 'description', 'quantity', 'unit_price'])
            ->withTimestamps();
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ClinicalPhoto::class);
    }

    public function getTotalAttribute(): float
    {
        return (float) $this->treatments->sum(fn ($t) => $t->pivot->quantity * $t->pivot->unit_price);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['patient_id', 'visited_at', 'reason', 'visit_type'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('visita-clinica');
    }
}
