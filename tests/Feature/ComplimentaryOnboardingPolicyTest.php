<?php

namespace Tests\Feature;

use App\Livewire\AdminDashboard;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ComplimentaryOnboardingPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_provisioning_uses_the_same_complimentary_window_as_registration(): void
    {
        $this->seed();
        $days = (int) config('platform.free_access_days', 30);
        $admin = User::where('role', 'ROLE_SUPER_ADMIN')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(AdminDashboard::class)
            ->set('newBusinessName', 'Onboarding Policy Venue')
            ->set('newCity', 'Lahore')
            ->set('newMinCapacity', 50)
            ->set('newMaxCapacity', 500)
            ->set('newBaseRent', 100000)
            ->set('newOwnerName', 'Policy Owner')
            ->set('newOwnerEmail', 'policy-owner@example.test')
            ->set('newOwnerPassword', 'secure-password-123')
            ->set('newOwnerPhone', '03001234567')
            ->call('provisionHallAccount')
            ->assertHasNoErrors();

        $tenant = Tenant::query()->where('slug', 'onboarding-policy-venue')->firstOrFail();
        $this->assertSame('ACTIVE', $tenant->subscription_status);
        $expected = now()->addDays($days)->toDateString();
        $this->assertSame($expected, $tenant->subscription_expires_at->toDateString());
    }
}
