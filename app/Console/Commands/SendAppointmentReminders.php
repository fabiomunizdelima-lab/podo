<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Enums\AppointmentStatus;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

/**
 * Invia i promemoria WhatsApp per gli appuntamenti imminenti.
 * Schedulato ogni 15 minuti (vedi routes/console.php).
 */
class SendAppointmentReminders extends Command
{
    protected $signature = 'podo:send-reminders {--hours= : Ore prima dell\'appuntamento}';

    protected $description = 'Invia i promemoria WhatsApp per gli appuntamenti imminenti';

    public function handle(WhatsAppService $whatsapp): int
    {
        if (! $whatsapp->isEnabled()) {
            $this->warn('WhatsApp non configurato: nessun promemoria inviato.');
            return self::SUCCESS;
        }

        $hours = (int) ($this->option('hours') ?: config('podo.whatsapp.reminder_hours_before', 24));
        $target = now()->addHours($hours);

        // Appuntamenti nella finestra [target-15min, target] non ancora avvisati
        $appointments = Appointment::query()
            ->whereNull('reminder_sent_at')
            ->where('reminder_channel', 'whatsapp')
            ->whereIn('status', [AppointmentStatus::SCHEDULED->value, AppointmentStatus::CONFIRMED->value])
            ->whereBetween('starts_at', [$target->copy()->subMinutes(15), $target])
            ->with('patient')
            ->get();

        $sent = 0;
        foreach ($appointments as $appointment) {
            if ($whatsapp->sendAppointmentReminder($appointment)) {
                $appointment->forceFill(['reminder_sent_at' => now()])->saveQuietly();
                $sent++;
            }
        }

        $this->info("Promemoria inviati: {$sent} su {$appointments->count()} appuntamenti idonei.");

        return self::SUCCESS;
    }
}
