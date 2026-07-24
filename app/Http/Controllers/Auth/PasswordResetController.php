<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

/**
 * Recupero password via email (broker standard di Laravel, tabella password_reset_tokens).
 *
 * Il link scade dopo 60 minuti ed e monouso. Le risposte non rivelano mai se
 * l'indirizzo esiste davvero (protezione contro l'enumerazione degli account).
 */
class PasswordResetController extends Controller
{
    /** Messaggio unico mostrato a prescindere dall'esito: evita l'enumerazione. */
    private const RISPOSTA_GENERICA = 'Se l\'indirizzo è associato a un account attivo, riceverai a breve un\'email con le istruzioni.';

    public function request()
    {
        return view('auth.forgot-password', [
            'mailAttiva' => (bool) Setting::mail()['enabled'],
        ]);
    }

    public function email(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        if (! Setting::mail()['enabled']) {
            throw ValidationException::withMessages([
                'email' => 'Invio email non ancora configurato: contatta l\'amministratore dello studio.',
            ]);
        }

        // Account disattivati: nessuna email, ma stessa risposta all'utente
        $utente = User::where('email', $data['email'])->first();
        if ($utente && ! $utente->is_active) {
            Log::channel('audit')->warning('password.reset.inactive', ['email' => $data['email']]);

            return back()->with('status', self::RISPOSTA_GENERICA);
        }

        $esito = Password::sendResetLink($data);

        Log::channel('audit')->info('password.reset.requested', [
            'email' => $data['email'],
            'esito' => $esito,
            'ip' => $request->ip(),
        ]);

        if ($esito === Password::RESET_THROTTLED) {
            throw ValidationException::withMessages([
                'email' => 'Hai già richiesto un collegamento da poco: controlla la casella o riprova tra qualche minuto.',
            ]);
        }

        return back()->with('status', self::RISPOSTA_GENERICA);
    }

    public function reset(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ], [], ['password' => 'password']);

        $esito = Password::reset($data, function (User $user, string $password) {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));

            Log::channel('audit')->info('password.reset.completed', ['user_id' => $user->id]);
        });

        if ($esito !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => 'Il collegamento non è più valido: richiedine uno nuovo.',
            ]);
        }

        return redirect()->route('login')->with('status', 'Password aggiornata: ora puoi accedere.');
    }
}
