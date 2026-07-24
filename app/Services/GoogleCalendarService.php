<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\GoogleToken;
use App\Models\Setting;
use App\Models\User;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Illuminate\Support\Facades\Log;

/**
 * Sincronizzazione agenda <-> Google Calendar (OAuth2).
 *
 * Flusso:
 *  1. Il superadmin inserisce Client ID/Secret in Impostazioni -> Integrazioni.
 *  2. L'utente collega il proprio account Google (/oauth/google/redirect).
 *  3. Salviamo access/refresh token cifrati (GoogleToken).
 *  4. Alla creazione/modifica di un appuntamento creiamo/aggiorniamo l'evento.
 */
class GoogleCalendarService
{
    private ?array $cfg = null;

    /** Credenziali dal DB (Impostazioni -> Integrazioni) con fallback sul .env. */
    public function config(): array
    {
        return $this->cfg ??= Setting::google();
    }

    /** Ricarica la configurazione (usato dopo il salvataggio dalle impostazioni). */
    public function refresh(): void
    {
        $this->cfg = null;
    }

    /** Credenziali presenti: il collegamento OAuth e possibile. */
    public function isConfigured(): bool
    {
        $c = $this->config();

        return ! empty($c['client_id']) && ! empty($c['client_secret']);
    }

    /** Integrazione attiva: credenziali presenti e interruttore acceso. */
    public function isEnabled(): bool
    {
        return (bool) ($this->config()['enabled'] ?? false) && $this->isConfigured();
    }

    /**
     * URI di callback comunicato a Google. Se non impostato esplicitamente
     * viene dedotto dalla rotta, cosi resta coerente con il dominio dell'app.
     */
    public function redirectUri(): string
    {
        return $this->config()['redirect_uri'] ?: route('google.callback');
    }

    public function calendarId(): string
    {
        return $this->config()['calendar_id'] ?: 'primary';
    }

    public function client(): GoogleClient
    {
        $c = $this->config();

        $client = new GoogleClient();
        $client->setClientId($c['client_id']);
        $client->setClientSecret($c['client_secret']);
        $client->setRedirectUri($this->redirectUri());
        $client->setScopes([GoogleCalendar::CALENDAR_EVENTS, 'email']);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client;
    }

    public function authUrl(?string $state = null): string
    {
        $client = $this->client();
        if ($state) {
            $client->setState($state);
        }

        return $client->createAuthUrl();
    }

    /**
     * Account Google da usare per la sincronizzazione: quello di chi ha creato
     * l'appuntamento se collegato, altrimenti il primo account collegato dello studio.
     */
    protected function tokenOwner(?User $preferred): ?User
    {
        if ($preferred && GoogleToken::where('user_id', $preferred->id)->exists()) {
            return $preferred;
        }

        return GoogleToken::query()->latest('updated_at')->first()?->user;
    }

    /** Costruisce un client autenticato per un utente, rinnovando il token se scaduto. */
    protected function authenticatedClient(User $user): ?GoogleClient
    {
        $token = GoogleToken::where('user_id', $user->id)->first();
        if (! $token) {
            return null;
        }

        $client = $this->client();
        $client->setAccessToken([
            'access_token' => $token->access_token,
            'refresh_token' => $token->refresh_token,
            'expires_in' => max(0, optional($token->expires_at)->diffInSeconds(now(), false) * -1),
        ]);

        if ($client->isAccessTokenExpired() && $token->refresh_token) {
            $new = $client->fetchAccessTokenWithRefreshToken($token->refresh_token);
            if (! isset($new['error'])) {
                $token->update([
                    'access_token' => $new['access_token'],
                    'expires_at' => now()->addSeconds($new['expires_in'] ?? 3600),
                ]);
            } else {
                Log::channel('audit')->error('google.token.refresh_failed', ['user_id' => $user->id]);

                return null;
            }
        }

        return $client;
    }

    /** Crea o aggiorna l'evento del calendario per l'appuntamento. */
    public function syncAppointment(Appointment $appointment): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $user = $this->tokenOwner($appointment->creator);
        if (! $user) {
            return null;
        }

        $client = $this->authenticatedClient($user);
        if (! $client) {
            return null;
        }

        $service = new GoogleCalendar($client);
        $calendarId = $this->calendarId();

        $event = new Event([
            'summary' => $appointment->title ?: ('Appuntamento - '.$appointment->patient?->full_name),
            'description' => trim($appointment->treatment.' '.$appointment->notes),
            'start' => new EventDateTime([
                'dateTime' => $appointment->starts_at->toRfc3339String(),
                'timeZone' => config('app.timezone'),
            ]),
            'end' => new EventDateTime([
                'dateTime' => $appointment->ends_at->toRfc3339String(),
                'timeZone' => config('app.timezone'),
            ]),
        ]);

        try {
            if ($appointment->google_event_id) {
                $updated = $service->events->update($calendarId, $appointment->google_event_id, $event);

                return $updated->getId();
            }

            $created = $service->events->insert($calendarId, $event);
            $appointment->forceFill(['google_event_id' => $created->getId()])->saveQuietly();

            return $created->getId();
        } catch (\Throwable $e) {
            Log::channel('audit')->error('google.calendar.sync_failed', [
                'appointment_id' => $appointment->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function deleteEvent(Appointment $appointment): void
    {
        if (! $this->isEnabled() || ! $appointment->google_event_id) {
            return;
        }

        $user = $this->tokenOwner($appointment->creator);
        if (! $user) {
            return;
        }

        $client = $this->authenticatedClient($user);
        if (! $client) {
            return;
        }

        try {
            (new GoogleCalendar($client))->events->delete($this->calendarId(), $appointment->google_event_id);
        } catch (\Throwable $e) {
            Log::channel('audit')->warning('google.calendar.delete_failed', [
                'appointment_id' => $appointment->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Verifica il collegamento leggendo il calendario configurato.
     * Ritorna [esito, messaggio] per il pulsante "Prova" delle impostazioni.
     */
    public function testConnection(User $user): array
    {
        if (! $this->isConfigured()) {
            return [false, 'Inserisci prima Client ID e Client Secret.'];
        }

        $owner = $this->tokenOwner($user);
        if (! $owner) {
            return [false, 'Nessun account Google collegato: usa "Collega account".'];
        }

        $client = $this->authenticatedClient($owner);
        if (! $client) {
            return [false, 'Token non valido o scaduto: ricollega l\'account Google.'];
        }

        try {
            $calendar = (new GoogleCalendar($client))->calendars->get($this->calendarId());

            return [true, 'Collegamento riuscito · calendario "'.$calendar->getSummary().'".'];
        } catch (\Throwable $e) {
            return [false, 'Google ha risposto: '.$e->getMessage()];
        }
    }
}
