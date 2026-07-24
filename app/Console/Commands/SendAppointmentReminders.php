<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Setting;
use App\Services\AppointmentReminderService;
use Illuminate\Console\Command;

/**
 * Invia i promemoria degli appuntamenti imminenti sul canale scelto
 * per ciascun appuntamento (WhatsApp o Email).
 * Schedulato ogni 15 minuti (vedi routes/console.php).
 */
class SendAppointmentReminders extends Command
{
    protected $signature = 'podo:send-reminders {--hours= : Ore prima dell\'appuntamento}';

    protected $description = 'Invia i promemoria degli appuntamenti imminenti (WhatsApp o email)';

    public function handle(AppointmentReminderService $reminders): int
    {
        $whatsapp = Setting::whatsapp();
        $mail = Setting::mail();

        $canali = array_keys(array_filter([
            'whatsapp' => $whatsapp['enabled'] ?? false,
            'email' => $mail['enabled'] ?? false,
        ]));

        if (! $canali) {
            $this->warn('Nessun canale di notifica attivo: nessun promemoria inviato.');

            return self::SUCCESS;
        }

        $hours = (int) ($this->option('hours') ?: ($whatsapp['reminder_hours_before'] ?? 24));
        $target = now()->addHours($hours);

        // Appuntamenti nella finestra [target-15min, target] non ancora avvisati
        $appointments = Appointment::query()
            ->whereNull('reminder_sent_at')
            ->whereIn('reminder_channel', $canali)
            ->whereIn('status', [AppointmentStatus::SCHEDULED->value, AppointmentStatus::CONFIRMED->value])
            ->whereBetween('starts_at', [$target->copy()->subMinutes(15), $target])
            ->with('patient')
            ->get();

        $sent = 0;
        foreach ($appointments as $appointment) {
            [$ok, $message] = $reminders->send($appointment);
            if ($ok) {
                $sent++;
            } else {
                $this->line("· #{$appointment->id}: {$message}");
            }
        }

        $this->info("Promemoria inviati: {$sent} su {$appointments->count()} appuntamenti idonei.");

        return self::SUCCESS;
    }
}
