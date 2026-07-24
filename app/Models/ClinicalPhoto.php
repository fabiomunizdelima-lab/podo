<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Metadati di una foto clinica. Il file binario e cifrato su disco privato.
 */
class ClinicalPhoto extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'legacy_ref', 'patient_id', 'clinical_visit_id', 'disk', 'path',
        'original_name', 'mime', 'size', 'foot', 'caption', 'taken_at', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'taken_at' => 'datetime',
            'size' => 'integer',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(ClinicalVisit::class, 'clinical_visit_id');
    }
}
