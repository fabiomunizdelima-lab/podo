<?php

namespace App\Console\Commands;

use App\Models\ClinicalPhoto;
use App\Models\ClinicalVisit;
use App\Models\Patient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Importa la documentazione fotografica storica di SmartPodos.
 *
 * ATTENZIONE: i file in Files/Images/Secure del vecchio gestionale sono
 * container FileMaker CIFRATI e inutilizzabili così come sono. Le immagini
 * vanno esportate in chiaro da FileMaker (script "Esporta contenuto campo"
 * o GetAs via ODBC) in una cartella, con un JSON di mappatura.
 *
 * I file importati vengono cifrati con la chiave dell'app (stesso schema
 * delle foto caricate da interfaccia) e i sorgenti in chiaro ELIMINATI.
 * Idempotente: dedup su legacy_ref.
 */
class ImportFoto extends Command
{
    protected $signature = 'podo:import-foto
        {dir=storage/app/import/foto : Cartella con i file immagine e foto.json}
        {--keep-sources : Non eliminare i file sorgente a fine import}
        {--dry-run : Simula senza scrivere}';

    protected $description = 'Importa le foto cliniche storiche (cifrandole; match su legacy_fm_id)';

    public function handle(): int
    {
        $dir = rtrim($this->argument('dir'), '/');
        $mapFile = $dir.'/foto.json';
        if (! is_file($mapFile)) {
            $this->error("Mappatura non trovata: {$mapFile}");

            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($mapFile), true);
        $rows = $data['photos'] ?? [];
        if (! $rows) {
            $this->warn('Nessuna foto nella mappatura.');

            return self::SUCCESS;
        }

        $dry = (bool) $this->option('dry-run');
        $patientMap = Patient::whereNotNull('legacy_fm_id')->pluck('id', 'legacy_fm_id');
        $visitMap = ClinicalVisit::whereNotNull('legacy_ref')->pluck('id', 'legacy_ref');

        $new = 0;
        $skip = 0;
        $missing = [];

        $bar = $this->output->createProgressBar(count($rows));
        foreach ($rows as $p) {
            $pid = $patientMap[$p['fm_id'] ?? ''] ?? null;
            $file = $dir.'/'.($p['file'] ?? '');

            if (! $pid || ! is_file($file)) {
                $skip++;
                if (! is_file($file) && count($missing) < 15) {
                    $missing[] = $p['file'] ?? '(senza nome)';
                }
                $bar->advance();

                continue;
            }

            // Accetta solo immagini reali
            $mime = mime_content_type($file) ?: '';
            if (! str_starts_with($mime, 'image/')) {
                $skip++;
                $bar->advance();

                continue;
            }

            if ($dry) {
                $new++;
                $bar->advance();

                continue;
            }

            $existing = ClinicalPhoto::where('legacy_ref', $p['ref'])->first();
            if ($existing) {
                $new++;
                $bar->advance();

                continue; // già importata
            }

            // Stesso schema di cifratura delle foto caricate da interfaccia
            $relPath = 'clinical/'.$pid.'/'.Str::uuid()->toString().'.enc';
            Storage::disk('local')->put($relPath, Crypt::encryptString(base64_encode(file_get_contents($file))));

            ClinicalPhoto::create([
                'legacy_ref' => $p['ref'],
                'patient_id' => $pid,
                'clinical_visit_id' => $visitMap[$p['visit_ref'] ?? ''] ?? null,
                'disk' => 'local',
                'path' => $relPath,
                'original_name' => basename($p['file']),
                'mime' => $mime,
                'size' => filesize($file),
                'foot' => $p['foot'] ?? null,
                'caption' => $p['caption'] ?? 'Importata da SmartPodos',
                'taken_at' => $p['taken_at'] ?? null,
            ]);
            $new++;
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);

        // I sorgenti in chiaro sono dati sanitari: via dal disco a fine import
        if (! $dry && ! $this->option('keep-sources') && $new > 0 && $skip === 0) {
            foreach ($rows as $p) {
                @unlink($dir.'/'.($p['file'] ?? ''));
            }
            $this->info('File sorgente in chiaro eliminati (i dati restano cifrati nell\'app).');
        }

        $this->table(['Voce', 'Totale'], [
            ['Foto importate', $new],
            ['Saltate (paziente/file mancante o non immagine)', $skip],
        ]);

        if ($missing) {
            $this->warn('File citati nella mappatura ma assenti (primi 15):');
            foreach ($missing as $m) {
                $this->line('  - '.$m);
            }
        }

        return self::SUCCESS;
    }
}
