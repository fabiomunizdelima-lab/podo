<?php

use App\Http\Middleware\EnforceMfa;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Sicurezza: header di sicurezza + CSP su ogni risposta web
        $middleware->web(append: [
            SecurityHeaders::class,
        ]);

        // Forza HTTPS in produzione (checklist: TLS)
        $middleware->trustProxies(at: '*');

        // Alias middleware per RBAC e MFA
        $middleware->alias([
            'role' => EnsureRole::class,
            'mfa'  => EnforceMfa::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
