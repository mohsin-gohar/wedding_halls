<?php

namespace Tests\Feature;

use App\Livewire\InventoryTracker;
use App\Models\Hall;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventorySettlementPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function purchaseOrder(string $tenantId): PurchaseOrder
    {
        return PurchaseOrder::create([
            'tenant_id' => $tenantId,
            'po_number' => 'PO-'.strtoupper(str()->random(10)),
            'supplier_name' => 'Test Supplier',
            'total_amount' => 25000,
            'status' => 'ORDERED',
            'order_date' => now()->toDateString(),
            'items_json' => [],
            'notes' => 'Permission boundary test',
        ]);
    }

    public function test_inventory_manager_cannot_settle_a_purchase_order(): void
    {
        $hall = Hall::query()->firstOrFail();
        $manager = User::factory()->create([
            'tenant_id' => $hall->tenant_id,
            'role' => 'ROLE_INVENTORY_MANAGER',
            'status' => 'ACTIVE',
        ]);
        $po = $this->purchaseOrder($hall->tenant_id);

        Livewire::actingAs($manager)
            ->test(InventoryTracker::class)
            ->call('markPoPaid', $po->id)
            ->assertForbidden();

        self::assertSame('ORDERED', $po->fresh()->status);
        $this->assertDatabaseMissing('expenses', ['receipt_number' => $po->po_number]);
    }

    public function test_accountant_can_settle_a_purchase_order_without_stock_management(): void
    {
        $hall = Hall::query()->firstOrFail();
        $accountant = User::factory()->create([
            'tenant_id' => $hall->tenant_id,
            'role' => 'ROLE_ACCOUNTANT',
            'status' => 'ACTIVE',
        ]);
        $po = $this->purchaseOrder($hall->tenant_id);

        Livewire::actingAs($accountant)
            ->test(InventoryTracker::class)
            ->call('markPoPaid', $po->id)
            ->assertHasNoErrors();

        self::assertSame('PAID', $po->fresh()->status);
        $this->assertDatabaseHas('expenses', [
            'tenant_id' => $hall->tenant_id,
            'receipt_number' => $po->po_number,
            'amount' => '25000.00',
        ]);
    }
}
