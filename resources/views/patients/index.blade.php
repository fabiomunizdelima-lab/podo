@extends('layouts.app')
@section('title', 'Pazienti')

@section('content')
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <form method="GET" class="flex-1 sm:max-w-xs">
        <input type="search" name="q" value="{{ $q }}" placeholder="Cerca per nome, CF, telefono…" class="input">
    </form>
    <a href="{{ route('patients.create') }}" class="btn-primary">+ Nuovo paziente</a>
</div>

<div class="card overflow-hidden">
    {{-- Tabella su desktop --}}
    <table class="hidden min-w-full divide-y divide-slate-200 sm:table">
        <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-5 py-3">Paziente</th>
                <th class="px-5 py-3">Codice fiscale</th>
                <th class="px-5 py-3">Telefono</th>
                <th class="px-5 py-3">Consensi</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
            @foreach ($patients as $p)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 font-medium text-slate-800">{{ $p->full_name }}</td>
                    <td class="px-5 py-3 font-mono text-xs text-slate-500">{{ $p->fiscal_code }}</td>
                    <td class="px-5 py-3 text-slate-600">{{ $p->phone }}</td>
                    <td class="px-5 py-3">
                        @if ($p->consent_whatsapp)<span class="badge bg-green-100 text-green-700">WhatsApp</span>@endif
                        @if ($p->consent_privacy)<span class="badge bg-slate-100 text-slate-600">Privacy</span>@endif
                    </td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('patients.show', $p) }}" class="text-brand-600 hover:underline">Apri</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Card list su mobile --}}
    <div class="divide-y divide-slate-100 sm:hidden">
        @foreach ($patients as $p)
            <a href="{{ route('patients.show', $p) }}" class="block px-4 py-3">
                <div class="font-medium text-slate-800">{{ $p->full_name }}</div>
                <div class="text-sm text-slate-500">{{ $p->phone }} · {{ $p->fiscal_code }}</div>
            </a>
        @endforeach
    </div>

    @if ($patients->isEmpty())
        <div class="px-5 py-10 text-center text-sm text-slate-400">Nessun paziente trovato.</div>
    @endif
</div>

<div class="mt-4">{{ $patients->links() }}</div>
@endsection
