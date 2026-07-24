<?php

namespace App\Mail;

use App\Models\Appointment;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Promemoria appuntamento via email.
 *
 * Contiene solo data, ora e prestazione: nessun dato clinico
 * (l'email non e un canale cifrato end-to-end).
 */
class AppointmentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Appointment $appointment) {}

    public function envelope(): Envelope
    {
        $studio = Setting::billing()['studio_name'] ?? config('app.name');

        return new Envelope(
            subject: 'Promemoria appuntamento · '.$studio,
        );
    }

    public function content(): Content
    {
        $billing = Setting::billing();

        return new Content(
            view: 'mail.appointment-reminder',
            with: [
                'patient' => $this->appointment->patient,
                'studio' => $billing['studio_name'] ?? config('app.name'),
                'studioAddress' => trim(($billing['address'] ?? '').' '.($billing['cap'] ?? '').' '.($billing['city'] ?? '')),
            ],
        );
    }
}
