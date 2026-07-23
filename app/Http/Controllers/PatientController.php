<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q'));

        $patients = Patient::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('last_name', 'ilike', "%{$q}%")
                        ->orWhere('first_name', 'ilike', "%{$q}%")
                        ->orWhere('fiscal_code', 'ilike', "%{$q}%")
                        ->orWhere('phone', 'ilike', "%{$q}%");
                });
            })
            ->orderBy('last_name')
            ->paginate(20)
            ->withQueryString();

        return view('patients.index', compact('patients', 'q'));
    }

    public function create()
    {
        return view('patients.create', ['patient' => new Patient()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $patient = Patient::create($data);

        return redirect()->route('patients.show', $patient)
            ->with('success', 'Paziente creato.');
    }

    public function show(Patient $patient)
    {
        $patient->load([
            'appointments' => fn ($q) => $q->latest('starts_at')->limit(20),
            'clinicalRecord',
            'clinicalVisits' => fn ($q) => $q->with('treatments')->withCount('photos')->limit(50),
            'clinicalPhotos' => fn ($q) => $q->latest('taken_at')->limit(60),
            'orthoses',
        ]);

        return view('patients.show', compact('patient'));
    }

    public function edit(Patient $patient)
    {
        return view('patients.edit', compact('patient'));
    }

    public function update(Request $request, Patient $patient)
    {
        $patient->update($this->validateData($request, $patient));

        return redirect()->route('patients.show', $patient)
            ->with('success', 'Paziente aggiornato.');
    }

    public function destroy(Patient $patient)
    {
        // Soft delete (GDPR: cancellazione logica, hard-delete riservato al superadmin)
        $patient->delete();

        return redirect()->route('patients.index')
            ->with('success', 'Paziente archiviato.');
    }

    private function validateData(Request $request, ?Patient $patient = null): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'fiscal_code' => ['nullable', 'string', 'size:16', Rule::unique('patients', 'fiscal_code')->ignore($patient?->id)->whereNull('deleted_at')],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:M,F,X'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp_phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:200'],
            'city' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'province' => ['nullable', 'string', 'max:4'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'clinical_notes' => ['nullable', 'string', 'max:5000'],
            'consent_privacy' => ['sometimes', 'boolean'],
            'consent_whatsapp' => ['sometimes', 'boolean'],
            'consent_marketing' => ['sometimes', 'boolean'],
        ]);
    }
}
