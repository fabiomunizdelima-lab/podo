@extends('layouts.app')
@section('title', 'Fatture')

@section('content')
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <form method="GET" class="flex gap-2">
        <select name="year" class="input" onchange="this.form.submit()">
            @foreach ($years as $y)<option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>@endforeach
        </select>
        <select name="status" class="input" onchange="this.form.submit()">
            <option value="">Tutti gli stati</option>
            @foreach (\App\Enums\InvoiceStatus::cases() as $s)
                <option value="{{ $s->value }}" @selected($status === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
    </form>
    <div class="flex gap-2">
        <a href="{{ route('invoices.ts', ['year' => $year]) }}" class="btn-secondary">Export Sistema TS</a>
        <a href="{{ route('invoices.create') }}" class="btn-primary">+ Nuova fattura</a>
    </div>
</div>

<div class="card overflow-hidden">
    <table class="hidden min-w-full divide-y divide-slate-200 sm:table">
        <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-5 py-3">Numero</th>
                <th class="px-5 py-3">Data</th>
                <th class="px-5 py-3">Paziente</th>
                <th class="px-5 py-3 text-right">Totale</th>
                <th class="px-5 py-3">Stato</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
            @foreach ($invoices as $inv)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 font-medium text-slate-800">{{ $inv->full_number }}</td>
                    <td class="px-5 py-3 text-slate-500">{{ optional($inv->issued_at)->format('d/m/Y') ?: '—' }}</td>
                    <td class="px-5 py-3 text-slate-700">{{ $inv->client_name }}</td>
                    <td class="px-5 py-3 text-right font-medium text-slate-800">€ {{ number_format((float) $inv->total, 2, ',', '.') }}</td>
                    <td class="px-5 py-3">
                        <span class="badge" style="background: {{ $inv->status->color() }}20; color: {{ $inv->status->color() }}">{{ $inv->status->label() }}</span>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('invoices.show', $inv) }}" class="text-brand-600 hover:underline">Apri</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="divide-y divide-slate-100 sm:hidden">
        @foreach ($invoices as $inv)
            <a href="{{ route('invoices.show', $inv) }}" class="block px-4 py-3">
                <div class="flex justify-between">
                    <span class="font-medium text-slate-800">{{ $inv->full_number }}</span>
                    <span class="font-medium">€ {{ number_format((float) $inv->total, 2, ',', '.') }}</span>
                </div>
                <div class="text-sm text-slate-500">{{ $inv->client_name }} · {{ $inv->status->label() }}</div>
            </a>
        @endforeach
    </div>
    @if ($invoices->isEmpty())
        <div class="px-5 py-10 text-center text-sm text-slate-400">Nessuna fattura.</div>
    @endif
</div>

<div class="mt-4">{{ $invoices->links() }}</div>
@endsection
