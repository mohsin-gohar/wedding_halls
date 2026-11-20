<?php

namespace Tests\Feature;

use App\Livewire\Auth\TwoFactorAuthComponent;
use App\Livewire\PublicAbout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * The challenge must be solvable over real HTTP — the TwoFactorAuth middleware
 * runs on the web group, and Livewire submits component actions through an XHR.
 * A previous version redirected that XHR and locked the user out permanently.
 */
class TwoFactorChallengeHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_two_factor_user_can_solve_the_challenge_over_http(): void
    {
        [$user, $google2fa, $secret] = $this->twoFactorUser();

        $this->actingAs($user);

        // Gated before verification.
        $this->get('/account/api-tokens')
            ->assertRedirectContains(route('2fa.verify'));

        // The challenge page itself is reachable.
        $this->get(route('2fa.verify'))->assertOk();

        // Build a genuine, checksum-valid Livewire snapshot for the challenge
        // component, then submit its verify call through the real update XHR.
        $snapshot = Livewire::test(TwoFactorAuthComponent::class, [
            'intended' => '/account/api-tokens',
        ])->snapshot;

        $this->postLivewireUpdate($snapshot, 'verifyTwoFactor', [
            'code' => $google2fa->getCurrentOtp($secret),
        ])->assertOk();

        $this->assertTrue(session('2fa_verified'));

        // No longer locked out.
        $this->get('/account/api-tokens')->assertOk();
    }

    public function test_other_livewire_components_stay_gated_during_the_challenge(): void
    {
        [$user] = $this->twoFactorUser();

        $this->actingAs($user);

        // A genuine snapshot for an unrelated component must stay gated.
        $snapshot = Livewire::test(PublicAbout::class)->snapshot;

        $this->postLivewireUpdate($snapshot, 'verifyTwoFactor')
            ->assertRedirectContains(route('2fa.verify'));

        $this->assertFalse(session('2fa_verified', false));
    }

    public function test_secret_rotating_methods_are_blocked_during_the_challenge(): void
    {
        [$user] = $this->twoFactorUser();

        $this->actingAs($user);

        $snapshot = Livewire::test(TwoFactorAuthComponent::class)->snapshot;

        $this->postLivewireUpdate($snapshot, 'generateSecret')
            ->assertRedirectContains(route('2fa.verify'));

        $this->assertFalse(session('2fa_verified', false));
    }

    private function postLivewireUpdate(array $snapshot, string $method, array $updates = [])
    {
        // Livewire requires both X-Livewire and a JSON content type.
        return $this->postJson(app('livewire')->getUpdateUri(), [
            'components' => [[
                'snapshot' => json_encode($snapshot),
                'calls' => [[
                    'method' => $method,
                    'params' => [],
                    'path' => '',
                ]],
                'updates' => $updates,
            ]],
        ], ['X-Livewire' => 'true']);
    }

    /**
     * @return array{0: User, 1: Google2FA, 2: string}
     */
    private function twoFactorUser(): array
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();

        $user = User::factory()->create([
            'role' => 'ROLE_CUSTOMER',
            'status' => 'ACTIVE',
            'two_factor_enabled' => true,
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ]);

        return [$user, $google2fa, $secret];
    }
}
