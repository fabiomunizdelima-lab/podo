<?php

namespace App\Http\Controllers;

use App\Models\GoogleToken;
use App\Models\Setting;
use App\Services\GoogleCalendarService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Impostazioni -> Integrazioni: Google Calendar, email (SMTP) e WhatsApp.
 *
 * Le credenziali vivono nella tabella settings (segreti cifrati con Setting::set(..., true))
 * cosi si configurano dall'interfaccia senza toccare il .env ne ricreare i container.
 * Riservato al superadmin (vedi routes/web.php).
 */
class IntegrationsController extends Controller
{
    /** Chiavi il cui valore non torna mai al browser. */
    private const SECRETS = [
        'google.client_secret',
        'mail.password',
        'whatsapp.access_token',
    ];

    public function edit(GoogleCalendarService $calendar)
    {
        $token = GoogleToken::where('user_id', request()->user()->id)->first();

        return view('settings.integrations', [
            'google' => $this->masked(Setting::google(), 'google'),
            'mail' => $this->masked(Setting::mail(), 'mail'),
            'whatsapp' => $this->masked(Setting::whatsapp(), 'whatsapp'),
            'googleRedirectUri' => $calendar->redirectUri(),
            'googleToken' => $token,
        ]);
    }

    public function updateGoogle(Request $request, GoogleCalendarService $calendar)
    {
        $data = $request->validate([
            'client_id' => ['nullable', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:255'],
            'calendar_id' => ['nullable', 'string', 'max:255'],
            'redirect_uri' => ['nullable', 'url', 'max:255'],
        ]);

        Setting::set('google.client_id', $data['client_id'] ?? null);
        $this->setSecret('google.client_secret', $data['client_secret'] ?? null);
        Setting::set('google.calendar_id', $data['calendar_id'] ?? null);
        Setting::set('google.redirect_uri', $data['redirect_uri'] ?? null);
        Setting::set('google.enabled', $request->boolean('enabled') ? '1' : '0');

        $calendar->refresh();
        $this->audit('google');

        return redirect()->route('integrations.edit')->with('success', 'Impostazioni Google Calendar salvate.');
    }

    public function updateMail(Request $request)
    {
        $data = $request->validate([
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'encryption' => ['required', 'in:tls,ssl,none'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'from_address' => ['nullable', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:150'],
        ]);

        foreach (['host', 'port', 'encryption', 'username', 'from_address', 'from_name'] as $k) {
            Setting::set('mail.'.$k, $data[$k] ?? null);
        }
        $this->setSecret('mail.password', $data['password'] ?? null);
        Setting::set('mail.enabled', $request->boolean('enabled') ? '1' : '0');

        $this->audit('mail');

        return redirect()->route('integrations.edit')->with('success', 'Impostazioni email salvate.');
    }

    public function updateWhatsapp(Request $request, WhatsAppService $whatsapp)
    {
        $data = $request->validate([
            'phone_number_id' => ['nullable', 'string', 'max:100'],
            'access_token' => ['nullable', 'string', 'max:500'],
            'template_name' => ['nullable', 'string', 'max:100'],
            'template_lang' => ['nullable', 'string', 'max:10'],
            'api_version' => ['nullable', 'string', 'max:10'],
            'reminder_hours_before' => ['nullable', 'integer', 'min:1', 'max:168'],
        ]);

        foreach (['phone_number_id', 'template_name', 'template_lang', 'api_version', 'reminder_hours_before'] as $k) {
            Setting::set('whatsapp.'.$k, $data[$k] ?? null);
        }
        $this->setSecret('whatsapp.access_token', $data['access_token'] ?? null);
        Setting::set('whatsapp.enabled', $request->boolean('enabled') ? '1' : '0');

        $whatsapp->refresh();
        $this->audit('whatsapp');

        return redirect()->route('integrations.edit')->with('success', 'Impostazioni WhatsApp salvate.');
    }

    /** Invio di prova SMTP con le credenziali appena salvate. */
    public function testMail(Request $request)
    {
        $data = $request->validate([
            'to' => ['required', 'email'],
        ]);

        $mail = Setting::mail();
        if (empty($mail['host'])) {
            return response()->json(['ok' => false, 'message' => 'Server SMTP non impostato.'], 422);
        }

        try {
            Mail::raw(
                "Questo e un messaggio di prova inviato da ".config('app.name').".\n".
                "Se lo stai leggendo, la configurazione SMTP funziona correttamente.",
                fn ($m) => $m->to($data['to'])->subject('Prova invio email · '.config('app.name'))
            );

            Log::channel('audit')->info('mail.test.sent', ['to' => $data['to'], 'user_id' => $request->user()->id]);

            return response()->json(['ok' => true, 'message' => 'Email di prova inviata a '.$data['to'].'.']);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('mail.test.failed', ['message' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Invio non riuscito: '.$e->getMessage()], 422);
        }
    }

    /** Invio di prova del template WhatsApp. */
    public function testWhatsapp(Request $request, WhatsAppService $whatsapp)
    {
        $data = $request->validate([
            'to' => ['required', 'string', 'regex:/^\+?[0-9]{8,15}$/'],
        ]);

        $whatsapp->refresh();
        [$ok, $message] = $whatsapp->sendTestMessage(ltrim($data['to'], '+'), $request->user()->name ?: 'Prova');

        return response()->json(['ok' => $ok, 'message' => $message], $ok ? 200 : 422);
    }

    /** Verifica del collegamento a Google Calendar. */
    public function testGoogle(Request $request, GoogleCalendarService $calendar)
    {
        $calendar->refresh();
        [$ok, $message] = $calendar->testConnection($request->user());

        return response()->json(['ok' => $ok, 'message' => $message], $ok ? 200 : 422);
    }

    /**
     * Salva un segreto cifrato. Campo lasciato vuoto = valore invariato:
     * l'interfaccia non ripropone mai il segreto in chiaro.
     */
    private function setSecret(string $key, ?string $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        Setting::set($key, $value, true);
    }

    /** Sostituisce i segreti con un segnaposto e aggiunge il flag "impostato". */
    private function masked(array $values, string $prefix): array
    {
        foreach ($values as $k => $v) {
            if (in_array($prefix.'.'.$k, self::SECRETS, true)) {
                $values[$k.'_set'] = ! empty($v);
                $values[$k] = '';
            }
        }

        return $values;
    }

    private function audit(string $gruppo): void
    {
        activity('integrazioni')
            ->causedBy(request()->user())
            ->event('updated')
            ->log('Aggiornate le impostazioni di integrazione: '.$gruppo);
    }
}
