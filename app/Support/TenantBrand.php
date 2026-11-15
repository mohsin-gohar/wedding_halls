<?php

namespace App\Support;

use App\Models\Tenant;

/**
 * White-label brand resolution for vendor microsites.
 *
 * A vendor can publish their own branded website on their own subdomain
 * (e.g. `nawabcaterers.riwajgrand.pk`). The tenant's own colours, logo and
 * copy override the platform defaults — without forking the application.
 */
class TenantBrand
{
    public function __construct(
        public readonly Tenant $tenant,
        public readonly string $name,
        public readonly ?string $logo,
        public readonly ?string $tagline,
        public readonly string $primaryColor,
        public readonly string $secondaryColor,
        public readonly string $accentColor,
        public readonly string $fontFamily,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly ?string $address,
        public readonly ?string $coverImage,
    ) {}

    /**
     * Build the brand for a tenant from its marketplace profile and the
     * optional `website_settings` overrides.
     */
    public static function for(Tenant $tenant): self
    {
        $settings = is_array($tenant->website_settings) ? $tenant->website_settings : [];

        return new self(
            tenant: $tenant,
            name: (string) ($settings['brand_name'] ?? $tenant->name),
            logo: $tenant->logo ?: null,
            tagline: (string) ($settings['tagline'] ?? $tenant->vendorCategory?->tagline ?? ''),
            primaryColor: (string) ($settings['primary_color'] ?? '#171513'),
            secondaryColor: (string) ($settings['secondary_color'] ?? '#24211E'),
            accentColor: (string) ($settings['accent_color'] ?? $tenant->vendorCategory?->hero_color ?? '#C6A15B'),
            fontFamily: (string) ($settings['font_family'] ?? 'Cinzel'),
            phone: $tenant->phone,
            email: $tenant->email,
            address: $tenant->address,
            coverImage: $tenant->cover_image ?: null,
        );
    }

    /**
     * Resolve the tenant that owns the current subdomain.
     *
     * Returns null on the platform root domain (no subdomain) or when the
     * subdomain is unknown.
     */
    public static function resolveFromHost(?string $host, string $rootDomain): ?self
    {
        if (! $host) {
            return null;
        }

        $host = strtolower(explode(':', $host)[0]);
        $rootDomain = strtolower($rootDomain);

        if ($host === $rootDomain || ! str_ends_with($host, '.'.$rootDomain)) {
            return null;
        }

        $subdomain = substr($host, 0, -strlen('.'.$rootDomain));

        if ($subdomain === '' || str_contains($subdomain, '.')) {
            return null;
        }

        $tenant = Tenant::query()
            ->where('subdomain', $subdomain)
            ->where('is_active', true)
            ->first();

        return $tenant ? self::for($tenant) : null;
    }

    /**
     * The public URL of this vendor's own branded site.
     */
    public function siteUrl(): string
    {
        $rootDomain = (string) config('platform.root_domain', 'riwajgrand.pk');
        $subdomain = $this->tenant->subdomain;

        if (! $subdomain) {
            return route('public.vendor-profile', ['slug' => $this->tenant->slug]);
        }

        return 'https://'.$subdomain.'.'.$rootDomain.'/site';
    }
}
