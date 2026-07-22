@extends('layouts.app')
@section('title', 'Modifica utente')

@section('content')
<div class="mx-auto max-w-xl">
    <form method="POST" action="{{ route('users.update', $user) }}" class="card p-6">
        @method('PUT')
        @include('users._form')
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('users.index') }}" class="btn-secondary">Annulla</a>
            <button class="btn-primary">Salva</button>
        </div>
    </form>

    <form method="POST" action="{{ route('users.destroy', $user) }}" class="mt-4 text-right"
          onsubmit="return confirm('Disattivare questo utente?')">
        @csrf @method('DELETE')
        <button class="text-sm text-red-600 hover:underline">Disattiva account</button>
    </form>
</div>
@endsection
