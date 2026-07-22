<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Services\GoogleCalendarService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function __construct(
        private GoogleCalendarService $calendar,
        private WhatsAppService $whatsapp,
    ) {}

    public function index()
    {
        $patients = Patient::where('is_active', true)->orderBy('last_name')->get(['id', 'first_name', 'last_name']);
        return view('appointments.index', compact('patients'));
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

    /** Invio manuale del promemoria WhatsApp. */
    public function sendReminder(Appointment $appointment)
    {
        $ok = $this->whatsapp->sendAppointmentReminder($appointment);
        if ($ok) {
            $appointment->forceFill(['reminder_sent_at' => now()])->saveQuietly();
        }

        return back()->with(
            $ok ? 'success' : 'error',
            $ok ? 'Promemoria WhatsApp inviato.' : 'Invio non riuscito (verifica consenso paziente e configurazione WhatsApp).'
        );
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
            'reminder_channel' => ['sometimes', 'in:whatsapp,email,none'],
        ]);
    }
}
