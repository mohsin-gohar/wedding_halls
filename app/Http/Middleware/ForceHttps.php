<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    /**
     * Redirect plain HTTP traffic to HTTPS.
     *
     * Only enforced when explicitly enabled (FORCE_HTTPS=true), so that local
     * development over http://127.0.0.1 continues to work untouched. Enable it
     * in production behind a TLS-terminating proxy (Nginx / Cloudflare / LB)
     * with trusted proxies configured so isSecure() is accurate.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $forceHttps = (bool) config('app.force_https', false);

        if ($forceHttps
            && ! $request->secure()
            && ! $this->isHealthCheck($request)) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }

    /**
     * Never redirect the health-check endpoint (used by orchestrators/LBs).
     */
    protected function isHealthCheck(Request $request): bool
    {
        return $request->is('up');
    }
}
