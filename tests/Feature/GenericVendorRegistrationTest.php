<?php

namespace Tests\Feature;

use App\Livewire\Auth\Register;
use App\Models\Hall;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VendorCategory;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Phase 2 — generic (non-hall) business registration & onboarding.
 *
 * A photographer, caterer or car rental business must be able to register
 * without being forced through hall-specific fields (capacity / shift rent)
 * and without Venue + Hall records being created for their tenant. The
 * legacy venue flow must remain byte-for-byte compatible.
 */
class GenericVendorRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function categoryId(string $slug): int
    {
        return (int) VendorCategory::query()->where('slug', $slug)->firstOrFail()->id;
    }

    public function test_photographer_registers_without_venue_fields_or_records(): void
    {
        $photoId = $this->categoryId('photography');

        Livewire::test(Register::class)
            ->set('vendorCategoryId', $photoId)
            ->set('hallName', 'Aperture Studio')
            ->set('ownerName', 'Aperture Owner')
            ->set('email', 'aperture.owner@example.test')
            ->set('city', 'Islamabad')
            ->set('phone', '+92 300 1112223')
            ->set('password', 'secret123')
            ->set('password_confirmation', 'secret123')
            // Deliberately invalid for venues (min 50 / min 10000) — a
            // non-venue business must not be validated on hall rules.
            ->set('capacity', 0)
            ->set('baseRent', 0)
            ->call('register')
            ->assertHasNoErrors();

        $owner = User::query()->where('email', 'aperture.owner@example.test')->firstOrFail();
        $tenant = Tenant::query()->findOrFail($owner->tenant_id);

        $this->assertSame($photoId, (int) $tenant->vendor_category_id);
        $this->assertSame('ROLE_TENANT_OWNER', $owner->role);
        $this->assertSame('PENDING', $tenant->verification_status);

        // No venue/hall scaffolding for a photography business.
        $this->assertFalse(Venue::query()->where('tenant_id', $tenant->id)->exists());
        $this->assertFalse(Hall::query()->where('tenant_id', $tenant->id)->exists());

        // Lands on the photography ERP dashboard, not the hall media flow.
        $this->assertAuthenticatedAs($owner);
    }

    public function test_photographer_registration_redirects_to_photography_dashboard(): void
    {
        $photoId = $this->categoryId('photography');

        Livewire::test(Register::class)
            ->set('vendorCategoryId', $photoId)
            ->set('hallName', 'Lumen Films')
            ->set('ownerName', 'Lumen Owner')
            ->set('email', 'lumen.owner@example.test')
            ->set('city', 'Lahore')
            ->set('phone', '+92 300 2223334')
            ->set('password', 'secret123')
            ->set('password_confirmation', 'secret123')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('vendor.dashboard.photography'));
    }

    public function test_legacy_venue_registration_still_creates_venue_and_hall(): void
    {
        // No category set explicitly — mount() defaults to SHIFT_VENUE,
        // reproducing the legacy behaviour byte-for-byte.
        Livewire::test(Register::class)
            ->set('hallName', 'Crystal Legacy Hall')
            ->set('ownerName', 'Legacy Owner')
            ->set('email', 'legacy.venue.owner@example.test')
            ->set('city', 'Karachi')
            ->set('phone', '+92 300 3334445')
            ->set('capacity', 600)
            ->set('baseRent', 200000)
            ->set('password', 'secret123')
            ->set('password_confirmation', 'secret123')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('hall.media'));

        $owner = User::query()->where('email', 'legacy.venue.owner@example.test')->firstOrFail();
        $tenant = Tenant::query()->findOrFail($owner->tenant_id);

        $this->assertTrue(Venue::query()->where('tenant_id', $tenant->id)->exists());
        $this->assertTrue(Hall::query()->where('tenant_id', $tenant->id)->exists());
        $this->assertSame($this->categoryId('wedding-halls'), (int) $tenant->vendor_category_id);

        $hall = Hall::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertSame(600, (int) $hall->max_capacity);
        $this->assertSame(200000.0, (float) $hall->base_rent);
    }

    public function test_venue_selection_still_requires_hall_fields(): void
    {
        Livewire::test(Register::class)
            ->set('vendorCategoryId', $this->categoryId('wedding-halls'))
            ->set('hallName', 'Incomplete Hall')
            ->set('ownerName', 'Incomplete Owner')
            ->set('email', 'incomplete.hall@example.test')
            ->set('city', 'Multan')
            ->set('phone', '+92 300 4445556')
            ->set('capacity', 40) // below venue minimum of 50
            ->set('baseRent', 1000)
            ->set('password', 'secret123')
            ->set('password_confirmation', 'secret123')
            ->call('register')
            ->assertHasErrors(['capacity']);

        $this->assertDatabaseMissing('users', ['email' => 'incomplete.hall@example.test']);
    }

    public function test_unknown_category_id_is_rejected(): void
    {
        Livewire::test(Register::class)
            ->set('vendorCategoryId', 99999999)
            ->set('hallName', 'Ghost Business')
            ->set('ownerName', 'Ghost Owner')
            ->set('email', 'ghost.business@example.test')
            ->set('city', 'Peshawar')
            ->set('phone', '+92 300 5556667')
            ->set('password', 'secret123')
            ->set('password_confirmation', 'secret123')
            ->call('register')
            ->assertHasErrors(['vendorCategoryId']);

        $this->assertDatabaseMissing('users', ['email' => 'ghost.business@example.test']);
    }

    public function test_register_page_uses_generic_business_copy(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Register Your Business')
            ->assertSee('Business Category');
    }
}
