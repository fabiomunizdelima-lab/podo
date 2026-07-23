<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 12px; color: #1e293b; margin: 0; }
        .row { width: 100%; }
        .right { text-align: right; }
        .center { text-align: center; }
        .muted { color: #64748b; }
        h1 { font-size: 16px; margin: 0; }
        .num { font-size: 20px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; border-bottom: 1px solid #cbd5e1; padding: 6px 4px; font-size: 10px; text-transform: uppercase; color: #64748b; }
        td { padding: 6px 4px; border-bottom: 1px solid #f1f5f9; }
        .box { background: #f8fafc; padding: 12px; margin-top: 16px; }
        .totals { width: 240px; float: right; margin-top: 12px; }
        .totals td { border: none; padding: 3px 4px; }
        .grand { border-top: 1px solid #cbd5e1; font-weight: bold; font-size: 14px; }
        .note { clear: both; padding-top: 30px; font-size: 10px; color: #94a3b8; }
    </style>
</head>
<body>
    <table class="row"><tr>
        <td>
            <h1>{{ $cfg['studio_name'] }}</h1>
            <div class="muted">
                @if ($cfg['vat_number'])P.IVA {{ $cfg['vat_number'] }}@endif
                @if ($cfg['fiscal_code']) &middot; CF {{ $cfg['fiscal_code'] }}@endif
            </div>
            <div class="muted">{{ $cfg['address'] }} {{ $cfg['cap'] }} {{ $cfg['city'] }} {{ $cfg['province'] }}</div>
        </td>
        <td class="right">
            <div class="muted">Fattura n.</div>
            <div class="num">{{ $invoice->full_number }}</div>
            <div class="muted">{{ optional($invoice->issued_at)->format('d/m/Y') }}</div>
        </td>
    </tr></table>

    <div class="box">
        <div class="muted">Cliente</div>
        <strong>{{ $invoice->client_name }}</strong>
        @if ($invoice->client_fiscal_code)<div class="muted">{{ $invoice->client_fiscal_code }}</div>@endif
        <div class="muted">{{ $invoice->client_address }} {{ $invoice->client_cap }} {{ $invoice->client_city }} {{ $invoice->client_province }}</div>
    </div>

    <table>
        <thead><tr><th>Descrizione</th><th class="center">Qta</th><th class="right">Prezzo</th><th class="right">IVA</th><th class="right">Totale</th></tr></thead>
        <tbody>
            @foreach ($invoice->lines as $l)
                <tr>
                    <td>{{ $l->description }}</td>
                    <td class="center">{{ $l->quantity }}</td>
                    <td class="right">&euro; {{ number_format((float) $l->unit_price, 2, ',', '.') }}</td>
                    <td class="right">{{ (float) $l->vat_rate > 0 ? number_format($l->vat_rate,0).'%' : ($l->vat_nature ?: 'esente') }}</td>
                    <td class="right">&euro; {{ number_format((float) $l->line_total, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td class="muted">Imponibile</td><td class="right">&euro; {{ number_format((float) $invoice->taxable, 2, ',', '.') }}</td></tr>
        @if ((float) $invoice->vat_amount > 0)<tr><td class="muted">IVA</td><td class="right">&euro; {{ number_format((float) $invoice->vat_amount, 2, ',', '.') }}</td></tr>@endif
        @if ((float) $invoice->stamp_amount > 0)<tr><td class="muted">Marca da bollo</td><td class="right">&euro; {{ number_format((float) $invoice->stamp_amount, 2, ',', '.') }}</td></tr>@endif
        @if ((float) $invoice->withholding_amount > 0)<tr><td class="muted">Ritenuta</td><td class="right">&minus; &euro; {{ number_format((float) $invoice->withholding_amount, 2, ',', '.') }}</td></tr>@endif
        <tr class="grand"><td>Netto a pagare</td><td class="right">&euro; {{ number_format((float) $invoice->net_to_pay, 2, ',', '.') }}</td></tr>
    </table>

    @if ($invoice->vat_exempt)
        <div class="note">{{ $cfg['register_note'] }}</div>
    @endif
</body>
</html>
