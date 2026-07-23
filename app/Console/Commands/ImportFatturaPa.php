<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Patient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use SimpleXMLElement;

/**
 * Importa lo storico da SmartPodos leggendo le fatture elettroniche FatturaPA.
 *
 * Da ogni XML ricava l anagrafica del cliente (paziente o struttura) e la
 * fattura con le sue righe. Gli importi sono presi cosi come sono dal file,
 * senza ricalcoli: sono documenti fiscali gia emessi.
 *
 * Uso:
 *   php artisan podo:import-fatturapa storage/app/import --dry-run
 *   php artisan podo:import-fatturapa storage/app/import
 */
class ImportFatturaPa extends Command
{
    protected $signature = 'podo:import-fatturapa
                            {path : Cartella con gli XML o gli archivi zip}
                            {--dry-run : Analizza senza scrivere nulla}
                            {--limit=0 : Limita il numero di fatture elaborate}';

    protected $description = 'Importa pazienti e fatture storiche dagli XML FatturaPA di SmartPodos';

    private array $stats = [
        'file' => 0, 'fatture_create' => 0, 'fatture_saltate' => 0,
        'pazienti_creati' => 0, 'pazienti_esistenti' => 0,
        'aziende' => 0, 'righe' => 0, 'errori' => 0,
    ];

    private array $errors = [];

    /** Codici fiscali gia visti: serve a contare i pazienti unici in dry-run. */
    private array $seen = [];

    public function handle(): int
    {
        $path = $this->argument('path');
        $dryRun = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');

        if (! is_dir($path)) {
            $this->error("Cartella non trovata: {$path}");

            return self::FAILURE;
        }

        $files = $this->collectXmlFiles($path);
        if (! $files) {
            $this->error('Nessun file XML trovato (ne dentro gli zip).');

            return self::FAILURE;
        }

        $this->info(sprintf('Trovate %d fatture XML%s', count($files), $dryRun ? ' — modalita analisi (nessuna scrittura)' : ''));
        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        foreach ($files as $file) {
            if ($limit > 0 && $this->stats['file'] >= $limit) {
                break;
            }
            $this->stats['file']++;

            try {
                $this->importFile($file, $dryRun);
            } catch (\Throwable $e) {
                $this->stats['errori']++;
                $this->errors[] = basename($file).': '.$e->getMessage();
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->report();

        return self::SUCCESS;
    }

    /** Raccoglie gli XML dalla cartella, estraendo gli zip in una cartella temporanea. */
    private function collectXmlFiles(string $path): array
    {
        $files = glob(rtrim($path, '/').'/*.xml') ?: [];

        foreach (glob(rtrim($path, '/').'/*.zip') ?: [] as $zipPath) {
            $zip = new \ZipArchive();
            if ($zip->open($zipPath) !== true) {
                continue;
            }
            $tmp = storage_path('app/import-tmp/'.md5($zipPath));
            if (! is_dir($tmp)) {
                mkdir($tmp, 0755, true);
            }
            $zip->extractTo($tmp);
            $zip->close();
            $files = array_merge($files, glob($tmp.'/*.xml') ?: []);
        }

        sort($files);

        return $files;
    }

    private function importFile(string $file, bool $dryRun): void
    {
        $raw = file_get_contents($file);
        $xml = new SimpleXMLElement($raw);

        $header = $xml->FatturaElettronicaHeader;
        $client = $this->parseClient($header->CessionarioCommittente);

        foreach ($xml->FatturaElettronicaBody as $body) {
            $doc = $body->DatiGenerali->DatiGeneraliDocumento;
            $number = (int) trim((string) $doc->Numero);
            $date = trim((string) $doc->Data);
            $year = (int) substr($date, 0, 4);

            if ($number <= 0 || $year <= 0) {
                throw new \RuntimeException('Numero o data non leggibili');
            }

            // Idempotente: non reimporta una fattura gia presente
            if (Invoice::withTrashed()->where('year', $year)->where('number', $number)->exists()) {
                $this->stats['fatture_saltate']++;

                continue;
            }

            $patientId = null;
            if ($client['is_person'] && $client['fiscal_code']) {
                $patientId = $this->resolvePatient($client, $dryRun);
            } elseif (! $client['is_person']) {
                $this->stats['aziende']++;
            }

            $lines = $this->parseLines($body);
            $this->stats['righe'] += count($lines);

            if ($dryRun) {
                $this->stats['fatture_create']++;

                continue;
            }

            DB::transaction(function () use ($doc, $client, $patientId, $lines, $number, $year, $date) {
                $stamp = isset($doc->DatiBollo->ImportoBollo) ? (float) $doc->DatiBollo->ImportoBollo : 0.0;
                $withholding = isset($doc->DatiRitenuta->ImportoRitenuta) ? (float) $doc->DatiRitenuta->ImportoRitenuta : 0.0;
                $total = (float) $doc->ImportoTotaleDocumento;
                $taxable = array_sum(array_column($lines, 'line_total'));
                $vat = array_sum(array_map(fn ($l) => round($l['line_total'] * $l['vat_rate'] / 100, 2), $lines));

                $invoice = Invoice::create([
                    'patient_id' => $patientId,
                    'status' => InvoiceStatus::ISSUED,
                    'number' => $number,
                    'year' => $year,
                    'issued_at' => $date,
                    'client_name' => $client['name'],
                    'client_fiscal_code' => $client['fiscal_code'],
                    'client_vat' => $client['vat'],
                    'client_address' => $client['address'],
                    'client_city' => $client['city'],
                    'client_cap' => $client['cap'],
                    'client_province' => $client['province'],
                    'taxable' => round($taxable, 2),
                    'vat_amount' => round($vat, 2),
                    'stamp_amount' => $stamp,
                    'withholding_amount' => $withholding,
                    'total' => $total,
                    'net_to_pay' => round($total - $withholding, 2),
                    'vat_exempt' => $vat == 0.0,
                    'vat_nature' => $lines[0]['vat_nature'] ?? null,
                    'regime' => 'ordinario',
                    'notes' => 'Importata da SmartPodos',
                ]);

                foreach ($lines as $line) {
                    $invoice->lines()->create($line);
                }
            });

            $this->stats['fatture_create']++;
        }
    }

    /** Legge il committente: persona fisica (paziente) o struttura con P.IVA. */
    private function parseClient(SimpleXMLElement $node): array
    {
        $ana = $node->DatiAnagrafici;
        $nome = trim((string) ($ana->Anagrafica->Nome ?? ''));
        $cognome = trim((string) ($ana->Anagrafica->Cognome ?? ''));
        $denominazione = trim((string) ($ana->Anagrafica->Denominazione ?? ''));
        $cf = strtoupper(trim((string) ($ana->CodiceFiscale ?? '')));
        $piva = trim((string) ($ana->IdFiscaleIVA->IdCodice ?? ''));
        $sede = $node->Sede;

        $isPerson = $denominazione === '' && ($nome !== '' || $cognome !== '');

        return [
            'is_person' => $isPerson,
            'first_name' => $nome,
            'last_name' => $cognome,
            'name' => $isPerson ? trim("{$nome} {$cognome}") : $denominazione,
            'fiscal_code' => strlen($cf) === 16 ? $cf : null,
            'vat' => $piva ?: null,
            'address' => trim((string) ($sede->Indirizzo ?? '')) ?: null,
            'city' => trim((string) ($sede->Comune ?? '')) ?: null,
            'cap' => $this->normalizeCap((string) ($sede->CAP ?? '')),
            'province' => mb_substr(trim((string) ($sede->Provincia ?? '')), 0, 4) ?: null,
        ];
    }

    /**
     * Normalizza il CAP: nei dati storici capita che sia un intervallo
     * (es. "20121-20162"), che non entra nel campo. Tiene il primo valore.
     */
    private function normalizeCap(string $cap): ?string
    {
        $cap = trim($cap);
        if ($cap === '') {
            return null;
        }
        if (preg_match('/\d{5}/', $cap, $m)) {
            return $m[0];
        }

        return mb_substr($cap, 0, 10);
    }

    /** Trova o crea il paziente in base al codice fiscale. */
    private function resolvePatient(array $client, bool $dryRun): ?int
    {
        $cf = $client['fiscal_code'];

        $existing = Patient::where('fiscal_code', $cf)->first();
        if ($existing) {
            $this->stats['pazienti_esistenti']++;

            return $existing->id;
        }

        // In analisi il paziente non viene scritto: tengo traccia dei CF
        // gia incontrati per non contarli piu volte.
        if ($dryRun) {
            if (isset($this->seen[$cf])) {
                $this->stats['pazienti_esistenti']++;

                return null;
            }
            $this->seen[$cf] = true;
            $this->stats['pazienti_creati']++;

            return null;
        }

        $this->stats['pazienti_creati']++;

        return Patient::create([
            'first_name' => $client['first_name'] ?: '—',
            'last_name' => $client['last_name'] ?: $client['name'],
            'fiscal_code' => $client['fiscal_code'],
            'address' => $client['address'],
            'city' => $client['city'],
            'postal_code' => $client['cap'],
            'province' => $client['province'],
            'notes' => 'Importato da SmartPodos',
            'is_active' => true,
        ])->id;
    }

    private function parseLines(SimpleXMLElement $body): array
    {
        $lines = [];

        foreach ($body->DatiBeniServizi->DettaglioLinee as $l) {
            $qty = (float) ($l->Quantita ?? 1);
            $unit = (float) ($l->PrezzoUnitario ?? 0);
            $tot = isset($l->PrezzoTotale) ? (float) $l->PrezzoTotale : $qty * $unit;

            $lines[] = [
                'description' => mb_substr(trim((string) $l->Descrizione), 0, 150) ?: 'Prestazione',
                'quantity' => max(1, (int) round($qty)),
                'unit_price' => round($unit, 2),
                'line_total' => round($tot, 2),
                'vat_rate' => round((float) ($l->AliquotaIVA ?? 0), 2),
                'vat_nature' => trim((string) ($l->Natura ?? '')) ?: null,
            ];
        }

        return $lines;
    }

    private function report(): void
    {
        $this->table(['Voce', 'Totale'], [
            ['File XML elaborati', $this->stats['file']],
            ['Fatture importate', $this->stats['fatture_create']],
            ['Fatture gia presenti (saltate)', $this->stats['fatture_saltate']],
            ['Righe prestazione', $this->stats['righe']],
            ['Pazienti creati', $this->stats['pazienti_creati']],
            ['Pazienti gia esistenti', $this->stats['pazienti_esistenti']],
            ['Fatture a strutture (non pazienti)', $this->stats['aziende']],
            ['Errori', $this->stats['errori']],
        ]);

        if ($this->errors) {
            $this->warn('Primi errori riscontrati:');
            foreach (array_slice($this->errors, 0, 10) as $e) {
                $this->line('  - '.$e);
            }
        }
    }
}
