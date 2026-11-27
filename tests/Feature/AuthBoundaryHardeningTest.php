<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Session and API-token boundaries that are easy to regress.
 */
class AuthBoundaryHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function owner(): User
    {
        return User::query()->where('role', 'ROLE_TENANT_OWNER')->firstOrFail();
    }

    public function test_logout_is_post_only(): void
    {
        $this->actingAs($this->owner())
            ->get('/logout')
            ->assertStatus(405);

        $this->assertAuthenticated();
    }

    public function test_post_logout_invalidates_the_session(): void
    {
        $this->actingAs($this->owner())
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_new_api_tokens_are_read_only_and_time_limited_by_default(): void
    {
        $response = $this->actingAs($this->owner())
            ->postJson('/account/api-tokens', ['name' => 'Default scoped token'])
            ->assertCreated();

        $this->assertSame(
            ['bookings.read', 'service-bookings.read', 'payments.read'],
            $response->json('abilities')
        );

        $expiresAt = $response->json('expires_at');
        $this->assertNotNull($expiresAt);
        $this->assertTrue(
            Carbon::parse($expiresAt)->between(now()->addDays(89), now()->addDays(91)),
            'A token created without expires_in_days must expire in roughly 90 days.'
        );
    }

    public function test_wildcard_access_requires_explicit_request_and_platform_administrator(): void
    {
        $superAdmin = User::query()->where('role', 'ROLE_SUPER_ADMIN')->firstOrFail();

        $this->actingAs($superAdmin)
            ->postJson('/account/api-tokens', [
                'name' => 'Explicit wildcard token',
                'abilities' => ['*'],
                'expires_in_days' => 30,
            ])
            ->assertCreated()
            ->assertJsonPath('abilities', ['*']);
    }
}
