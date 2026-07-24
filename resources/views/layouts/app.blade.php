<!DOCTYPE html>
<html lang="it" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <title>@yield('title', 'Podo') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full text-slate-800" x-data="{ sidebar: false }">
<div class="min-h-full">

    {{-- Sidebar mobile overlay --}}
    <div x-show="sidebar" x-cloak class="relative z-40 lg:hidden" @keydown.escape.window="sidebar=false">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" x-transition:enter="transition-opacity ease-linear duration-200" x-transition:enter-start="opacity-0" x-transition:leave="transition-opacity ease-linear duration-150" x-transition:leave-end="opacity-0" @click="sidebar=false"></div>
        <div class="fixed inset-y-0 left-0 flex w-64 flex-col bg-gradient-to-b from-brand-900 to-brand-950 p-4" x-transition:enter="transition ease-in-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:leave="transition ease-in-out duration-150" x-transition:leave-end="-translate-x-full">
            @include('layouts.nav')
        </div>
    </div>

    {{-- Sidebar desktop --}}
    <aside class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col bg-gradient-to-b from-brand-900 to-brand-950 p-4">
        @include('layouts.nav')
    </aside>

    <div class="lg:pl-64">
        {{-- Topbar --}}
        <header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-slate-200/80 bg-white/80 px-4 backdrop-blur-md supports-[backdrop-filter]:bg-white/70 sm:px-6">
            <button class="-ml-1 rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 lg:hidden" @click="sidebar=true" aria-label="Menu">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="flex-1 truncate text-base font-semibold text-slate-800">@yield('title', 'Dashboard')</div>
            <div class="flex items-center gap-3">
                <div class="hidden text-right sm:block">
                    <div class="text-sm font-medium text-slate-700">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-slate-400">{{ auth()->user()->role->label() }}</div>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700 ring-1 ring-inset ring-brand-200">
                    {{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->take(2)->map(fn ($w) => \Illuminate\Support\Str::substr($w, 0, 1))->implode('') }}
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded-lg p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-600" title="Esci">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7M13 16v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </header>

        <main class="mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
            @php $flash = ['success' => 'bg-green-50 text-green-800 ring-green-200', 'error' => 'bg-red-50 text-red-800 ring-red-200', 'warning' => 'bg-amber-50 text-amber-800 ring-amber-200']; @endphp
            @foreach ($flash as $type => $classes)
                @if (session($type))
                    <div class="mb-4 rounded-lg px-4 py-3 text-sm ring-1 {{ $classes }}">
                        {{ session($type) }}
                    </div>
                @endif
            @endforeach
            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
