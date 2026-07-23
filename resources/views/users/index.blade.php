@extends('layouts.app')
@section('title', 'Utenti')

@section('content')
<div class="mb-4 flex items-center justify-between">
    <h2 class="font-semibold text-slate-800">Gestione utenti</h2>
    <a href="{{ route('users.create') }}" class="btn-primary">+ Nuovo utente</a>
</div>

<div class="card overflow-hidden">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs font-medium uppercase text-slate-500">
            <tr>
                <th class="px-5 py-3">Nome</th>
                <th class="px-5 py-3">Email</th>
                <th class="px-5 py-3">Ruolo</th>
                <th class="px-5 py-3">Paziente</th>
                <th class="px-5 py-3">Stato</th>
                <th class="px-5 py-3">MFA</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($users as $u)
                <tr>
                    <td class="px-5 py-3 font-medium text-slate-800">{{ $u->name }}</td>
                    <td class="px-5 py-3 text-slate-600">{{ $u->email }}</td>
                    <td class="px-5 py-3"><span class="badge bg-brand-100 text-brand-800">{{ $u->role->label() }}</span></td>
                    <td class="px-5 py-3 text-slate-600">{{ $u->patient?->full_name ?: '—' }}</td>
                    <td class="px-5 py-3">
                        @if ($u->is_active)<span class="badge bg-green-100 text-green-700">Attivo</span>
                        @else<span class="badge bg-slate-100 text-slate-500">Disattivo</span>@endif
                    </td>
                    <td class="px-5 py-3">{{ $u->hasMfaEnabled() ? '✓' : '—' }}</td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('users.edit', $u) }}" class="text-brand-600 hover:underline">Modifica</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $users->links() }}</div>
@endsection
