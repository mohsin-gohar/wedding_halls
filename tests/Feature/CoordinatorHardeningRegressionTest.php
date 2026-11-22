<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\User;
use App\Support\TenantAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoordinatorHardeningRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_platform_finance_lands_on_a_route_it_can_open(): void
    {
        $finance = User::factory()->create([
            'role' => 'ROLE_PLATFORM_FINANCE',
            'status' => 'ACTIVE',
            'tenant_id' => null,
        ]);
        $finance->assignRole('ROLE_PLATFORM_FINANCE');

        $this->assertSame('support.center', $finance->portalHomeRoute());

        $this->actingAs($finance->fresh())
            ->get(route('support.center'))
            ->assertOk();
    }

    public function test_expired_tenant_subscription_locks_web_sessions(): void
    {
        $owner = $this->venueOwner();

        $owner->tenant->forceFill(['subscription_status' => 'EXPIRED'])->saveQuietly();

        $this->assertTrue(TenantAccess::isLocked($owner->tenant->fresh()));

        $this->actingAs($owner->fresh())
            ->get('/dashboard')
            ->assertRedirect(route('login'));
    }

    public function test_expired_tenant_subscription_locks_bearer_tokens(): void
    {
        $owner = $this->venueOwner();

        $owner->tenant->forceFill(['subscription_status' => 'EXPIRED'])->saveQuietly();

        [, $plain] = ApiToken::issue($owner->fresh(), 'Expired tenant token');

        $this->withHeader('Authorization', 'Bearer '.$plain)
            ->getJson('/api/v1/bookings')
            ->assertStatus(403)
            ->assertJsonPath('message', TenantAccess::lockMessage());
    }

    private function venueOwner(): User
    {
        return User::query()
            ->where('email', 'owner@riwajgrand.pk')
            ->firstOrFail();
    }
}
