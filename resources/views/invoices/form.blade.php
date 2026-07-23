@extends('layouts.app')
@section('title', $invoice->exists ? 'Modifica fattura' : 'Nuova fattura')

@php
    $isEdit = $invoice->exists;
    $action = $isEdit ? route('invoices.update', $invoice) : route('invoices.store');
    $existingLines = old('lines', $isEdit
        ? $invoice->lines->map(fn ($l) => [
            'treatment_id' => $l->treatment_id,
            'description' => $l->description,
            'quantity' => $l->quantity,
            'unit_price' => $l->unit_price,
            'vat_rate' => $l->vat_rate,
        ])->values()->all()
        : []);
    $catalog = $treatments->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'price' => (float) $t->price])->values();
@endphp

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-4">
        <a href="{{ route('invoices.index') }}" class="text-sm text-slate-500 hover:underline">&larr; Fatture</a>
        <h1 class="mt-1 text-lg font-semibold text-slate-800">{{ $isEdit ? 'Modifica bozza' : 'Nuova fattura' }}</h1>
    </div>

    <form method="POST" action="{{ $action }}" class="card p-5"
          x-data="invoiceForm({{ Illuminate\Support\Js::from($catalog) }}, {{ Illuminate\Support\Js::from($existingLines) }})">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="patient_id">Paziente *</label>
                @if ($isEdit || $patient)
                    <input class="input bg-slate-50" value="{{ $invoice->client_name ?: $patient->full_name }}" disabled>
                    <input type="hidden" name="patient_id" value="{{ $invoice->patient_id ?: $patient->id }}">
                @else
                    <select class="input" id="patient_id" name="patient_id" required>
                        <option value="">— seleziona —</option>
                        @foreach ($patients as $p)
                            <option value="{{ $p->id }}" @selected(old('patient_id') == $p->id)>{{ $p->last_name }} {{ $p->first_name }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
            <div>
                <label class="label" for="notes">Note</label>
                <input class="input" id="notes" name="notes" value="{{ old('notes', $invoice->notes) }}">
            </div>
        </div>

        <fieldset class="mt-6 rounded-lg bg-slate-50 p-4">
            <legend class="px-1 text-sm font-medium text-slate-700">Righe</legend>
            <template x-for="(line, i) in lines" :key="i">
                <div class="mb-2 grid grid-cols-12 items-center gap-2">
                    <select class="input col-span-5 text-sm" @change="pick(i, $event.target.value)">
                        <option value="">— dal listino —</option>
                        <template x-for="t in catalog" :key="t.id">
                            <option :value="t.id" :selected="t.id === line.treatment_id" x-text="t.name"></option>
                        </template>
                    </select>
                    <input type="hidden" :name="`lines[${i}][treatment_id]`" :value="line.treatment_id">
                    <input class="input col-span-3 text-sm" :name="`lines[${i}][description]`" x-model="line.description" placeholder="descrizione">
                    <input class="input col-span-1 text-sm" type="number" min="1" :name="`lines[${i}][quantity]`" x-model="line.quantity" title="qta">
                    <input class="input col-span-1 text-sm" type="number" step="0.01" min="0" :name="`lines[${i}][unit_price]`" x-model="line.unit_price" title="prezzo">
                    <input class="input col-span-1 text-sm" type="number" step="0.01" min="0" :name="`lines[${i}][vat_rate]`" x-model="line.vat_rate" title="IVA %">
                    <button type="button" class="col-span-1 text-red-500 hover:text-red-700" @click="remove(i)">×</button>
                </div>
            </template>
            <div class="mt-2 flex items-center justify-between">
                <button type="button" class="text-sm text-brand-600 hover:underline" @click="add()">+ Aggiungi riga</button>
                <div class="text-sm text-slate-600">Imponibile: € <span x-text="taxable()"></span></div>
            </div>
        </fieldset>

        <p class="mt-3 text-xs text-slate-400">Marca da bollo, ritenuta e totale vengono calcolati automaticamente al salvataggio secondo il regime configurato.</p>

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('invoices.index') }}" class="btn-secondary">Annulla</a>
            <button type="submit" class="btn-primary">{{ $isEdit ? 'Salva bozza' : 'Crea bozza' }}</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function invoiceForm(catalog, existing) {
        return {
            catalog,
            lines: existing.length ? existing.map(l => ({
                treatment_id: l.treatment_id ?? '',
                description: l.description ?? '',
                quantity: l.quantity ?? 1,
                unit_price: l.unit_price ?? 0,
                vat_rate: l.vat_rate ?? 0,
            })) : [{ treatment_id: '', description: '', quantity: 1, unit_price: 0, vat_rate: 0 }],
            add() { this.lines.push({ treatment_id: '', description: '', quantity: 1, unit_price: 0, vat_rate: 0 }); },
            remove(i) { this.lines.splice(i, 1); },
            pick(i, id) {
                const t = this.catalog.find(x => String(x.id) === String(id));
                this.lines[i].treatment_id = id || '';
                if (t) { this.lines[i].description = t.name; this.lines[i].unit_price = t.price; }
            },
            taxable() {
                return this.lines
                    .reduce((s, l) => s + (Number(l.quantity) || 0) * (Number(l.unit_price) || 0), 0)
                    .toFixed(2).replace('.', ',');
            },
        };
    }
</script>
@endpush
@endsection
