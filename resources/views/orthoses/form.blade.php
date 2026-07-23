@extends('layouts.app')
@section('title', $orthosis->exists ? 'Modifica ortesi' : 'Nuova ortesi')

@php
    $isEdit = $orthosis->exists;
    $action = $isEdit ? route('orthoses.update', $orthosis) : route('orthoses.store');
@endphp

@section('content')
<div class="mx-auto max-w-2xl">
    <div class="mb-4">
        <a href="{{ route('orthoses.index') }}" class="text-sm text-slate-500 hover:underline">&larr; Ortesi</a>
        <h1 class="mt-1 text-lg font-semibold text-slate-800">{{ $isEdit ? 'Modifica ortesi' : 'Nuova ortesi' }}</h1>
    </div>

    <form method="POST" action="{{ $action }}" class="card p-5">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="label" for="patient_id">Paziente *</label>
                @if ($isEdit || $patient)
                    <input class="input bg-slate-50" value="{{ ($orthosis->patient ?? $patient)->full_name }}" disabled>
                    <input type="hidden" name="patient_id" value="{{ $orthosis->patient_id ?: $patient->id }}">
                @else
                    <select class="input" id="patient_id" name="patient_id" required>
                        <option value="">— seleziona —</option>
                        @foreach ($patients as $p)
                            <option value="{{ $p->id }}" @selected(old('patient_id') == $p->id)>{{ $p->last_name }} {{ $p->first_name }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
            <div>
                <label class="label" for="type">Tipo ortesi *</label>
                <input class="input" id="type" name="type" list="orth-types" value="{{ old('type', $orthosis->type) }}" required>
                <datalist id="orth-types">
                    @foreach (['Plantare su misura', 'Ortesi digitale in silicone', 'Ortesi ungueale', 'Plantare sportivo', 'Plantare diabetico'] as $t)
                        <option value="{{ $t }}"></option>
                    @endforeach
                </datalist>
            </div>
            <div>
                <label class="label" for="foot">Piede</label>
                <select class="input" id="foot" name="foot">
                    <option value="">—</option>
                    <option value="L" @selected(old('foot', $orthosis->foot) === 'L')>Sinistro</option>
                    <option value="R" @selected(old('foot', $orthosis->foot) === 'R')>Destro</option>
                    <option value="both" @selected(old('foot', $orthosis->foot) === 'both')>Entrambi</option>
                </select>
            </div>
            <div>
                <label class="label" for="material">Materiale</label>
                <input class="input" id="material" name="material" value="{{ old('material', $orthosis->material) }}">
            </div>
            <div>
                <label class="label" for="price">Prezzo (€)</label>
                <input class="input" id="price" name="price" type="number" step="0.01" min="0" value="{{ old('price', $orthosis->price) }}">
            </div>
            <div>
                <label class="label" for="status">Stato</label>
                <select class="input" id="status" name="status">
                    @foreach (\App\Enums\OrthosisStatus::cases() as $s)
                        <option value="{{ $s->value }}" @selected(old('status', $orthosis->status?->value ?? 'prescribed') === $s->value)>{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="prescribed_at">Data prescrizione</label>
                <input class="input" id="prescribed_at" name="prescribed_at" type="date" value="{{ old('prescribed_at', optional($orthosis->prescribed_at)->format('Y-m-d')) }}">
            </div>
            <div>
                <label class="label" for="delivered_at">Data consegna</label>
                <input class="input" id="delivered_at" name="delivered_at" type="date" value="{{ old('delivered_at', optional($orthosis->delivered_at)->format('Y-m-d')) }}">
            </div>
        </div>

        <div class="mt-4">
            <label class="label" for="specifications">Misure / specifiche tecniche</label>
            <textarea class="input" id="specifications" name="specifications" rows="3">{{ old('specifications', $orthosis->specifications) }}</textarea>
        </div>
        <div class="mt-4">
            <label class="label" for="notes">Note</label>
            <textarea class="input" id="notes" name="notes" rows="2">{{ old('notes', $orthosis->notes) }}</textarea>
        </div>

        <div class="mt-6 flex items-center justify-between">
            @if ($isEdit && auth()->user()->atLeast(\App\Enums\Role::ADMIN))
                <button type="submit" form="del-orth" class="text-sm text-red-600 hover:underline" onclick="return confirm('Archiviare questa ortesi?')">Archivia</button>
            @else
                <span></span>
            @endif
            <div class="flex gap-3">
                <a href="{{ route('orthoses.index') }}" class="btn-secondary">Annulla</a>
                <button type="submit" class="btn-primary">{{ $isEdit ? 'Salva' : 'Registra' }}</button>
            </div>
        </div>
    </form>

    @if ($isEdit)
        <form id="del-orth" method="POST" action="{{ route('orthoses.destroy', $orthosis) }}" class="hidden">@csrf @method('DELETE')</form>
    @endif
</div>
@endsection
