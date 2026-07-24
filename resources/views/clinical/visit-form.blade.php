@extends('layouts.app')
@section('title', ($visit->exists ? 'Visita' : 'Nuova visita').' · '.$patient->full_name)

@php
    $isEdit = $visit->exists;
    $action = $isEdit ? route('visits.update', $visit) : route('patients.visits.store', $patient);

    // Righe prestazione preesistenti (in modifica) o vuote (nuova)
    $existingLines = old('lines', $isEdit
        ? $visit->treatments->map(fn ($t) => [
            'treatment_id' => $t->id,
            'description' => $t->pivot->description,
            'quantity' => $t->pivot->quantity,
            'unit_price' => $t->pivot->unit_price,
        ])->values()->all()
        : []);

    $catalog = $treatments->map(fn ($t) => [
        'id' => $t->id, 'name' => $t->name, 'price' => (float) $t->price,
    ])->values();
@endphp

@section('content')
<div class="mx-auto max-w-2xl">
    <div class="mb-4">
        <a href="{{ route('patients.show', $patient) }}" class="text-sm text-slate-500 hover:underline">&larr; {{ $patient->full_name }}</a>
        <h1 class="mt-1 text-lg font-semibold text-slate-800">{{ $isEdit ? 'Modifica visita' : 'Nuova visita' }}</h1>
    </div>

    <form method="POST" action="{{ $action }}" class="card p-5"
          x-data="visitForm({{ Illuminate\Support\Js::from($catalog) }}, {{ Illuminate\Support\Js::from($existingLines) }})">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="visited_at">Data e ora *</label>
                <input class="input" id="visited_at" name="visited_at" type="datetime-local" required
                       value="{{ old('visited_at', optional($visit->visited_at)->format('Y-m-d\TH:i') ?: now()->format('Y-m-d\TH:i')) }}">
            </div>
            <div>
                <label class="label" for="reason">Motivo della visita</label>
                <input class="input" id="reason" name="reason" value="{{ old('reason', $visit->reason) }}">
            </div>
            <div>
                <label class="label" for="visit_type">Tipo visita</label>
                <select class="input" id="visit_type" name="visit_type">
                    <option value="">—</option>
                    @foreach (\App\Enums\VisitType::cases() as $vt)
                        <option value="{{ $vt->value }}" @selected(old('visit_type', $visit->visit_type?->value) === $vt->value)>{{ $vt->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-4">
            <label class="label" for="objective_exam">Esame obiettivo</label>
            <textarea class="input" id="objective_exam" name="objective_exam" rows="3">{{ old('objective_exam', $visit->objective_exam) }}</textarea>
        </div>
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="diagnosis">Diagnosi</label>
                <textarea class="input" id="diagnosis" name="diagnosis" rows="2">{{ old('diagnosis', $visit->diagnosis) }}</textarea>
            </div>
            <div>
                <label class="label" for="treatment_performed">Trattamento eseguito</label>
                <textarea class="input" id="treatment_performed" name="treatment_performed" rows="2">{{ old('treatment_performed', $visit->treatment_performed) }}</textarea>
            </div>
        </div>
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="recommendations">Indicazioni / prescrizioni</label>
                <textarea class="input" id="recommendations" name="recommendations" rows="2">{{ old('recommendations', $visit->recommendations) }}</textarea>
            </div>
            <div>
                <label class="label" for="next_visit_at">Prossimo controllo</label>
                <input class="input" id="next_visit_at" name="next_visit_at" type="date" value="{{ old('next_visit_at', optional($visit->next_visit_at)->format('Y-m-d')) }}">
            </div>
        </div>

        {{-- Prestazioni erogate (da listino) --}}
        <fieldset class="mt-6 rounded-lg bg-slate-50 p-4">
            <legend class="px-1 text-sm font-medium text-slate-700">Prestazioni erogate</legend>

            <template x-for="(line, i) in lines" :key="i">
                <div class="mb-2 grid grid-cols-12 items-center gap-2">
                    <select class="input col-span-6 text-sm" @change="pick(i, $event.target.value)">
                        <option value="">— prestazione dal listino —</option>
                        <template x-for="t in catalog" :key="t.id">
                            <option :value="t.id" :selected="t.id === line.treatment_id" x-text="t.name"></option>
                        </template>
                    </select>
                    <input type="hidden" :name="`lines[${i}][treatment_id]`" :value="line.treatment_id">
                    <input class="input col-span-3 text-sm" :name="`lines[${i}][description]`" x-model="line.description" placeholder="descrizione">
                    <input class="input col-span-1 text-sm" type="number" min="1" :name="`lines[${i}][quantity]`" x-model="line.quantity">
                    <input class="input col-span-1 text-sm" type="number" step="0.01" min="0" :name="`lines[${i}][unit_price]`" x-model="line.unit_price">
                    <button type="button" class="col-span-1 text-red-500 hover:text-red-700" @click="remove(i)">×</button>
                </div>
            </template>

            <div class="mt-2 flex items-center justify-between">
                <button type="button" class="text-sm text-brand-600 hover:underline" @click="add()">+ Aggiungi prestazione</button>
                <div class="text-sm font-medium text-slate-700">Totale: € <span x-text="total()"></span></div>
            </div>
        </fieldset>

        <div class="mt-6 flex items-center justify-between">
            @if ($isEdit)
                <button type="submit" form="delete-visit" class="text-sm text-red-600 hover:underline"
                        onclick="return confirm('Archiviare questa visita?')">Archivia visita</button>
            @else
                <span></span>
            @endif
            <div class="flex gap-3">
                <a href="{{ route('patients.show', $patient) }}" class="btn-secondary">Annulla</a>
                <button type="submit" class="btn-primary">{{ $isEdit ? 'Salva modifiche' : 'Registra visita' }}</button>
            </div>
        </div>
    </form>

    @if ($isEdit)
        <form id="delete-visit" method="POST" action="{{ route('visits.destroy', $visit) }}" class="hidden">
            @csrf @method('DELETE')
        </form>
        @if (auth()->user()->atLeast(\App\Enums\Role::ADMIN))
            <form method="POST" action="{{ route('invoices.from_visit', $visit) }}" class="mt-4">
                @csrf
                <button class="btn-secondary">Genera fattura da questa visita &rarr;</button>
            </form>
        @endif
    @endif
</div>

@push('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce') }}">
    function visitForm(catalog, existing) {
        return {
            catalog,
            lines: existing.length ? existing.map(l => ({
                treatment_id: l.treatment_id ?? '',
                description: l.description ?? '',
                quantity: l.quantity ?? 1,
                unit_price: l.unit_price ?? 0,
            })) : [],
            add() { this.lines.push({ treatment_id: '', description: '', quantity: 1, unit_price: 0 }); },
            remove(i) { this.lines.splice(i, 1); },
            pick(i, id) {
                const t = this.catalog.find(x => String(x.id) === String(id));
                this.lines[i].treatment_id = id || '';
                if (t) {
                    this.lines[i].description = t.name;
                    this.lines[i].unit_price = t.price;
                }
            },
            total() {
                return this.lines
                    .reduce((s, l) => s + (Number(l.quantity) || 0) * (Number(l.unit_price) || 0), 0)
                    .toFixed(2).replace('.', ',');
            },
        };
    }
</script>
@endpush
@endsection
