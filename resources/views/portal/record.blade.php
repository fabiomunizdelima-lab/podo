@extends('layouts.app')
@section('title', 'La mia cartella')

@php $rec = $patient->clinicalRecord; @endphp

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div class="card p-6">
        <h2 class="text-xl font-semibold text-slate-800">{{ $patient->full_name }}</h2>
        <dl class="mt-4 grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
            <div><dt class="text-slate-500">Telefono</dt><dd class="text-slate-800">{{ $patient->phone ?: '—' }}</dd></div>
            <div><dt class="text-slate-500">Email</dt><dd class="text-slate-800">{{ $patient->email ?: '—' }}</dd></div>
            <div><dt class="text-slate-500">Nascita</dt><dd class="text-slate-800">{{ optional($patient->birth_date)->format('d/m/Y') ?: '—' }}</dd></div>
            <div><dt class="text-slate-500">Codice fiscale</dt><dd class="font-mono text-slate-800">{{ $patient->fiscal_code ?: '—' }}</dd></div>
        </dl>
    </div>

    {{-- Prossimi appuntamenti --}}
    <div class="card">
        <div class="border-b border-slate-200 px-6 py-4 font-semibold text-slate-800">Prossimi appuntamenti</div>
        <div class="divide-y divide-slate-100 text-sm">
            @forelse ($patient->appointments as $a)
                <div class="flex items-center gap-3 px-6 py-3">
                    <span class="font-mono text-slate-500">{{ $a->starts_at->format('d/m/Y H:i') }}</span>
                    <span class="text-slate-700">{{ $a->treatment ?: $a->title }}</span>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-slate-400">Nessun appuntamento in programma.</div>
            @endforelse
        </div>
    </div>

    {{-- Le mie visite --}}
    <div class="card">
        <div class="border-b border-slate-200 px-6 py-4 font-semibold text-slate-800">Le mie visite</div>
        <div class="divide-y divide-slate-100">
            @forelse ($patient->clinicalVisits as $v)
                <div class="px-6 py-4 text-sm">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-semibold text-slate-800">{{ $v->visited_at->format('d/m/Y') }}</span>
                        @if ($v->visit_type)<span class="badge" style="background: {{ $v->visit_type->color() }}20; color: {{ $v->visit_type->color() }}">{{ $v->visit_type->label() }}</span>@endif
                    </div>
                    @if ($v->diagnosis)<p class="mt-1 text-slate-600"><span class="text-slate-400">Diagnosi:</span> {{ $v->diagnosis }}</p>@endif
                    @if ($v->treatments->isNotEmpty())
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach ($v->treatments as $t)<span class="badge bg-brand-50 text-brand-700">{{ $t->pivot->description }}</span>@endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="px-6 py-8 text-center text-slate-400">Nessuna visita registrata.</div>
            @endforelse
        </div>
    </div>

    {{-- Le mie ortesi --}}
    @if ($patient->orthoses->isNotEmpty())
        <div class="card">
            <div class="border-b border-slate-200 px-6 py-4 font-semibold text-slate-800">Le mie ortesi</div>
            <div class="divide-y divide-slate-100 text-sm">
                @foreach ($patient->orthoses as $o)
                    <div class="flex items-center justify-between px-6 py-3">
                        <span class="text-slate-700">{{ $o->type }} @if ($o->foot)<span class="text-slate-400">· {{ $o->foot }}</span>@endif</span>
                        <span class="badge" style="background: {{ $o->status->color() }}20; color: {{ $o->status->color() }}">{{ $o->status->label() }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <p class="text-center text-xs text-slate-400">Per modifiche o richieste contatta lo studio.</p>
</div>
@endsection
