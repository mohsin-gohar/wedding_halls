<?php

namespace Tests\Feature;

use App\Livewire\AdminCategoryManager;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VendorCategory;
use Database\Seeders\VendorCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Phase 2 — Super Admin hierarchical category management.
 *
 * Covers: route authorization (guest / non-super-admin / super admin),
 * CRUD for categories & subcategories, slug uniqueness, SEO fields,
 * enable/disable cascade, sibling reordering, hierarchy cycle prevention
 * and safe (referenced-aware) deletion.
 */
class AdminCategoryManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function superAdmin(): User
    {
        return User::query()->firstOrCreate(
            ['email' => 'taxonomy.admin@example.test'],
            [
                'name' => 'Taxonomy Admin',
                'phone' => '+92 300 1230001',
                'role' => 'ROLE_SUPER_ADMIN',
                'password' => Hash::make('secret123'),
            ]
        );
    }

    private function tenantOwner(): User
    {
        $tenant = Tenant::query()->where('slug', 'nawab-caterers')->firstOrFail();

        $owner = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('role', 'ROLE_TENANT_OWNER')
            ->first();

        if ($owner) {
            return $owner;
        }

        return User::firstOrCreate(
            ['email' => 'catering.owner@example.test'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Catering Owner',
                'phone' => '+92 300 1230002',
                'role' => 'ROLE_TENANT_OWNER',
                'password' => Hash::make('secret123'),
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────
    // Route authorization
    // ─────────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_category_admin(): void
    {
        $this->get(route('admin.categories'))->assertRedirect(route('login'));
    }

    public function test_non_super_admin_gets_hard_403_on_category_admin(): void
    {
        $this->actingAs($this->tenantOwner())
            ->get(route('admin.categories'))
            ->assertStatus(403);
    }

    public function test_super_admin_renders_category_admin(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('admin.categories'))
            ->assertOk()
            ->assertSee('Categories & Subcategories')
            ->assertSee('Wedding Halls & Marquees');
    }

    public function test_component_mount_defies_route_bypass(): void
    {
        // Livewire tests bypass route middleware — the component's own
        // mount() guard must still refuse non-super-admins.
        Livewire::actingAs($this->tenantOwner())
            ->test(AdminCategoryManager::class)
            ->assertStatus(403);
    }

    // ─────────────────────────────────────────────────────────────
    // CRUD — categories & subcategories
    // ─────────────────────────────────────────────────────────────

    public function test_super_admin_creates_top_level_category_with_automatic_slug(): void
    {
        Livewire::actingAs($this->superAdmin())
            ->test(AdminCategoryManager::class)
            ->call('openCreateModal', null)
            ->set('name', 'Luxury Wedding Cars')
            ->set('bookingMode', 'TRANSPORT')
            ->set('heroColor', '#123456')
            ->call('saveCategory')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('vendor_categories', [
            'slug' => 'luxury-wedding-cars',
            'parent_id' => null,
            'is_active' => true,
            'booking_mode' => 'TRANSPORT',
        ]);
    }

    public function test_super_admin_creates_subcategory_under_parent(): void
    {
        $parent = VendorCategory::query()->where('slug', 'transport')->firstOrFail();

        Livewire::actingAs($this->superAdmin())
            ->test(AdminCategoryManager::class)
            ->call('openCreateModal', (int) $parent->id)
            ->set('name', 'Bridal Cars')
            ->set('slug', 'bridal-cars')
            ->set('bookingMode', 'TRANSPORT')
            ->call('saveCategory')
            ->assertHasNoErrors();

        $child = VendorCategory::query()->where('slug', 'bridal-cars')->firstOrFail();
        $this->assertSame((int) $parent->id, (int) $child->parent_id);
    }

    public function test_duplicate_slug_is_rejected(): void
    {
        Livewire::actingAs($this->superAdmin())
            ->test(AdminCategoryManager::class)
            ->call('openCreateModal', null)
            ->set('name', 'Wedding Halls Copy')
            ->set('slug', 'wedding-halls')
            ->set('bookingMode', 'SHIFT_VENUE')
            ->call('saveCategory')
            ->assertHasErrors(['slug']);

        $this->assertDatabaseMissing('vendor_categories', ['name' => 'Wedding Halls Copy']);
    }

    public function test_editing_updates_seo_and_identity_fields(): void
    {
        $category = VendorCategory::query()->where('slug', 'photography')->firstOrFail();

        Livewire::actingAs($this->superAdmin())
            ->test(AdminCategoryManager::class)
            ->call('openEditModal', (int) $category->id)
            ->set('name', 'Photography & Cinematography')
            ->set('seoTitle', 'Best Wedding Photographers in Pakistan')
            ->set('seoDescription', 'Compare verified wedding photographers, drone crews and studios.')
            ->set('tagline', 'Cinematic weddings & drone coverage')
            ->call('saveCategory')
            ->assertHasNoErrors();

        $category->refresh();
        $this->assertSame('Photography & Cinematography', $category->name);
        $this->assertSame('Best Wedding Photographers in Pakistan', $category->seo_title);
        $this->assertSame('Compare verified wedding photographers, drone crews and studios.', $category->seo_description);
        $this->assertSame('photography', $category->slug, 'Slug must remain stable when not changed.');
    }

    public function test_invalid_booking_mode_is_rejected(): void
    {
        Livewire::actingAs($this->superAdmin())
            ->test(AdminCategoryManager::class)
            ->call('openCreateModal', null)
            ->set('name', 'Bad Mode Category')
            ->set('bookingMode', 'NOT_A_REAL_MODE')
            ->call('saveCategory')
            ->assertHasErrors(['bookingMode']);
    }

    // ─────────────────────────────────────────────────────────────
    // Enable / disable + cascade
    // ─────────────────────────────────────────────────────────────

    public function test_disabling_parent_cascades_to_subcategories(): void
    {
        $parent = VendorCategory::query()->where('slug', 'transport')->firstOrFail();

        $child = VendorCategory::create([
            'parent_id' => (int) $parent->id,
            'name' => 'Chauffeur Services',
            'slug' => 'chauffeur-services',
            'booking_mode' => 'TRANSPORT',
            'is_active' => true,
            'sort' => 99,
        ]);

        Livewire::actingAs($this->superAdmin())
            ->test(AdminCategoryManager::class)
            ->call('toggleCategory', (int) $parent->id);

        $this->assertFalse($parent->fresh()->is_active);
        $this->assertFalse($child->fresh()->is_active, 'Subcategory must follow its parent.');

        // Re-enable cascades back on as well.
        Livewire::actingAs($this->superAdmin())
            ->test(AdminCategoryManager::class)
            ->call('toggleCategory', (int) $parent->id);

        $this->assertTrue($parent->fresh()->is_active);
        $this->assertTrue($child->fresh()->is_active);
    }

    // ─────────────────────────────────────────────────────────────
    // Reordering
    // ─────────────────────────────────────────────────────────────

    public function test_move_down_swaps_siblings_and_reindexes(): void
    {
        $weddingHalls = VendorCategory::query()->where('slug', 'wedding-halls')->firstOrFail();
        $photography = VendorCategory::query()->where('slug', 'photography')->firstOrFail();

        $this->assertSame(1, (int) $weddingHalls->sort);
        $this->assertSame(2, (int) $photography->sort);

        Livewire::actingAs($this->superAdmin())
            ->test(AdminCategoryManager::class)
            ->call('moveCategory', (int) $weddingHalls->id, 'down')
            ->assertHasNoErrors();

        $this->assertSame(2, (int) $weddingHalls->fresh()->sort);
        $this->assertSame(1, (int) $photography->fresh()->sort);
    }

    public function test_move_up_at_top_reports_error_without_changing_order(): void
    {
        $weddingHalls = VendorCategory::query()->where('slug', 'wedding-halls')->firstOrFail();

        Livewire::actingAs($this->superAdmin())
            ->test(AdminCategoryManager::class)
            ->call('moveCategory', (int) $weddingHalls->id, 'up');

        $this->assertSame(1, (int) $weddingHalls->fresh()->sort);
    }

    // ─────────────────────────────────────────────────────────────
    // Hierarchy safety
    // ─────────────────────────────────────────────────────────────

    public function test_category_cannot_be_nested_inside_its_own_subcategory(): void
    {
        $parent = VendorCategory::query()->where('slug', 'transport')->firstOrFail();

        $child = VendorCategory::create([
            'parent_id' => (int) $parent->id,
            'name' => 'Airport Transfers',
            'slug' => 'airport-transfers',
            'booking_mode' => 'TRANSPORT',
            'is_active' => true,
            'sort' => 98,
        ]);

        Livewire::actingAs($this->superAdmin())
            ->test(AdminCategoryManager::class)
            ->call('openEditModal', (int) $parent->id)
            ->set('parentId', (int) $child->id)
            ->call('saveCategory')
            ->assertHasErrors(['parentId']);

        $this->assertNull($parent->fresh()->parent_id, 'Cycle attempt must not persist.');
    }

    public function test_self_parenting_is_rejected(): void
    {
        $category = VendorCategory::query()->where('slug', 'catering')->firstOrFail();

        Livewire::actingAs($this->superAdmin())
            ->test(AdminCategoryManager::class)
            ->call('openEditModal', (int) $category->id)
            ->set('parentId', (int) $category->id)
            ->call('saveCategory')
            ->assertHasErrors(['parentId']);
    }

    // ─────────────────────────────────────────────────────────────
    // Safe deletion
    // ─────────────────────────────────────────────────────────────

    public function test_unused_category_can_be_deleted(): void
    {
        VendorCategory::create([
            'name' => 'Temporary Category',
            'slug' => 'temporary-category',
            'booking_mode' => 'PROJECT',
            'is_active' => false,
            'sort' => 200,
        ]);

        Livewire::actingAs($this->superAdmin())
            ->test(AdminCategoryManager::class)
            ->call('deleteCategory', (int) VendorCategory::query()->where('slug', 'temporary-category')->firstOrFail()->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('vendor_categories', ['slug' => 'temporary-category']);
    }

    public function test_category_with_children_or_tenants_cannot_be_deleted(): void
    {
        $parent = VendorCategory::query()->where('slug', 'transport')->firstOrFail();

        VendorCategory::create([
            'parent_id' => (int) $parent->id,
            'name' => 'Luxury SUVs',
            'slug' => 'luxury-suvs',
            'booking_mode' => 'TRANSPORT',
            'is_active' => true,
            'sort' => 97,
        ]);

        $component = Livewire::actingAs($this->superAdmin())
            ->test(AdminCategoryManager::class)
            ->call('deleteCategory', (int) $parent->id);

        $this->assertNotNull(VendorCategory::query()->where('slug', 'transport')->first());
        $this->assertNotNull($component->get('errorMessage'));

        // A seeded category with tenant businesses assigned must also refuse.
        $catering = VendorCategory::query()->where('slug', 'catering')->firstOrFail();
        $this->assertTrue($catering->tenants()->exists(), 'Precondition: catering has tenants.');

        Livewire::actingAs($this->superAdmin())
            ->test(AdminCategoryManager::class)
            ->call('deleteCategory', (int) $catering->id);

        $this->assertNotNull(VendorCategory::query()->where('slug', 'catering')->first());
    }

    public function test_third_level_nesting_is_rejected_server_side(): void
    {
        $parent = VendorCategory::query()->where('slug', 'transport')->firstOrFail();

        $child = VendorCategory::create([
            'parent_id' => (int) $parent->id,
            'name' => 'Airport Transfers',
            'slug' => 'airport-transfers-depth',
            'booking_mode' => 'TRANSPORT',
            'is_active' => true,
            'sort' => 96,
        ]);

        Livewire::actingAs($this->superAdmin())
            ->test(AdminCategoryManager::class)
            ->call('openCreateModal', (int) $child->id)
            ->set('name', 'Nested Too Deep')
            ->set('slug', 'nested-too-deep')
            ->set('bookingMode', 'TRANSPORT')
            ->call('saveCategory')
            ->assertHasErrors(['parentId']);

        $this->assertDatabaseMissing('vendor_categories', ['slug' => 'nested-too-deep']);
    }

    public function test_parent_with_subcategories_cannot_become_a_subcategory(): void
    {
        $transport = VendorCategory::query()->where('slug', 'transport')->firstOrFail();
        $photography = VendorCategory::query()->where('slug', 'photography')->firstOrFail();

        VendorCategory::create([
            'parent_id' => (int) $transport->id,
            'name' => 'Bridal Car Fleet',
            'slug' => 'bridal-car-fleet',
            'booking_mode' => 'TRANSPORT',
            'is_active' => true,
            'sort' => 95,
        ]);

        Livewire::actingAs($this->superAdmin())
            ->test(AdminCategoryManager::class)
            ->call('openEditModal', (int) $transport->id)
            ->set('parentId', (int) $photography->id)
            ->call('saveCategory')
            ->assertHasErrors(['parentId']);

        $this->assertNull($transport->fresh()->parent_id, 'A parent with children must not be demoted.');
    }

    public function test_moving_parent_rebands_its_subcategory_sorts(): void
    {
        $weddingHalls = VendorCategory::query()->where('slug', 'wedding-halls')->firstOrFail();
        $children = VendorCategory::query()->where('parent_id', $weddingHalls->id)->pluck('id');

        $this->assertGreaterThan(0, $children->count(), 'Precondition: wedding-halls has subcategories.');

        Livewire::actingAs($this->superAdmin())
            ->test(AdminCategoryManager::class)
            ->call('moveCategory', (int) $weddingHalls->id, 'down');

        $newParentSort = (int) $weddingHalls->fresh()->sort;
        $this->assertSame(2, $newParentSort);

        $childSorts = VendorCategory::query()
            ->where('parent_id', $weddingHalls->id)
            ->orderBy('sort')
            ->pluck('sort')
            ->map(fn ($sort) => (int) $sort)
            ->all();

        $this->assertSame($newParentSort * 100 + 1, $childSorts[0], 'Children must follow the parent sort band.');
        $this->assertSame($newParentSort * 100 + count($childSorts), end($childSorts));
    }

    public function test_reseeding_does_not_overwrite_admin_managed_category_data(): void
    {
        $category = VendorCategory::query()->where('slug', 'photography')->firstOrFail();

        $category->update([
            'name' => 'Admin Renamed Photography',
            'sort' => 77,
            'is_active' => false,
        ]);

        $this->seed(VendorCategorySeeder::class);

        $fresh = $category->fresh();

        $this->assertSame('Admin Renamed Photography', $fresh->name);
        $this->assertSame(77, (int) $fresh->sort);
        $this->assertFalse($fresh->is_active);
        $this->assertSame($category->slug, $fresh->slug);
    }
}
