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
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" x-model="form.whatsapp" class="rounded border-slate-300 text-brand-600">
                    Invia promemoria WhatsApp
                </label>
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
        form: {},
        mount() {
            this.calendar = window.initAgenda(document.getElementById('calendar'), {
                feedUrl: '{{ route('appointments.feed') }}',
                onDateClick: (date) => this.openNew(date),
                onEventClick: (event) => this.openEdit(event),
            });
        },
        emptyForm(date) {
            const start = date ? new Date(date) : new Date();
            const end = new Date(start.getTime() + 30 * 60000);
            const fmt = (d) => new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
            return { id: null, patient_id: '', starts_at: fmt(start), ends_at: fmt(end), treatment: '', notes: '', whatsapp: true };
        },
        openNew(date) { this.form = this.emptyForm(date); this.modal = true; },
        openEdit(event) {
            this.form = {
                id: event.id,
                patient_id: event.extendedProps.patient_id,
                starts_at: event.start ? new Date(event.start.getTime() - event.start.getTimezoneOffset()*60000).toISOString().slice(0,16) : '',
                ends_at: event.end ? new Date(event.end.getTime() - event.end.getTimezoneOffset()*60000).toISOString().slice(0,16) : '',
                treatment: '', notes: '', whatsapp: true,
            };
            this.modal = true;
        },
        async save() {
            const payload = { ...this.form, reminder_channel: this.form.whatsapp ? 'whatsapp' : 'none' };
            const url = this.form.id ? `/agenda/${this.form.id}` : '/agenda';
            const method = this.form.id ? 'put' : 'post';
            await window.axios[method](url, payload);
            this.modal = false;
            this.calendar.refetchEvents();
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
