<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Console\Command;

/**
 * Importa l'agenda storica di SmartPodos, agganciando gli appuntamenti
 * ai pazienti tramite legacy_fm_id (stessa convenzione di podo:import-cliniche).
 *
 * Idempotente: dedup su legacy_ref. Gli appuntamenti nel passato vengono
 * marcati "completed", quelli futuri restano "scheduled".
 * Il JSON contiene dati personali e NON va committato.
 */
class ImportAppuntamenti extends Command
{
    protected $signature = 'podo:import-appuntamenti
        {file=storage/app/import/appuntamenti.json : Percorso del JSON}
        {--dry-run : Simula senza scrivere}';

    protected $description = 'Importa l\'agenda storica (match su legacy_fm_id, dedup su legacy_ref)';

    public function handle(): int
    {
        $path = $this->argument('file');
        if (! is_file($path)) {
            $this->error("File non trovato: {$path}");

            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($path), true);
        $rows = $data['appointments'] ?? [];
        if (! $rows) {
            $this->warn('Nessun appuntamento nel file.');

            return self::SUCCESS;
        }

        $dry = (bool) $this->option('dry-run');
        $map = Patient::whereNotNull('legacy_fm_id')->pluck('id', 'legacy_fm_id');

        $new = 0;
        $skip = 0;
        $unmatched = [];

        $bar = $this->output->createProgressBar(count($rows));
        foreach ($rows as $a) {
            $pid = $map[$a['fm_id'] ?? ''] ?? null;
            $start = $a['starts_at'] ?? null;

            if (! $pid || ! $start) {
                $skip++;
                if (count($unmatched) < 20) {
                    $unmatched[] = ($a['fm_id'] ?? '?').' | '.($start ?? 'senza data').' | '.($a['treatment'] ?? '');
                }
                $bar->advance();

                continue;
            }

            if ($dry) {
                $new++;
                $bar->advance();

                continue;
            }

            $startAt = \Carbon\Carbon::parse($start);
            // Se l'export non ha l'orario di fine, usa la durata standard di 30 minuti
            $endAt = ! empty($a['ends_at'])
                ? \Carbon\Carbon::parse($a['ends_at'])
                : $startAt->copy()->addMinutes((int) ($a['duration_minutes'] ?? 30));

            $appt = Appointment::where('legacy_ref', $a['ref'])->first() ?: new Appointment();
            $appt->legacy_ref = $a['ref'];
            $appt->patient_id = $pid;
            $appt->starts_at = $startAt;
            $appt->ends_at = $endAt;
            $appt->treatment = $a['treatment'] ?? null;
            $appt->notes = $a['notes'] ?? null;
            $appt->status = $a['status']
                ?? ($startAt->isPast() ? AppointmentStatus::COMPLETED->value : AppointmentStatus::SCHEDULED->value);
            $appt->reminder_channel = 'none';
            $appt->save();
            $new++;
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);

        $this->table(['Voce', 'Totale'], [
            ['Appuntamenti importati/aggiornati', $new],
            ['Saltati (paziente non trovato o senza data)', $skip],
        ]);

        if ($unmatched) {
            $this->warn('Da rivedere a mano (primi 20):');
            foreach ($unmatched as $u) {
                $this->line('  - '.$u);
            }
        }

        return self::SUCCESS;
    }
}
