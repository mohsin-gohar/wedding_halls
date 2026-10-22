<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\InventoryItem;
use App\Models\InventoryLog;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Inventory & Kitchen Procurement — VenueCloud ERP'])]
class InventoryTracker extends Component
{
    public string $activeTab = 'stock'; // stock, movement, procurement

    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    // Filter
    public string $selectedCategory = 'ALL';

    // Add / Edit Item Modal
    public bool $showItemModal = false;

    public bool $isEditingItem = false;

    public ?string $editingItemId = null;

    public string $itemName = '';

    public string $itemCategory = 'RAW_FOOD';

    public string $itemUnit = 'KG';

    public float $itemCurrentStock = 50.0;

    public float $itemMinThreshold = 15.0;

    public float $itemUnitCost = 1200.0;

    public string $itemSupplierName = '';

    public string $itemSupplierPhone = '';

    // Stock Movement Modal (Stock In / Out)
    public bool $showMovementModal = false;

    public ?string $movementItemId = null;

    public string $movementItemName = '';

    public string $movementType = 'STOCK_IN'; // STOCK_IN, STOCK_OUT, ADJUSTMENT, WASTAGE

    public float $movementQuantity = 10.0;

    public float $movementUnitPrice = 0.0;

    public string $movementNote = '';

    // Create Purchase Order Modal
    public bool $showPoModal = false;

    public string $poSupplierName = '';

    public string $poDate = '';

    public float $poTotalAmount = 25000.0;

    public string $poNotes = '';

    public function mount()
    {
        $user = Auth::user();
        if (! $user || (! $user->isTenantOwner() && ! $user->isManager() && ! $user->isChef() && ! $user->isAccountant() && ! $user->isSuperAdmin())) {
            abort(403, 'Unauthorized. Access to Inventory ERP is restricted to Owners, Chefs, and Managers.');
        }

        $this->poDate = Carbon::today()->format('Y-m-d');
        $this->seedInitialItemsIfEmpty();
    }

    public function setTab(string $tab)
    {
        $this->activeTab = $tab;
        $this->feedbackMessage = null;
        $this->errorMessage = null;
    }

    protected function seedInitialItemsIfEmpty()
    {
        $tenantId = Auth::user()->tenant_id;
        if ($tenantId && InventoryItem::where('tenant_id', $tenantId)->count() === 0) {
            $defaultItems = [
                ['name' => 'Mutton Goat (Fresh Karahi Cut)', 'category' => 'RAW_FOOD', 'unit' => 'KG', 'current_stock' => 120, 'min_threshold' => 30, 'unit_cost' => 2200, 'supplier_name' => 'Punjab Meat Mart'],
                ['name' => 'Broiler Chicken (Curry Cut)', 'category' => 'RAW_FOOD', 'unit' => 'KG', 'current_stock' => 250, 'min_threshold' => 50, 'unit_cost' => 650, 'supplier_name' => 'Al-Noor Poultry'],
                ['name' => 'Super Kernel Basmati Rice (Aged)', 'category' => 'RAW_FOOD', 'unit' => 'BAGS', 'current_stock' => 25, 'min_threshold' => 8, 'unit_cost' => 11500, 'supplier_name' => 'Guard Rice Mills'],
                ['name' => 'Cooking Banaspati Ghee', 'category' => 'RAW_FOOD', 'unit' => 'KG', 'current_stock' => 180, 'min_threshold' => 40, 'unit_cost' => 540, 'supplier_name' => 'Kashmir Banaspati'],
                ['name' => 'Generator Diesel Fuel (Standby)', 'category' => 'FUEL', 'unit' => 'LITRE', 'current_stock' => 650, 'min_threshold' => 200, 'unit_cost' => 285, 'supplier_name' => 'PSO Liberty Station'],
                ['name' => 'Heavy Commercial Gas Cylinders (45.4kg)', 'category' => 'FUEL', 'unit' => 'CYLINDERS', 'current_stock' => 12, 'min_threshold' => 4, 'unit_cost' => 13500, 'supplier_name' => 'Apex Gas Supply'],
                ['name' => 'Luxury Ivory Linen Tablecloths', 'category' => 'LINEN', 'unit' => 'PIECES', 'current_stock' => 150, 'min_threshold' => 50, 'unit_cost' => 1800, 'supplier_name' => 'ChenOne Corporate'],
                ['name' => 'Fine Bone China Dinner Plates', 'category' => 'CROCKERY', 'unit' => 'PIECES', 'current_stock' => 800, 'min_threshold' => 200, 'unit_cost' => 750, 'supplier_name' => 'Tariq Glass & Crockery'],
            ];

            foreach ($defaultItems as $item) {
                InventoryItem::create(array_merge($item, ['tenant_id' => $tenantId]));
            }
        }
    }

    // ==========================================
    // ITEM CRUD
    // ==========================================
    public function openCreateItemModal()
    {
        $this->isEditingItem = false;
        $this->editingItemId = null;
        $this->itemName = '';
        $this->itemCategory = 'RAW_FOOD';
        $this->itemUnit = 'KG';
        $this->itemCurrentStock = 50.0;
        $this->itemMinThreshold = 15.0;
        $this->itemUnitCost = 1200.0;
        $this->itemSupplierName = '';
        $this->itemSupplierPhone = '';
        $this->showItemModal = true;
    }

    public function openEditItemModal(string $itemId)
    {
        $item = InventoryItem::where('tenant_id', Auth::user()->tenant_id)->findOrFail($itemId);
        $this->isEditingItem = true;
        $this->editingItemId = $item->id;
        $this->itemName = $item->name;
        $this->itemCategory = $item->category;
        $this->itemUnit = $item->unit;
        $this->itemCurrentStock = (float) $item->current_stock;
        $this->itemMinThreshold = (float) $item->min_threshold;
        $this->itemUnitCost = (float) $item->unit_cost;
        $this->itemSupplierName = $item->supplier_name ?? '';
        $this->itemSupplierPhone = $item->supplier_phone ?? '';
        $this->showItemModal = true;
    }

    public function saveItem()
    {
        $this->validate([
            'itemName' => 'required|string|min:2|max:120',
            'itemCategory' => 'required|string|max:60',
            'itemUnit' => 'required|string|max:30',
            'itemCurrentStock' => 'required|numeric|min:0',
            'itemMinThreshold' => 'required|numeric|min:0',
            'itemUnitCost' => 'required|numeric|min:0',
        ]);

        $tenantId = Auth::user()->tenant_id;

        if ($this->isEditingItem && $this->editingItemId) {
            $item = InventoryItem::where('tenant_id', $tenantId)->findOrFail($this->editingItemId);
            $item->update([
                'name' => $this->itemName,
                'category' => $this->itemCategory,
                'unit' => $this->itemUnit,
                'current_stock' => $this->itemCurrentStock,
                'min_threshold' => $this->itemMinThreshold,
                'unit_cost' => $this->itemUnitCost,
                'supplier_name' => $this->itemSupplierName,
                'supplier_phone' => $this->itemSupplierPhone,
            ]);
            $this->feedbackMessage = "Inventory item [{$item->name}] updated successfully.";
        } else {
            $item = InventoryItem::create([
                'tenant_id' => $tenantId,
                'name' => $this->itemName,
                'category' => $this->itemCategory,
                'unit' => $this->itemUnit,
                'current_stock' => $this->itemCurrentStock,
                'min_threshold' => $this->itemMinThreshold,
                'unit_cost' => $this->itemUnitCost,
                'supplier_name' => $this->itemSupplierName,
                'supplier_phone' => $this->itemSupplierPhone,
                'is_active' => true,
            ]);
            $this->feedbackMessage = "New item [{$item->name}] added to inventory.";
        }

        $this->showItemModal = false;
    }

    public function deleteItem(string $itemId)
    {
        $item = InventoryItem::where('tenant_id', Auth::user()->tenant_id)->findOrFail($itemId);
        $name = $item->name;
        $item->delete();
        $this->feedbackMessage = "Inventory item [{$name}] deleted.";
    }

    // ==========================================
    // STOCK MOVEMENT
    // ==========================================
    public function openMovementModal(string $itemId, string $defaultType = 'STOCK_IN')
    {
        $item = InventoryItem::where('tenant_id', Auth::user()->tenant_id)->findOrFail($itemId);
        $this->movementItemId = $item->id;
        $this->movementItemName = $item->name;
        $this->movementType = $defaultType;
        $this->movementQuantity = 10.0;
        $this->movementUnitPrice = (float) $item->unit_cost;
        $this->movementNote = '';
        $this->showMovementModal = true;
    }

    public function recordStockMovement()
    {
        $this->validate([
            'movementQuantity' => 'required|numeric|min:0.1',
        ]);

        $item = InventoryItem::where('tenant_id', Auth::user()->tenant_id)->findOrFail($this->movementItemId);
        $tenantId = Auth::user()->tenant_id;

        $qty = (float) $this->movementQuantity;
        $totalCost = round($qty * $this->movementUnitPrice, 2);

        if ($this->movementType === 'STOCK_IN') {
            $item->current_stock += $qty;
        } elseif ($this->movementType === 'STOCK_OUT' || $this->movementType === 'WASTAGE') {
            if ($item->current_stock < $qty) {
                $this->errorMessage = "Cannot deduct {$qty} {$item->unit}; available stock is only {$item->current_stock} {$item->unit}.";

                return;
            }
            $item->current_stock -= $qty;
        } elseif ($this->movementType === 'ADJUSTMENT') {
            $item->current_stock = $qty;
        }

        $item->save();

        InventoryLog::create([
            'tenant_id' => $tenantId,
            'inventory_item_id' => $item->id,
            'type' => $this->movementType,
            'quantity' => $qty,
            'unit_price' => $this->movementUnitPrice,
            'total_cost' => $totalCost,
            'reference_note' => $this->movementNote,
            'logged_by' => Auth::id(),
        ]);

        AuditLog::log(
            'INVENTORY_MOVEMENT',
            "Recorded [{$this->movementType}] of {$qty} {$item->unit} for [{$item->name}].",
            $tenantId,
            'InventoryItem',
            $item->id
        );

        $this->showMovementModal = false;
        $this->feedbackMessage = "Stock movement logged. Current stock of [{$item->name}] is now {$item->current_stock} {$item->unit}.";
    }

    // ==========================================
    // PURCHASE ORDERS / PROCUREMENT
    // ==========================================
    public function openPoModal()
    {
        $this->poSupplierName = '';
        $this->poDate = Carbon::today()->format('Y-m-d');
        $this->poTotalAmount = 25000.0;
        $this->poNotes = '';
        $this->showPoModal = true;
    }

    public function createPurchaseOrder()
    {
        $this->validate([
            'poSupplierName' => 'required|string|min:3|max:120',
            'poTotalAmount' => 'required|numeric|min:100',
            'poDate' => 'required|date',
        ]);

        $tenantId = Auth::user()->tenant_id;
        $poNumber = 'PO-'.date('Ymd').'-'.rand(100, 999);

        PurchaseOrder::create([
            'tenant_id' => $tenantId,
            'po_number' => $poNumber,
            'supplier_name' => $this->poSupplierName,
            'total_amount' => $this->poTotalAmount,
            'status' => 'ORDERED',
            'order_date' => $this->poDate,
            'notes' => $this->poNotes,
        ]);

        $this->showPoModal = false;
        $this->feedbackMessage = "Purchase order [{$poNumber}] registered successfully.";
    }

    public function markPoPaid(string $poId)
    {
        $po = PurchaseOrder::where('tenant_id', Auth::user()->tenant_id)->findOrFail($poId);
        $po->update(['status' => 'PAID']);

        // Auto-post expense to ERP ledger under CATERING_SUPPLIES or RAW_FOOD_MEAT
        Expense::create([
            'tenant_id' => Auth::user()->tenant_id,
            'category' => 'RAW_FOOD_MEAT',
            'title' => "Supplier Procurement - {$po->supplier_name} ({$po->po_number})",
            'amount' => $po->total_amount,
            'expense_date' => Carbon::today()->format('Y-m-d'),
            'payee' => $po->supplier_name,
            'payment_method' => 'BANK_TRANSFER',
            'receipt_number' => $po->po_number,
            'notes' => $po->notes ?: 'Inventory procurement purchase order cleared.',
            'created_by_user_id' => Auth::id(),
        ]);

        AuditLog::log(
            'PO_PAID',
            "Purchase order [{$po->po_number}] of PKR ".number_format($po->total_amount).' marked as PAID and auto-logged to Expenses.',
            Auth::user()->tenant_id,
            'PurchaseOrder',
            $po->id
        );

        $this->feedbackMessage = "Purchase order [{$po->po_number}] marked as PAID and posted to ERP Expense Ledger.";
    }

    public function render()
    {
        $tenantId = Auth::user()->tenant_id;

        $itemsQuery = InventoryItem::where('tenant_id', $tenantId)->latest();
        if ($this->selectedCategory !== 'ALL') {
            $itemsQuery->where('category', $this->selectedCategory);
        }
        $items = $itemsQuery->get();

        $totalInventoryValue = $items->sum(fn ($i) => $i->current_stock * $i->unit_cost);
        $lowStockCount = $items->filter(fn ($i) => $i->isLowStock())->count();

        $logs = InventoryLog::where('tenant_id', $tenantId)
            ->with(['item', 'logger'])
            ->latest()
            ->paginate(15);

        $orders = PurchaseOrder::where('tenant_id', $tenantId)
            ->latest()
            ->paginate(15);

        return view('livewire.inventory-tracker', [
            'items' => $items,
            'logs' => $logs,
            'orders' => $orders,
            'totalInventoryValue' => $totalInventoryValue,
            'lowStockCount' => $lowStockCount,
        ]);
    }
}
