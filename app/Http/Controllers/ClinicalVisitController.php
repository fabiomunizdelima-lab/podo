<?php

namespace App\Http\Controllers;

use App\Models\ClinicalVisit;
use App\Models\Patient;
use App\Models\Treatment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Visite / trattamenti clinici del paziente.
 */
class ClinicalVisitController extends Controller
{
    public function create(Patient $patient)
    {
        $visit = new ClinicalVisit(['visited_at' => now()]);
        $treatments = Treatment::active()->orderBy('name')->get();

        return view('clinical.visit-form', compact('patient', 'visit', 'treatments'));
    }

    public function store(Request $request, Patient $patient)
    {
        $data = $this->validateData($request);

        $visit = $patient->clinicalVisits()->create($data + [
            'created_by' => $request->user()->id,
        ]);

        $this->syncLines($visit, $request);

        return redirect()->route('patients.show', $patient)
            ->with('success', 'Visita registrata.');
    }

    public function edit(ClinicalVisit $visit)
    {
        $patient = $visit->patient;
        $visit->load('treatments');
        $treatments = Treatment::active()->orderBy('name')->get();

        return view('clinical.visit-form', compact('patient', 'visit', 'treatments'));
    }

    public function update(Request $request, ClinicalVisit $visit)
    {
        $visit->update($this->validateData($request));
        $this->syncLines($visit, $request);

        return redirect()->route('patients.show', $visit->patient)
            ->with('success', 'Visita aggiornata.');
    }

    public function destroy(ClinicalVisit $visit)
    {
        $patient = $visit->patient;
        $visit->delete();

        return redirect()->route('patients.show', $patient)
            ->with('success', 'Visita archiviata.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'visited_at' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:200'],
            'visit_type' => ['nullable', 'in:podologica,onicopatie,verruca,diabetico,extra'],
            'objective_exam' => ['nullable', 'string', 'max:5000'],
            'diagnosis' => ['nullable', 'string', 'max:3000'],
            'treatment_performed' => ['nullable', 'string', 'max:5000'],
            'recommendations' => ['nullable', 'string', 'max:3000'],
            'next_visit_at' => ['nullable', 'date'],
        ]);
    }

    /** Ricostruisce le righe prestazione (snapshot descrizione + prezzo). */
    private function syncLines(ClinicalVisit $visit, Request $request): void
    {
        DB::table('clinical_visit_treatment')->where('clinical_visit_id', $visit->id)->delete();

        $lines = $request->input('lines', []);
        $now = now();
        $rows = [];

        foreach ($lines as $line) {
            $description = trim((string) ($line['description'] ?? ''));
            $treatmentId = $line['treatment_id'] ?? null;

            if ($description === '' && $treatmentId) {
                $description = optional(Treatment::find($treatmentId))->name ?? 'Prestazione';
            }
            if ($description === '') {
                continue;
            }

            $rows[] = [
                'clinical_visit_id' => $visit->id,
                'treatment_id' => $treatmentId ?: null,
                'description' => mb_substr($description, 0, 150),
                'quantity' => max(1, (int) ($line['quantity'] ?? 1)),
                'unit_price' => round((float) ($line['unit_price'] ?? 0), 2),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows) {
            DB::table('clinical_visit_treatment')->insert($rows);
        }
    }
}
