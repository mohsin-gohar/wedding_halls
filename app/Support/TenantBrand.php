<?php

namespace App\Support;

use App\Models\Tenant;
use App\Services\SubscriptionService;

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
            // website_settings may be edited through admin tooling or imported
            // data. Never let arbitrary stored strings become CSS declarations.
            primaryColor: self::safeColor($settings['primary_color'] ?? null, '#171513'),
            secondaryColor: self::safeColor($settings['secondary_color'] ?? null, '#24211E'),
            accentColor: self::safeColor($settings['accent_color'] ?? $tenant->vendorCategory?->hero_color, '#C6A15B'),
            fontFamily: (string) ($settings['font_family'] ?? 'Cinzel'),
            phone: $tenant->phone,
            email: $tenant->email,
            address: $tenant->address,
            coverImage: $tenant->cover_image ?: null,
        );
    }

    private static function safeColor(mixed $value, string $fallback): string
    {
        return is_string($value) && preg_match('/\\A#[0-9a-fA-F]{6}\\z/', $value)
            ? $value
            : $fallback;
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

        if (! (bool) config('platform.white_label_enabled', true)) {
            return null;
        }

        $tenant = Tenant::query()
            ->where('subdomain', $subdomain)
            ->where('is_active', true)
            ->where('verification_status', 'APPROVED')
            ->first();

        if (! $tenant || app(SubscriptionService::class)->isLocked($tenant)) {
            return null;
        }

        return self::for($tenant);
    }

    /**
     * The public URL of this vendor's own branded site.
     */
    public function siteUrl(): string
    {
        // The microsite is served by the existing /site/{slug} route. Keep the
        // vendor's subdomain as a host when available, but always use the real
        // route path so the URL cannot point at a non-existent /site endpoint.
        $path = route('public.vendor-site', ['slug' => $this->tenant->slug], false);
        $subdomain = $this->tenant->subdomain;
        $rootDomain = (string) config('platform.root_domain', 'riwajgrand.pk');

        if ($subdomain) {
            return 'https://'.$subdomain.'.'.$rootDomain.$path;
        }

        return route('public.vendor-site', ['slug' => $this->tenant->slug]);
    }
}
