@extends('layouts.app')
@section('title', 'Anamnesi · '.$patient->full_name)

@php
    $riskFlags = [
        'diabetes' => 'Diabete', 'on_anticoagulants' => 'Terapia anticoagulante', 'smoker' => 'Fumatore',
        'hypertension' => 'Ipertensione', 'circulatory_disorders' => 'Disturbi circolatori',
        'neuropathy' => 'Neuropatia', 'immunosuppressed' => 'Immunodepressione',
        'pacemaker' => 'Pacemaker', 'latex_allergy' => 'Allergia al lattice',
    ];
    $footTypes = ['normale' => 'Normale', 'piatto' => 'Piatto', 'cavo' => 'Cavo'];
    $textFields = [
        'medical_history' => 'Anamnesi patologica (patologie in atto/pregresse)',
        'surgeries' => 'Interventi chirurgici',
        'medications' => 'Farmaci in uso',
        'allergies' => 'Allergie / intolleranze',
        'podiatric_notes' => 'Note podologiche',
    ];
@endphp

@section('content')
<div class="mx-auto max-w-2xl">
    <div class="mb-4">
        <a href="{{ route('patients.show', $patient) }}" class="text-sm text-slate-500 hover:underline">&larr; {{ $patient->full_name }}</a>
        <h1 class="mt-1 text-lg font-semibold text-slate-800">Anamnesi</h1>
        <p class="text-xs text-slate-400">Dati sanitari cifrati a riposo (art. 9 GDPR).</p>
    </div>

    <form method="POST" action="{{ route('patients.record.update', $patient) }}" class="card p-5">
        @csrf @method('PUT')

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="profession">Professione</label>
                <input class="input" id="profession" name="profession" value="{{ old('profession', $record->profession) }}">
            </div>
            <div>
                <label class="label" for="sport_activity">Attività sportiva</label>
                <input class="input" id="sport_activity" name="sport_activity" value="{{ old('sport_activity', $record->sport_activity) }}">
            </div>
            <div>
                <label class="label" for="foot_type_left">Morfologia piede sinistro</label>
                <select class="input" id="foot_type_left" name="foot_type_left">
                    <option value="">—</option>
                    @foreach ($footTypes as $val => $lbl)<option value="{{ $val }}" @selected(old('foot_type_left', $record->foot_type_left) === $val)>{{ $lbl }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="label" for="foot_type_right">Morfologia piede destro</label>
                <select class="input" id="foot_type_right" name="foot_type_right">
                    <option value="">—</option>
                    @foreach ($footTypes as $val => $lbl)<option value="{{ $val }}" @selected(old('foot_type_right', $record->foot_type_right) === $val)>{{ $lbl }}</option>@endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="label" for="footwear_notes">Calzature abituali</label>
                <input class="input" id="footwear_notes" name="footwear_notes" value="{{ old('footwear_notes', $record->footwear_notes) }}">
            </div>
        </div>

        <fieldset class="mt-5 rounded-lg bg-slate-50 p-4">
            <legend class="px-1 text-sm font-medium text-slate-700">Fattori di rischio</legend>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                @foreach ($riskFlags as $field => $label)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $record->$field)) class="rounded border-slate-300 text-brand-600">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            <div class="mt-3 sm:max-w-xs">
                <label class="label" for="diabetes_type">Tipo diabete (se presente)</label>
                <input class="input" id="diabetes_type" name="diabetes_type" placeholder="es. tipo 2" value="{{ old('diabetes_type', $record->diabetes_type) }}">
            </div>
        </fieldset>

        @foreach ($textFields as $field => $label)
            <div class="mt-4">
                <label class="label" for="{{ $field }}">{{ $label }}</label>
                <textarea class="input" id="{{ $field }}" name="{{ $field }}" rows="2">{{ old($field, $record->$field) }}</textarea>
            </div>
        @endforeach

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('patients.show', $patient) }}" class="btn-secondary">Annulla</a>
            <button type="submit" class="btn-primary">Salva anamnesi</button>
        </div>
    </form>
</div>
@endsection
