@extends('layouts.app')
@section('title', $patient->full_name)

@php
    $rec = $patient->clinicalRecord;
    $isAdmin = auth()->user()->atLeast(\App\Enums\Role::ADMIN);
    $riskFlags = [
        'diabetes' => 'Diabete', 'on_anticoagulants' => 'Anticoagulanti', 'smoker' => 'Fumatore',
        'hypertension' => 'Ipertensione', 'circulatory_disorders' => 'Disturbi circolatori',
        'neuropathy' => 'Neuropatia', 'immunosuppressed' => 'Immunodepressione',
        'pacemaker' => 'Pacemaker', 'latex_allergy' => 'Allergia lattice',
    ];
@endphp

@section('content')
<div class="mx-auto max-w-4xl space-y-6" x-data="{ tab: 'info' }">

    {{-- Intestazione paziente --}}
    <div class="card p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-slate-800">{{ $patient->full_name }}</h2>
                <p class="text-sm text-slate-500 font-mono">{{ $patient->fiscal_code }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('patients.edit', $patient) }}" class="btn-secondary">Modifica anagrafica</a>
                @if ($isAdmin)
                    <form method="POST" action="{{ route('patients.destroy', $patient) }}" onsubmit="return confirm('Archiviare questo paziente?')">
                        @csrf @method('DELETE')
                        <button class="btn-danger">Archivia</button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Flag di rischio in evidenza --}}
        @if ($rec)
            <div class="mt-3 flex flex-wrap gap-1.5">
                @foreach ($riskFlags as $field => $label)
                    @if ($rec->$field)
                        <span class="badge bg-red-100 text-red-700">{{ $label }}</span>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    {{-- Tab bar --}}
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex gap-6 text-sm font-medium">
            @foreach (['info' => 'Riepilogo', 'anamnesi' => 'Cartella clinica', 'visite' => 'Visite', 'foto' => 'Foto', 'ortesi' => 'Ortesi'] as $key => $label)
                <button @click="tab='{{ $key }}'"
                        :class="tab==='{{ $key }}' ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="border-b-2 px-1 py-3">
                    {{ $label }}
                    @if ($key === 'visite') <span class="ml-1 text-xs text-slate-400">{{ $patient->clinicalVisits->count() }}</span>@endif
                    @if ($key === 'foto') <span class="ml-1 text-xs text-slate-400">{{ $patient->clinicalPhotos->count() }}</span>@endif
                @if ($key === 'ortesi') <span class="ml-1 text-xs text-slate-400">{{ $patient->orthoses->count() }}</span>@endif
                </button>
            @endforeach
        </nav>
    </div>

    {{-- RIEPILOGO --}}
    <div x-show="tab==='info'" x-cloak class="space-y-6">
        <div class="card p-6">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                <div><dt class="text-slate-500">Telefono</dt><dd class="text-slate-800">{{ $patient->phone ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">WhatsApp</dt><dd class="text-slate-800">{{ $patient->whatsapp_phone ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Email</dt><dd class="text-slate-800">{{ $patient->email ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Nascita</dt><dd class="text-slate-800">{{ optional($patient->birth_date)->format('d/m/Y') ?: '—' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-slate-500">Indirizzo</dt><dd class="text-slate-800">{{ $patient->address }} {{ $patient->postal_code }} {{ $patient->city }} {{ $patient->province }}</dd></div>
            </dl>
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

    {{-- CARTELLA CLINICA (anamnesi) --}}
    <div x-show="tab==='anamnesi'" x-cloak class="card p-6">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">Anamnesi</h3>
            <a href="{{ route('patients.record.edit', $patient) }}" class="btn-secondary">Modifica anamnesi</a>
        </div>
        @if (! $rec)
            <p class="py-6 text-center text-sm text-slate-400">Anamnesi non ancora compilata.</p>
        @else
            <dl class="grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                <div><dt class="text-slate-500">Professione</dt><dd class="text-slate-800">{{ $rec->profession ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Attività sportiva</dt><dd class="text-slate-800">{{ $rec->sport_activity ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Morfologia piede sx</dt><dd class="text-slate-800">{{ $rec->foot_type_left ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Morfologia piede dx</dt><dd class="text-slate-800">{{ $rec->foot_type_right ?: '—' }}</dd></div>
            </dl>
            @foreach (['medical_history' => 'Anamnesi patologica', 'surgeries' => 'Interventi', 'medications' => 'Farmaci', 'allergies' => 'Allergie', 'podiatric_notes' => 'Note podologiche'] as $f => $l)
                @if ($rec->$f)
                    <div class="mt-4">
                        <div class="text-sm font-medium text-slate-700">{{ $l }}</div>
                        <p class="mt-1 whitespace-pre-line text-sm text-slate-600">{{ $rec->$f }}</p>
                    </div>
                @endif
            @endforeach
        @endif
    </div>

    {{-- VISITE --}}
    <div x-show="tab==='visite'" x-cloak class="space-y-4">
        <div class="flex justify-end">
            <a href="{{ route('patients.visits.create', $patient) }}" class="btn-primary">+ Nuova visita</a>
        </div>
        @forelse ($patient->clinicalVisits as $v)
            <div class="card p-5">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold text-slate-800">{{ $v->visited_at->format('d/m/Y H:i') }}</span>
                            @if ($v->visit_type)
                                <span class="badge" style="background: {{ $v->visit_type->color() }}20; color: {{ $v->visit_type->color() }}">{{ $v->visit_type->label() }}</span>
                            @endif
                        </div>
                        @if ($v->reason)<div class="text-sm text-slate-500">{{ $v->reason }}</div>@endif
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        @if ($v->treatments->isNotEmpty())<span class="font-medium text-slate-700">€ {{ number_format($v->total, 2, ',', '.') }}</span>@endif
                        <a href="{{ route('visits.edit', $v) }}" class="text-brand-600 hover:underline">Apri</a>
                    </div>
                </div>
                @if ($v->diagnosis)<p class="mt-2 text-sm text-slate-600"><span class="text-slate-400">Diagnosi:</span> {{ $v->diagnosis }}</p>@endif
                @if ($v->treatments->isNotEmpty())
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @foreach ($v->treatments as $t)
                            <span class="badge bg-brand-50 text-brand-700">{{ $t->pivot->quantity }}× {{ $t->pivot->description }}</span>
                        @endforeach
                    </div>
                @endif
                @if ($v->photos_count)<div class="mt-2 text-xs text-slate-400">{{ $v->photos_count }} foto</div>@endif
            </div>
        @empty
            <div class="card px-6 py-10 text-center text-sm text-slate-400">Nessuna visita registrata.</div>
        @endforelse
    </div>

    {{-- FOTO --}}
    <div x-show="tab==='foto'" x-cloak class="space-y-4">
        <form method="POST" action="{{ route('patients.photos.store', $patient) }}" enctype="multipart/form-data" class="card p-5">
            @csrf
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                <div class="sm:col-span-2">
                    <label class="label" for="photo">Nuova foto (jpg/png/webp, max 8MB)</label>
                    <input class="input" id="photo" name="photo" type="file" accept="image/*" required>
                </div>
                <div>
                    <label class="label" for="foot">Piede</label>
                    <select class="input" id="foot" name="foot">
                        <option value="">—</option>
                        <option value="L">Sinistro</option>
                        <option value="R">Destro</option>
                        <option value="both">Entrambi</option>
                    </select>
                </div>
                <div>
                    <label class="label" for="caption">Didascalia</label>
                    <input class="input" id="caption" name="caption" maxlength="200">
                </div>
            </div>
            <div class="mt-3 flex justify-end"><button class="btn-primary">Carica</button></div>
        </form>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @forelse ($patient->clinicalPhotos as $ph)
                <div class="group relative overflow-hidden rounded-lg ring-1 ring-slate-200">
                    <a href="{{ route('photos.show', $ph) }}" target="_blank">
                        <img src="{{ route('photos.show', $ph) }}" alt="{{ $ph->caption }}" class="h-32 w-full object-cover">
                    </a>
                    <div class="flex items-center justify-between bg-white px-2 py-1 text-xs text-slate-500">
                        <span class="truncate">{{ $ph->foot ? $ph->foot.' · ' : '' }}{{ optional($ph->taken_at)->format('d/m/y') }}</span>
                        @if ($isAdmin)
                            <form method="POST" action="{{ route('photos.destroy', $ph) }}" onsubmit="return confirm('Eliminare la foto?')">
                                @csrf @method('DELETE')
                                <button class="text-red-500 hover:text-red-700">×</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full py-10 text-center text-sm text-slate-400">Nessuna foto caricata.</div>
            @endforelse
        </div>
    </div>

    {{-- ORTESI --}}
    <div x-show="tab==='ortesi'" x-cloak class="space-y-4">
        <div class="flex justify-end">
            <a href="{{ route('orthoses.create', ['patient' => $patient->id]) }}" class="btn-primary">+ Nuova ortesi</a>
        </div>
        @forelse ($patient->orthoses as $o)
            <div class="card p-5">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <div class="font-semibold text-slate-800">{{ $o->type }}</div>
                        <div class="text-sm text-slate-500">
                            {{ optional($o->prescribed_at)->format('d/m/Y') }}
                            @if ($o->foot) · {{ $o->foot }}@endif
                            @if ($o->material) · {{ $o->material }}@endif
                        </div>
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        <span class="badge" style="background: {{ $o->status->color() }}20; color: {{ $o->status->color() }}">{{ $o->status->label() }}</span>
                        <a href="{{ route('orthoses.edit', $o) }}" class="text-brand-600 hover:underline">Apri</a>
                    </div>
                </div>
                @if ($o->specifications)<p class="mt-2 text-sm text-slate-600">{{ $o->specifications }}</p>@endif
            </div>
        @empty
            <div class="card px-6 py-10 text-center text-sm text-slate-400">Nessuna ortesi registrata.</div>
        @endforelse
    </div>
</div>
@endsection
