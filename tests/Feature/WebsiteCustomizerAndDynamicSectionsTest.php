<?php

namespace Tests\Feature;

use App\Livewire\HallMediaManager;
use App\Models\Hall;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WebsiteCustomizerAndDynamicSectionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_hall_owner_can_configure_website_settings_faqs_and_reviews(): void
    {
        $tenant = Tenant::create([
            'name' => 'Royal Opal Banquets',
            'slug' => 'royal-opal',
            'subscription_status' => 'ACTIVE',
            'verification_status' => 'APPROVED',
            'is_active' => true,
        ]);

        $owner = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Sheikh Rehan',
            'email' => 'rehan@royalopal.pk',
            'password' => bcrypt('Secret123!'),
            'role' => 'ROLE_TENANT_OWNER',
        ]);

        $venue = Venue::create([
            'tenant_id' => $tenant->id,
            'name' => 'Royal Opal Complex',
            'slug' => 'royal-opal-complex',
            'city' => 'Lahore',
        ]);

        $hall = Hall::create([
            'tenant_id' => $tenant->id,
            'venue_id' => $venue->id,
            'name' => 'Grand Crystal Hall',
            'base_rent' => 175000,
            'is_active' => true,
        ]);

        $this->actingAs($owner);

        // Interact with HallMediaManager to update website settings
        Livewire::test(HallMediaManager::class)
            ->set('website_settings.announcement_banner', 'Exclusive Winter 2026 15% Discount on Barat Packages!')
            ->set('website_settings.show_announcement', true)
            ->set('newFaqQuestion', 'Is sound and DJ system included in hall rent?')
            ->set('newFaqAnswer', 'Yes, a high-end JBL line-array sound system is complimentary.')
            ->call('addFaq')
            ->set('newReviewAuthor', 'Malik Zeeshan & Family')
            ->set('newReviewEvent', 'Barat Night (650 Guests)')
            ->set('newReviewRating', 5)
            ->set('newReviewQuote', 'Unmatched elegance and attentive hospitality staff.')
            ->call('addReview')
            ->call('save');

        $hall->refresh();
        $this->assertNotNull($hall->website_settings);
        $this->assertEquals('Exclusive Winter 2026 15% Discount on Barat Packages!', $hall->website_settings['announcement_banner']);
        $this->assertCount(1, $hall->website_settings['faqs']);
        $this->assertEquals('Is sound and DJ system included in hall rent?', $hall->website_settings['faqs'][0]['question']);
        $this->assertCount(1, $hall->website_settings['reviews']);

        // Check that the public landing page renders these custom dynamic elements
        $response = $this->get(route('hall.showcase', $hall->id));
        $response->assertStatus(200);
        $response->assertSee('Exclusive Winter 2026 15% Discount on Barat Packages!');
        $response->assertSee('Is sound and DJ system included in hall rent?');
        $response->assertSee('Malik Zeeshan &amp; Family', false);
    }
}
