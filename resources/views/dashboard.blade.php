@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500">Pazienti attivi</p>
                <p class="mt-1 text-3xl font-semibold tracking-tight text-slate-800">{{ number_format($stats['patients'], 0, ',', '.') }}</p>
            </div>
            <div class="stat-icon bg-brand-50 text-brand-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z"/></svg>
            </div>
        </div>
        <span class="absolute inset-x-0 bottom-0 h-1 bg-brand-500/70"></span>
    </div>
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500">Appuntamenti oggi</p>
                <p class="mt-1 text-3xl font-semibold tracking-tight text-slate-800">{{ $stats['today'] }}</p>
            </div>
            <div class="stat-icon bg-emerald-50 text-emerald-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
        </div>
        <span class="absolute inset-x-0 bottom-0 h-1 bg-emerald-500/70"></span>
    </div>
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500">Questa settimana</p>
                <p class="mt-1 text-3xl font-semibold tracking-tight text-slate-800">{{ $stats['week'] }}</p>
            </div>
            <div class="stat-icon bg-violet-50 text-violet-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h10a2 2 0 012 2v14a2 2 0 01-2 2z"/></svg>
            </div>
        </div>
        <span class="absolute inset-x-0 bottom-0 h-1 bg-violet-500/70"></span>
    </div>
</div>

<div class="card mt-6">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <h2 class="font-semibold text-slate-800">Appuntamenti di oggi</h2>
        <a href="{{ route('appointments.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700 hover:underline">Vai all'agenda →</a>
    </div>
    <div class="divide-y divide-slate-100">
        @forelse ($todayAppointments as $a)
            <div class="flex flex-wrap items-center gap-3 px-5 py-3 text-sm">
                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 font-mono text-xs font-medium text-slate-600">{{ $a->starts_at->format('H:i') }}</span>
                <span class="font-medium text-slate-800">{{ $a->patient?->full_name }}</span>
                <span class="text-slate-500">{{ $a->treatment }}</span>
                <span class="badge ml-auto" style="background: {{ $a->status->color() }}20; color: {{ $a->status->color() }}">{{ $a->status->label() }}</span>
                @if ($a->patient?->consent_whatsapp)
                    <form method="POST" action="{{ route('appointments.reminder', $a) }}">
                        @csrf
                        <button class="text-xs font-medium text-emerald-600 hover:underline disabled:text-slate-400 disabled:no-underline" {{ $a->reminder_sent_at ? 'disabled' : '' }}>
                            {{ $a->reminder_sent_at ? 'Promemoria inviato' : 'Invia WhatsApp' }}
                        </button>
                    </form>
                @endif
            </div>
        @empty
            <div class="px-5 py-12 text-center">
                <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <p class="mt-2 text-sm text-slate-400">Nessun appuntamento per oggi.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
