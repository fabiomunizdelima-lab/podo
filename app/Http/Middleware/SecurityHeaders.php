<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header di sicurezza HTTP su ogni risposta.
 * Checklist: Content Security Policy, protezione XSS/CSRF, hardening.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Content Security Policy: nonce per gli script inline.
        // Va generato PRIMA di $next(): le viste lo leggono mentre vengono renderizzate,
        // altrimenti resterebbe vuoto e il browser bloccherebbe ogni <script> inline.
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $nonce);

        /** @var Response $response */
        $response = $next($request);

        $directives = [
            "default-src 'self'",
            "base-uri 'self'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "style-src 'self' 'unsafe-inline'",
            // NOTA: 'unsafe-eval' è richiesto da Alpine.js (interattività UI).
            // Hardening TODO: migrare alla build CSP di Alpine per rimuoverlo (vedi docs/SICUREZZA.md).
            "script-src 'self' 'unsafe-eval' 'nonce-{$nonce}'",
            "connect-src 'self'",
            "object-src 'none'",
        ];

        // Forza l'upgrade a HTTPS solo quando la connessione è già sicura
        // (es. dietro reverse proxy TLS). In HTTP puro romperebbe la navigazione.
        if ($request->isSecure()) {
            $directives[] = 'upgrade-insecure-requests';
        }

        $csp = implode('; ', $directives);

        $headers = [
            'Content-Security-Policy' => $csp,
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=(), payment=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cross-Origin-Resource-Policy' => 'same-origin',
            'X-Permitted-Cross-Domain-Policies' => 'none',
        ];

        // HSTS solo su HTTPS (checklist: TLS 1.3, forza HTTPS)
        if ($request->isSecure()) {
            $headers['Strict-Transport-Security'] = 'max-age=63072000; includeSubDomains; preload';
        }

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value, false);
        }

        // Rimuove header che rivelano lo stack
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
