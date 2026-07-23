@extends('layouts.app')
@section('title', 'Impostazioni')

@section('content')
<div class="mx-auto max-w-2xl">
    <h1 class="mb-4 text-lg font-semibold text-slate-800">Impostazioni studio</h1>

    <form method="POST" action="{{ route('settings.update') }}" class="card p-5"
          x-data="{ regime: '{{ old('regime', $billing['regime'] ?? 'forfettario') }}', wh: {{ old('withholding_enabled', $billing['withholding_enabled'] ?? false) ? 'true' : 'false' }} }">
        @csrf @method('PUT')

        <h2 class="text-sm font-semibold text-slate-700">Dati dello studio (prestatore)</h2>
        <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="label" for="studio_name">Denominazione / Nome studio</label>
                <input class="input" id="studio_name" name="studio_name" value="{{ old('studio_name', $billing['studio_name'] ?? '') }}">
            </div>
            <div>
                <label class="label" for="vat_number">Partita IVA</label>
                <input class="input font-mono" id="vat_number" name="vat_number" value="{{ old('vat_number', $billing['vat_number'] ?? '') }}">
            </div>
            <div>
                <label class="label" for="fiscal_code">Codice fiscale</label>
                <input class="input font-mono uppercase" id="fiscal_code" name="fiscal_code" value="{{ old('fiscal_code', $billing['fiscal_code'] ?? '') }}">
            </div>
            <div class="sm:col-span-2">
                <label class="label" for="address">Indirizzo</label>
                <input class="input" id="address" name="address" value="{{ old('address', $billing['address'] ?? '') }}">
            </div>
            <div>
                <label class="label" for="city">Citt&agrave;</label>
                <input class="input" id="city" name="city" value="{{ old('city', $billing['city'] ?? '') }}">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label" for="cap">CAP</label>
                    <input class="input" id="cap" name="cap" value="{{ old('cap', $billing['cap'] ?? '') }}">
                </div>
                <div>
                    <label class="label" for="province">Prov.</label>
                    <input class="input uppercase" id="province" name="province" maxlength="4" value="{{ old('province', $billing['province'] ?? '') }}">
                </div>
            </div>
        </div>

        <h2 class="mt-6 text-sm font-semibold text-slate-700">Fatturazione</h2>
        <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="regime">Regime fiscale</label>
                <select class="input" id="regime" name="regime" x-model="regime">
                    <option value="forfettario">Forfettario</option>
                    <option value="ordinario">Ordinario</option>
                </select>
            </div>
            <div>
                <label class="label" for="tax_regime_code">Codice regime SDI</label>
                <input class="input" id="tax_regime_code" name="tax_regime_code" maxlength="4"
                       :placeholder="regime === 'forfettario' ? 'RF19' : 'RF01'"
                       value="{{ old('tax_regime_code', $billing['tax_regime_code'] ?? '') }}">
                <p class="mt-1 text-xs text-slate-400">Se vuoto viene dedotto dal regime.</p>
            </div>
            <div>
                <label class="label" for="sdi_code">Codice destinatario SDI</label>
                <input class="input font-mono" id="sdi_code" name="sdi_code" maxlength="7" placeholder="0000000" value="{{ old('sdi_code', $billing['sdi_code'] ?? '') }}">
            </div>
            <div>
                <label class="label" for="pec">PEC</label>
                <input class="input" id="pec" name="pec" value="{{ old('pec', $billing['pec'] ?? '') }}">
            </div>
            <div class="sm:col-span-2">
                <label class="label" for="register_note">Nota in fattura (esenzione / iscrizione albo)</label>
                <input class="input" id="register_note" name="register_note" value="{{ old('register_note', $billing['register_note'] ?? '') }}">
            </div>
            <div>
                <label class="label" for="ts_default_type">Tipologia spesa Sistema TS</label>
                <input class="input" id="ts_default_type" name="ts_default_type" maxlength="8" value="{{ old('ts_default_type', $billing['ts_default_type'] ?? 'SR') }}">
            </div>
        </div>

        <fieldset class="mt-5 rounded-lg bg-slate-50 p-4">
            <legend class="px-1 text-sm font-medium text-slate-700">Ritenuta d'acconto</legend>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="withholding_enabled" value="1" x-model="wh" class="rounded border-slate-300 text-brand-600">
                Applica ritenuta d'acconto (di norma NON per i forfettari)
            </label>
            <div class="mt-3 sm:max-w-xs" x-show="wh" x-cloak>
                <label class="label" for="withholding_rate">Aliquota ritenuta (%)</label>
                <input class="input" id="withholding_rate" name="withholding_rate" type="number" step="0.01" min="0" max="100"
                       value="{{ old('withholding_rate', $billing['withholding_rate'] ?? 20) }}">
            </div>
        </fieldset>

        <div class="mt-6 flex justify-end">
            <button type="submit" class="btn-primary">Salva impostazioni</button>
        </div>
    </form>

    {{-- Stato sistema (sola lettura) --}}
    <div class="card mt-6 p-5 text-sm">
        <h2 class="mb-3 text-sm font-semibold text-slate-700">Stato sistema</h2>
        <dl class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <div class="flex justify-between"><dt class="text-slate-500">MFA obbligatoria admin</dt><dd>{{ ($security['mfa_required_for_admins'] ?? false) ? 'Attiva' : 'Disattivata (dev)' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Promemoria WhatsApp</dt><dd>{{ ($integrations['whatsapp'] ?? false) ? 'Attivo' : 'Non configurato' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Google Calendar</dt><dd>{{ ($integrations['google_calendar'] ?? false) ? 'Attivo' : 'Non configurato' }}</dd></div>
        </dl>
        <p class="mt-3 text-xs text-slate-400">MFA e integrazioni si configurano da .env (richiedono riavvio dei container).</p>
    </div>
</div>
@endsection
