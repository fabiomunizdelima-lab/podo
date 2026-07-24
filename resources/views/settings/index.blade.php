@extends('layouts.app')
@section('title', 'Impostazioni')

@section('content')
<div class="mx-auto max-w-2xl">
    <h1 class="mb-4 text-lg font-semibold text-slate-800">Impostazioni studio</h1>

    {{-- Aggiornamenti applicativo --}}
    <div class="card mb-6 p-5" x-data="updater({{ Illuminate\Support\Js::from($update) }})" x-init="init()">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-slate-700">Aggiornamenti</h2>
                <p class="text-sm text-slate-500">Versione installata: <span class="font-mono font-medium text-slate-800" x-text="current"></span></p>
            </div>
            <div class="flex gap-2">
                <button type="button" class="btn-secondary" @click="check()" x-show="!running" :disabled="checking">
                    <span x-text="checking ? 'Controllo…' : 'Controlla aggiornamenti'"></span>
                </button>
                <button type="button" class="btn-primary" @click="start()" x-show="available && !running" x-cloak>Aggiorna ora</button>
            </div>
        </div>

        <p class="mt-3 text-sm text-green-700" x-show="checked && !available && !errorMsg && !running" x-cloak>
            L'applicazione è aggiornata all'ultima versione.
        </p>
        <p class="mt-3 text-sm text-amber-700" x-show="available && !running" x-cloak>
            È disponibile la versione <span class="font-mono font-semibold" x-text="remote"></span>.
        </p>
        <p class="mt-3 text-sm text-red-700" x-show="errorMsg" x-cloak x-text="errorMsg"></p>

        {{-- Barra di avanzamento --}}
        <div class="mt-4" x-show="running || state === 'done' || state === 'error'" x-cloak>
            <div class="mb-1 flex items-center justify-between text-sm">
                <span class="text-slate-600" x-text="label"></span>
                <span class="font-mono text-slate-500" x-text="percent + '%'"></span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200">
                <div class="h-2 rounded-full transition-all duration-500"
                     :class="state === 'error' ? 'bg-red-500' : (state === 'done' ? 'bg-green-500' : 'bg-brand-600')"
                     :style="`width: ${percent}%`"></div>
            </div>
            <p class="mt-2 text-sm font-medium" x-show="state === 'done'" x-cloak>
                <span class="text-green-700">Aggiornamento completato.</span>
                <a href="{{ route('settings.edit') }}" class="text-brand-600 underline">Ricarica la pagina</a>
            </p>
            <pre class="mt-3 max-h-48 overflow-auto rounded-lg bg-slate-900 p-3 text-xs leading-relaxed text-slate-200"
                 x-show="log.length" x-text="log.join('\n')"></pre>
        </div>
    </div>

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
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-700">Stato sistema</h2>
            <a href="{{ route('integrations.edit') }}" class="text-sm text-brand-600 underline">Configura integrazioni →</a>
        </div>
        <dl class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <div class="flex justify-between"><dt class="text-slate-500">MFA obbligatoria admin</dt><dd>{{ ($security['mfa_required_for_admins'] ?? false) ? 'Attiva' : 'Disattivata (dev)' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Email (SMTP)</dt><dd>{{ ($integrations['mail'] ?? false) ? 'Attiva' : 'Non configurata' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Promemoria WhatsApp</dt><dd>{{ ($integrations['whatsapp'] ?? false) ? 'Attivo' : 'Non configurato' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Google Calendar</dt><dd>{{ ($integrations['google_calendar'] ?? false) ? 'Attivo' : 'Non configurato' }}</dd></div>
        </dl>
        <p class="mt-3 text-xs text-slate-400">La MFA si imposta da .env (richiede <span class="font-mono">docker compose up -d</span>); le integrazioni si configurano dall'interfaccia.</p>
    </div>
</div>

@push('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce') }}">
    function updater(initial) {
        return {
            current: initial.current || '—',
            remote: initial.last ? initial.last.remote : null,
            available: initial.last ? !!initial.last.available : false,
            checked: !!initial.last,
            checking: false,
            errorMsg: initial.last ? (initial.last.error || null) : null,
            state: initial.running ? 'running' : 'idle',
            step: 0,
            total: 6,
            label: '',
            log: [],
            timer: null,

            get percent() { return this.total ? Math.round((this.step / this.total) * 100) : 0; },
            get running() { return ['queued', 'running'].includes(this.state); },

            init() { if (this.running) this.poll(); },

            async check() {
                this.checking = true;
                this.errorMsg = null;
                try {
                    const r = await fetch('{{ route('update.check') }}', { headers: { 'Accept': 'application/json' } });
                    const d = await r.json();
                    this.remote = d.remote;
                    this.available = d.available;
                    this.errorMsg = d.error;
                    this.checked = true;
                } catch (e) {
                    this.errorMsg = 'Controllo non riuscito.';
                }
                this.checking = false;
            },

            async start() {
                this.errorMsg = null;
                const token = document.querySelector('meta[name="csrf-token"]').content;
                const r = await fetch('{{ route('update.start') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                });
                if (r.ok) {
                    this.state = 'queued';
                    this.label = 'In coda…';
                    this.poll();
                } else {
                    const d = await r.json().catch(() => ({}));
                    this.errorMsg = d.error || 'Impossibile avviare l\'aggiornamento.';
                }
            },

            poll() {
                clearInterval(this.timer);
                this.timer = setInterval(async () => {
                    try {
                        const r = await fetch('{{ route('update.status') }}', { headers: { 'Accept': 'application/json' } });
                        const s = await r.json();
                        this.state = s.state;
                        this.step = s.step;
                        this.total = s.total;
                        this.label = s.label || '';
                        this.log = s.log || [];
                        if (['done', 'error'].includes(s.state)) {
                            clearInterval(this.timer);
                            if (s.state === 'done') {
                                this.current = s.version || this.current;
                                this.available = false;
                            }
                        }
                    } catch (e) { /* riprova al prossimo tick */ }
                }, 1500);
            },
        };
    }
</script>
@endpush
@endsection
