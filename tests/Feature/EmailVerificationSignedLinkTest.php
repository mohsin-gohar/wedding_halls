<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationSignedLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_query_link_verifies_the_account_on_initial_get(): void
    {
        $user = User::factory()->create([
            'email' => 'verify-query@example.test',
            'email_verified_at' => null,
        ]);
        $token = hash_hmac('sha256', $user->email.'|'.$user->id, config('app.key'));
        $url = URL::temporarySignedRoute(
            'verify.email',
            now()->addHour(),
            ['token' => $token, 'email' => $user->email]
        );

        $this->get($url)
            ->assertOk()
            ->assertSee('Email Verified');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_expired_signed_link_does_not_verify_the_account(): void
    {
        $user = User::factory()->create([
            'email' => 'verify-expired@example.test',
            'email_verified_at' => null,
        ]);
        $token = hash_hmac('sha256', $user->email.'|'.$user->id, config('app.key'));
        $url = URL::temporarySignedRoute(
            'verify.email',
            now()->subMinute(),
            ['token' => $token, 'email' => $user->email]
        );

        $this->get($url)
            ->assertOk()
            ->assertSee('Invalid or expired verification link');

        $this->assertNull($user->fresh()->email_verified_at);
    }
}
