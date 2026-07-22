@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div class="card p-5">
        <p class="text-sm text-slate-500">Pazienti attivi</p>
        <p class="mt-1 text-3xl font-semibold text-slate-800">{{ $stats['patients'] }}</p>
    </div>
    <div class="card p-5">
        <p class="text-sm text-slate-500">Appuntamenti oggi</p>
        <p class="mt-1 text-3xl font-semibold text-brand-600">{{ $stats['today'] }}</p>
    </div>
    <div class="card p-5">
        <p class="text-sm text-slate-500">Questa settimana</p>
        <p class="mt-1 text-3xl font-semibold text-slate-800">{{ $stats['week'] }}</p>
    </div>
</div>

<div class="card mt-6">
    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
        <h2 class="font-semibold text-slate-800">Appuntamenti di oggi</h2>
        <a href="{{ route('appointments.index') }}" class="text-sm text-brand-600 hover:underline">Vai all'agenda →</a>
    </div>
    <div class="divide-y divide-slate-100">
        @forelse ($todayAppointments as $a)
            <div class="flex flex-wrap items-center gap-3 px-5 py-3 text-sm">
                <span class="font-mono text-slate-500">{{ $a->starts_at->format('H:i') }}</span>
                <span class="font-medium text-slate-800">{{ $a->patient?->full_name }}</span>
                <span class="text-slate-500">{{ $a->treatment }}</span>
                <span class="badge ml-auto" style="background: {{ $a->status->color() }}20; color: {{ $a->status->color() }}">{{ $a->status->label() }}</span>
                @if ($a->patient?->consent_whatsapp)
                    <form method="POST" action="{{ route('appointments.reminder', $a) }}">
                        @csrf
                        <button class="text-xs text-green-600 hover:underline" {{ $a->reminder_sent_at ? 'disabled' : '' }}>
                            {{ $a->reminder_sent_at ? 'Promemoria inviato' : 'Invia WhatsApp' }}
                        </button>
                    </form>
                @endif
            </div>
        @empty
            <div class="px-5 py-8 text-center text-sm text-slate-400">Nessun appuntamento per oggi.</div>
        @endforelse
    </div>
</div>
@endsection
