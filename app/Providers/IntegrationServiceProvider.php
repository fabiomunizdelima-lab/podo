<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

/**
 * Applica a runtime le impostazioni delle integrazioni salvate in DB
 * (Impostazioni -> Integrazioni), cosi da non dover toccare il .env.
 *
 * Il mailer viene riconfigurato solo quando serve davvero: la lettura del DB
 * avviene alla prima risoluzione del "mail.manager", non a ogni richiesta.
 */
class IntegrationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->resolving('mail.manager', fn () => $this->configureMailer());

        $this->localizeResetPasswordMail();
    }

    /** Sovrascrive la configurazione SMTP con quella salvata in DB. */
    protected function configureMailer(): void
    {
        try {
            $mail = Setting::mail();
        } catch (\Throwable $e) {
            // DB non raggiungibile (es. durante le migrazioni iniziali): resta il .env
            return;
        }

        if (empty($mail['host'])) {
            return;
        }

        // "ssl" = TLS implicito (porta 465), "tls" = STARTTLS (porta 587),
        // negoziato in automatico da Symfony Mailer sullo schema smtp.
        $scheme = ($mail['encryption'] ?? 'tls') === 'ssl' ? 'smtps' : 'smtp';

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.scheme' => $scheme,
            'mail.mailers.smtp.host' => $mail['host'],
            'mail.mailers.smtp.port' => $mail['port'],
            'mail.mailers.smtp.username' => $mail['username'] !== '' ? $mail['username'] : null,
            'mail.mailers.smtp.password' => $mail['password'] !== '' ? $mail['password'] : null,
            'mail.mailers.smtp.encryption' => $mail['encryption'] === 'none' ? null : $mail['encryption'],
            'mail.from.address' => $mail['from_address'],
            'mail.from.name' => $mail['from_name'],
        ]);
    }

    /** Email di reimpostazione password in italiano, con il nome dello studio. */
    protected function localizeResetPasswordMail(): void
    {
        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);

            $minuti = (int) config('auth.passwords.users.expire', 60);

            return (new MailMessage)
                ->subject('Reimposta la password · '.config('app.name'))
                ->greeting('Ciao '.($notifiable->name ?: '').',')
                ->line('Abbiamo ricevuto una richiesta di reimpostazione della password per il tuo account.')
                ->action('Reimposta la password', $url)
                ->line("Il collegamento scade tra {$minuti} minuti e puo essere usato una sola volta.")
                ->line('Se non hai richiesto tu la reimpostazione, ignora questa email: la password resta invariata.')
                ->salutation('— '.config('app.name'));
        });
    }
}
