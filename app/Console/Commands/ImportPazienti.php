<?php

namespace App\Console\Commands;

use App\Models\Patient;
use Illuminate\Console\Command;

/**
 * Importa e arricchisce i pazienti dall'export FileMaker di SmartPodos.
 *
 * Match sul CODICE FISCALE (deterministico): i pazienti gia' presenti
 * (dalle fatture XML) vengono arricchiti con nascita, sesso, telefoni,
 * email, indirizzo, consenso e note cliniche; i nuovi vengono creati.
 * Salva "legacy_fm_id" (N PAZIENTE) per agganciare le cartelle cliniche.
 *
 * Il file JSON contiene dati personali/sanitari e NON va committato.
 */
class ImportPazienti extends Command
{
    protected $signature = 'podo:import-pazienti
        {file=storage/app/import/pazienti.json : Percorso del JSON}
        {--dry-run : Simula senza scrivere}';

    protected $description = 'Importa/arricchisce i pazienti dall\'export FileMaker (match su codice fiscale)';

    public function handle(): int
    {
        $path = $this->argument('file');
        if (! is_file($path)) {
            $this->error("File non trovato: {$path}");
            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($path), true);
        $patients = $data['patients'] ?? [];
        if (! $patients) {
            $this->warn('Nessun paziente nel file.');
            return self::SUCCESS;
        }

        $dry = (bool) $this->option('dry-run');
        $created = 0;
        $updated = 0;

        $bar = $this->output->createProgressBar(count($patients));
        foreach ($patients as $p) {
            $cf = $p['fiscal_code'] ?? null;
            $fm = $p['fm_id'] ?? null;

            $patient = null;
            if ($cf) {
                $patient = Patient::where('fiscal_code', $cf)->first();
            }
            if (! $patient && $fm) {
                $patient = Patient::where('legacy_fm_id', $fm)->first();
            }
            $exists = (bool) $patient;
            if (! $patient) {
                $patient = new Patient();
            }

            // Chiavi
            $patient->fiscal_code = $patient->fiscal_code ?: $cf;
            $patient->legacy_fm_id = $fm ?: $patient->legacy_fm_id;
            $patient->last_name = $patient->last_name ?: ($p['last_name'] ?: '—');
            $patient->first_name = $patient->first_name ?: ($p['first_name'] ?? '');

            // Campi anagrafici: FileMaker autorevole dove manca il dato
            $patient->birth_date = $patient->birth_date ?: $p['birth_date'];
            $patient->gender = $patient->gender ?: $p['gender'];
            $patient->email = $patient->email ?: $p['email'];
            $patient->phone = $patient->phone ?: $p['phone'];
            $patient->whatsapp_phone = $patient->whatsapp_phone ?: $p['whatsapp_phone'];
            $patient->address = $patient->address ?: $p['address'];
            $patient->city = $patient->city ?: $p['city'];
            // CAP: normalizza (gestisce range tipo "20121-20162" -> prime 5 cifre)
            $cap = $p['cap'] ?? null;
            if ($cap !== null && $cap !== '') {
                $cap = preg_match('/\d{5}/', (string) $cap, $m) ? $m[0] : substr((string) $cap, 0, 10);
            }
            $patient->postal_code = $patient->postal_code ?: $cap;
            $patient->province = $patient->province ?: $p['province'];

            if (! empty($p['consent_privacy'])) {
                $patient->consent_privacy = true;
            }
            if (! empty($p['notes'])) {
                $patient->notes = $patient->notes ?: $p['notes'];
            }
            if (! empty($p['clinical_notes'])) {
                $patient->clinical_notes = $patient->clinical_notes ?: $p['clinical_notes'];
            }

            if (! $exists) {
                $patient->is_active = true;
            }

            if (! $dry) {
                $patient->save();
            }

            $exists ? $updated++ : $created++;
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        $this->info(($dry ? '[DRY-RUN] ' : '')."Pazienti nuovi: {$created} · arricchiti: {$updated}");

        return self::SUCCESS;
    }
}
