<?php

namespace App\Console\Commands;

use App\Models\Patient;
use Illuminate\Console\Command;

/**
 * Importa le schede anamnestiche di SmartPodos (una per paziente),
 * agganciate tramite legacy_fm_id.
 *
 * Idempotente per natura: l'anamnesi è 1:1 col paziente (updateOrCreate).
 * I campi clinici sono cifrati a riposo dal model. Il JSON NON va committato.
 */
class ImportAnamnesi extends Command
{
    protected $signature = 'podo:import-anamnesi
        {file=storage/app/import/anamnesi.json : Percorso del JSON}
        {--dry-run : Simula senza scrivere}';

    protected $description = 'Importa le anamnesi storiche (match su legacy_fm_id)';

    /** Flag booleani riconosciuti nell'export. */
    private const FLAGS = [
        'diabetes', 'on_anticoagulants', 'smoker', 'hypertension',
        'circulatory_disorders', 'neuropathy', 'immunosuppressed',
        'pacemaker', 'latex_allergy',
    ];

    /** Campi di testo (quelli clinici vengono cifrati dal cast del model). */
    private const TEXTS = [
        'profession', 'sport_activity', 'footwear_notes', 'diabetes_type',
        'foot_type_left', 'foot_type_right',
        'medical_history', 'surgeries', 'medications', 'allergies', 'podiatric_notes',
    ];

    public function handle(): int
    {
        $path = $this->argument('file');
        if (! is_file($path)) {
            $this->error("File non trovato: {$path}");

            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($path), true);
        $rows = $data['records'] ?? [];
        if (! $rows) {
            $this->warn('Nessuna anamnesi nel file.');

            return self::SUCCESS;
        }

        $dry = (bool) $this->option('dry-run');
        $map = Patient::whereNotNull('legacy_fm_id')->pluck('id', 'legacy_fm_id');

        $done = 0;
        $skip = 0;

        $bar = $this->output->createProgressBar(count($rows));
        foreach ($rows as $r) {
            $pid = $map[$r['fm_id'] ?? ''] ?? null;
            if (! $pid) {
                $skip++;
                $bar->advance();

                continue;
            }
            if ($dry) {
                $done++;
                $bar->advance();

                continue;
            }

            $attrs = [];
            foreach (self::TEXTS as $f) {
                if (array_key_exists($f, $r) && $r[$f] !== null && $r[$f] !== '') {
                    $attrs[$f] = $r[$f];
                }
            }
            foreach (self::FLAGS as $f) {
                if (array_key_exists($f, $r)) {
                    $attrs[$f] = filter_var($r[$f], FILTER_VALIDATE_BOOLEAN);
                }
            }

            if ($attrs) {
                Patient::find($pid)->clinicalRecord()
                    ->updateOrCreate(['patient_id' => $pid], $attrs);
                $done++;
            } else {
                $skip++;
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);

        $this->table(['Voce', 'Totale'], [
            ['Anamnesi importate/aggiornate', $done],
            ['Saltate (paziente non trovato o scheda vuota)', $skip],
        ]);

        return self::SUCCESS;
    }
}
