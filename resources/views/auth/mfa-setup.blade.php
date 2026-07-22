@extends('layouts.guest')
@section('title', 'Attiva MFA')

@section('content')
<h2 class="mb-2 text-base font-semibold text-slate-800">Attiva l'autenticazione a due fattori</h2>
<p class="mb-4 text-sm text-slate-500">
    Inquadra il QR code con Google Authenticator, Authy o simili, poi inserisci il codice a 6 cifre.
</p>

<div class="mb-4 flex justify-center rounded-lg bg-slate-50 p-4">
    {!! $qrSvg !!}
</div>

<p class="mb-4 break-all text-center text-xs text-slate-400">
    Chiave manuale: <span class="font-mono">{{ $secret }}</span>
</p>

<form method="POST" action="{{ route('mfa.confirm') }}" class="space-y-4">
    @csrf
    <div>
        <label class="label" for="code">Codice di verifica</label>
        <input class="input text-center tracking-widest" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" required autofocus>
    </div>
    <button type="submit" class="btn-primary w-full">Attiva MFA</button>
</form>
@endsection
