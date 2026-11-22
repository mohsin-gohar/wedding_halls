<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Livewire\HallChat;
use App\Models\Hall;
use App\Models\HallChatMessage;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChatTenantIsolationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_hall_owner_cannot_open_another_tenants_chat_thread(): void
    {
        $ownerA = User::where('email', 'owner@riwajgrand.pk')->firstOrFail();

        $tenantB = Tenant::create([
            'name' => 'Rival Banquet Group',
            'slug' => 'rival-banquet-'.uniqid(),
            'city' => 'Karachi',
            'phone' => '+92 21 111000111',
            'is_active' => true,
            'verification_status' => 'APPROVED',
        ]);

        $venueB = Venue::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Rival Complex',
            'slug' => 'rival-complex-'.uniqid(),
            'city' => 'Karachi',
            'is_active' => true,
        ]);

        $hallB = Hall::create([
            'tenant_id' => $tenantB->id,
            'venue_id' => $venueB->id,
            'name' => 'Rival Crystal Hall',
            'slug' => 'rival-crystal-'.uniqid(),
            'min_capacity' => 100,
            'max_capacity' => 400,
            'base_rent' => 90000,
            'is_active' => true,
        ]);

        HallChatMessage::create([
            'hall_id' => $hallB->id,
            'user_id' => null,
            'ip_address' => '203.0.113.50',
            'session_id' => 'victim-session-xyz',
            'sender_type' => 'customer',
            'message_type' => 'text',
            'message' => 'Secret inquiry for tenant B',
            'is_read' => false,
        ]);

        // Cross-tenant hall lookup must fail closed (404 ModelNotFound, not a leaked conversation).
        Livewire::actingAs($ownerA)
            ->test(Dashboard::class)
            ->call('openChatModal', $hallB->id, '203.0.113.50', 'victim-session-xyz')
            ->assertNotFound();
    }

    public function test_guest_cannot_select_arbitrary_customer_chat_thread(): void
    {
        $hall = Hall::where('is_active', true)
            ->whereHas('tenant', fn ($q) => $q->where('verification_status', 'APPROVED'))
            ->firstOrFail();

        HallChatMessage::create([
            'hall_id' => $hall->id,
            'user_id' => null,
            'ip_address' => '198.51.100.10',
            'session_id' => 'private-guest-session',
            'sender_type' => 'customer',
            'message_type' => 'text',
            'message' => 'Private guest chat',
            'is_read' => false,
        ]);

        Livewire::test(HallChat::class, ['slug' => $hall->slug])
            ->call('setSelectedCustomer', '198.51.100.10', 'private-guest-session')
            ->assertForbidden();
    }

    public function test_unapproved_tenant_hall_chat_is_hidden_from_public(): void
    {
        $pending = Tenant::where('verification_status', 'PENDING')->first();
        if (! $pending) {
            $this->markTestSkipped('No pending tenant in seed data.');
        }

        $hall = Hall::where('tenant_id', $pending->id)->where('is_active', true)->first();
        if (! $hall || ! $hall->slug) {
            $this->markTestSkipped('No active hall on pending tenant.');
        }

        $this->get(route('hall.chat', $hall->slug))->assertNotFound();
        $this->get(route('public.venue.chat', $hall->slug))->assertNotFound();
    }
}
