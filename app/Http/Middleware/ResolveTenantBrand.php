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
 */
class ResolveTenantBrand
{
    public function handle(Request $request, Closure $next): Response
    {
        $brand = null;

        if (config('platform.white_label_enabled', true)) {
            $brand = TenantBrand::resolveFromHost(
                $request->getHost(),
                (string) config('platform.root_domain', 'riwajgrand.pk')
            );
        }

        View::share('tenantBrand', $brand);

        // Also expose it to Livewire components.
        view()->share('tenantBrand', $brand);

        return $next($request);
    }
}
