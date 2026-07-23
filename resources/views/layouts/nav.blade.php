@php
    $user = auth()->user();
    $nav = [
        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        ['route' => 'appointments.index', 'label' => 'Agenda', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
        ['route' => 'patients.index', 'label' => 'Pazienti', 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z'],
        ['route' => 'treatments.index', 'label' => 'Listino', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
        ['route' => 'orthoses.index', 'label' => 'Ortesi', 'icon' => 'M7.864 4.243A7.5 7.5 0 0119.5 10.5c0 2.92-.556 5.709-1.568 8.268M5.742 6.364A7.465 7.465 0 004.5 10.5a7.464 7.464 0 01-1.15 3.993m1.989 3.559A11.209 11.209 0 008.25 10.5a3.75 3.75 0 117.5 0c0 .527-.021 1.049-.064 1.565M12 10.5a14.94 14.94 0 01-3.6 9.75m6.633-4.596a18.666 18.666 0 01-2.485 5.33'],
    ];
@endphp
<div class="flex h-full flex-col">
    <div class="flex items-center gap-2 px-2 py-3 text-white">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-500 font-bold">P</div>
        <span class="text-lg font-semibold">Podo</span>
    </div>

    @if ($user->isPatient())
        {{-- Portale paziente: solo la propria cartella --}}
        <nav class="mt-4 flex-1 space-y-1">
            <a href="{{ route('portal.record') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('portal.*') ? 'bg-brand-700 text-white' : 'text-brand-100 hover:bg-brand-800' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                La mia cartella
            </a>
        </nav>
    @else
        <nav class="mt-4 flex-1 space-y-1">
            @foreach ($nav as $item)
                @php $active = request()->routeIs($item['route']) || request()->routeIs(str_replace('.index','.*',$item['route'])); @endphp
                <a href="{{ route($item['route']) }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ $active ? 'bg-brand-700 text-white' : 'text-brand-100 hover:bg-brand-800' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                    {{ $item['label'] }}
                </a>
            @endforeach

            <a href="{{ route('invoices.index') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('invoices.*') ? 'bg-brand-700 text-white' : 'text-brand-100 hover:bg-brand-800' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                Fatture
            </a>

            <div class="mt-3 border-t border-brand-800 pt-3">
                <div class="px-3 pb-1 text-xs uppercase tracking-wide text-brand-300">Amministrazione</div>
                <a href="{{ route('users.index') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('users.*') ? 'bg-brand-700 text-white' : 'text-brand-100 hover:bg-brand-800' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    Utenti
                </a>
                <a href="{{ route('audit.index') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('audit.*') ? 'bg-brand-700 text-white' : 'text-brand-100 hover:bg-brand-800' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    Audit
                </a>
                @if ($user->isSuperAdmin())
                    <a href="{{ route('settings.edit') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('settings.*') ? 'bg-brand-700 text-white' : 'text-brand-100 hover:bg-brand-800' }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.5a1 1 0 011.9 0l.4 1.3a1 1 0 001.3.65l1.3-.4a1 1 0 011.2 1.2l-.4 1.3a1 1 0 00.65 1.3l1.3.4a1 1 0 010 1.9l-1.3.4a1 1 0 00-.65 1.3l.4 1.3a1 1 0 01-1.2 1.2l-1.3-.4a1 1 0 00-1.3.65l-.4 1.3a1 1 0 01-1.9 0l-.4-1.3a1 1 0 00-1.3-.65l-1.3.4a1 1 0 01-1.2-1.2l.4-1.3a1 1 0 00-.65-1.3l-1.3-.4a1 1 0 010-1.9l1.3-.4a1 1 0 00.65-1.3l-.4-1.3a1 1 0 011.2-1.2l1.3.4a1 1 0 001.3-.65l.4-1.3z"/><circle cx="12" cy="12" r="2.5"/></svg>
                        Impostazioni
                    </a>
                @endif
            </div>
        </nav>

        <div class="border-t border-brand-800 pt-3">
            <a href="{{ route('google.redirect') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-brand-100 hover:bg-brand-800">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Collega Google Calendar
            </a>
        </div>
    @endif
</div>
