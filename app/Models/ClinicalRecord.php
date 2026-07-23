<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Anamnesi unica del paziente (1:1).
 * I campi di testo clinico sono cifrati a riposo (dati art.9 GDPR).
 */
class ClinicalRecord extends Model
{
    use LogsActivity;

    protected $fillable = [
        'patient_id', 'profession', 'sport_activity', 'footwear_notes',
        'diabetes', 'diabetes_type', 'on_anticoagulants', 'smoker', 'hypertension',
        'circulatory_disorders', 'neuropathy', 'immunosuppressed', 'pacemaker', 'latex_allergy',
        'foot_type_left', 'foot_type_right',
        'medical_history', 'surgeries', 'medications', 'allergies', 'podiatric_notes',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'diabetes' => 'boolean',
            'on_anticoagulants' => 'boolean',
            'smoker' => 'boolean',
            'hypertension' => 'boolean',
            'circulatory_disorders' => 'boolean',
            'neuropathy' => 'boolean',
            'immunosuppressed' => 'boolean',
            'pacemaker' => 'boolean',
            'latex_allergy' => 'boolean',
            // Testo clinico cifrato
            'medical_history' => 'encrypted',
            'surgeries' => 'encrypted',
            'medications' => 'encrypted',
            'allergies' => 'encrypted',
            'podiatric_notes' => 'encrypted',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['diabetes', 'on_anticoagulants', 'neuropathy', 'foot_type_left', 'foot_type_right'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('cartella-clinica');
    }
}
