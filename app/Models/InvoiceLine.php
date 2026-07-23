<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends Model
{
    protected $fillable = [
        'invoice_id', 'treatment_id', 'description',
        'quantity', 'unit_price', 'line_total', 'vat_rate', 'vat_nature',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'vat_rate' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (InvoiceLine $line) {
            $line->line_total = round($line->quantity * $line->unit_price, 2);
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }
}
