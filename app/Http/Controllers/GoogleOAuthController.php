<?php

namespace App\Http\Controllers;

use App\Models\GoogleToken;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Collega l'account Google dell'operatore per la sincronizzazione del calendario.
 * Le credenziali OAuth si impostano in Impostazioni -> Integrazioni.
 */
class GoogleOAuthController extends Controller
{
    public function __construct(private GoogleCalendarService $calendar) {}

    public function redirect(Request $request)
    {
        if (! $this->calendar->isConfigured()) {
            return redirect()->route('integrations.edit')
                ->with('error', 'Inserisci prima Client ID e Client Secret di Google.');
        }

        // Parametro "state" anti-CSRF: verificato al ritorno da Google
        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);

        return redirect()->away($this->calendar->authUrl($state));
    }

    public function callback(Request $request)
    {
        $atteso = $request->session()->pull('google_oauth_state');

        if ($request->has('error') || ! $request->filled('code')) {
            return $this->tornaAlleIntegrazioni('error', 'Autorizzazione Google annullata.');
        }

        if (! $atteso || ! hash_equals($atteso, (string) $request->input('state'))) {
            Log::channel('audit')->warning('google.oauth.state_mismatch', ['user_id' => $request->user()->id]);

            return $this->tornaAlleIntegrazioni('error', 'Richiesta non valida: riprova il collegamento.');
        }

        $client = $this->calendar->client();
        $token = $client->fetchAccessTokenWithAuthCode($request->input('code'));

        if (isset($token['error'])) {
            Log::channel('audit')->error('google.oauth.error', ['error' => $token['error']]);

            return $this->tornaAlleIntegrazioni('error', 'Errore durante il collegamento a Google: '.$token['error']);
        }

        $esistente = GoogleToken::where('user_id', $request->user()->id)->first();

        GoogleToken::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'account_email' => $this->emailDelToken($token),
                'access_token' => $token['access_token'],
                // Google invia il refresh token solo al primo consenso: non sovrascriverlo con null
                'refresh_token' => $token['refresh_token'] ?? $esistente?->refresh_token,
                'expires_at' => now()->addSeconds($token['expires_in'] ?? 3600),
                'scope' => $token['scope'] ?? null,
            ]
        );

        Log::channel('audit')->info('google.oauth.linked', ['user_id' => $request->user()->id]);

        return $this->tornaAlleIntegrazioni('success', 'Google Calendar collegato con successo.');
    }

    /** Revoca il collegamento locale (i token restano validi finché non revocati anche su Google). */
    public function disconnect(Request $request)
    {
        GoogleToken::where('user_id', $request->user()->id)->delete();

        Log::channel('audit')->info('google.oauth.unlinked', ['user_id' => $request->user()->id]);

        return $this->tornaAlleIntegrazioni('success', 'Account Google scollegato.');
    }

    /**
     * L'indirizzo dell'account e nel payload dell'id_token (scope "email"):
     * lo leggiamo senza chiamate di rete aggiuntive.
     */
    private function emailDelToken(array $token): ?string
    {
        if (empty($token['id_token'])) {
            return null;
        }

        $parti = explode('.', $token['id_token']);
        if (count($parti) < 2) {
            return null;
        }

        $payload = json_decode(base64_decode(strtr($parti[1], '-_', '+/')) ?: '', true);

        return is_array($payload) ? ($payload['email'] ?? null) : null;
    }

    private function tornaAlleIntegrazioni(string $tipo, string $messaggio)
    {
        $rotta = request()->user()?->isSuperAdmin() ? 'integrations.edit' : 'dashboard';

        return redirect()->route($rotta)->with($tipo, $messaggio);
    }
}
