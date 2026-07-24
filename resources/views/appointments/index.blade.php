@extends('layouts.app')
@section('title', 'Agenda')

@section('content')
<div x-data="agendaPage()" x-init="mount()">
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-slate-500">Trascina per creare, tocca un evento per i dettagli.</p>
        <button class="btn-primary" @click="openNew()">+ Appuntamento</button>
    </div>

    <div class="card p-3 sm:p-4">
        <div id="calendar" wire:ignore></div>
    </div>

    {{-- Modale nuovo/modifica appuntamento --}}
    <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50 p-0 sm:items-center sm:p-4" @keydown.escape.window="modal=false">
        <div class="w-full max-w-lg rounded-t-2xl bg-white p-6 shadow-xl sm:rounded-2xl" @click.outside="modal=false">
            <h3 class="mb-4 text-lg font-semibold text-slate-800" x-text="form.id ? 'Modifica appuntamento' : 'Nuovo appuntamento'"></h3>
            <form @submit.prevent="save()" class="space-y-4">
                <div>
                    <label class="label">Paziente *</label>
                    <select class="input" x-model="form.patient_id" required>
                        <option value="">Seleziona…</option>
                        @foreach ($patients as $p)
                            <option value="{{ $p->id }}">{{ $p->last_name }} {{ $p->first_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Inizio *</label>
                        <input type="datetime-local" class="input" x-model="form.starts_at" required>
                    </div>
                    <div>
                        <label class="label">Fine *</label>
                        <input type="datetime-local" class="input" x-model="form.ends_at" required>
                    </div>
                </div>
                <div>
                    <label class="label">Trattamento</label>
                    <input class="input" x-model="form.treatment" placeholder="Es. Trattamento podologico">
                </div>
                <div>
                    <label class="label">Note</label>
                    <textarea class="input" rows="2" x-model="form.notes"></textarea>
                </div>

                <div>
                    <label class="label">Promemoria al paziente</label>
                    <select class="input" x-model="form.reminder_channel">
                        @foreach ($channels as $value => $etichetta)
                            <option value="{{ $value }}">{{ $etichetta }}</option>
                        @endforeach
                    </select>
                    @if (count($channels) === 1)
                        <p class="mt-1 text-xs text-amber-600">
                            Nessun canale attivo: va configurato in Impostazioni → Integrazioni.
                            @if (auth()->user()->isSuperAdmin())
                                <a href="{{ route('integrations.edit') }}" class="underline">Apri le integrazioni</a>
                            @endif
                        </p>
                    @else
                        <p class="mt-1 text-xs text-slate-400">
                            Inviato in automatico il giorno prima. WhatsApp richiede il consenso del paziente,
                            l'email un indirizzo in anagrafica.
                        </p>
                    @endif
                </div>

                <div x-show="form.id && form.reminder_channel !== 'none'" x-cloak class="rounded-lg bg-slate-50 p-3">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-slate-600" x-text="form.reminder_sent ? 'Promemoria già inviato.' : 'Promemoria non ancora inviato.'"></span>
                        <button type="button" class="btn-secondary" @click="sendReminder()" :disabled="sending">
                            <span x-text="sending ? 'Invio…' : 'Invia ora'"></span>
                        </button>
                    </div>
                    <p class="mt-2 text-sm" x-show="reminderMsg" x-cloak
                       :class="reminderOk ? 'text-green-700' : 'text-red-700'" x-text="reminderMsg"></p>
                </div>

                <div class="flex justify-between gap-3 pt-2">
                    <button type="button" x-show="form.id" class="btn-danger" @click="destroy()">Elimina</button>
                    <div class="ml-auto flex gap-3">
                        <button type="button" class="btn-secondary" @click="modal=false">Chiudi</button>
                        <button type="submit" class="btn-primary">Salva</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce') }}">
function agendaPage() {
    return {
        calendar: null,
        modal: false,
        sending: false,
        reminderMsg: '',
        reminderOk: false,
        form: {},
        mount() {
            this.calendar = window.initAgenda(document.getElementById('calendar'), {
                feedUrl: '{{ route('appointments.feed') }}',
                onDateClick: (date) => this.openNew(date),
                onEventClick: (event) => this.openEdit(event),
            });
        },
        fmt(d) { return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 16); },
        emptyForm(date) {
            const start = date ? new Date(date) : new Date();
            const end = new Date(start.getTime() + 30 * 60000);
            return {
                id: null, patient_id: '', starts_at: this.fmt(start), ends_at: this.fmt(end),
                treatment: '', notes: '', reminder_channel: '{{ $defaultChannel }}', reminder_sent: false,
            };
        },
        openNew(date) { this.resetMessages(); this.form = this.emptyForm(date); this.modal = true; },
        openEdit(event) {
            this.resetMessages();
            const p = event.extendedProps || {};
            this.form = {
                id: event.id,
                patient_id: p.patient_id,
                starts_at: event.start ? this.fmt(event.start) : '',
                ends_at: event.end ? this.fmt(event.end) : '',
                treatment: p.treatment || '',
                notes: p.notes || '',
                reminder_channel: p.reminder_channel || 'none',
                reminder_sent: !!p.reminder_sent,
            };
            this.modal = true;
        },
        resetMessages() { this.reminderMsg = ''; this.reminderOk = false; this.sending = false; },
        async save() {
            const url = this.form.id ? `/agenda/${this.form.id}` : '/agenda';
            const method = this.form.id ? 'put' : 'post';
            await window.axios[method](url, this.form);
            this.modal = false;
            this.calendar.refetchEvents();
        },
        async sendReminder() {
            this.sending = true;
            try {
                const r = await window.axios.post(`/agenda/${this.form.id}/reminder`);
                this.reminderOk = true;
                this.reminderMsg = r.data.message;
                this.form.reminder_sent = true;
            } catch (e) {
                this.reminderOk = false;
                this.reminderMsg = e.response?.data?.message || 'Invio non riuscito.';
            }
            this.sending = false;
        },
        async destroy() {
            if (!confirm('Eliminare l\'appuntamento?')) return;
            await window.axios.delete(`/agenda/${this.form.id}`);
            this.modal = false;
            this.calendar.refetchEvents();
        },
    };
}
</script>
@endpush
@endsection
