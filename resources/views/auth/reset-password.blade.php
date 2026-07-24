@extends('layouts.guest')
@section('title', 'Nuova password')

@section('content')
<h1 class="mb-2 text-base font-semibold text-slate-800">Imposta una nuova password</h1>
<p class="mb-4 text-sm text-slate-500">
    Almeno {{ config('podo.security.password_min_length', 12) }} caratteri, con maiuscole, minuscole, numeri e simboli.
</p>

<form method="POST" action="{{ route('password.update') }}" class="space-y-4">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div>
        <label class="label" for="email">Email</label>
        <input class="input" id="email" name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="username">
    </div>
    <div>
        <label class="label" for="password">Nuova password</label>
        <input class="input" id="password" name="password" type="password" required autofocus autocomplete="new-password">
    </div>
    <div>
        <label class="label" for="password_confirmation">Conferma password</label>
        <input class="input" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
    </div>
    <button type="submit" class="btn-primary w-full">Salva la nuova password</button>
</form>

<p class="mt-4 text-center text-sm">
    <a href="{{ route('login') }}" class="text-brand-600 underline">Torna all'accesso</a>
</p>
@endsection
