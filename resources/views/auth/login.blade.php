@extends('layouts.guest')
@section('title', 'Accesso')

@section('content')
<form method="POST" action="{{ route('login') }}" class="space-y-4">
    @csrf
    <div>
        <label class="label" for="email">Email</label>
        <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username">
    </div>
    <div>
        <label class="label" for="password">Password</label>
        <input class="input" id="password" name="password" type="password" required autocomplete="current-password">
    </div>
    <div class="flex items-center justify-between">
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
            Ricordami
        </label>
    </div>
    <button type="submit" class="btn-primary w-full">Accedi</button>
</form>
@endsection
