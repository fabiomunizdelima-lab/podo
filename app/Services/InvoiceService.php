<?php

namespace App\Services;
use App\Models\Setting;

use App\Enums\InvoiceStatus;
use App\Models\ClinicalVisit;
use App\Models\Invoice;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Logica di fatturazione: numerazione progressiva, creazione da visita, emissione.
 */
class InvoiceService
{
    /** Prossimo numero progressivo per l anno indicato. */
    public function nextNumber(int $year): int
    {
        return (int) Invoice::withTrashed()->where('year', $year)->max('number') + 1;
    }

    /** Crea una bozza di fattura a partire dalle prestazioni di una visita. */
    public function createDraftFromVisit(ClinicalVisit $visit, User $user): Invoice
    {
        $patient = $visit->patient;
        $cfg = Setting::billing();

        $invoice = Invoice::create([
            'patient_id' => $patient->id,
            'clinical_visit_id' => $visit->id,
            'created_by' => $user->id,
            'status' => InvoiceStatus::DRAFT,
            'client_name' => $patient->full_name,
            'client_fiscal_code' => $patient->fiscal_code,
            'client_address' => $patient->address,
            'client_city' => $patient->city,
            'client_cap' => $patient->postal_code,
            'client_province' => $patient->province,
            'vat_exempt' => true,
            'vat_nature' => $cfg['vat_nature'],
            'regime' => $cfg['regime'],
        ]);

        foreach ($visit->treatments as $t) {
            $treatment = Treatment::find($t->id);
            $exempt = $treatment?->vat_exempt ?? true;

            $invoice->lines()->create([
                'treatment_id' => $t->id,
                'description' => $t->pivot->description,
                'quantity' => $t->pivot->quantity,
                'unit_price' => $t->pivot->unit_price,
                'vat_rate' => $exempt ? 0 : ($treatment->vat_rate ?? 0),
                'vat_nature' => $exempt ? $cfg['vat_nature'] : null,
            ]);
        }

        $invoice->load('lines');
        $invoice->recomputeTotals();
        $invoice->save();

        return $invoice;
    }

    /** Emette (numera) una fattura in bozza in modo atomico. */
    public function issue(Invoice $invoice): Invoice
    {
        if ($invoice->status !== InvoiceStatus::DRAFT) {
            return $invoice;
        }

        return DB::transaction(function () use ($invoice) {
            $year = now()->year;
            $number = $this->nextNumber($year);

            $invoice->load('lines');
            $invoice->recomputeTotals();
            $invoice->fill([
                'number' => $number,
                'year' => $year,
                'issued_at' => now()->toDateString(),
                'status' => InvoiceStatus::ISSUED,
            ])->save();

            return $invoice;
        });
    }

    public function markPaid(Invoice $invoice, ?string $method, ?string $date): Invoice
    {
        $invoice->update([
            'status' => InvoiceStatus::PAID,
            'payment_method' => $method,
            'paid_at' => $date ?: now()->toDateString(),
        ]);

        return $invoice;
    }
}
