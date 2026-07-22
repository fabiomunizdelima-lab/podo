<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Appuntamento in agenda.
 * Può essere sincronizzato con Google Calendar (google_event_id)
 * e generare un promemoria WhatsApp.
 */
class Appointment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'patient_id',
        'created_by',
        'title',
        'starts_at',
        'ends_at',
        'status',
        'treatment',
        'notes',
        'google_event_id',
        'reminder_channel',
        'reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'status' => AppointmentStatus::class,
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['patient_id', 'starts_at', 'ends_at', 'status', 'treatment'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('appuntamento');
    }
}
