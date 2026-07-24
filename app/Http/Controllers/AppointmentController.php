<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Setting;
use App\Services\AppointmentReminderService;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function __construct(
        private GoogleCalendarService $calendar,
        private AppointmentReminderService $reminders,
    ) {}

    public function index()
    {
        $patients = Patient::where('is_active', true)->orderBy('last_name')->get(['id', 'first_name', 'last_name']);

        // Canali disponibili per il promemoria: mostriamo solo quelli configurati
        $whatsappOn = (bool) Setting::whatsapp()['enabled'];
        $mailOn = (bool) Setting::mail()['enabled'];
        $channels = array_filter([
            'whatsapp' => $whatsappOn ? 'WhatsApp' : null,
            'email' => $mailOn ? 'Email' : null,
            'none' => 'Nessuno',
        ]);
        $defaultChannel = $whatsappOn ? 'whatsapp' : ($mailOn ? 'email' : 'none');

        return view('appointments.index', compact('patients', 'channels', 'defaultChannel'));
    }

    /** Feed JSON per il calendario (FullCalendar). */
    public function feed(Request $request)
    {
        $start = $request->date('start') ?? now()->startOfMonth();
        $end = $request->date('end') ?? now()->endOfMonth();

        $events = Appointment::with('patient')
            ->whereBetween('starts_at', [$start, $end])
            ->get()
            ->map(fn (Appointment $a) => [
                'id' => $a->id,
                'title' => ($a->patient?->full_name ?? 'Paziente').' — '.($a->treatment ?: $a->title),
                'start' => $a->starts_at->toIso8601String(),
                'end' => $a->ends_at->toIso8601String(),
                'color' => $a->status->color(),
                'extendedProps' => [
                    'status' => $a->status->label(),
                    'patient_id' => $a->patient_id,
                    // servono al modale di modifica: senza, il salvataggio li azzererebbe
                    'treatment' => $a->treatment,
                    'notes' => $a->notes,
                    'reminder_channel' => $a->reminder_channel,
                    'reminder_sent' => (bool) $a->reminder_sent_at,
                ],
            ]);

        return response()->json($events);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;

        $appointment = Appointment::create($data);

        // Sincronizza con Google Calendar (best-effort, non blocca)
        $this->calendar->syncAppointment($appointment);

        return $this->respond($request, $appointment, 'Appuntamento creato.');
    }

    public function update(Request $request, Appointment $appointment)
    {
        $appointment->update($this->validateData($request));
        $this->calendar->syncAppointment($appointment);

        return $this->respond($request, $appointment, 'Appuntamento aggiornato.');
    }

    public function destroy(Request $request, Appointment $appointment)
    {
        $this->calendar->deleteEvent($appointment);
        $appointment->delete();

        return $this->respond($request, null, 'Appuntamento eliminato.');
    }

    /** Invio manuale del promemoria sul canale scelto per l'appuntamento. */
    public function sendReminder(Request $request, Appointment $appointment)
    {
        [$ok, $message] = $this->reminders->send($appointment);

        if ($request->expectsJson()) {
            return response()->json(['ok' => $ok, 'message' => $message], $ok ? 200 : 422);
        }

        return back()->with($ok ? 'success' : 'error', $message);
    }

    private function respond(Request $request, ?Appointment $appointment, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $message, 'appointment' => $appointment]);
        }

        return back()->with('success', $message);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'title' => ['nullable', 'string', 'max:150'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'status' => ['sometimes', 'in:'.implode(',', array_column(AppointmentStatus::cases(), 'value'))],
            'treatment' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'reminder_channel' => ['sometimes', 'in:'.implode(',', AppointmentReminderService::CHANNELS)],
        ]);
    }
}
