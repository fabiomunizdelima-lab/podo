@php
    $nav = [
        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        ['route' => 'appointments.index', 'label' => 'Agenda', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
        ['route' => 'patients.index', 'label' => 'Pazienti', 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z'],
    ];
@endphp
<div class="flex h-full flex-col">
    <div class="flex items-center gap-2 px-2 py-3 text-white">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-500 font-bold">P</div>
        <span class="text-lg font-semibold">Podo</span>
    </div>
    <nav class="mt-4 flex-1 space-y-1">
        @foreach ($nav as $item)
            @php $active = request()->routeIs($item['route']) || request()->routeIs(str_replace('.index','.*',$item['route'])); @endphp
            <a href="{{ route($item['route']) }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ $active ? 'bg-brand-700 text-white' : 'text-brand-100 hover:bg-brand-800' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                {{ $item['label'] }}
            </a>
        @endforeach

        @if (auth()->user()->isSuperAdmin())
            <a href="{{ route('users.index') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('users.*') ? 'bg-brand-700 text-white' : 'text-brand-100 hover:bg-brand-800' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                Utenti
            </a>
        @endif
    </nav>

    <div class="border-t border-brand-800 pt-3">
        <a href="{{ route('google.redirect') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-brand-100 hover:bg-brand-800">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Collega Google Calendar
        </a>
    </div>
</div>
