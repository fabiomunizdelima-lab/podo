<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PragmaRX\Google2FA\Google2FA;

/**
 * Setup e verifica MFA (TOTP - Google Authenticator, Authy, ecc.).
 * Checklist: MFA.
 */
class MfaController extends Controller
{
    public function __construct(private Google2FA $google2fa) {}

    /** Pagina di attivazione MFA: mostra QR code e chiede il primo codice. */
    public function setup(Request $request)
    {
        $user = $request->user();

        if ($user->hasMfaEnabled()) {
            return redirect()->route('dashboard');
        }

        // Genera (o riusa) un segreto temporaneo in sessione
        $secret = $request->session()->get('mfa_setup_secret')
            ?: $this->google2fa->generateSecretKey();
        $request->session()->put('mfa_setup_secret', $secret);

        $otpauthUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        $renderer = new ImageRenderer(new RendererStyle(220), new SvgImageBackEnd());
        $qrSvg = (new Writer($renderer))->writeString($otpauthUrl);

        return view('auth.mfa-setup', compact('secret', 'qrSvg'));
    }

    public function confirm(Request $request)
    {
        $request->validate(['code' => ['required', 'string']]);
        $user = $request->user();
        $secret = $request->session()->get('mfa_setup_secret');

        if (! $secret || ! $this->google2fa->verifyKey($secret, $request->input('code'))) {
            return back()->withErrors(['code' => 'Codice non valido. Riprova.']);
        }

        // Genera recovery codes
        $recovery = collect(range(1, 8))
            ->map(fn () => strtoupper(bin2hex(random_bytes(4))))
            ->all();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recovery,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->forget('mfa_setup_secret');
        $request->session()->put('mfa_passed', true);

        Log::channel('audit')->info('mfa.enabled', ['user_id' => $user->id]);

        return redirect()->route('dashboard')
            ->with('recovery_codes', $recovery)
            ->with('success', 'MFA attivata. Conserva i codici di recupero in un luogo sicuro.');
    }

    /** Challenge: richiesta del codice ad ogni sessione per utenti privilegiati. */
    public function challenge()
    {
        return view('auth.mfa-challenge');
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => ['required', 'string']]);
        $user = $request->user();

        $ok = $this->google2fa->verifyKey($user->two_factor_secret, $request->input('code'));

        // Fallback: recovery code monouso
        if (! $ok) {
            $codes = collect($user->two_factor_recovery_codes ?? []);
            $submitted = strtoupper(trim($request->input('code')));
            if ($codes->contains($submitted)) {
                $user->forceFill([
                    'two_factor_recovery_codes' => $codes->reject(fn ($c) => $c === $submitted)->values()->all(),
                ])->save();
                $ok = true;
            }
        }

        if (! $ok) {
            Log::channel('audit')->warning('mfa.failed', ['user_id' => $user->id, 'ip' => $request->ip()]);
            return back()->withErrors(['code' => 'Codice non valido.']);
        }

        $request->session()->put('mfa_passed', true);
        Log::channel('audit')->info('mfa.passed', ['user_id' => $user->id]);

        return redirect()->intended(route('dashboard'));
    }
}
