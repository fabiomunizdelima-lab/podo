<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Portale paziente: un account "user" vede solo la propria cartella (sola lettura).
 */
class MyRecordController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $patient = $user->patient;

        if (! $patient) {
            return view('portal.no-record');
        }

        $patient->load([
            'clinicalRecord',
            'clinicalVisits' => fn ($q) => $q->with('treatments')->limit(50),
            'orthoses',
            'appointments' => fn ($q) => $q->where('starts_at', '>=', now()->startOfDay())->orderBy('starts_at')->limit(20),
        ]);

        return view('portal.record', compact('patient'));
    }
}
