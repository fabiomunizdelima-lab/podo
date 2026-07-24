@extends('layouts.app')
@section('title', 'Integrazioni')

@section('content')
<div class="mx-auto max-w-2xl" x-data="integrazioni()">
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-lg font-semibold text-slate-800">Integrazioni</h1>
        <a href="{{ route('settings.edit') }}" class="text-sm text-brand-600 underline">← Impostazioni studio</a>
    </div>

    <p class="mb-6 text-sm text-slate-500">
        Credenziali dei servizi esterni. Sono salvate cifrate nel database: non serve modificare il file
        <span class="font-mono">.env</span> né riavviare l'applicazione.
    </p>

    {{-- ---------------------------------------------------------------- Google Calendar --}}
    <div class="card mb-6 p-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-slate-700">Google Calendar</h2>
                <p class="mt-1 text-sm text-slate-500">Gli appuntamenti dell'agenda vengono creati anche sul calendario Google dello studio.</p>
            </div>
            <span class="badge {{ $google['enabled'] ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' }}">
                {{ $google['enabled'] ? 'Attiva' : 'Disattivata' }}
            </span>
        </div>

        <form method="POST" action="{{ route('integrations.google') }}" class="mt-4">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="label" for="g_client_id">Client ID</label>
                    <input class="input font-mono text-xs" id="g_client_id" name="client_id"
                           value="{{ old('client_id', $google['client_id']) }}"
                           placeholder="123456789-xxxx.apps.googleusercontent.com">
                </div>
                <div>
                    <label class="label" for="g_client_secret">Client Secret</label>
                    <input class="input font-mono text-xs" id="g_client_secret" name="client_secret" type="password"
                           autocomplete="new-password"
                           placeholder="{{ $google['client_secret_set'] ? '•••••••• (invariato)' : 'GOCSPX-…' }}">
                    <p class="mt-1 text-xs text-slate-400">Lascia vuoto per non modificare il valore già salvato.</p>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="g_calendar_id">ID calendario</label>
                        <input class="input" id="g_calendar_id" name="calendar_id"
                               value="{{ old('calendar_id', $google['calendar_id']) }}" placeholder="primary">
                    </div>
                    <div>
                        <label class="label" for="g_redirect">URI di reindirizzamento</label>
                        <input class="input font-mono text-xs" id="g_redirect" name="redirect_uri"
                               value="{{ old('redirect_uri', $google['redirect_uri']) }}"
                               placeholder="{{ $googleRedirectUri }}">
                    </div>
                </div>
            </div>

            <div class="mt-4 rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
                Nella Google Cloud Console → <em>Credenziali → ID client OAuth (applicazione web)</em>,
                inserisci come URI di reindirizzamento autorizzato:
                <span class="mt-1 block break-all font-mono text-slate-800">{{ $googleRedirectUri }}</span>
            </div>

            <label class="mt-4 flex items-center gap-2 text-sm">
                <input type="checkbox" name="enabled" value="1" @checked($google['enabled'])
                       class="rounded border-slate-300 text-brand-600">
                Attiva la sincronizzazione degli appuntamenti
            </label>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                <div class="text-sm">
                    @if ($googleToken)
                        <span class="text-green-700">
                            Account collegato{{ $googleToken->account_email ? ' · '.$googleToken->account_email : '' }}
                        </span>
                    @else
                        <span class="text-slate-500">Nessun account Google collegato.</span>
                    @endif
                </div>
                <div class="flex gap-2">
                    <button type="button" class="btn-secondary" @click="test('{{ route('integrations.test_google') }}', {}, 'google')"
                            :disabled="busy === 'google'">
                        <span x-text="busy === 'google' ? 'Verifica…' : 'Verifica collegamento'"></span>
                    </button>
                    <a href="{{ route('google.redirect') }}" class="btn-secondary">
                        {{ $googleToken ? 'Ricollega account' : 'Collega account' }}
                    </a>
                    <button type="submit" class="btn-primary">Salva</button>
                </div>
            </div>
            <p class="mt-3 text-sm" x-show="msg.google" x-cloak :class="ok.google ? 'text-green-700' : 'text-red-700'" x-text="msg.google"></p>
        </form>

        @if ($googleToken)
            <form method="POST" action="{{ route('google.disconnect') }}" class="mt-3 border-t border-slate-100 pt-3">
                @csrf @method('DELETE')
                <button type="submit" class="text-sm text-red-600 underline">Scollega l'account Google</button>
            </form>
        @endif
    </div>

    {{-- ---------------------------------------------------------------- Email / SMTP --}}
    <div class="card mb-6 p-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-slate-700">Email (SMTP)</h2>
                <p class="mt-1 text-sm text-slate-500">Serve per il recupero password e per i promemoria via email.</p>
            </div>
            <span class="badge {{ $mail['enabled'] ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' }}">
                {{ $mail['enabled'] ? 'Attiva' : 'Disattivata' }}
            </span>
        </div>

        <form method="POST" action="{{ route('integrations.mail') }}" class="mt-4">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="label" for="m_host">Server SMTP</label>
                    <input class="input" id="m_host" name="host" value="{{ old('host', $mail['host']) }}" placeholder="smtps.aruba.it">
                </div>
                <div>
                    <label class="label" for="m_port">Porta</label>
                    <input class="input" id="m_port" name="port" type="number" min="1" max="65535"
                           value="{{ old('port', $mail['port']) }}" placeholder="587">
                </div>
                <div>
                    <label class="label" for="m_encryption">Cifratura</label>
                    <select class="input" id="m_encryption" name="encryption">
                        <option value="tls" @selected($mail['encryption'] === 'tls')>STARTTLS (porta 587)</option>
                        <option value="ssl" @selected($mail['encryption'] === 'ssl')>SSL/TLS (porta 465)</option>
                        <option value="none" @selected($mail['encryption'] === 'none')>Nessuna</option>
                    </select>
                </div>
                <div>
                    <label class="label" for="m_username">Utente</label>
                    <input class="input" id="m_username" name="username" autocomplete="off"
                           value="{{ old('username', $mail['username']) }}">
                </div>
                <div>
                    <label class="label" for="m_password">Password</label>
                    <input class="input" id="m_password" name="password" type="password" autocomplete="new-password"
                           placeholder="{{ $mail['password_set'] ? '•••••••• (invariata)' : '' }}">
                </div>
                <div>
                    <label class="label" for="m_from_address">Indirizzo mittente</label>
                    <input class="input" id="m_from_address" name="from_address" type="email"
                           value="{{ old('from_address', $mail['from_address']) }}" placeholder="studio@example.it">
                </div>
                <div>
                    <label class="label" for="m_from_name">Nome mittente</label>
                    <input class="input" id="m_from_name" name="from_name"
                           value="{{ old('from_name', $mail['from_name']) }}" placeholder="Studio Podologico">
                </div>
            </div>

            <label class="mt-4 flex items-center gap-2 text-sm">
                <input type="checkbox" name="enabled" value="1" @checked($mail['enabled'])
                       class="rounded border-slate-300 text-brand-600">
                Abilita l'invio delle email
            </label>

            <div class="mt-4 flex justify-end gap-2">
                <button type="submit" class="btn-primary">Salva</button>
            </div>
        </form>

        <div class="mt-4 border-t border-slate-100 pt-4">
            <label class="label" for="test_mail_to">Invio di prova</label>
            <div class="flex flex-wrap gap-2">
                <input class="input flex-1" id="test_mail_to" type="email" x-model="mailTo" placeholder="tuo@indirizzo.it">
                <button type="button" class="btn-secondary"
                        @click="test('{{ route('integrations.test_mail') }}', { to: mailTo }, 'mail')"
                        :disabled="busy === 'mail' || !mailTo">
                    <span x-text="busy === 'mail' ? 'Invio…' : 'Invia prova'"></span>
                </button>
            </div>
            <p class="mt-2 text-sm" x-show="msg.mail" x-cloak :class="ok.mail ? 'text-green-700' : 'text-red-700'" x-text="msg.mail"></p>
            <p class="mt-2 text-xs text-slate-400">La prova usa le credenziali già salvate: salva prima di provare.</p>
        </div>
    </div>

    {{-- ---------------------------------------------------------------- WhatsApp --}}
    <div class="card mb-6 p-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-slate-700">WhatsApp</h2>
                <p class="mt-1 text-sm text-slate-500">Promemoria appuntamento tramite WhatsApp Business Cloud API (Meta).</p>
            </div>
            <span class="badge {{ $whatsapp['enabled'] ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' }}">
                {{ $whatsapp['enabled'] ? 'Attiva' : 'Disattivata' }}
            </span>
        </div>

        <form method="POST" action="{{ route('integrations.whatsapp') }}" class="mt-4">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="label" for="w_phone">Phone Number ID</label>
                    <input class="input font-mono" id="w_phone" name="phone_number_id"
                           value="{{ old('phone_number_id', $whatsapp['phone_number_id']) }}" placeholder="123456789012345">
                </div>
                <div class="sm:col-span-2">
                    <label class="label" for="w_token">Token di accesso</label>
                    <input class="input font-mono text-xs" id="w_token" name="access_token" type="password"
                           autocomplete="new-password"
                           placeholder="{{ $whatsapp['access_token_set'] ? '•••••••• (invariato)' : 'EAAG…' }}">
                    <p class="mt-1 text-xs text-slate-400">Lascia vuoto per non modificare il token già salvato.</p>
                </div>
                <div>
                    <label class="label" for="w_template">Nome template</label>
                    <input class="input" id="w_template" name="template_name"
                           value="{{ old('template_name', $whatsapp['template_name']) }}" placeholder="promemoria_appuntamento">
                </div>
                <div>
                    <label class="label" for="w_lang">Lingua template</label>
                    <input class="input" id="w_lang" name="template_lang"
                           value="{{ old('template_lang', $whatsapp['template_lang']) }}" placeholder="it">
                </div>
                <div>
                    <label class="label" for="w_api">Versione API</label>
                    <input class="input" id="w_api" name="api_version"
                           value="{{ old('api_version', $whatsapp['api_version']) }}" placeholder="v21.0">
                </div>
                <div>
                    <label class="label" for="w_hours">Preavviso (ore)</label>
                    <input class="input" id="w_hours" name="reminder_hours_before" type="number" min="1" max="168"
                           value="{{ old('reminder_hours_before', $whatsapp['reminder_hours_before']) }}">
                </div>
            </div>

            <div class="mt-4 rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
                Il template va creato e approvato in Meta Business con due parametri nel corpo:
                <span class="font-mono">@{{1}}</span> nome del paziente,
                <span class="font-mono">@{{2}}</span> data e ora dell'appuntamento.
            </div>

            <label class="mt-4 flex items-center gap-2 text-sm">
                <input type="checkbox" name="enabled" value="1" @checked($whatsapp['enabled'])
                       class="rounded border-slate-300 text-brand-600">
                Abilita l'invio dei promemoria WhatsApp
            </label>

            <div class="mt-4 flex justify-end gap-2">
                <button type="submit" class="btn-primary">Salva</button>
            </div>
        </form>

        <div class="mt-4 border-t border-slate-100 pt-4">
            <label class="label" for="test_wa_to">Invio di prova</label>
            <div class="flex flex-wrap gap-2">
                <input class="input flex-1 font-mono" id="test_wa_to" x-model="waTo" placeholder="393401234567">
                <button type="button" class="btn-secondary"
                        @click="test('{{ route('integrations.test_whatsapp') }}', { to: waTo }, 'whatsapp')"
                        :disabled="busy === 'whatsapp' || !waTo">
                    <span x-text="busy === 'whatsapp' ? 'Invio…' : 'Invia prova'"></span>
                </button>
            </div>
            <p class="mt-2 text-sm" x-show="msg.whatsapp" x-cloak :class="ok.whatsapp ? 'text-green-700' : 'text-red-700'" x-text="msg.whatsapp"></p>
            <p class="mt-2 text-xs text-slate-400">Numero in formato internazionale senza "+" (es. 393401234567).</p>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce') }}">
function integrazioni() {
    return {
        busy: null,
        mailTo: '{{ auth()->user()->email }}',
        waTo: '',
        msg: { google: '', mail: '', whatsapp: '' },
        ok: { google: false, mail: false, whatsapp: false },

        async test(url, payload, key) {
            this.busy = key;
            this.msg[key] = '';
            try {
                const r = await window.axios.post(url, payload);
                this.ok[key] = true;
                this.msg[key] = r.data.message;
            } catch (e) {
                this.ok[key] = false;
                this.msg[key] = e.response?.data?.message
                    || Object.values(e.response?.data?.errors || {}).flat().join(' ')
                    || 'Operazione non riuscita.';
            }
            this.busy = null;
        },
    };
}
</script>
@endpush
@endsection
