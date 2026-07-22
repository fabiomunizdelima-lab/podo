@csrf
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <label class="label" for="first_name">Nome *</label>
        <input class="input" id="first_name" name="first_name" value="{{ old('first_name', $patient->first_name) }}" required>
    </div>
    <div>
        <label class="label" for="last_name">Cognome *</label>
        <input class="input" id="last_name" name="last_name" value="{{ old('last_name', $patient->last_name) }}" required>
    </div>
    <div>
        <label class="label" for="fiscal_code">Codice fiscale</label>
        <input class="input font-mono uppercase" id="fiscal_code" name="fiscal_code" maxlength="16" value="{{ old('fiscal_code', $patient->fiscal_code) }}">
    </div>
    <div>
        <label class="label" for="birth_date">Data di nascita</label>
        <input class="input" id="birth_date" name="birth_date" type="date" value="{{ old('birth_date', optional($patient->birth_date)->format('Y-m-d')) }}">
    </div>
    <div>
        <label class="label" for="gender">Sesso</label>
        <select class="input" id="gender" name="gender">
            <option value="">—</option>
            @foreach (['M' => 'M', 'F' => 'F', 'X' => 'Altro'] as $val => $lbl)
                <option value="{{ $val }}" @selected(old('gender', $patient->gender) === $val)>{{ $lbl }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label" for="phone">Telefono</label>
        <input class="input" id="phone" name="phone" value="{{ old('phone', $patient->phone) }}">
    </div>
    <div>
        <label class="label" for="whatsapp_phone">Numero WhatsApp</label>
        <input class="input" id="whatsapp_phone" name="whatsapp_phone" placeholder="+39…" value="{{ old('whatsapp_phone', $patient->whatsapp_phone) }}">
    </div>
    <div>
        <label class="label" for="email">Email</label>
        <input class="input" id="email" name="email" type="email" value="{{ old('email', $patient->email) }}">
    </div>
    <div class="sm:col-span-2">
        <label class="label" for="address">Indirizzo</label>
        <input class="input" id="address" name="address" value="{{ old('address', $patient->address) }}">
    </div>
    <div>
        <label class="label" for="city">Città</label>
        <input class="input" id="city" name="city" value="{{ old('city', $patient->city) }}">
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="label" for="postal_code">CAP</label>
            <input class="input" id="postal_code" name="postal_code" value="{{ old('postal_code', $patient->postal_code) }}">
        </div>
        <div>
            <label class="label" for="province">Prov.</label>
            <input class="input uppercase" id="province" name="province" maxlength="4" value="{{ old('province', $patient->province) }}">
        </div>
    </div>
</div>

<div class="mt-4">
    <label class="label" for="notes">Note</label>
    <textarea class="input" id="notes" name="notes" rows="2">{{ old('notes', $patient->notes) }}</textarea>
</div>
<div class="mt-4">
    <label class="label" for="clinical_notes">Note cliniche <span class="text-xs text-slate-400">(cifrate)</span></label>
    <textarea class="input" id="clinical_notes" name="clinical_notes" rows="3">{{ old('clinical_notes', $patient->clinical_notes) }}</textarea>
</div>

<fieldset class="mt-5 space-y-2 rounded-lg bg-slate-50 p-4">
    <legend class="px-1 text-sm font-medium text-slate-700">Consensi (GDPR)</legend>
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="consent_privacy" value="1" @checked(old('consent_privacy', $patient->consent_privacy)) class="rounded border-slate-300 text-brand-600">
        Informativa privacy accettata
    </label>
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="consent_whatsapp" value="1" @checked(old('consent_whatsapp', $patient->consent_whatsapp)) class="rounded border-slate-300 text-brand-600">
        Consenso all'invio di promemoria via WhatsApp
    </label>
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="consent_marketing" value="1" @checked(old('consent_marketing', $patient->consent_marketing)) class="rounded border-slate-300 text-brand-600">
        Consenso comunicazioni promozionali
    </label>
</fieldset>
