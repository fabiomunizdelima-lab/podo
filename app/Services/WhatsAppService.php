<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Invio promemoria via WhatsApp Business Cloud API (Meta).
 *
 * Richiede un template di messaggio approvato da Meta, es. "promemoria_appuntamento"
 * con parametri: {{1}} nome paziente, {{2}} data/ora appuntamento.
 *
 * Le credenziali si impostano da Impostazioni -> Integrazioni (cifrate in DB);
 * in mancanza valgono quelle del .env.
 *
 * Documentazione: https://developers.facebook.com/docs/whatsapp/cloud-api
 */
class WhatsAppService
{
    private ?array $cfg = null;

    public function config(): array
    {
        return $this->cfg ??= Setting::whatsapp();
    }

    public function refresh(): void
    {
        $this->cfg = null;
    }

    /** Credenziali presenti (a prescindere dall'interruttore). */
    public function isConfigured(): bool
    {
        $c = $this->config();

        return ! empty($c['phone_number_id']) && ! empty($c['access_token']);
    }

    public function isEnabled(): bool
    {
        return (bool) ($this->config()['enabled'] ?? false) && $this->isConfigured();
    }

    /**
     * Invia il promemoria per un appuntamento usando il template approvato.
     * Rispetta il consenso WhatsApp del paziente (GDPR).
     * Ritorna [esito, messaggio] per poterlo mostrare all'operatore.
     */
    public function sendAppointmentReminder(Appointment $appointment): array
    {
        $patient = $appointment->patient;

        if (! $patient || ! $patient->consent_whatsapp) {
            Log::channel('audit')->info('whatsapp.skip.no_consent', [
                'appointment_id' => $appointment->id,
                'patient_id' => $patient?->id,
            ]);

            return [false, 'Il paziente non ha dato il consenso ai messaggi WhatsApp.'];
        }

        $to = $patient->whatsappE164();
        if (! $to) {
            return [false, 'Il paziente non ha un numero WhatsApp valido.'];
        }

        if (! $this->isEnabled()) {
            Log::channel('audit')->warning('whatsapp.disabled', ['appointment_id' => $appointment->id]);

            return [false, 'Integrazione WhatsApp non attiva: configurala in Impostazioni → Integrazioni.'];
        }

        $when = $appointment->starts_at->timezone(config('app.timezone'))->format('d/m/Y \a\l\l\e H:i');

        return $this->sendTemplate($to, [
            $patient->first_name ?: $patient->full_name,
            $when,
        ], $appointment);
    }

    /**
     * Invio di prova dalle impostazioni: stesso template, destinatario libero.
     *
     * @return array{0:bool,1:string}
     */
    public function sendTestMessage(string $to, string $name = 'Prova'): array
    {
        if (! $this->isConfigured()) {
            return [false, 'Inserisci prima Phone Number ID e token di accesso.'];
        }

        $when = now()->timezone(config('app.timezone'))->format('d/m/Y \a\l\l\e H:i');

        return $this->sendTemplate($to, [$name, $when]);
    }

    /**
     * @param  array<int,string>  $params  Parametri posizionali del body del template.
     * @return array{0:bool,1:string}
     */
    protected function sendTemplate(string $to, array $params, ?Appointment $appointment = null): array
    {
        $c = $this->config();

        $components = [[
            'type' => 'body',
            'parameters' => array_map(
                fn ($p) => ['type' => 'text', 'text' => (string) $p],
                $params
            ),
        ]];

        try {
            $response = Http::withToken($c['access_token'])
                ->asJson()
                ->timeout(15)
                ->post("https://graph.facebook.com/{$c['api_version']}/{$c['phone_number_id']}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'template',
                    'template' => [
                        'name' => $c['template_name'],
                        'language' => ['code' => $c['template_lang']],
                        'components' => $components,
                    ],
                ]);

            if ($response->successful()) {
                Log::channel('audit')->info('whatsapp.sent', [
                    'appointment_id' => $appointment?->id,
                    'wamid' => $response->json('messages.0.id'),
                ]);

                return [true, 'Messaggio WhatsApp inviato.'];
            }

            Log::channel('audit')->error('whatsapp.error', [
                'appointment_id' => $appointment?->id,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return [false, 'Meta ha risposto '.$response->status().': '.($response->json('error.message') ?: 'errore sconosciuto')];
        } catch (\Throwable $e) {
            Log::channel('audit')->error('whatsapp.exception', [
                'appointment_id' => $appointment?->id,
                'message' => $e->getMessage(),
            ]);

            return [false, 'Invio non riuscito: '.$e->getMessage()];
        }
    }
}
