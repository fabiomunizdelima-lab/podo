<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Invoice extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'patient_id', 'clinical_visit_id', 'created_by',
        'status', 'number', 'year', 'issued_at',
        'client_name', 'client_fiscal_code', 'client_vat',
        'client_address', 'client_city', 'client_cap', 'client_province',
        'taxable', 'vat_amount', 'withholding_amount', 'stamp_amount', 'total', 'net_to_pay',
        'vat_exempt', 'vat_nature', 'regime',
        'payment_method', 'paid_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'issued_at' => 'date',
            'paid_at' => 'date',
            'taxable' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'withholding_amount' => 'decimal:2',
            'stamp_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'net_to_pay' => 'decimal:2',
            'vat_exempt' => 'boolean',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function getFullNumberAttribute(): string
    {
        return $this->number ? sprintf('%d/%d', $this->number, $this->year) : 'BOZZA';
    }

    public function isEditable(): bool
    {
        return $this->status === InvoiceStatus::DRAFT;
    }

    /** Ricalcola i totali dalle righe secondo la configurazione fiscale. */
    public function recomputeTotals(): void
    {
        $cfg = Setting::billing();
        $lines = $this->relationLoaded('lines') ? $this->lines : $this->lines()->get();

        $this->taxable = round($lines->sum('line_total'), 2);
        $this->vat_amount = round($lines->sum(fn ($l) => $l->line_total * ($l->vat_rate / 100)), 2);

        // Marca da bollo: operazioni esenti/non soggette sopra soglia
        $this->stamp_amount = ($this->vat_amount == 0.0 && $this->taxable > $cfg['stamp_threshold'])
            ? $cfg['stamp_amount'] : 0;

        $this->withholding_amount = $cfg['withholding_enabled']
            ? round($this->taxable * ($cfg['withholding_rate'] / 100), 2) : 0;

        $this->total = round($this->taxable + $this->vat_amount + $this->stamp_amount, 2);
        $this->net_to_pay = round($this->total - $this->withholding_amount, 2);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'number', 'year', 'total', 'paid_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('fattura');
    }
}
