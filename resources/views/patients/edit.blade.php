@extends('layouts.app')
@section('title', 'Modifica paziente')

@section('content')
<div class="mx-auto max-w-3xl">
    <form method="POST" action="{{ route('patients.update', $patient) }}" class="card p-6">
        @method('PUT')
        @include('patients._form')
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('patients.show', $patient) }}" class="btn-secondary">Annulla</a>
            <button class="btn-primary">Salva modifiche</button>
        </div>
    </form>
</div>
@endsection
