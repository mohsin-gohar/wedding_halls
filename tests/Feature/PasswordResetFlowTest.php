<?php

namespace Tests\Feature;

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\ResetPassword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordResetFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_uses_component_state_to_send_a_reset_link(): void
    {
        $user = User::factory()->create([
            'role' => 'ROLE_CUSTOMER',
            'status' => 'ACTIVE',
        ]);

        Livewire::test(ForgotPassword::class)
            ->set('email', $user->email)
            ->call('sendResetLink')
            ->assertHasNoErrors()
            ->assertSet('resetLinkSent', true);

        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_reset_password_uses_component_state_and_logs_the_user_in(): void
    {
        $user = User::factory()->create([
            'role' => 'ROLE_CUSTOMER',
            'status' => 'ACTIVE',
            'password' => Hash::make('old-password'),
        ]);
        $token = Password::createToken($user);

        Livewire::test(ResetPassword::class, [
            'token' => $token,
            'email' => $user->email,
        ])
            ->set('password', 'brand-new-password')
            ->set('password_confirmation', 'brand-new-password')
            ->call('resetPassword')
            ->assertHasNoErrors()
            ->assertSet('resetComplete', true);

        $this->assertTrue(Hash::check('brand-new-password', $user->fresh()->password));
        $this->assertSame($user->id, auth()->id());
    }

    public function test_an_invalid_token_does_not_change_the_password(): void
    {
        $user = User::factory()->create([
            'role' => 'ROLE_CUSTOMER',
            'status' => 'ACTIVE',
            'password' => Hash::make('original-password'),
        ]);
        $originalHash = $user->password;

        Livewire::test(ResetPassword::class, [
            'token' => 'not-a-real-token',
            'email' => $user->email,
        ])
            ->set('password', 'replacement-password')
            ->set('password_confirmation', 'replacement-password')
            ->call('resetPassword');

        $this->assertSame($originalHash, $user->fresh()->password);
        $this->assertNull(auth()->id());
    }
}
