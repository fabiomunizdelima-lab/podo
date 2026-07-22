<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Solo utenti attivi possono autenticarsi
        $credentials['is_active'] = true;

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            Log::channel('audit')->warning('auth.login.failed', [
                'email' => $request->input('email'),
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'email' => __('Credenziali non valide.'),
            ]);
        }

        $request->session()->regenerate();
        // Reset stato MFA all'inizio di una nuova sessione
        $request->session()->forget('mfa_passed');

        $user = $request->user();
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->saveQuietly();

        Log::channel('audit')->info('auth.login.success', [
            'user_id' => $user->id,
            'role' => $user->role?->value,
            'ip' => $request->ip(),
        ]);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $userId = $request->user()?->id;

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::channel('audit')->info('auth.logout', ['user_id' => $userId]);

        return redirect()->route('login');
    }
}
