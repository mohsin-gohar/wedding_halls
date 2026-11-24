<?php

namespace Tests\Feature;

use App\Livewire\PublicVendorProfile;
use App\Livewire\PublicVendors;
use App\Models\Tenant;
use App\Models\VendorCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Phase 3 — marketplace browse UX on the new taxonomy.
 *
 * Covers: subcategory drill-down (chips only under a selected top-level
 * category, sibling set when a subcategory is active), parent tab highlight
 * while a subcategory filter is active, subcategory-aware filtering, the
 * results heading naming the active subcategory, and category SEO meta
 * (seo_title / seo_description are seeded & admin-managed — they must
 * actually reach the page <title>/<meta description>).
 */
class MarketplaceCategoryBrowseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function transportChildren()
    {
        return VendorCategory::query()
            ->where('parent_id', VendorCategory::query()->where('slug', 'transport')->value('id'))
            ->orderBy('sort')
            ->get();
    }

    // ─────────────────────────────────────────────────────────────
    // Drill-down visibility
    // ─────────────────────────────────────────────────────────────

    public function test_no_subcategory_chips_when_no_category_selected(): void
    {
        $component = Livewire::test(PublicVendors::class);

        $this->assertSame('all', $component->get('activeParentSlug'));
        $this->assertCount(0, $component->get('subcategories'), 'No chips without an active category.');
    }

    public function test_selecting_a_top_level_category_shows_its_subcategories(): void
    {
        $component = Livewire::test(PublicVendors::class)
            ->set('category', 'transport');

        $subs = $component->get('subcategories');
        $expected = $this->transportChildren();

        $this->assertCount($expected->count(), $subs);
        $this->assertTrue($subs->contains('slug', 'wedding-cars'));
        $this->assertTrue($subs->contains('slug', 'valet-parking'));
        $this->assertSame('transport', $component->get('activeParentSlug'));
    }

    public function test_selecting_a_subcategory_shows_its_siblings_and_keeps_parent_tab_active(): void
    {
        $component = Livewire::test(PublicVendors::class)
            ->set('category', 'wedding-cars');

        // Parent tab stays lit…
        $this->assertSame('transport', $component->get('activeParentSlug'));

        // …and the chip row shows the sibling set (same parent).
        $subs = $component->get('subcategories');
        $this->assertSame(
            $this->transportChildren()->pluck('slug')->sort()->values()->all(),
            $subs->pluck('slug')->sort()->values()->all()
        );
    }

    public function test_unrelated_category_shows_no_transport_chips(): void
    {
        $component = Livewire::test(PublicVendors::class)
            ->set('category', 'photography');

        $this->assertSame('photography', $component->get('activeParentSlug'));
        $this->assertFalse($component->get('subcategories')->contains('slug', 'wedding-cars'));
        $this->assertTrue($component->get('subcategories')->contains('slug', 'drone-aerial-coverage'));
    }

    public function test_unknown_category_slug_degrades_to_all(): void
    {
        $component = Livewire::test(PublicVendors::class)
            ->set('category', 'no-such-category');

        $this->assertSame('all', $component->get('activeParentSlug'));
        $this->assertCount(0, $component->get('subcategories'));
    }

    // ─────────────────────────────────────────────────────────────
    // Filtering + rendered heading
    // ─────────────────────────────────────────────────────────────

    public function test_filtering_by_subcategory_slug_returns_only_that_subcategory_vendors(): void
    {
        // Put one tenant in 'wedding-cars' and one in a sibling 'valet-parking'.
        $children = $this->transportChildren();
        $weddingCars = $children->firstWhere('slug', 'wedding-cars');
        $valet = $children->firstWhere('slug', 'valet-parking');

        $fleet = Tenant::query()->where('slug', 'shahi-fleet-rentals')->firstOrFail();
        $fleet->update(['vendor_category_id' => $weddingCars->id]);

        // A second approved vendor in the sibling subcategory.
        $other = Tenant::query()->where('slug', 'nawab-caterers')->firstOrFail();
        $other->update([
            'vendor_category_id' => $valet->id,
            'city' => 'Lahore',
        ]);

        // Rendered HTML: fleet appears under 'wedding-cars', caterers do not.
        Livewire::test(PublicVendors::class)
            ->set('category', 'wedding-cars')
            ->assertSee('Shahi Fleet Rentals')
            ->assertDontSee('Nawab Caterers')
            ->assertSee('Wedding Cars'); // results heading names the subcategory

        // Parent tab still includes BOTH subcategory tenants.
        Livewire::test(PublicVendors::class)
            ->set('category', 'transport')
            ->assertSee('Shahi Fleet Rentals')
            ->assertSee('Nawab Caterers');
    }

    // ─────────────────────────────────────────────────────────────
    // SEO meta (category seo_title / seo_description)
    // ─────────────────────────────────────────────────────────────

    public function test_default_marketplace_title_is_used_without_a_category(): void
    {
        $this->get(route('public.vendors'))
            ->assertOk()
            ->assertSee('Wedding Services Marketplace', false);
    }

    public function test_category_seo_fields_reach_the_page_meta(): void
    {
        $category = VendorCategory::query()->where('slug', 'photography')->firstOrFail();
        $category->update([
            'seo_title' => 'Best Wedding Photographers in Pakistan — Riwaj Grand',
            'seo_description' => 'Compare verified wedding photographers, drone crews and studios across Pakistan.',
        ]);

        $response = $this->get(route('public.vendors').'?category=photography');

        $response->assertOk();
        $response->assertSee('Best Wedding Photographers in Pakistan — Riwaj Grand', false);
        $response->assertSee('Compare verified wedding photographers, drone crews and studios across Pakistan.', false);
    }

    public function test_category_without_seo_falls_back_to_name_and_tagline(): void
    {
        $category = VendorCategory::query()->where('slug', 'event-services')->firstOrFail();
        $category->update(['seo_title' => null, 'seo_description' => null]);

        $response = $this->get(route('public.vendors').'?category=event-services');

        $response->assertOk();
        $response->assertSee('Event Services', false);
        // Blade escapes `&` in the meta tag — assert the escaped form.
        $response->assertSee(e($category->fresh()->tagline), false);
    }

    public function test_seeded_top_level_categories_carry_seo_defaults_from_tagline(): void
    {
        // Every seeded top-level category must render a non-empty meta
        // description (seo_description seeded null → tagline fallback).
        foreach (VendorCategory::query()->whereNull('parent_id')->pluck('slug') as $slug) {
            $response = $this->get(route('public.vendors').'?category='.$slug);

            $response->assertOk();

            $category = VendorCategory::query()->where('slug', $slug)->firstOrFail();
            $this->assertNotEmpty($category->tagline, "{$slug} needs a tagline for meta fallback.");
        }
    }

    public function test_render_sets_a_dedicated_title_for_a_filtered_category(): void
    {
        $category = VendorCategory::query()->where('slug', 'catering')->firstOrFail();
        $category->update(['seo_title' => 'Wedding Caterers PK — Per-Head Menus | Riwaj Grand']);

        $response = $this->get(route('public.vendors').'?category=catering');

        $response->assertOk();
        $response->assertSee('<title>Wedding Caterers PK — Per-Head Menus | Riwaj Grand</title>', false);

        // Unfiltered page must keep the marketplace default title.
        $this->get(route('public.vendors'))
            ->assertOk()
            ->assertSee('<title>Wedding Services Marketplace', false);
    }

    // ─────────────────────────────────────────────────────────────
    // Public vendor profile — SEO meta chain
    // ─────────────────────────────────────────────────────────────

    public function test_vendor_profile_renders_with_category_aware_title(): void
    {
        $vendor = Tenant::query()->where('slug', 'nawab-caterers')
            ->where('marketplace_visible', true)
            ->where('verification_status', 'APPROVED')
            ->firstOrFail();

        $response = $this->get(route('public.vendor-profile', ['slug' => $vendor->slug]));

        $response->assertOk();
        $response->assertSee('<title>'.$vendor->name.' — Catering', false);
    }

    public function test_vendor_profile_meta_falls_back_to_category_seo_when_about_is_empty(): void
    {
        $vendor = Tenant::query()->where('slug', 'nawab-caterers')
            ->where('marketplace_visible', true)
            ->where('verification_status', 'APPROVED')
            ->firstOrFail();

        $category = VendorCategory::query()->where('slug', 'catering')->firstOrFail();
        $category->update(['seo_description' => 'Verified caterers with per-head wedding menus.']);
        $vendor->update(['about' => null]);

        $response = $this->get(route('public.vendor-profile', ['slug' => $vendor->slug]));

        $response->assertOk();
        $response->assertSee('Verified caterers with per-head wedding menus.', false);
    }

    public function test_vendor_profile_meta_falls_back_to_a_generated_sentence_when_no_seo_exists(): void
    {
        $vendor = Tenant::query()->where('slug', 'nawab-caterers')
            ->where('marketplace_visible', true)
            ->where('verification_status', 'APPROVED')
            ->firstOrFail();

        $category = VendorCategory::query()->where('slug', 'catering')->firstOrFail();
        $category->update(['seo_description' => null, 'tagline' => null]);
        $vendor->update(['about' => null]);

        $response = $this->get(route('public.vendor-profile', ['slug' => $vendor->slug]));

        $response->assertOk();
        $response->assertSee('Hire '.$vendor->name, false);
    }

    public function test_hidden_vendor_profile_returns_404(): void
    {
        $vendor = Tenant::query()->where('slug', 'nawab-caterers')->firstOrFail();
        $vendor->update(['marketplace_visible' => false]);

        $this->get(route('public.vendor-profile', ['slug' => $vendor->slug]))
            ->assertNotFound();
    }

    // ─────────────────────────────────────────────────────────────
    // Similar vendors rail
    // ─────────────────────────────────────────────────────────────

    public function test_profile_shows_similar_vendors_from_the_same_category_only(): void
    {
        $vendor = Tenant::query()->where('slug', 'nawab-caterers')->firstOrFail();

        $response = $this->get(route('public.vendor-profile', ['slug' => $vendor->slug]));

        $response->assertOk()->assertSee('Similar');

        $html = $response->getContent();

        // Another catering vendor must appear…
        $otherCaterer = Tenant::query()
            ->where('id', '!=', $vendor->id)
            ->where('vendor_category_id', $vendor->vendor_category_id)
            ->where('marketplace_visible', true)
            ->where('verification_status', 'APPROVED')
            ->where('is_active', true)
            ->first();

        if ($otherCaterer) {
            $this->assertStringContainsString($otherCaterer->name, $html, 'Same-category vendor missing from rail.');
        }

        // …and a vendor from a different category must NOT.
        $otherCategory = Tenant::query()
            ->where('vendor_category_id', '!=', $vendor->vendor_category_id)
            ->where('marketplace_visible', true)
            ->where('verification_status', 'APPROVED')
            ->where('is_active', true)
            ->first();

        if ($otherCategory) {
            $this->assertStringNotContainsString(
                'href="'.route('public.vendor-profile', ['slug' => $otherCategory->slug]).'"',
                $html,
                'Cross-category vendor leaked into the similar rail.'
            );
        }
    }

    public function test_similar_rail_never_includes_the_vendor_itself(): void
    {
        $vendor = Tenant::query()->where('slug', 'nawab-caterers')->firstOrFail();

        $component = Livewire::test(PublicVendorProfile::class, ['slug' => $vendor->slug]);

        $similar = $component->get('similarVendors');

        $this->assertFalse(
            $similar->contains('id', $vendor->id),
            'The profile must not list itself as similar.'
        );
        $this->assertLessThanOrEqual(3, $similar->count(), 'Rail is capped at 3 cards.');
    }

    // ─────────────────────────────────────────────────────────────
    // Discoverability — the marketplace must be reachable from the nav
    // ─────────────────────────────────────────────────────────────

    public function test_main_nav_and_footer_link_to_the_vendor_marketplace(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee(route('public.vendors'), false);

        $html = $response->getContent();

        // Desktop nav entry labelled "Marketplace"…
        $this->assertStringContainsString('>Marketplace</a>', $html, 'Nav is missing the Marketplace link.');

        // …and the footer directory link.
        $this->assertStringContainsString('Vendor Marketplace', $html, 'Footer is missing the marketplace link.');
    }

    public function test_expired_tenant_is_excluded_from_vendor_results_and_city_filters(): void
    {
        $vendor = Tenant::query()->where('slug', 'nawab-caterers')->firstOrFail();
        $vendor->update([
            'city' => 'Hidden Expired City',
            'subscription_status' => 'EXPIRED',
            'subscription_expires_at' => now()->addYear(),
        ]);

        $component = Livewire::test(PublicVendors::class);

        $this->assertFalse(
            $component->get('vendors')->getCollection()->contains('id', $vendor->id),
            'Expired tenants must not appear in public marketplace results.'
        );
        $this->assertFalse(
            $component->get('cities')->contains('Hidden Expired City'),
            'Cities with only expired vendors must not appear in public filters.'
        );
    }
}
