<?php

namespace App\Http\Controllers;

use App\Enums\OrthosisStatus;
use App\Models\Orthosis;
use App\Models\Patient;
use Illuminate\Http\Request;

/**
 * Ortesi / plantari su misura.
 */
class OrthosisController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status');

        $orthoses = Orthosis::query()
            ->with('patient')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByRaw("CASE status WHEN 'ready' THEN 0 WHEN 'in_production' THEN 1 WHEN 'prescribed' THEN 2 ELSE 3 END")
            ->orderByDesc('prescribed_at')
            ->paginate(25)
            ->withQueryString();

        return view('orthoses.index', compact('orthoses', 'status'));
    }

    public function create(Request $request)
    {
        $patient = $request->filled('patient') ? Patient::findOrFail($request->input('patient')) : null;
        $orthosis = new Orthosis(['status' => OrthosisStatus::PRESCRIBED, 'prescribed_at' => now()->toDateString()]);
        $patients = Patient::orderBy('last_name')->get(['id', 'first_name', 'last_name']);

        return view('orthoses.form', compact('orthosis', 'patient', 'patients'));
    }

    public function store(Request $request)
    {
        $orthosis = Orthosis::create($this->validateData($request) + ['created_by' => $request->user()->id]);

        return redirect()->route('orthoses.index')
            ->with('success', 'Ortesi registrata.');
    }

    public function edit(Orthosis $orthosis)
    {
        $patient = $orthosis->patient;
        $patients = Patient::orderBy('last_name')->get(['id', 'first_name', 'last_name']);

        return view('orthoses.form', compact('orthosis', 'patient', 'patients'));
    }

    public function update(Request $request, Orthosis $orthosis)
    {
        $orthosis->update($this->validateData($request));

        return redirect()->route('orthoses.index')
            ->with('success', 'Ortesi aggiornata.');
    }

    public function updateStatus(Request $request, Orthosis $orthosis)
    {
        $data = $request->validate([
            'status' => ['required', 'in:prescribed,in_production,ready,delivered,cancelled'],
        ]);

        $update = ['status' => $data['status']];
        if ($data['status'] === 'delivered' && ! $orthosis->delivered_at) {
            $update['delivered_at'] = now()->toDateString();
        }
        $orthosis->update($update);

        return back()->with('success', 'Stato aggiornato.');
    }

    public function destroy(Orthosis $orthosis)
    {
        $orthosis->delete();

        return redirect()->route('orthoses.index')
            ->with('success', 'Ortesi archiviata.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'type' => ['required', 'string', 'max:120'],
            'foot' => ['nullable', 'in:L,R,both'],
            'material' => ['nullable', 'string', 'max:120'],
            'specifications' => ['nullable', 'string', 'max:3000'],
            'status' => ['nullable', 'in:prescribed,in_production,ready,delivered,cancelled'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'prescribed_at' => ['nullable', 'date'],
            'delivered_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
