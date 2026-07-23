@extends("layouts.app")
@section("title", "Listino prestazioni")

@php $canManage = auth()->user()->atLeast(\App\Enums\Role::ADMIN); @endphp

@section("content")
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <form method="GET" class="flex flex-1 flex-col gap-2 sm:flex-row sm:max-w-xl">
        <input type="search" name="q" value="{{ $q }}" placeholder="Cerca prestazione o codice…" class="input">
        <select name="category" class="input sm:max-w-xs" onchange="this.form.submit()">
            <option value="">Tutte le categorie</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat }}" @selected($category === $cat)>{{ $cat }}</option>
            @endforeach
        </select>
    </form>
    @if ($canManage)
        <a href="{{ route("treatments.create") }}" class="btn-primary whitespace-nowrap">+ Nuova prestazione</a>
    @endif
</div>

<div class="card overflow-hidden">
    <table class="hidden min-w-full divide-y divide-slate-200 sm:table">
        <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-5 py-3">Prestazione</th>
                <th class="px-5 py-3">Categoria</th>
                <th class="px-5 py-3 text-right">Prezzo</th>
                <th class="px-5 py-3">Durata</th>
                <th class="px-5 py-3">IVA</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
            @foreach ($treatments as $t)
                <tr class="hover:bg-slate-50 {{ $t->is_active ? "" : "opacity-50" }}">
                    <td class="px-5 py-3">
                        <div class="font-medium text-slate-800">{{ $t->name }}</div>
                        @if ($t->code)<div class="font-mono text-xs text-slate-400">{{ $t->code }}</div>@endif
                    </td>
                    <td class="px-5 py-3 text-slate-600">{{ $t->category }}</td>
                    <td class="px-5 py-3 text-right font-medium text-slate-800">€ {{ $t->price_label }}</td>
                    <td class="px-5 py-3 text-slate-500">{{ $t->duration_minutes ? $t->duration_minutes." min" : "—" }}</td>
                    <td class="px-5 py-3">
                        @if ($t->vat_exempt)
                            <span class="badge bg-slate-100 text-slate-600" title="Esente art.10 – natura {{ $t->vat_nature }}">Esente</span>
                        @else
                            <span class="badge bg-amber-100 text-amber-700">{{ rtrim(rtrim($t->vat_rate, "0"), ".") }}%</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right">
                        @if ($canManage)
                            <a href="{{ route("treatments.edit", $t) }}" class="text-brand-600 hover:underline">Modifica</a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divide-y divide-slate-100 sm:hidden">
        @foreach ($treatments as $t)
            <div class="px-4 py-3 {{ $t->is_active ? "" : "opacity-50" }}">
                <div class="flex items-center justify-between">
                    <div class="font-medium text-slate-800">{{ $t->name }}</div>
                    <div class="font-medium text-slate-800">€ {{ $t->price_label }}</div>
                </div>
                <div class="text-sm text-slate-500">
                    {{ $t->category }}@if ($t->duration_minutes) · {{ $t->duration_minutes }} min @endif
                    @if ($canManage) · <a href="{{ route("treatments.edit", $t) }}" class="text-brand-600">Modifica</a>@endif
                </div>
            </div>
        @endforeach
    </div>

    @if ($treatments->isEmpty())
        <div class="px-5 py-10 text-center text-sm text-slate-400">Nessuna prestazione nel listino.</div>
    @endif
</div>

<div class="mt-4">{{ $treatments->links() }}</div>
@endsection
