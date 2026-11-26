<?php

namespace Tests\Feature;

use App\Models\Hall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Portal role routing must agree with PermissionMatrix's role families.
 *
 * Enterprise alias roles (ROLE_VENDOR_OWNER, ROLE_VENDOR_MANAGER, …) and
 * staff-only roles must never be bounced between a guarded route and an
 * inaccessible portal home (an infinite redirect loop).
 */
class PortalRoleRoutingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function tenantId(): string
    {
        return (string) Hall::query()->firstOrFail()->tenant_id;
    }

    private function staffUser(string $role): User
    {
        return User::factory()->create([
            'tenant_id' => $this->tenantId(),
            'role' => $role,
            'status' => 'ACTIVE',
        ]);
    }

    public function test_enterprise_alias_owner_reaches_the_dashboard(): void
    {
        $this->actingAs($this->staffUser('ROLE_VENDOR_OWNER'))
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_enterprise_alias_manager_reaches_the_dashboard(): void
    {
        $this->actingAs($this->staffUser('ROLE_VENDOR_MANAGER'))
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_staff_only_roles_land_on_an_accessible_surface_instead_of_looping(): void
    {
        // Staff-only roles must land on a surface they can actually open.
        // Inventory managers have a dedicated ERP page; the remaining roles
        // use the shared notification surface.
        foreach ([
            'ROLE_STAFF_MEMBER' => 'notifications',
            'ROLE_PHOTOGRAPHER' => 'notifications',
            'ROLE_INVENTORY_MANAGER' => 'inventory',
        ] as $role => $landingRoute) {
            $user = $this->staffUser($role);

            $this->actingAs($user)
                ->get('/dashboard')
                ->assertRedirect(route($landingRoute));

            $this->actingAs($user)
                ->get(route($landingRoute))
                ->assertOk();
        }
    }
}
