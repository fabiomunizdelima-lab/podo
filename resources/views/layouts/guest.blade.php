<!DOCTYPE html>
<html lang="it" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <title>@yield('title', 'Accesso') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full items-center justify-center bg-gradient-to-br from-slate-100 via-white to-brand-50 p-4">
    <div class="w-full max-w-sm">
        <div class="mb-6 flex flex-col items-center">
            <img src="{{ asset('img/logo-full.png') }}" alt="Podo — Gestionale per podologi" class="h-20 w-auto">
        </div>

        <div class="card p-6 shadow-lg shadow-slate-900/5">
            @if (session('status'))
                <div class="mb-4 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-800 ring-1 ring-green-200">
                    {{ session('status') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800 ring-1 ring-red-200">
                    {{ $errors->first() }}
                </div>
            @endif
            @yield('content')
        </div>

        <p class="mt-6 text-center text-xs text-slate-400">
            Connessione cifrata · Accesso protetto
        </p>
    </div>
</body>
</html>
