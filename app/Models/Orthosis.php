<?php

namespace App\Models;

use App\Enums\OrthosisStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Orthosis extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'orthoses';

    protected $fillable = [
        'patient_id', 'clinical_visit_id', 'created_by',
        'type', 'foot', 'material', 'specifications', 'status', 'price',
        'prescribed_at', 'delivered_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrthosisStatus::class,
            'price' => 'decimal:2',
            'prescribed_at' => 'date',
            'delivered_at' => 'date',
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'status', 'delivered_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('ortesi');
    }
}
