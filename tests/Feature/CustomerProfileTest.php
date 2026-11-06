<?php

namespace Tests\Feature;

use App\Livewire\CustomerDashboard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_profile_tab(): void
    {
        $customer = User::factory()->create([
            'role' => 'ROLE_CUSTOMER',
            'name' => 'Ahmad Khan',
            'email' => 'ahmad@example.com',
            'phone' => '+92 300 1234567',
        ]);

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertStatus(200)
            ->assertSee('My Profile & Account Settings');
    }

    public function test_customer_can_update_profile_details(): void
    {
        $customer = User::factory()->create([
            'role' => 'ROLE_CUSTOMER',
            'name' => 'Original Name',
            'phone' => '+92 300 1111111',
        ]);

        Livewire::actingAs($customer)
            ->test(CustomerDashboard::class)
            ->set('activeTab', 'profile')
            ->set('profileName', 'Updated Luxury Name')
            ->set('profilePhone', '+92 300 9999999')
            ->call('updateProfile')
            ->assertHasNoErrors()
            ->assertSet('profileFeedback', 'Profile details updated successfully!');

        $customer->refresh();
        $this->assertEquals('Updated Luxury Name', $customer->name);
        $this->assertEquals('+92 300 9999999', $customer->phone);
    }

    public function test_customer_can_update_password(): void
    {
        $customer = User::factory()->create([
            'role' => 'ROLE_CUSTOMER',
            'password' => Hash::make('oldpassword123'),
        ]);

        Livewire::actingAs($customer)
            ->test(CustomerDashboard::class)
            ->set('activeTab', 'profile')
            ->set('currentPassword', 'oldpassword123')
            ->set('newPassword', 'newsecret456')
            ->set('newPassword_confirmation', 'newsecret456')
            ->call('updatePassword')
            ->assertHasNoErrors()
            ->assertSet('profileFeedback', 'Password changed successfully!');

        $customer->refresh();
        $this->assertTrue(Hash::check('newsecret456', $customer->password));
    }
}
