<?php

namespace Tests\Feature;

use App\Livewire\PublicVendors;
use App\Models\Tenant;
use App\Models\VendorCategory;
use Database\Seeders\VendorCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Phase 2 — marketplace taxonomy (14 categories + subcategories).
 *
 * Guards the invariants the marketplace, registration and admin console
 * depend on:
 *  - seeded structure (top-level count, unique slugs, no orphans, the
 *    original nine slugs/sorts unchanged for backward compatibility)
 *  - public tabs show top-level categories only (83 subcategories must not
 *    flood the filter bar)
 *  - filtering a parent tab also surfaces vendors registered under its
 *    subcategories
 *  - registration only offers top-level categories
 */
class VendorCategoryTaxonomyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    // ─────────────────────────────────────────────────────────────
    // Seeded structure
    // ─────────────────────────────────────────────────────────────

    public function test_seeded_taxonomy_has_fourteen_top_level_categories(): void
    {
        $topLevel = VendorCategory::query()->whereNull('parent_id')->get();

        $this->assertSame(14, $topLevel->count());
        $this->assertSame(
            range(1, 14),
            $topLevel->sortBy('sort')->pluck('sort')->map(fn ($s) => (int) $s)->values()->all(),
            'Top-level sorts must be a clean 1..14 band.'
        );
        $this->assertTrue($topLevel->every(fn ($c) => $c->is_active));
    }

    public function test_original_nine_categories_keep_slug_and_sort(): void
    {
        $expected = [
            'wedding-halls' => 1, 'photography' => 2, 'catering' => 3,
            'decoration' => 4, 'beauty-bridal' => 5, 'wedding-planners' => 6,
            'transport' => 7, 'entertainment' => 8, 'invitations-printing' => 9,
        ];

        foreach ($expected as $slug => $sort) {
            $row = VendorCategory::query()->where('slug', $slug)->firstOrFail();
            $this->assertNull($row->parent_id, "{$slug} must stay top-level.");
            $this->assertSame($sort, (int) $row->sort, "{$slug} sort must not change.");
        }
    }

    public function test_every_subcategory_has_a_live_parent_and_unique_slug(): void
    {
        $children = VendorCategory::query()->whereNotNull('parent_id')->get();

        $this->assertGreaterThanOrEqual(80, $children->count(), 'Full taxonomy expects 80+ subcategories.');

        $parentIds = VendorCategory::query()->whereNull('parent_id')->pluck('id');

        foreach ($children as $child) {
            $this->assertTrue($parentIds->contains($child->parent_id), "Orphan subcategory: {$child->slug}");
            $this->assertNotEmpty($child->parent_id);
            $this->assertTrue($child->is_active);
        }

        $this->assertSame(
            VendorCategory::query()->count(),
            VendorCategory::query()->distinct()->count('slug'),
            'Slugs are globally unique (seeder keys updateOrCreate by slug).'
        );
    }

    public function test_subcategories_sit_inside_their_parents_sort_band(): void
    {
        foreach (VendorCategory::query()->whereNotNull('parent_id')->get() as $child) {
            $parentSort = (int) VendorCategory::query()->where('id', $child->parent_id)->value('sort');
            $min = $parentSort * 100;
            $max = $parentSort * 100 + 99;

            $this->assertTrue(
                (int) $child->sort > $min && (int) $child->sort <= $max,
                "Subcategory {$child->slug} sort {$child->sort} outside parent band ({$min}-{$max})."
            );
        }
    }

    public function test_seeder_is_idempotent(): void
    {
        $before = VendorCategory::query()->count();

        $this->seed(VendorCategorySeeder::class);

        $this->assertSame($before, VendorCategory::query()->count(), 'Re-seeding must not duplicate rows.');
        $this->assertSame($before, VendorCategory::query()->distinct()->count('slug'));
    }

    public function test_active_scope_orders_by_sort_then_name_and_excludes_inactive_categories(): void
    {
        // Insert in a deliberately different order from the expected result so
        // storage order cannot satisfy the assertion by accident.
        $zuluLast = VendorCategory::create([
            'name' => 'Zulu Scope Category',
            'slug' => 'scope-zulu-last',
            'booking_mode' => 'DATE_EVENT',
            'is_active' => true,
            'sort' => 900,
        ]);

        $alphaSameSort = VendorCategory::create([
            'name' => 'Alpha Scope Category',
            'slug' => 'scope-alpha-same-sort',
            'booking_mode' => 'DATE_EVENT',
            'is_active' => true,
            'sort' => 900,
        ]);

        $bravoLowerSort = VendorCategory::create([
            'name' => 'Bravo Scope Category',
            'slug' => 'scope-bravo-lower-sort',
            'booking_mode' => 'DATE_EVENT',
            'is_active' => true,
            'sort' => 800,
        ]);

        $inactive = VendorCategory::create([
            'name' => 'Aaa Inactive Scope Category',
            'slug' => 'scope-aaa-inactive',
            'booking_mode' => 'DATE_EVENT',
            'is_active' => false,
            'sort' => 700,
        ]);

        $ordered = VendorCategory::query()
            ->whereKey([$zuluLast->id, $alphaSameSort->id, $bravoLowerSort->id, $inactive->id])
            ->active()
            ->pluck('slug')
            ->all();

        $this->assertSame(
            [$bravoLowerSort->slug, $alphaSameSort->slug, $zuluLast->slug],
            $ordered,
            'Active categories must be sorted by sort, then name, regardless of insertion order.'
        );
        $this->assertNotContains($inactive->slug, $ordered, 'Inactive categories must be excluded.');
    }

    // ─────────────────────────────────────────────────────────────
    // Public marketplace — tabs and subcategory-aware filtering
    // ─────────────────────────────────────────────────────────────

    public function test_public_marketplace_tabs_show_only_top_level_categories(): void
    {
        $topLevelCount = VendorCategory::query()->whereNull('parent_id')->count();

        $component = Livewire::test(PublicVendors::class);

        // Computed props (getXProperty) are read via Testable::get(), not viewData().
        $tabs = collect($component->get('categories'));

        $this->assertSame($topLevelCount, $tabs->count(), 'Tabs must equal top-level count (not 97).');
        $this->assertTrue($tabs->every(fn ($c) => $c->parent_id === null), 'No subcategory may appear as a tab.');
        $this->assertFalse($tabs->contains('slug', 'banquet-halls'));
        $this->assertTrue($tabs->contains('slug', 'wedding-halls'));
        $this->assertTrue($tabs->contains('slug', 'event-services'));
    }

    public function test_filtering_a_parent_tab_includes_subcategory_vendors(): void
    {
        // Move a seeded, marketplace-visible tenant into a SUBCATEGORY of
        // "transport" — the parent tab must still surface it.
        $parent = VendorCategory::query()->where('slug', 'transport')->firstOrFail();
        $child = VendorCategory::query()
            ->where('parent_id', $parent->id)
            ->where('slug', 'wedding-cars')
            ->firstOrFail();

        $tenant = Tenant::query()->where('slug', 'shahi-fleet-rentals')->firstOrFail();
        $this->assertSame($parent->id, (int) $tenant->vendor_category_id, 'Precondition: tenant in parent.');

        $tenant->update(['vendor_category_id' => $child->id]);

        // Assert on rendered HTML: the view re-renders in the same cycle as
        // set(), so computed props are guaranteed fresh here.
        Livewire::test(PublicVendors::class)
            ->set('category', 'transport')
            ->set('search', '')
            ->assertSee('Shahi Fleet Rentals')
            ->assertDontSee('Nawab Caterers');

        // Restore for any later assertion in this test's DB state.
        $tenant->update(['vendor_category_id' => $parent->id]);
    }

    // ─────────────────────────────────────────────────────────────
    // Registration — top-level only
    // ─────────────────────────────────────────────────────────────

    public function test_registration_offers_only_top_level_categories(): void
    {
        $page = $this->get(route('register'));

        $page->assertOk()->assertSee('Business Category');

        $html = $page->getContent();

        foreach (VendorCategory::query()->whereNull('parent_id')->pluck('name') as $name) {
            $this->assertStringContainsString(e($name), $html, "Missing top-level option: {$name}");
        }

        // Subcategories must not be offered as business types (a business
        // registers at category level; services may later use subcategories).
        $this->assertStringNotContainsString('Banquet Halls', $html);
        $this->assertStringNotContainsString('Wedding Photographers', $html);
    }
}
