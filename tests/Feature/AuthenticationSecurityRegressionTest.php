<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationSecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_component_has_no_credential_free_quick_login_helper(): void
    {
        $this->assertFalse(method_exists(Login::class, 'quickLogin'));
        $this->assertFalse(method_exists(Login::class, 'setLoginPassword'));
    }

    public function test_pending_account_is_logged_out_of_an_existing_session(): void
    {
        $user = \\App\\Models\\User::factory()->create([
            'role' => 'ROLE_CUSTOMER',
            'status' => 'PENDING',
        ]);

        $this->actingAs($user)
            ->get(route('customer.dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_native_login_routes_are_not_exempt_from_csrf_validation(): void
    {
        $excluded = app(ValidateCsrfToken::class)->getExcludedPaths();

        $this->assertNotContains('login', $excluded);
        $this->assertNotContains('customer/login', $excluded);
        $this->assertContains('api/*', $excluded);
        $this->assertContains('webhooks/*', $excluded);
    }
}
