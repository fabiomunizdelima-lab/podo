@extends('layouts.app')
@section('title', 'Ortesi')

@php $isAdmin = auth()->user()->atLeast(\App\Enums\Role::ADMIN); @endphp

@section('content')
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <form method="GET">
        <select name="status" class="input" onchange="this.form.submit()">
            <option value="">Tutti gli stati</option>
            @foreach (\App\Enums\OrthosisStatus::cases() as $s)
                <option value="{{ $s->value }}" @selected($status === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
    </form>
    <a href="{{ route('orthoses.create') }}" class="btn-primary">+ Nuova ortesi</a>
</div>

<div class="card overflow-hidden">
    <table class="hidden min-w-full divide-y divide-slate-200 sm:table">
        <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-5 py-3">Tipo</th>
                <th class="px-5 py-3">Paziente</th>
                <th class="px-5 py-3">Prescritto</th>
                <th class="px-5 py-3">Stato</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
            @foreach ($orthoses as $o)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 font-medium text-slate-800">{{ $o->type }}<div class="text-xs text-slate-400">{{ $o->foot }}</div></td>
                    <td class="px-5 py-3 text-slate-700">{{ $o->patient?->full_name }}</td>
                    <td class="px-5 py-3 text-slate-500">{{ optional($o->prescribed_at)->format('d/m/Y') ?: '—' }}</td>
                    <td class="px-5 py-3">
                        <form method="POST" action="{{ route('orthoses.status', $o) }}">
                            @csrf
                            <select name="status" class="rounded-lg border-slate-200 py-1 text-xs" style="color: {{ $o->status->color() }}" onchange="this.form.submit()">
                                @foreach (\App\Enums\OrthosisStatus::cases() as $s)
                                    <option value="{{ $s->value }}" @selected($o->status === $s)>{{ $s->label() }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('orthoses.edit', $o) }}" class="text-brand-600 hover:underline">Apri</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="divide-y divide-slate-100 sm:hidden">
        @foreach ($orthoses as $o)
            <a href="{{ route('orthoses.edit', $o) }}" class="block px-4 py-3">
                <div class="flex justify-between">
                    <span class="font-medium text-slate-800">{{ $o->type }}</span>
                    <span class="badge" style="background: {{ $o->status->color() }}20; color: {{ $o->status->color() }}">{{ $o->status->label() }}</span>
                </div>
                <div class="text-sm text-slate-500">{{ $o->patient?->full_name }}</div>
            </a>
        @endforeach
    </div>
    @if ($orthoses->isEmpty())
        <div class="px-5 py-10 text-center text-sm text-slate-400">Nessuna ortesi.</div>
    @endif
</div>

<div class="mt-4">{{ $orthoses->links() }}</div>
@endsection
