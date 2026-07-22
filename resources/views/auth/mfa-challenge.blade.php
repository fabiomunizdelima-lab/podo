@extends('layouts.guest')
@section('title', 'Verifica MFA')

@section('content')
<h2 class="mb-2 text-base font-semibold text-slate-800">Verifica in due passaggi</h2>
<p class="mb-4 text-sm text-slate-500">Inserisci il codice a 6 cifre della tua app di autenticazione.</p>

<form method="POST" action="{{ route('mfa.verify') }}" class="space-y-4">
    @csrf
    <div>
        <label class="label" for="code">Codice</label>
        <input class="input text-center tracking-widest" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" required autofocus>
    </div>
    <button type="submit" class="btn-primary w-full">Verifica</button>
</form>

<p class="mt-4 text-center text-xs text-slate-400">
    Puoi usare anche uno dei codici di recupero.
</p>
@endsection
