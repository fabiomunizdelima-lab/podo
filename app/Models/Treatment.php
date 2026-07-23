<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Prestazione a listino (catalogo trattamenti podologici).
 */
class Treatment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        "code",
        "name",
        "category",
        "description",
        "price",
        "vat_exempt",
        "vat_rate",
        "vat_nature",
        "ts_type",
        "duration_minutes",
        "is_active",
    ];

    protected function casts(): array
    {
        return [
            "price" => "decimal:2",
            "vat_rate" => "decimal:2",
            "vat_exempt" => "boolean",
            "is_active" => "boolean",
            "duration_minutes" => "integer",
        ];
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function scopeActive($query)
    {
        return $query->where("is_active", true);
    }

    /** Prezzo formattato in stile italiano: "1.234,50". */
    public function getPriceLabelAttribute(): string
    {
        return number_format((float) $this->price, 2, ",", ".");
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(["code", "name", "price", "vat_exempt", "is_active"])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
