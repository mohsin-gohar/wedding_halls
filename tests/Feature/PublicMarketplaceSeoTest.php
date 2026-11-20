<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\VendorCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicMarketplaceSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_sitemap_includes_public_vendor_and_category_urls(): void
    {
        $vendor = Tenant::query()
            ->where('marketplace_visible', true)
            ->where('is_active', true)
            ->where('verification_status', 'APPROVED')
            ->whereNotNull('slug')
            ->firstOrFail();

        $category = VendorCategory::query()
            ->active()
            ->topLevel()
            ->firstOrFail();

        $response = $this->get('/sitemap.xml');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('public.vendor-profile', $vendor->slug), false)
            ->assertSee(route('public.vendors', ['category' => $category->slug]), false);
    }

    public function test_sitemap_excludes_private_vendor_erp_routes(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertDontSee('/vendor/dashboard', false)
            ->assertDontSee('/vendor/finance', false);
    }

    public function test_public_page_titles_are_not_double_encoded(): void
    {
        foreach (['/gallery', '/faqs', '/contact', '/services', '/marketplace'] as $uri) {
            $html = $this->get($uri)->assertOk()->getContent();

            $this->assertIsString($html);
            $this->assertStringNotContainsString('â€', $html, $uri.' contains mojibake in its title or content.');
            $this->assertStringNotContainsString('ðŸ', $html, $uri.' contains mojibake emoji.');
        }
    }

    public function test_robots_allows_public_vendor_profiles_but_blocks_vendor_erp(): void
    {
        $robots = (string) file_get_contents(public_path('robots.txt'));

        $this->assertStringNotContainsString('Disallow: /vendor '.PHP_EOL, $robots);
        $this->assertStringNotContainsString("Disallow: /vendor\n", $robots);
        $this->assertStringContainsString('Disallow: /vendor/dashboard', $robots);
        $this->assertStringContainsString('Allow: /', $robots);
    }
}
