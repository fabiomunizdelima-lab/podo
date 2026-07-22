<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Impone l'MFA per gli utenti privilegiati (admin/superadmin).
 * - Se l'MFA non è ancora configurata -> redirect al setup.
 * - Se configurata ma non verificata nella sessione -> redirect alla challenge.
 * Checklist: MFA, Session Management.
 */
class EnforceMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->requiresMfa()) {
            if (! $user->hasMfaEnabled()) {
                return redirect()
                    ->route('mfa.setup')
                    ->with('warning', 'Per il tuo ruolo è obbligatorio attivare l\'autenticazione a due fattori.');
            }

            if (! $request->session()->get('mfa_passed', false)) {
                return redirect()->route('mfa.challenge');
            }
        }

        return $next($request);
    }
}
