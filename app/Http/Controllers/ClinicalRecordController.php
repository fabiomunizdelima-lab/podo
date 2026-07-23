<?php

namespace App\Http\Controllers;

use App\Models\ClinicalRecord;
use App\Models\Patient;
use Illuminate\Http\Request;

/**
 * Anamnesi unica del paziente. Accessibile a tutti i ruoli operativi.
 */
class ClinicalRecordController extends Controller
{
    public function edit(Patient $patient)
    {
        $record = $patient->clinicalRecord()->firstOrNew([]);

        return view('clinical.record', compact('patient', 'record'));
    }

    public function update(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'profession' => ['nullable', 'string', 'max:150'],
            'sport_activity' => ['nullable', 'string', 'max:150'],
            'footwear_notes' => ['nullable', 'string', 'max:1000'],
            'diabetes_type' => ['nullable', 'string', 'max:20'],
            'foot_type_left' => ['nullable', 'in:normale,piatto,cavo'],
            'foot_type_right' => ['nullable', 'in:normale,piatto,cavo'],
            'medical_history' => ['nullable', 'string', 'max:5000'],
            'surgeries' => ['nullable', 'string', 'max:3000'],
            'medications' => ['nullable', 'string', 'max:3000'],
            'allergies' => ['nullable', 'string', 'max:2000'],
            'podiatric_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        foreach (['diabetes', 'on_anticoagulants', 'smoker', 'hypertension', 'circulatory_disorders', 'neuropathy', 'immunosuppressed', 'pacemaker', 'latex_allergy'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }
        $data['updated_by'] = $request->user()->id;

        $patient->clinicalRecord()->updateOrCreate(['patient_id' => $patient->id], $data);

        return redirect()->route('patients.show', $patient)
            ->with('success', 'Anamnesi aggiornata.');
    }
}
