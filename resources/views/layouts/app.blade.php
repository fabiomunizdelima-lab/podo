<!DOCTYPE html>
<html lang="it" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Podo') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full text-slate-800" x-data="{ sidebar: false }">
<div class="min-h-full">

    {{-- Sidebar mobile overlay --}}
    <div x-show="sidebar" x-cloak class="relative z-40 lg:hidden" @keydown.escape.window="sidebar=false">
        <div class="fixed inset-0 bg-slate-900/50" @click="sidebar=false"></div>
        <div class="fixed inset-y-0 left-0 w-64 bg-brand-900 p-4" x-transition:enter="transition ease-in-out duration-200" x-transition:enter-start="-translate-x-full">
            @include('layouts.nav')
        </div>
    </div>

    {{-- Sidebar desktop --}}
    <aside class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col bg-brand-900 p-4">
        @include('layouts.nav')
    </aside>

    <div class="lg:pl-64">
        {{-- Topbar --}}
        <header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-slate-200 bg-white px-4 sm:px-6">
            <button class="lg:hidden text-slate-500" @click="sidebar=true" aria-label="Menu">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="flex-1 text-sm font-semibold text-slate-700">@yield('title', 'Dashboard')</div>
            <div class="flex items-center gap-3 text-sm">
                <span class="hidden sm:block text-slate-500">{{ auth()->user()->name }}</span>
                <span class="badge bg-brand-100 text-brand-800">{{ auth()->user()->role->label() }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="text-slate-400 hover:text-red-600" title="Esci">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7M13 16v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </header>

        <main class="p-4 sm:p-6">
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
