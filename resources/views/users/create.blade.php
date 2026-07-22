@extends('layouts.app')
@section('title', 'Nuovo utente')

@section('content')
<div class="mx-auto max-w-xl">
    <form method="POST" action="{{ route('users.store') }}" class="card p-6">
        @include('users._form')
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('users.index') }}" class="btn-secondary">Annulla</a>
            <button class="btn-primary">Crea utente</button>
        </div>
    </form>
</div>
@endsection
