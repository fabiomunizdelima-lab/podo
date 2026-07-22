<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\GoogleToken;
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
 *  1. L'utente collega il proprio account Google (/oauth/google/redirect).
 *  2. Salviamo access/refresh token cifrati (GoogleToken).
 *  3. Alla creazione/modifica di un appuntamento creiamo/aggiorniamo l'evento.
 */
class GoogleCalendarService
{
    public function isEnabled(): bool
    {
        return (bool) config('podo.google_calendar.enabled')
            && config('podo.google_calendar.client_id')
            && config('podo.google_calendar.client_secret');
    }

    public function client(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId(config('podo.google_calendar.client_id'));
        $client->setClientSecret(config('podo.google_calendar.client_secret'));
        $client->setRedirectUri(config('podo.google_calendar.redirect_uri'));
        $client->setScopes([GoogleCalendar::CALENDAR_EVENTS]);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client;
    }

    public function authUrl(): string
    {
        return $this->client()->createAuthUrl();
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

        $user = $appointment->creator ?? User::whereNotNull('id')->first();
        if (! $user) {
            return null;
        }

        $client = $this->authenticatedClient($user);
        if (! $client) {
            return null;
        }

        $service = new GoogleCalendar($client);
        $calendarId = config('podo.google_calendar.calendar_id');

        $event = new Event([
            'summary' => $appointment->title ?: ('Appuntamento - '.$appointment->patient?->full_name),
            'description' => $appointment->treatment.' '.$appointment->notes,
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

        $user = $appointment->creator;
        if (! $user) {
            return;
        }

        $client = $this->authenticatedClient($user);
        if (! $client) {
            return;
        }

        try {
            (new GoogleCalendar($client))->events->delete(
                config('podo.google_calendar.calendar_id'),
                $appointment->google_event_id
            );
        } catch (\Throwable $e) {
            Log::channel('audit')->warning('google.calendar.delete_failed', [
                'appointment_id' => $appointment->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
