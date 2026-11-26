<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\CustomerRegister;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class ReloadProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('reload-protection');
    }

    public function test_repeated_safe_login_page_views_do_not_block_the_login_page(): void
    {
        // GET requests render the login page; credential attempts are throttled
        // by the dedicated login limiter tested below.
        for ($i = 1; $i <= 6; $i++) {
            $this->withHeaders(['X-Test-Rate-Limit' => 'true'])
                ->get('/login')
                ->assertOk();
        }
    }

    public function test_customer_login_reloads_are_throttled_after_5_attempts(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->withHeaders(['X-Test-Rate-Limit' => 'true'])
                ->get(route('customer.login'))
                ->assertOk();
        }

        $this->withHeaders(['X-Test-Rate-Limit' => 'true'])
            ->get(route('customer.login'))
            ->assertStatus(429)
            ->assertSee('Aap website ko bar bar reload nahi kar saktay')
            ->assertSee('Server Protection')
            ->assertSee('countdown');
    }

    public function test_login_throttling_after_5_attempts_displays_warning(): void
    {
        $testEmail = 'abuse_tester@riwajgrand.pk';

        for ($i = 1; $i <= 5; $i++) {
            Livewire::test(Login::class)
                ->set('email', $testEmail)
                ->set('password', 'wrong-pass-'.$i)
                ->call('login')
                ->assertHasErrors(['email']);
        }

        // 6th attempt must be stopped by rate limiter
        Livewire::test(Login::class)
            ->set('email', $testEmail)
            ->set('password', 'wrong-pass-6')
            ->call('login')
            ->assertHasErrors(['email'])
            ->assertSee('Aap dobara se login koshish nahi kar saktay');
    }

    public function test_customer_register_throttling_after_5_attempts(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            Livewire::test(CustomerRegister::class)
                ->set('name', 'Tester '.$i)
                ->set('email', 'bademail') // invalid so it fails validation
                ->set('phone', '03001234567')
                ->set('password', 'password123')
                ->set('password_confirmation', 'password123')
                ->call('register')
                ->assertHasErrors(['email']);
        }

        // 6th attempt should trigger rate limiter error on name
        Livewire::test(CustomerRegister::class)
            ->set('name', 'Tester 6')
            ->set('email', 'fresh@example.com')
            ->set('phone', '03001234567')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['name'])
            ->assertSee('Aap dobara se register koshish nahi kar saktay');
    }
}
