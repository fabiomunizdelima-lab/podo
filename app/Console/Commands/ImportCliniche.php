<?php

namespace App\Console\Commands;

use App\Models\ClinicalVisit;
use App\Models\Orthosis;
use App\Models\Patient;
use Illuminate\Console\Command;

/**
 * Importa le cartelle cliniche (visite) e le ortesi storiche di SmartPodos,
 * agganciandole ai pazienti tramite legacy_fm_id (codice "P###").
 *
 * Idempotente: dedup su legacy_ref. I campi clinici sono cifrati a riposo.
 * Il JSON contiene dati sanitari e NON va committato.
 */
class ImportCliniche extends Command
{
    protected $signature = 'podo:import-cliniche
        {file=storage/app/import/cliniche.json : Percorso del JSON}
        {--dry-run : Simula senza scrivere}';

    protected $description = 'Importa visite cliniche e ortesi storiche (match su legacy_fm_id)';

    public function handle(): int
    {
        $path = $this->argument('file');
        if (! is_file($path)) {
            $this->error("File non trovato: {$path}");
            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($path), true);
        $dry = (bool) $this->option('dry-run');

        // Mappa legacy_fm_id -> patient_id (una query sola)
        $map = Patient::whereNotNull('legacy_fm_id')->pluck('id', 'legacy_fm_id');

        $vNew = 0; $vSkip = 0; $oNew = 0; $oSkip = 0;

        // ---- Visite ----
        $visits = $data['visits'] ?? [];
        $bar = $this->output->createProgressBar(count($visits));
        foreach ($visits as $v) {
            $pid = $map[$v['fm_id']] ?? null;
            if (! $pid || empty($v['visited_at'])) { $vSkip++; $bar->advance(); continue; }
            if ($dry) { $vNew++; $bar->advance(); continue; }

            $visit = ClinicalVisit::where('legacy_ref', $v['ref'])->first() ?: new ClinicalVisit();
            $visit->legacy_ref = $v['ref'];
            $visit->patient_id = $pid;
            $visit->visited_at = $v['visited_at'];
            $visit->visit_type = $v['visit_type'] ?? 'podologica';
            $visit->reason = $v['reason'] ?? null;
            $visit->diagnosis = $v['diagnosis'] ?? null;
            $visit->treatment_performed = $v['treatment_performed'] ?? null;
            $visit->objective_exam = $v['objective_exam'] ?? null;
            $visit->recommendations = $v['recommendations'] ?? null;
            $visit->save();
            $vNew++;
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        // ---- Ortesi ----
        foreach (($data['orthoses'] ?? []) as $o) {
            $pid = $map[$o['fm_id']] ?? null;
            if (! $pid) { $oSkip++; continue; }
            if ($dry) { $oNew++; continue; }

            $ort = Orthosis::where('legacy_ref', $o['ref'])->first() ?: new Orthosis();
            $ort->legacy_ref = $o['ref'];
            $ort->patient_id = $pid;
            $ort->type = $o['type'] ?: 'Ortesi plantare';
            $ort->material = $o['material'] ?? null;
            $ort->specifications = $o['specifications'] ?? null;
            $ort->prescribed_at = $o['prescribed_at'] ?? null;
            $ort->notes = $o['notes'] ?? null;
            $ort->save();
            $oNew++;
        }

        $this->info(($dry ? '[DRY-RUN] ' : '')
            ."Visite: {$vNew} importate, {$vSkip} saltate (paziente/data mancante) · Ortesi: {$oNew} importate, {$oSkip} saltate.");

        return self::SUCCESS;
    }
}
