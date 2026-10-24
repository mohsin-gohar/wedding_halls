<?php

namespace Tests\Feature;

use App\Livewire\HallReviews;
use App\Models\Hall;
use App\Models\Review;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewsAndRatingsTest extends TestCase
{
    use RefreshDatabase;

    protected function makeHall(): Hall
    {
        $tenant = Tenant::create([
            'name' => 'Royal Pearl Banquets',
            'slug' => 'royal-pearl-banquets',
            'subdomain' => 'royal-pearl-banquets',
            'is_active' => true,
            'verification_status' => 'APPROVED',
        ]);

        $venue = Venue::create([
            'tenant_id' => $tenant->id,
            'name' => 'Royal Pearl Complex',
            'slug' => 'royal-pearl-complex',
            'city' => 'Lahore',
            'area' => 'Gulberg III',
            'address' => 'Main Boulevard Gulberg',
            'contact_phone' => '03001234567',
        ]);

        return Hall::create([
            'tenant_id' => $tenant->id,
            'venue_id' => $venue->id,
            'name' => 'Emerald Crystal Hall',
            'slug' => 'emerald-crystal-hall',
            'min_capacity' => 200,
            'max_capacity' => 800,
            'base_rent' => 250000,
            'is_active' => true,
        ]);
    }

    protected function makeCustomer(string $email = 'customer_review@test.pk'): User
    {
        return User::create([
            'name' => 'Customer User',
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => 'ROLE_CUSTOMER',
            'tenant_id' => null,
        ]);
    }

    public function test_guest_cannot_submit_review(): void
    {
        $hall = $this->makeHall();

        Livewire::test(HallReviews::class, ['hallId' => $hall->id])
            ->set('body', 'Great venue with wonderful service and food!')
            ->call('submit')
            ->assertSet('error', 'Please log in to leave a review.');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_customer_can_submit_review_and_it_is_pending(): void
    {
        $hall = $this->makeHall();
        $customer = $this->makeCustomer();

        $this->actingAs($customer);

        Livewire::test(HallReviews::class, ['hallId' => $hall->id])
            ->set('rating', 5)
            ->set('title', 'Magical evening')
            ->set('body', 'Absolutely beautiful venue with great staff and food.')
            ->call('submit')
            ->assertSet('submitted', true);

        $this->assertDatabaseHas('reviews', [
            'hall_id' => $hall->id,
            'user_id' => $customer->id,
            'rating' => 5,
            'status' => 'pending',
        ]);
    }

    public function test_duplicate_review_is_rejected(): void
    {
        $hall = $this->makeHall();
        $customer = $this->makeCustomer();

        Review::create([
            'user_id' => $customer->id,
            'hall_id' => $hall->id,
            'rating' => 4,
            'body' => 'Already reviewed this venue before.',
            'status' => 'approved',
        ]);

        $this->actingAs($customer);

        Livewire::test(HallReviews::class, ['hallId' => $hall->id])
            ->set('body', 'Trying to review again should fail.')
            ->call('submit')
            ->assertSet('error', 'You have already reviewed this venue.');

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_average_counts_only_approved_reviews(): void
    {
        $hall = $this->makeHall();
        $customer = $this->makeCustomer('a@test.pk');
        $other = $this->makeCustomer('b@test.pk');

        Review::create([
            'user_id' => $customer->id,
            'hall_id' => $hall->id,
            'rating' => 4,
            'body' => 'Great venue approved review text.',
            'status' => 'approved',
        ]);

        Review::create([
            'user_id' => $other->id,
            'hall_id' => $hall->id,
            'rating' => 1,
            'body' => 'Pending review should not be visible yet.',
            'status' => 'pending',
        ]);

        $this->assertEquals(4.0, Review::averageForHall($hall->id));
        $this->assertEquals(1, Review::countForHall($hall->id));
    }
}
