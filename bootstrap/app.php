<?php

use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsurePortalRole;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureTenantStaff;
use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);

        // Enforce HTTPS redirects early (production only via FORCE_HTTPS).
        $middleware->web(prepend: [
            ForceHttps::class,
        ]);

        // Append baseline security headers to every web response.
        $middleware->web(append: [
            SecurityHeaders::class,
            EnsureActiveAccount::class,
        ]);

        $middleware->alias([
            'tenant.staff' => EnsureTenantStaff::class,
            'portal.role' => EnsurePortalRole::class,
            'super.admin' => EnsureSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ThrottleRequestsException $e, $request) {
            $headers = $e->getHeaders();
            $retryAfter = (int) ($headers['Retry-After'] ?? 60);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Aap website ko bar bar reload nahi kar saktay. Baraye meherbani thori der intezar karein.',
                    'retry_after' => $retryAfter,
                ], 429, $headers);
            }

            return response()->view('errors.429', [
                'retryAfter' => $retryAfter,
                'exception' => $e,
            ], 429, $headers);
        });
    })->create();
