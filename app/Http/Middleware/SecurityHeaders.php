<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Attach a baseline set of HTTP security headers to every web response.
     *
     * The Content-Security-Policy is tuned for this stack (Livewire + Alpine.js
     * + Tailwind + Google Fonts). Alpine/Livewire evaluate expressions at
     * runtime, so 'unsafe-inline' and 'unsafe-eval' are required for scripts.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Baseline hardening headers — safe in every environment.
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // HSTS is only meaningful (and only valid) over HTTPS.
        if ($request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // Content-Security-Policy (can be toggled off via env if needed).
        if (config('app.security_headers_csp', true)
            && ! $response->headers->has('Content-Security-Policy')) {
            $response->headers->set(
                'Content-Security-Policy',
                $this->contentSecurityPolicy()
            );
        }

        return $response;
    }

    /**
     * Build the Content-Security-Policy header value.
     */
    protected function contentSecurityPolicy(): string
    {
        $viteOrigins = app()->isProduction()
            ? []
            : [
                'http://localhost:5173',
                'http://127.0.0.1:5173',
            ];

        $scriptSrc = trim("'self' 'unsafe-inline' 'unsafe-eval' https://fonts.googleapis.com ".implode(' ', $viteOrigins));
        $styleSrc = trim("'self' 'unsafe-inline' https://fonts.googleapis.com ".implode(' ', $viteOrigins));
        $connectSrc = trim("'self'".(app()->isProduction() ? '' : ' ws: wss: http://localhost:5173 http://127.0.0.1:5173'));

        return collect([
            "default-src 'self'",
            "script-src {$scriptSrc}",
            "style-src {$styleSrc}",
            "font-src 'self' data: https://fonts.gstatic.com",
            "img-src 'self' data: blob: https:",
            "media-src 'self' data: blob: https:",
            "connect-src {$connectSrc}",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
        ])->implode('; ');
    }
}
