<?php

namespace App\Http\Controllers;
use App\Models\Setting;

use App\Enums\InvoiceStatus;
use App\Models\ClinicalVisit;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Treatment;
use App\Services\FatturaElettronicaService;
use App\Services\InvoiceService;
use App\Services\SistemaTsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $service)
    {
    }

    public function index(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $status = $request->input('status');

        $invoices = Invoice::query()
            ->with('patient')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->where(function ($q) use ($year) {
                $q->where('year', $year)->orWhereNull('year');
            })
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $years = range(now()->year, now()->year - 5);

        return view('invoices.index', compact('invoices', 'year', 'status', 'years'));
    }

    public function create(Request $request)
    {
        $patient = $request->filled('patient') ? Patient::findOrFail($request->input('patient')) : null;
        $invoice = new Invoice(['status' => InvoiceStatus::DRAFT]);
        $treatments = Treatment::active()->orderBy('name')->get();
        $patients = Patient::orderBy('last_name')->get(['id', 'first_name', 'last_name']);

        return view('invoices.form', compact('invoice', 'patient', 'treatments', 'patients'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_type' => ['required', 'in:patient,struttura'],
            'patient_id' => ['nullable', 'required_if:client_type,patient', 'exists:patients,id'],
            'client_name' => ['nullable', 'required_if:client_type,struttura', 'string', 'max:200'],
            'client_fiscal_code' => ['nullable', 'string', 'max:16'],
            'client_vat' => ['nullable', 'string', 'max:20'],
            'client_address' => ['nullable', 'string', 'max:200'],
            'client_city' => ['nullable', 'string', 'max:100'],
            'client_cap' => ['nullable', 'string', 'max:10'],
            'client_province' => ['nullable', 'string', 'max:4'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $cfg = Setting::billing();

        if ($data['client_type'] === 'patient') {
            $patient = Patient::findOrFail($data['patient_id']);
            $client = [
                'patient_id' => $patient->id,
                'client_name' => $patient->full_name,
                'client_fiscal_code' => $patient->fiscal_code,
                'client_vat' => null,
                'client_address' => $patient->address,
                'client_city' => $patient->city,
                'client_cap' => $patient->postal_code,
                'client_province' => $patient->province,
            ];
        } else {
            $client = [
                'patient_id' => null,
                'client_name' => $data['client_name'],
                'client_fiscal_code' => $data['client_fiscal_code'] ?? null,
                'client_vat' => $data['client_vat'] ?? null,
                'client_address' => $data['client_address'] ?? null,
                'client_city' => $data['client_city'] ?? null,
                'client_cap' => $data['client_cap'] ?? null,
                'client_province' => $data['client_province'] ?? null,
            ];
        }

        $invoice = Invoice::create(array_merge($client, [
            'created_by' => $request->user()->id,
            'status' => InvoiceStatus::DRAFT,
            'vat_exempt' => true,
            'vat_nature' => $cfg['vat_nature'],
            'regime' => $cfg['regime'],
            'notes' => $data['notes'] ?? null,
        ]));

        $this->syncLines($invoice, $request);

        return redirect()->route('invoices.edit', $invoice)
            ->with('success', 'Bozza fattura creata.');
    }

    public function fromVisit(ClinicalVisit $visit, Request $request)
    {
        $invoice = $this->service->createDraftFromVisit($visit, $request->user());

        return redirect()->route('invoices.edit', $invoice)
            ->with('success', 'Bozza fattura generata dalla visita.');
    }

    public function edit(Invoice $invoice)
    {
        abort_unless($invoice->isEditable(), 403, 'Fattura gia emessa: non modificabile.');
        $invoice->load('lines');
        $treatments = Treatment::active()->orderBy('name')->get();
        $patients = Patient::orderBy('last_name')->get(['id', 'first_name', 'last_name']);
        $patient = $invoice->patient;

        return view('invoices.form', compact('invoice', 'patient', 'treatments', 'patients'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        abort_unless($invoice->isEditable(), 403);

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $invoice->update($data);
        $this->syncLines($invoice, $request);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Fattura aggiornata.');
    }

    public function issue(Invoice $invoice)
    {
        $this->service->issue($invoice);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Fattura emessa e numerata: '.$invoice->full_number);
    }

    public function markPaid(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'payment_method' => ['nullable', 'string', 'max:50'],
            'paid_at' => ['nullable', 'date'],
        ]);
        $this->service->markPaid($invoice, $data['payment_method'] ?? null, $data['paid_at'] ?? null);

        return back()->with('success', 'Fattura segnata come pagata.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('lines', 'patient');

        return view('invoices.show', ['invoice' => $invoice, 'cfg' => Setting::billing()]);
    }

    public function pdf(Invoice $invoice)
    {
        $invoice->load('lines', 'patient');
        $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $invoice, 'cfg' => Setting::billing()])
            ->setPaper('a4');

        return $pdf->download('fattura-'.str_replace('/', '-', $invoice->full_number).'.pdf');
    }

    public function xml(Invoice $invoice, FatturaElettronicaService $fe)
    {
        abort_if($invoice->status === InvoiceStatus::DRAFT, 400, 'Emetti la fattura prima di generare l XML.');
        $xml = $fe->build($invoice);

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="'.$fe->filename($invoice).'"',
        ]);
    }

    public function tsExport(Request $request, SistemaTsService $ts)
    {
        $year = (int) $request->input('year', now()->year);
        $month = $request->filled('month') ? (int) $request->input('month') : null;
        $csv = $ts->export($year, $month);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$ts->filename($year, $month).'"',
        ]);
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Fattura archiviata.');
    }

    /** Ricostruisce le righe fattura e ricalcola i totali. */
    private function syncLines(Invoice $invoice, Request $request): void
    {
        $cfg = Setting::billing();
        $invoice->lines()->delete();

        foreach ($request->input('lines', []) as $line) {
            $description = trim((string) ($line['description'] ?? ''));
            if ($description === '') {
                continue;
            }
            $exemptLine = ! isset($line['vat_rate']) || (float) $line['vat_rate'] == 0.0;

            $invoice->lines()->create([
                'treatment_id' => $line['treatment_id'] ?? null,
                'description' => mb_substr($description, 0, 150),
                'quantity' => max(1, (int) ($line['quantity'] ?? 1)),
                'unit_price' => round((float) ($line['unit_price'] ?? 0), 2),
                'vat_rate' => round((float) ($line['vat_rate'] ?? 0), 2),
                'vat_nature' => $exemptLine ? $cfg['vat_nature'] : null,
            ]);
        }

        $invoice->load('lines');
        $invoice->recomputeTotals();
        $invoice->save();
    }
}
