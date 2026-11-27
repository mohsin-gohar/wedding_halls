<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityBoundaryHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_tenant_owner_cannot_mint_wildcard_api_token(): void
    {
        $tenant = Tenant::query()->where('slug', 'riwaj-grand')->firstOrFail();
        $owner = User::factory()->create([
            'tenant_id' => $tenant->id, 'role' => 'ROLE_TENANT_OWNER', 'status' => 'ACTIVE',
        ]);

        $this->actingAs($owner)
            ->postJson('/account/api-tokens', [
                'name' => 'Unrestricted',
                'abilities' => ['*'],
            ])->assertForbidden();

        $this->actingAs($owner)
            ->postJson('/account/api-tokens', [
                'name' => 'Scoped',
                'abilities' => ['bookings.read'],
            ])->assertCreated();
    }
}
