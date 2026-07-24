<?php

namespace App\Services;

use App\Mail\AppointmentReminderMail;
use App\Models\Appointment;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Smista il promemoria dell'appuntamento sul canale scelto sul form agenda
 * (WhatsApp o Email). Unico punto di verita per invio manuale e schedulato.
 */
class AppointmentReminderService
{
    public function __construct(private WhatsAppService $whatsapp) {}

    /** Canali selezionabili nell'interfaccia. */
    public const CHANNELS = ['whatsapp', 'email', 'none'];

    /**
     * Invia il promemoria e, se riuscito, marca l'appuntamento.
     *
     * @return array{0:bool,1:string} esito e messaggio per l'operatore
     */
    public function send(Appointment $appointment): array
    {
        [$ok, $message] = match ($appointment->reminder_channel) {
            'whatsapp' => $this->whatsapp->sendAppointmentReminder($appointment),
            'email' => $this->viaEmail($appointment),
            default => [false, 'Per questo appuntamento non e previsto alcun promemoria.'],
        };

        if ($ok) {
            $appointment->forceFill(['reminder_sent_at' => now()])->saveQuietly();
        }

        return [$ok, $message];
    }

    /**
     * Promemoria via email. E una comunicazione di servizio legata alla
     * prestazione richiesta (art. 6.1.b GDPR): non richiede consenso marketing,
     * ma serve un indirizzo email in anagrafica.
     *
     * @return array{0:bool,1:string}
     */
    protected function viaEmail(Appointment $appointment): array
    {
        $patient = $appointment->patient;

        if (! $patient?->email) {
            return [false, 'Il paziente non ha un indirizzo email in anagrafica.'];
        }

        $mail = Setting::mail();
        if (! ($mail['enabled'] ?? false) || empty($mail['host'])) {
            return [false, 'Invio email non attivo: configuralo in Impostazioni → Integrazioni.'];
        }

        try {
            Mail::to($patient->email)->send(new AppointmentReminderMail($appointment));

            Log::channel('audit')->info('mail.reminder.sent', [
                'appointment_id' => $appointment->id,
                'patient_id' => $patient->id,
            ]);

            return [true, 'Promemoria inviato via email a '.$patient->email.'.'];
        } catch (\Throwable $e) {
            Log::channel('audit')->error('mail.reminder.failed', [
                'appointment_id' => $appointment->id,
                'message' => $e->getMessage(),
            ]);

            return [false, 'Invio email non riuscito: '.$e->getMessage()];
        }
    }
}
