@csrf
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label class="label" for="name">Descrizione prestazione *</label>
        <input class="input" id="name" name="name" value="{{ old("name", $treatment->name) }}" required autofocus>
    </div>
    <div>
        <label class="label" for="code">Codice</label>
        <input class="input font-mono uppercase" id="code" name="code" maxlength="30" value="{{ old("code", $treatment->code) }}">
    </div>
    <div>
        <label class="label" for="category">Categoria</label>
        <input class="input" id="category" name="category" list="cat-list" value="{{ old("category", $treatment->category) }}">
        <datalist id="cat-list">
            @foreach (["Visite","Podologia generale","Onicologia","Ortesi","Piede diabetico"] as $c)
                <option value="{{ $c }}"></option>
            @endforeach
        </datalist>
    </div>
    <div>
        <label class="label" for="price">Prezzo (€) *</label>
        <input class="input" id="price" name="price" type="number" step="0.01" min="0" value="{{ old("price", $treatment->price) }}" required>
    </div>
    <div>
        <label class="label" for="duration_minutes">Durata tipica (min)</label>
        <input class="input" id="duration_minutes" name="duration_minutes" type="number" min="0" step="5" value="{{ old("duration_minutes", $treatment->duration_minutes) }}">
    </div>
    <div class="sm:col-span-2">
        <label class="label" for="description">Note / descrizione estesa</label>
        <textarea class="input" id="description" name="description" rows="2">{{ old("description", $treatment->description) }}</textarea>
    </div>
</div>

<fieldset class="mt-5 rounded-lg bg-slate-50 p-4" x-data="{ exempt: {{ old('vat_exempt', $treatment->vat_exempt ?? true) ? 'true' : 'false' }} }">
    <legend class="px-1 text-sm font-medium text-slate-700">Fiscale</legend>
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="vat_exempt" value="1" x-model="exempt" class="rounded border-slate-300 text-brand-600">
        Prestazione sanitaria esente IVA (art.10 c.1 n.18 – natura N4)
    </label>
    <div class="mt-3 grid grid-cols-2 gap-4" x-show="!exempt" x-cloak>
        <div>
            <label class="label" for="vat_rate">Aliquota IVA (%)</label>
            <input class="input" id="vat_rate" name="vat_rate" type="number" step="0.01" min="0" max="100" value="{{ old("vat_rate", $treatment->vat_rate) }}">
        </div>
        <div>
            <label class="label" for="ts_type">Tipologia spesa Sistema TS</label>
            <input class="input" id="ts_type" name="ts_type" maxlength="8" value="{{ old("ts_type", $treatment->ts_type) }}" placeholder="(da definire)">
        </div>
    </div>
</fieldset>

<label class="mt-4 flex items-center gap-2 text-sm">
    <input type="checkbox" name="is_active" value="1" @checked(old("is_active", $treatment->is_active ?? true)) class="rounded border-slate-300 text-brand-600">
    Attiva (disponibile in agenda e fatturazione)
</label>
