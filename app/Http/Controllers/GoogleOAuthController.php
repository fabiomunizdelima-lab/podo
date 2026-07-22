<?php

namespace App\Http\Controllers;

use App\Models\GoogleToken;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Collega l'account Google dell'operatore per la sincronizzazione del calendario.
 */
class GoogleOAuthController extends Controller
{
    public function __construct(private GoogleCalendarService $calendar) {}

    public function redirect()
    {
        if (! $this->calendar->isEnabled()) {
            return back()->with('error', 'Integrazione Google Calendar non configurata.');
        }

        return redirect()->away($this->calendar->authUrl());
    }

    public function callback(Request $request)
    {
        if ($request->has('error') || ! $request->has('code')) {
            return redirect()->route('dashboard')->with('error', 'Autorizzazione Google annullata.');
        }

        $client = $this->calendar->client();
        $token = $client->fetchAccessTokenWithAuthCode($request->input('code'));

        if (isset($token['error'])) {
            Log::channel('audit')->error('google.oauth.error', ['error' => $token['error']]);
            return redirect()->route('dashboard')->with('error', 'Errore durante il collegamento a Google.');
        }

        GoogleToken::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? null,
                'expires_at' => now()->addSeconds($token['expires_in'] ?? 3600),
                'scope' => $token['scope'] ?? null,
            ]
        );

        Log::channel('audit')->info('google.oauth.linked', ['user_id' => $request->user()->id]);

        return redirect()->route('dashboard')->with('success', 'Google Calendar collegato con successo.');
    }
}
