<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Forza HTTPS in produzione (checklist: TLS 1.3)
        if (config('podo.force_https')) {
            URL::forceScheme('https');
            $this->app['request']->server->set('HTTPS', 'on');
        }

        // Password policy robusta di default (checklist: Password Policy)
        Password::defaults(function () {
            return Password::min(config('podo.security.password_min_length', 12))
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised();
        });

        // Rate limiting login (checklist: Rate Limiting, protezione brute force)
        RateLimiter::for('login', function (Request $request) {
            $key = strtolower((string) $request->input('email')).'|'.$request->ip();
            return [
                Limit::perMinute(config('podo.security.login_rate_limit', 5))->by($key),
            ];
        });

        // Rate limiting generico per le API
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
