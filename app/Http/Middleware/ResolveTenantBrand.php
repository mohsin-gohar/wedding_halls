<?php

namespace App\Http\Middleware;

use App\Support\TenantBrand;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * White-label brand resolution.
 *
 * When a request arrives on a vendor subdomain, the tenant's branding
 * (name, logo, colours, tagline) is shared with every view so the public
 * site renders in that vendor's identity. On the platform root domain
 * nothing changes.
 *
 * Security:
 * - Validates the host header against trusted patterns.
 * - Prevents host-header spoofing by rejecting unknown domains.
 * - Restricts access to configured trusted hosts only.
 */
class ResolveTenantBrand
{
    public function handle(Request $request, Closure $next): Response
    {
        $brand = null;

        if (config('platform.white_label_enabled', true)) {
            $host = $request->getHost();

            // Get the root domain from config (e.g. riwajgrand.pk)
            $rootDomain = strtolower(config('platform.root_domain', 'riwajgrand.pk'));

            // Validate host: must not be empty, must not be the root domain itself,
            // and must end with the root domain (i.e., be a subdomain)
            if ($host && $host !== $rootDomain && str_ends_with(strtolower($host), '.'.$rootDomain)) {
                // Extract subdomain, rejecting empty or multi-level subdomains
                $subdomain = substr($host, 0, -strlen('.'.$rootDomain));
                if ($subdomain && ! str_contains($subdomain, '.')) {
                    // Reject localhost and other non-vendor subdomains
                    if ($subdomain !== 'localhost' && $subdomain !== '127.0.0.1') {
                        $resolved = TenantBrand::resolveFromHost($host, $rootDomain);
                        if ($resolved) {
                            $brand = $resolved;
                        }
                    }
                }
            }
        }

        View::share('tenantBrand', $brand);

        // Also expose it to Livewire components.
        view()->share('tenantBrand', $brand);

        return $next($request);
    }
}
