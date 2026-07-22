<!DOCTYPE html>
<html lang="it" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Accesso') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="mb-6 flex flex-col items-center gap-2">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-600 text-xl font-bold text-white">P</div>
            <h1 class="text-xl font-semibold text-slate-800">{{ config('app.name') }}</h1>
            <p class="text-sm text-slate-500">Gestionale per podologi</p>
        </div>

        <div class="card p-6">
            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800 ring-1 ring-red-200">
                    {{ $errors->first() }}
                </div>
            @endif
            @yield('content')
        </div>

        <p class="mt-6 text-center text-xs text-slate-400">
            Connessione cifrata · Accesso protetto MFA
        </p>
    </div>
</body>
</html>
