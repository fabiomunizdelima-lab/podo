@extends("layouts.app")
@section("title", "Nuova prestazione")

@section("content")
<div class="mx-auto max-w-2xl">
    <div class="mb-4">
        <a href="{{ route("treatments.index") }}" class="text-sm text-slate-500 hover:underline">&larr; Listino</a>
        <h1 class="mt-1 text-lg font-semibold text-slate-800">Nuova prestazione</h1>
    </div>
    <form method="POST" action="{{ route("treatments.store") }}" class="card p-5">
        @include("treatments._form")
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route("treatments.index") }}" class="btn-secondary">Annulla</a>
            <button type="submit" class="btn-primary">Salva</button>
        </div>
    </form>
</div>
@endsection
