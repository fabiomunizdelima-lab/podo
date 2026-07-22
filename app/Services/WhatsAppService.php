<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Invio promemoria via WhatsApp Business Cloud API (Meta).
 *
 * Richiede un template di messaggio approvato da Meta, es. "promemoria_appuntamento"
 * con parametri: {{1}} nome paziente, {{2}} data/ora appuntamento.
 *
 * Documentazione: https://developers.facebook.com/docs/whatsapp/cloud-api
 */
class WhatsAppService
{
    public function isEnabled(): bool
    {
        return (bool) config('podo.whatsapp.enabled')
            && config('podo.whatsapp.phone_number_id')
            && config('podo.whatsapp.access_token');
    }

    /**
     * Invia il promemoria per un appuntamento usando il template approvato.
     * Rispetta il consenso WhatsApp del paziente (GDPR).
     */
    public function sendAppointmentReminder(Appointment $appointment): bool
    {
        $patient = $appointment->patient;

        if (! $patient || ! $patient->consent_whatsapp) {
            Log::channel('audit')->info('whatsapp.skip.no_consent', [
                'appointment_id' => $appointment->id,
                'patient_id' => $patient?->id,
            ]);
            return false;
        }

        $to = $patient->whatsappE164();
        if (! $to) {
            return false;
        }

        if (! $this->isEnabled()) {
            Log::channel('audit')->warning('whatsapp.disabled', ['appointment_id' => $appointment->id]);
            return false;
        }

        $when = $appointment->starts_at->timezone(config('app.timezone'))->format('d/m/Y \a\l\l\e H:i');

        return $this->sendTemplate($to, [
            $patient->first_name ?: $patient->full_name,
            $when,
        ], $appointment);
    }

    /**
     * @param array<int,string> $params Parametri posizionali del body del template.
     */
    protected function sendTemplate(string $to, array $params, ?Appointment $appointment = null): bool
    {
        $version = config('podo.whatsapp.api_version');
        $phoneId = config('podo.whatsapp.phone_number_id');
        $token = config('podo.whatsapp.access_token');

        $components = [[
            'type' => 'body',
            'parameters' => array_map(
                fn ($p) => ['type' => 'text', 'text' => (string) $p],
                $params
            ),
        ]];

        try {
            $response = Http::withToken($token)
                ->asJson()
                ->timeout(15)
                ->post("https://graph.facebook.com/{$version}/{$phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'template',
                    'template' => [
                        'name' => config('podo.whatsapp.template_name'),
                        'language' => ['code' => config('podo.whatsapp.template_lang')],
                        'components' => $components,
                    ],
                ]);

            if ($response->successful()) {
                Log::channel('audit')->info('whatsapp.sent', [
                    'appointment_id' => $appointment?->id,
                    'wamid' => $response->json('messages.0.id'),
                ]);
                return true;
            }

            Log::channel('audit')->error('whatsapp.error', [
                'appointment_id' => $appointment?->id,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('whatsapp.exception', [
                'appointment_id' => $appointment?->id,
                'message' => $e->getMessage(),
            ]);
        }

        return false;
    }
}
