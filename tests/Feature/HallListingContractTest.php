<?php

namespace Tests\Feature;

use App\Livewire\PublicVenues;
use App\Models\Hall;
use App\Models\Tenant;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression test for a schema-drift class of bug: the public marketplace
 * queries `halls.listing_status`, but the column existed only in some local
 * SQLite databases. MySQL/MariaDB crashed ("Unknown column"), and SQLite
 * silently degraded to a tautology because an unknown double-quoted identifier
 * falls back to a string literal, so WITHDRAWN halls were never actually
 * hidden. The column contract is now pinned here on every driver.
 */
class HallListingContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_columns_exist_on_every_driver(): void
    {
        foreach (['listing_status', 'listing_purpose', 'sale_price'] as $column) {
            $this->assertTrue(
                Schema::hasColumn('halls', $column),
                "halls.{$column} must be created by migrations (schema drift is not acceptable)"
            );
        }
    }

    public function test_withdrawn_hall_is_hidden_from_public_marketplace(): void
    {
        $tenant = Tenant::create([
            'name' => 'Approved Listing Group',
            'slug' => 'approved-listing',
            'email' => 'approved@listing.pk',
            'city' => 'Lahore',
            'verification_status' => 'APPROVED',
            'is_active' => true,
            'marketplace_visible' => true,
        ]);
        $venue = Venue::create([
            'tenant_id' => $tenant->id,
            'name' => 'Approved Complex',
            'slug' => 'approved-complex',
            'city' => 'Lahore',
            'is_active' => true,
        ]);

        $visible = Hall::create([
            'tenant_id' => $tenant->id,
            'venue_id' => $venue->id,
            'name' => 'Visible Hall Listing Contract',
            'min_capacity' => 100,
            'max_capacity' => 500,
            'base_rent' => 100000,
            'is_active' => true,
            'listing_status' => 'LISTED',
        ]);
        $withdrawn = Hall::create([
            'tenant_id' => $tenant->id,
            'venue_id' => $venue->id,
            'name' => 'Withdrawn Hall Must Disappear',
            'min_capacity' => 100,
            'max_capacity' => 500,
            'base_rent' => 100000,
            'is_active' => true,
            'listing_status' => 'WITHDRAWN',
        ]);

        $component = Livewire::test(PublicVenues::class);
        $component->assertSee('Visible Hall Listing Contract');
        $component->assertDontSee('Withdrawn Hall Must Disappear');
    }

    public function test_db_default_listing_status_is_listed(): void
    {
        $tenant = Tenant::create([
            'name' => 'Default Listing Group',
            'slug' => 'default-listing',
            'email' => 'default@listing.pk',
            'city' => 'Lahore',
            'verification_status' => 'APPROVED',
            'is_active' => true,
        ]);
        $venue = Venue::create([
            'tenant_id' => $tenant->id,
            'name' => 'Default Complex',
            'slug' => 'default-complex',
            'city' => 'Lahore',
            'is_active' => true,
        ]);
        $hall = Hall::create([
            'tenant_id' => $tenant->id,
            'venue_id' => $venue->id,
            'name' => 'Default Status Hall',
            'min_capacity' => 100,
            'max_capacity' => 500,
            'base_rent' => 100000,
            'is_active' => true,
        ]);

        $this->assertSame('LISTED', $hall->refresh()->listing_status);
    }
}
