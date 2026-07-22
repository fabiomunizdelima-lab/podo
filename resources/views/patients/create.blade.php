@extends('layouts.app')
@section('title', 'Nuovo paziente')

@section('content')
<div class="mx-auto max-w-3xl">
    <form method="POST" action="{{ route('patients.store') }}" class="card p-6">
        @include('patients._form')
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('patients.index') }}" class="btn-secondary">Annulla</a>
            <button class="btn-primary">Salva paziente</button>
        </div>
    </form>
</div>
@endsection
