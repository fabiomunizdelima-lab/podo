@extends('layouts.app')
@section('title', 'Fattura '.$invoice->full_number)

@php $isAdmin = auth()->user()->atLeast(\App\Enums\Role::ADMIN); @endphp

@section('content')
<div class="mx-auto max-w-3xl space-y-4">

    {{-- Barra azioni --}}
    <div class="flex flex-wrap items-center justify-between gap-2">
        <a href="{{ route('invoices.index') }}" class="text-sm text-slate-500 hover:underline">&larr; Fatture</a>
        <div class="flex flex-wrap gap-2">
            @if ($invoice->isEditable())
                <a href="{{ route('invoices.edit', $invoice) }}" class="btn-secondary">Modifica</a>
                <form method="POST" action="{{ route('invoices.issue', $invoice) }}" onsubmit="return confirm('Emettere e numerare la fattura? Non sara piu modificabile.')">
                    @csrf <button class="btn-primary">Emetti fattura</button>
                </form>
            @else
                <a href="{{ route('invoices.pdf', $invoice) }}" class="btn-secondary">PDF</a>
                <a href="{{ route('invoices.xml', $invoice) }}" class="btn-secondary">XML FatturaPA</a>
                @if ($invoice->status !== \App\Enums\InvoiceStatus::PAID)
                    <form method="POST" action="{{ route('invoices.paid', $invoice) }}">
                        @csrf
                        <input type="hidden" name="payment_method" value="Bonifico">
                        <button class="btn-primary">Segna pagata</button>
                    </form>
                @endif
            @endif
        </div>
    </div>

    {{-- Documento --}}
    <div class="card p-6">
        <div class="flex items-start justify-between">
            <div>
                <div class="text-lg font-semibold text-slate-800">{{ $cfg['studio_name'] }}</div>
                <div class="text-sm text-slate-500">
                    @if ($cfg['vat_number'])P.IVA {{ $cfg['vat_number'] }}@endif
                    @if ($cfg['fiscal_code']) · CF {{ $cfg['fiscal_code'] }}@endif
                </div>
                <div class="text-sm text-slate-500">{{ $cfg['address'] }} {{ $cfg['cap'] }} {{ $cfg['city'] }} {{ $cfg['province'] }}</div>
            </div>
            <div class="text-right">
                <div class="text-sm text-slate-500">Fattura n.</div>
                <div class="text-xl font-bold text-slate-800">{{ $invoice->full_number }}</div>
                <div class="text-sm text-slate-500">{{ optional($invoice->issued_at)->format('d/m/Y') ?: 'da emettere' }}</div>
                <span class="badge mt-1" style="background: {{ $invoice->status->color() }}20; color: {{ $invoice->status->color() }}">{{ $invoice->status->label() }}</span>
            </div>
        </div>

        <div class="mt-6 rounded-lg bg-slate-50 p-4 text-sm">
            <div class="text-slate-500">Cliente</div>
            <div class="font-medium text-slate-800">{{ $invoice->client_name }}</div>
            @if ($invoice->client_fiscal_code)<div class="font-mono text-xs text-slate-500">{{ $invoice->client_fiscal_code }}</div>@endif
            <div class="text-slate-600">{{ $invoice->client_address }} {{ $invoice->client_cap }} {{ $invoice->client_city }} {{ $invoice->client_province }}</div>
        </div>

        <table class="mt-6 min-w-full text-sm">
            <thead class="border-b border-slate-200 text-left text-xs uppercase text-slate-500">
                <tr><th class="py-2">Descrizione</th><th class="py-2 text-center">Qta</th><th class="py-2 text-right">Prezzo</th><th class="py-2 text-right">IVA</th><th class="py-2 text-right">Totale</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($invoice->lines as $l)
                    <tr>
                        <td class="py-2 text-slate-800">{{ $l->description }}</td>
                        <td class="py-2 text-center text-slate-600">{{ $l->quantity }}</td>
                        <td class="py-2 text-right text-slate-600">€ {{ number_format((float) $l->unit_price, 2, ',', '.') }}</td>
                        <td class="py-2 text-right text-slate-500">{{ (float) $l->vat_rate > 0 ? rtrim(rtrim(number_format($l->vat_rate,2), '0'), '.').'%' : ($l->vat_nature ?: 'esente') }}</td>
                        <td class="py-2 text-right font-medium text-slate-800">€ {{ number_format((float) $l->line_total, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4 flex justify-end">
            <dl class="w-64 space-y-1 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Imponibile</dt><dd class="text-slate-800">€ {{ number_format((float) $invoice->taxable, 2, ',', '.') }}</dd></div>
                @if ((float) $invoice->vat_amount > 0)<div class="flex justify-between"><dt class="text-slate-500">IVA</dt><dd class="text-slate-800">€ {{ number_format((float) $invoice->vat_amount, 2, ',', '.') }}</dd></div>@endif
                @if ((float) $invoice->stamp_amount > 0)<div class="flex justify-between"><dt class="text-slate-500">Marca da bollo</dt><dd class="text-slate-800">€ {{ number_format((float) $invoice->stamp_amount, 2, ',', '.') }}</dd></div>@endif
                @if ((float) $invoice->withholding_amount > 0)<div class="flex justify-between"><dt class="text-slate-500">Ritenuta</dt><dd class="text-red-600">− € {{ number_format((float) $invoice->withholding_amount, 2, ',', '.') }}</dd></div>@endif
                <div class="flex justify-between border-t border-slate-200 pt-1 text-base font-semibold"><dt>Netto a pagare</dt><dd>€ {{ number_format((float) $invoice->net_to_pay, 2, ',', '.') }}</dd></div>
            </dl>
        </div>

        @if ($invoice->vat_exempt)
            <p class="mt-4 text-xs text-slate-400">{{ $cfg['register_note'] }}</p>
        @endif
    </div>

    @if ($isAdmin && $invoice->isEditable())
        <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" onsubmit="return confirm('Archiviare la bozza?')">
            @csrf @method('DELETE')
            <button class="text-sm text-red-600 hover:underline">Archivia bozza</button>
        </form>
    @endif
</div>
@endsection
