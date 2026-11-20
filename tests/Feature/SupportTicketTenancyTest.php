<?php

namespace Tests\Feature;

use App\Livewire\SupportCenter;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupportTicketTenancyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_vendor_staff_cannot_see_or_mutate_a_customer_ticket_without_a_tenant(): void
    {
        $customer = User::factory()->create(['role' => 'ROLE_CUSTOMER', 'status' => 'ACTIVE']);
        $ticket = $this->makeTicket($customer, null);
        $vendorOwner = $this->vendorOwner();

        $this->actingAs($vendorOwner);
        $tickets = app(SupportCenter::class)->getTicketsProperty();
        $this->assertFalse($tickets->contains('id', $ticket->id));

        Livewire::actingAs($vendorOwner)
            ->test(SupportCenter::class)
            ->call('openTicket', $ticket->id)
            ->assertStatus(404);

        $this->assertSame('OPEN', $ticket->fresh()->status);
        $this->assertSame(1, TicketMessage::query()->where('support_ticket_id', $ticket->id)->count());
    }

    public function test_platform_support_can_handle_customer_tickets_without_a_tenant(): void
    {
        $customer = User::factory()->create(['role' => 'ROLE_CUSTOMER', 'status' => 'ACTIVE']);
        $ticket = $this->makeTicket($customer, null);

        $support = User::factory()->create([
            'role' => 'ROLE_PLATFORM_SUPPORT',
            'tenant_id' => null,
            'status' => 'ACTIVE',
        ]);
        $support->assignRole('ROLE_PLATFORM_SUPPORT');

        $this->actingAs($support);
        $tickets = app(SupportCenter::class)->getTicketsProperty();
        $this->assertTrue($tickets->contains('id', $ticket->id));

        Livewire::actingAs($support)
            ->test(SupportCenter::class)
            ->call('openTicket', $ticket->id)
            ->set('replyBody', 'Platform support is reviewing this request.')
            ->call('reply')
            ->assertHasNoErrors();

        $this->assertSame('IN_PROGRESS', $ticket->fresh()->status);
        $this->assertSame(2, TicketMessage::query()->where('support_ticket_id', $ticket->id)->count());
    }

    public function test_vendor_cannot_open_a_ticket_belonging_to_another_tenant(): void
    {
        $tenantB = Tenant::create([
            'name' => 'Other Tenant Support',
            'slug' => 'other-tenant-support',
            'email' => 'support-b@riwaj.pk',
            'phone' => '+92 300 2223344',
            'city' => 'Lahore',
            'subscription_plan' => 'STARTER',
            'currency' => 'PKR',
            'verification_status' => 'APPROVED',
            'is_active' => true,
        ]);
        $requester = User::factory()->create([
            'tenant_id' => $tenantB->id,
            'role' => 'ROLE_TENANT_OWNER',
            'status' => 'ACTIVE',
        ]);
        $ticket = $this->makeTicket($requester, $tenantB->id);

        Livewire::actingAs($this->vendorOwner())
            ->test(SupportCenter::class)
            ->call('openTicket', $ticket->id)
            ->assertStatus(404);

        $this->assertSame('OPEN', $ticket->fresh()->status);
    }

    private function vendorOwner(): User
    {
        $tenant = Tenant::query()->where('slug', 'nawab-caterers')->firstOrFail();

        return User::query()->where('tenant_id', $tenant->id)
            ->where('role', 'ROLE_TENANT_OWNER')
            ->firstOrFail();
    }

    private function makeTicket(User $requester, ?string $tenantId): SupportTicket
    {
        $ticket = SupportTicket::create([
            'ticket_no' => SupportTicket::generateTicketNo(),
            'user_id' => $requester->id,
            'tenant_id' => $tenantId,
            'subject' => 'Confidential customer request',
            'category' => 'GENERAL',
            'priority' => 'MEDIUM',
            'status' => 'OPEN',
        ]);

        TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $requester->id,
            'message' => 'This message must not be visible to another vendor.',
            'is_staff_reply' => false,
        ]);

        return $ticket;
    }
}
