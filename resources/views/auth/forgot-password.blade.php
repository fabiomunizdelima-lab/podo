@extends('layouts.guest')
@section('title', 'Password dimenticata')

@section('content')
<h1 class="mb-2 text-base font-semibold text-slate-800">Password dimenticata</h1>
<p class="mb-4 text-sm text-slate-500">
    Inserisci l'indirizzo email del tuo account: ti invieremo un collegamento per impostare una nuova password.
</p>

@unless ($mailAttiva)
    <div class="mb-4 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800 ring-1 ring-amber-200">
        L'invio delle email non è ancora configurato su questo sistema.
    </div>
@endunless

<form method="POST" action="{{ route('password.email') }}" class="space-y-4">
    @csrf
    <div>
        <label class="label" for="email">Email</label>
        <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username">
    </div>
    <button type="submit" class="btn-primary w-full">Invia il collegamento</button>
</form>

<p class="mt-4 text-center text-sm">
    <a href="{{ route('login') }}" class="text-brand-600 underline">Torna all'accesso</a>
</p>
@endsection
