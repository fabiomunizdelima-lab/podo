<?php

namespace App\Services;
use App\Models\Setting;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Support\Collection;

/**
 * Export delle spese sanitarie per il Sistema Tessera Sanitaria (730 precompilato).
 *
 * NOTA: il tracciato ufficiale TS ha un formato preciso (e credenziali di invio).
 * Questo export produce un CSV con i campi essenziali, allineabile al tracciato
 * reale quando saranno disponibili i codici/credenziali TS dello studio.
 */
class SistemaTsService
{
    /** Righe CSV per le fatture emesse/pagate in un intervallo. */
    public function export(int $year, ?int $month = null): string
    {
        $cfg = Setting::billing();

        $query = Invoice::query()
            ->whereIn('status', [InvoiceStatus::ISSUED->value, InvoiceStatus::PAID->value])
            ->whereYear('issued_at', $year);

        if ($month) {
            $query->whereMonth('issued_at', $month);
        }

        $invoices = $query->orderBy('issued_at')->orderBy('number')->get();

        $rows = new Collection();
        $rows->push($this->header());

        foreach ($invoices as $inv) {
            $rows->push($this->row($inv, $cfg));
        }

        return $rows->map(fn ($r) => implode(';', $r))->implode("\r\n");
    }

    private function header(): array
    {
        return [
            'PIVA_Prestatore', 'CF_Cittadino', 'Data_Documento', 'Numero_Documento',
            'Tipo_Documento', 'Tipo_Spesa', 'Importo', 'Pagamento_Tracciato', 'Opposizione_730',
        ];
    }

    private function row(Invoice $inv, array $cfg): array
    {
        // Pagamento tracciato: 1 se metodo diverso da contanti
        $tracciato = $inv->payment_method && ! str_contains(strtolower($inv->payment_method), 'contant') ? '1' : '0';

        return [
            $cfg['vat_number'] ?: '',
            $inv->client_fiscal_code ?: '',
            optional($inv->issued_at)->format('d/m/Y') ?: '',
            (string) $inv->full_number,
            'F',                                   // Fattura
            $cfg['ts_default_type'] ?: 'SR',
            number_format((float) $inv->total, 2, '.', ''),
            $tracciato,
            '0',                                   // opposizione all invio: no
        ];
    }

    public function filename(int $year, ?int $month = null): string
    {
        return $month
            ? sprintf('sistema-ts-%d-%02d.csv', $year, $month)
            : sprintf('sistema-ts-%d.csv', $year);
    }
}
