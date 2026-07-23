@extends('layouts.app')
@section('title', 'Audit')

@php
    $eventColors = [
        'login' => '#16a34a', 'logout' => '#64748b', 'login_failed' => '#dc2626',
        'created' => '#2563eb', 'updated' => '#d97706', 'deleted' => '#dc2626', 'deactivated' => '#dc2626',
    ];
@endphp

@section('content')
<h2 class="mb-4 font-semibold text-slate-800">Registro di audit</h2>

<form method="GET" class="card mb-4 grid grid-cols-1 gap-3 p-4 sm:grid-cols-3 lg:grid-cols-6">
    <select name="log" class="input text-sm">
        <option value="">Tutti i registri</option>
        @foreach ($logNames as $ln)
            <option value="{{ $ln }}" @selected(request('log') === $ln)>{{ $ln }}</option>
        @endforeach
    </select>
    <select name="event" class="input text-sm">
        <option value="">Ogni evento</option>
        @foreach (['login' => 'Accesso', 'login_failed' => 'Accesso fallito', 'logout' => 'Disconnessione', 'created' => 'Creazione', 'updated' => 'Modifica', 'deleted' => 'Cancellazione', 'deactivated' => 'Disattivazione'] as $ev => $lbl)
            <option value="{{ $ev }}" @selected(request('event') === $ev)>{{ $lbl }}</option>
        @endforeach
    </select>
    <select name="causer" class="input text-sm">
        <option value="">Ogni utente</option>
        @foreach ($users as $u)
            <option value="{{ $u->id }}" @selected((int) request('causer') === $u->id)>{{ $u->name }}</option>
        @endforeach
    </select>
    <input type="date" name="from" value="{{ request('from') }}" class="input text-sm" title="Dal">
    <input type="date" name="to" value="{{ request('to') }}" class="input text-sm" title="Al">
    <div class="flex gap-2">
        <button class="btn-primary flex-1">Filtra</button>
        <a href="{{ route('audit.index') }}" class="btn-secondary">Azzera</a>
    </div>
</form>

<div class="card overflow-hidden">
    <table class="hidden min-w-full divide-y divide-slate-200 text-sm md:table">
        <thead class="bg-slate-50 text-left text-xs font-medium uppercase text-slate-500">
            <tr>
                <th class="px-4 py-3">Data/ora</th>
                <th class="px-4 py-3">Utente</th>
                <th class="px-4 py-3">Evento</th>
                <th class="px-4 py-3">Oggetto</th>
                <th class="px-4 py-3">Dettagli</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($activities as $a)
                @php
                    $ev = $a->event ?: '—';
                    $color = $eventColors[$ev] ?? '#64748b';
                    $props = $a->properties ?? collect();
                    $causerName = $a->causer?->name ?? ($props['email'] ?? 'Sistema');
                @endphp
                <tr>
                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-500">{{ $a->created_at->format('d/m/Y H:i:s') }}</td>
                    <td class="px-4 py-3 text-slate-700">{{ $causerName }}</td>
                    <td class="px-4 py-3">
                        <span class="badge" style="background: {{ $color }}20; color: {{ $color }}">{{ $ev }}</span>
                        @if ($a->log_name)<span class="ml-1 text-xs text-slate-400">{{ $a->log_name }}</span>@endif
                    </td>
                    <td class="px-4 py-3 text-slate-600">
                        {{ $a->description }}
                        @if ($a->subject_type)<div class="text-xs text-slate-400">{{ class_basename($a->subject_type) }} #{{ $a->subject_id }}</div>@endif
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-500">
                        @if (isset($props['ip']))IP {{ $props['ip'] }}@endif
                        @if (isset($props['attributes']))
                            <span class="text-slate-400">{{ collect($props['attributes'])->keys()->implode(', ') }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divide-y divide-slate-100 md:hidden">
        @foreach ($activities as $a)
            @php $ev = $a->event ?: '—'; $color = $eventColors[$ev] ?? '#64748b'; $props = $a->properties ?? collect(); @endphp
            <div class="px-4 py-3">
                <div class="flex items-center justify-between">
                    <span class="badge" style="background: {{ $color }}20; color: {{ $color }}">{{ $ev }}</span>
                    <span class="font-mono text-xs text-slate-400">{{ $a->created_at->format('d/m H:i') }}</span>
                </div>
                <div class="mt-1 text-sm text-slate-700">{{ $a->causer?->name ?? ($props['email'] ?? 'Sistema') }} · {{ $a->description }}</div>
            </div>
        @endforeach
    </div>

    @if ($activities->isEmpty())
        <div class="px-5 py-10 text-center text-sm text-slate-400">Nessuna attività registrata.</div>
    @endif
</div>

<div class="mt-4">{{ $activities->links() }}</div>
@endsection
