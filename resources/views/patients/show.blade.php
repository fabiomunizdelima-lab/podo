@extends('layouts.app')
@section('title', $patient->full_name)

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div class="card p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-slate-800">{{ $patient->full_name }}</h2>
                <p class="text-sm text-slate-500 font-mono">{{ $patient->fiscal_code }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('patients.edit', $patient) }}" class="btn-secondary">Modifica</a>
                @if (auth()->user()->atLeast(\App\Enums\Role::ADMIN))
                    <form method="POST" action="{{ route('patients.destroy', $patient) }}" onsubmit="return confirm('Archiviare questo paziente?')">
                        @csrf @method('DELETE')
                        <button class="btn-danger">Archivia</button>
                    </form>
                @endif
            </div>
        </div>

        <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
            <div><dt class="text-slate-500">Telefono</dt><dd class="text-slate-800">{{ $patient->phone ?: '—' }}</dd></div>
            <div><dt class="text-slate-500">WhatsApp</dt><dd class="text-slate-800">{{ $patient->whatsapp_phone ?: '—' }}</dd></div>
            <div><dt class="text-slate-500">Email</dt><dd class="text-slate-800">{{ $patient->email ?: '—' }}</dd></div>
            <div><dt class="text-slate-500">Nascita</dt><dd class="text-slate-800">{{ optional($patient->birth_date)->format('d/m/Y') ?: '—' }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-slate-500">Indirizzo</dt><dd class="text-slate-800">{{ $patient->address }} {{ $patient->postal_code }} {{ $patient->city }} {{ $patient->province }}</dd></div>
        </dl>

        @if ($patient->clinical_notes)
            <div class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-900 ring-1 ring-amber-200">
                <div class="mb-1 font-medium">Note cliniche</div>
                {{ $patient->clinical_notes }}
            </div>
        @endif
    </div>

    <div class="card">
        <div class="border-b border-slate-200 px-6 py-4 font-semibold text-slate-800">Storico appuntamenti</div>
        <div class="divide-y divide-slate-100 text-sm">
            @forelse ($patient->appointments as $a)
                <div class="flex items-center gap-3 px-6 py-3">
                    <span class="font-mono text-slate-500">{{ $a->starts_at->format('d/m/Y H:i') }}</span>
                    <span class="text-slate-700">{{ $a->treatment ?: $a->title }}</span>
                    <span class="badge ml-auto" style="background: {{ $a->status->color() }}20; color: {{ $a->status->color() }}">{{ $a->status->label() }}</span>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-slate-400">Nessun appuntamento registrato.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
