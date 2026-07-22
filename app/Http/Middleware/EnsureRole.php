<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RBAC: consente l'accesso solo se l'utente ha ALMENO il ruolo richiesto.
 * Uso nelle rotte:  ->middleware('role:admin')  oppure  'role:superadmin'
 * Checklist: RBAC, Least Privilege.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            abort(403, 'Accesso non consentito.');
        }

        $required = Role::from($role);

        if (! $user->atLeast($required)) {
            abort(403, 'Permessi insufficienti per questa operazione.');
        }

        return $next($request);
    }
}
