@extends("layouts.app")
@section("title", "Modifica prestazione")

@section("content")
<div class="mx-auto max-w-2xl">
    <div class="mb-4 flex items-center justify-between">
        <div>
            <a href="{{ route("treatments.index") }}" class="text-sm text-slate-500 hover:underline">&larr; Listino</a>
            <h1 class="mt-1 text-lg font-semibold text-slate-800">{{ $treatment->name }}</h1>
        </div>
    </div>
    <form method="POST" action="{{ route("treatments.update", $treatment) }}" class="card p-5">
        @method("PUT")
        @include("treatments._form")
        <div class="mt-6 flex items-center justify-between">
            <button type="submit" form="delete-treatment" class="text-sm text-red-600 hover:underline"
                    onclick="return confirm(\"Archiviare questa prestazione?\")">Archivia</button>
            <div class="flex gap-3">
                <a href="{{ route("treatments.index") }}" class="btn-secondary">Annulla</a>
                <button type="submit" class="btn-primary">Salva modifiche</button>
            </div>
        </div>
    </form>
    <form id="delete-treatment" method="POST" action="{{ route("treatments.destroy", $treatment) }}" class="hidden">
        @csrf @method("DELETE")
    </form>
</div>
@endsection
