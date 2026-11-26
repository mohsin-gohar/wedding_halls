<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\InventoryItem;
use App\Models\InventoryLog;
use App\Models\PurchaseOrder;
use App\Support\CashOnlyPayments;
use App\Support\PermissionMatrix;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Inventory & Kitchen Procurement — Riwaj Grand ERP'])]
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

    /** Server-issued token: one successful submission per movement request. */
    public ?string $movementRequestToken = null;

    // Create Purchase Order Modal
    public bool $showPoModal = false;

    public string $poSupplierName = '';

    public string $poDate = '';

    public float $poTotalAmount = 25000.0;

    public string $poNotes = '';

    public function mount()
    {
        $user = Auth::user();
        abort_unless($user && PermissionMatrix::userCan($user, 'inventory.view'), 403, 'Inventory view permission is required.');

        $this->poDate = Carbon::today()->format('Y-m-d');
    }

    /** Authorization is checked on every mutation, not only on mount. */
    private function authorizeInventoryAction(string $action): void
    {
        $user = Auth::user();
        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        if ($action === 'po.paid') {
            abort_unless(
                PermissionMatrix::userCan($user, 'inventory.view')
                    && PermissionMatrix::userCan($user, 'expenses.manage'),
                403,
                'Inventory view and expense management permissions are required to settle a purchase order.'
            );

            return;
        }

        $permission = $action === 'inventory.view' ? 'inventory.view' : 'inventory.manage';
        $message = $permission === 'inventory.view'
            ? 'Inventory view permission is required.'
            : 'Inventory management permission is required for this action.';

        abort_unless(PermissionMatrix::userCan($user, $permission), 403, $message);
    }

    public function setTab(string $tab)
    {
        $this->activeTab = $tab;
        $this->feedbackMessage = null;
        $this->errorMessage = null;
    }

    // ==========================================
    // ITEM CRUD
    // ==========================================
    public function openCreateItemModal()
    {
        $this->authorizeInventoryAction('item.create');
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
        $this->authorizeInventoryAction('item.update');
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
        $this->authorizeInventoryAction($this->isEditingItem ? 'item.update' : 'item.create');

        $this->validate([
            'itemName' => 'required|string|min:2|max:120',
            'itemCategory' => 'required|string|max:60',
            'itemUnit' => 'required|string|max:30',
            'itemCurrentStock' => 'required|numeric|min:0|max:99999999.99',
            'itemMinThreshold' => 'required|numeric|min:0|max:99999999.99',
            'itemUnitCost' => 'required|numeric|min:0|max:9999999999.99',
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
        $this->authorizeInventoryAction('item.delete');

        $tenantId = Auth::user()->tenant_id;
        $item = InventoryItem::where('tenant_id', $tenantId)->findOrFail($itemId);
        $name = $item->name;

        // Never erase an item that still has stock-ledger history: archive it
        // instead so past valuations and movement references stay intact.
        if ($item->logs()->exists()) {
            $item->update(['is_active' => false]);
            AuditLog::log(
                'INVENTORY_ITEM_ARCHIVED',
                "Inventory item [{$name}] was archived because stock movements reference it.",
                $tenantId,
                'InventoryItem',
                $item->id
            );
            $this->feedbackMessage = "Inventory item [{$name}] has movement history and was archived (not deleted).";

            return;
        }

        $item->delete();
        AuditLog::log(
            'INVENTORY_ITEM_DELETED',
            "Inventory item [{$name}] was deleted (no movement history).",
            $tenantId,
            'InventoryItem',
            $itemId
        );
        $this->feedbackMessage = "Inventory item [{$name}] deleted.";
    }

    // ==========================================
    // STOCK MOVEMENT
    // ==========================================
    public function openMovementModal(string $itemId, string $defaultType = 'STOCK_IN')
    {
        $this->authorizeInventoryAction('stock.movement');

        validator(['movementType' => $defaultType], [
            'movementType' => ['required', Rule::in(InventoryLog::TYPES)],
        ])->validate();

        $item = InventoryItem::where('tenant_id', Auth::user()->tenant_id)->findOrFail($itemId);
        $this->movementItemId = $item->id;
        $this->movementItemName = $item->name;
        $this->movementType = $defaultType;
        $this->movementQuantity = 10.0;
        $this->movementUnitPrice = (float) $item->unit_cost;
        $this->movementNote = '';
        $this->movementRequestToken = (string) Str::ulid();
        $this->showMovementModal = true;
    }

    public function recordStockMovement()
    {
        $this->authorizeInventoryAction('stock.movement');

        // Match the decimal(10,2) ledger precision before validating so a
        // sub-cent submission is rejected rather than silently truncated by
        // the database driver. Integers remain valid two-decimal amounts.
        $rawQuantity = $this->movementQuantity;
        $rawUnitPrice = $this->movementUnitPrice;

        if (is_numeric($this->movementQuantity)) {
            $this->movementQuantity = round((float) $this->movementQuantity, 2);
        }
        if (is_numeric($this->movementUnitPrice)) {
            $this->movementUnitPrice = round((float) $this->movementUnitPrice, 2);
        }

        $tenantId = Auth::user()->tenant_id;
        $itemRule = Rule::exists('inventory_items', 'id')->where('tenant_id', $tenantId);

        $this->validate([
            'movementItemId' => ['required', 'string', $itemRule],
            'movementType' => ['required', Rule::in(InventoryLog::TYPES)],
            'movementQuantity' => [
                'required', 'numeric', 'min:0.1', 'max:99999999.99',
                $this->twoDecimalRule($rawQuantity),
            ],
            'movementUnitPrice' => [
                'required', 'numeric', 'min:0', 'max:9999999999.99',
                $this->twoDecimalRule($rawUnitPrice),
            ],
            'movementNote' => 'nullable|string|max:255',
        ]);

        $token = $this->movementRequestToken;
        if (! is_string($token) || ! preg_match('/^[A-Za-z0-9]{8,64}$/', $token)) {
            $token = (string) Str::ulid();
            $this->movementRequestToken = $token;
        }
        $token = Str::upper($token);

        // The marker lives inside the existing note column (no schema change)
        // and is checked under the stock row lock, so a retried submission can
        // never apply the same movement twice.
        $marker = '[movement-token:'.$token.']';
        $note = trim((string) $this->movementNote);
        $referenceNote = $note === ''
            ? $marker
            : $marker.' '.Str::limit($note, max(1, 255 - strlen($marker) - 1), '');

        $itemId = $this->movementItemId;
        $movementType = $this->movementType;
        $qty = (float) $this->movementQuantity;
        $unitPrice = (float) $this->movementUnitPrice;
        $totalCost = round($qty * $unitPrice, 2);
        if (! is_finite($totalCost) || $totalCost > 9999999999.99) {
            $this->addError('movementQuantity', 'Movement total exceeds the supported inventory ledger amount.');

            return;
        }

        $result = DB::transaction(function () use ($tenantId, $itemId, $movementType, $qty, $unitPrice, $totalCost, $marker, $referenceNote) {
            // Lock the stock row so concurrent movements cannot oversell or
            // overwrite each other's resulting quantity.
            $item = InventoryItem::where('tenant_id', $tenantId)
                ->whereKey($itemId)
                ->lockForUpdate()
                ->firstOrFail();

            $currentStock = (float) $item->current_stock;

            $alreadyLogged = InventoryLog::where('tenant_id', $tenantId)
                ->where('reference_note', 'like', $marker.'%')
                ->lockForUpdate()
                ->first();

            if ($alreadyLogged) {
                return ['duplicate' => true, 'item' => $item->fresh(), 'stock' => $currentStock];
            }

            if ($movementType === 'STOCK_IN') {
                $newStock = $currentStock + $qty;
            } elseif ($movementType === 'STOCK_OUT' || $movementType === 'WASTAGE') {
                if ($currentStock < $qty) {
                    return ['error' => "Cannot deduct {$qty} {$item->unit}; available stock is only {$currentStock} {$item->unit}."];
                }
                $newStock = $currentStock - $qty;
            } else {
                // ADJUSTMENT is an absolute physical count.
                $newStock = $qty;
            }

            if (! is_finite($newStock) || $newStock > 99999999.99) {
                return ['error' => 'Resulting stock exceeds the supported inventory quantity range.'];
            }

            $item->update(['current_stock' => round($newStock, 2)]);

            InventoryLog::create([
                'tenant_id' => $tenantId,
                'inventory_item_id' => $item->id,
                'type' => $movementType,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'total_cost' => $totalCost,
                'reference_note' => $referenceNote,
                'logged_by' => Auth::id(),
            ]);

            AuditLog::log(
                'INVENTORY_MOVEMENT',
                "Recorded [{$movementType}] of {$qty} {$item->unit} for [{$item->name}].",
                $tenantId,
                'InventoryItem',
                $item->id
            );

            return ['item' => $item->fresh(), 'stock' => $newStock];
        });

        if (isset($result['duplicate'])) {
            $this->showMovementModal = false;
            $this->errorMessage = null;
            $this->feedbackMessage = "Stock movement was already recorded for this request. Current stock of [{$result['item']->name}] is still ".number_format((float) $result['stock'], 2)." {$result['item']->unit}.";

            return;
        }

        if (isset($result['error'])) {
            $this->errorMessage = $result['error'];

            return;
        }

        $this->showMovementModal = false;
        $this->errorMessage = null;
        $this->feedbackMessage = "Stock movement logged. Current stock of [{$result['item']->name}] is now ".number_format((float) $result['stock'], 2)." {$result['item']->unit}.";
    }

    /**
     * Validate ledger precision without Laravel's `decimal:2`, which rejects
     * whole numbers such as 10 even though they fit decimal(10,2).
     */
    private function twoDecimalRule(mixed $original): callable
    {
        return static function (string $attribute, mixed $value, callable $fail) use ($original): void {
            if (! is_numeric($original)) {
                return;
            }

            if (abs((float) $original - round((float) $original, 2)) > 0.000001) {
                $fail(__('validation.decimal', [
                    'attribute' => str_replace('_', ' ', $attribute),
                    'decimal' => 2,
                ]));
            }
        };
    }

    // ==========================================
    // PURCHASE ORDERS / PROCUREMENT
    // ==========================================
    public function openPoModal()
    {
        $this->authorizeInventoryAction('po.create');
        $this->poSupplierName = '';
        $this->poDate = Carbon::today()->format('Y-m-d');
        $this->poTotalAmount = 25000.0;
        $this->poNotes = '';
        $this->showPoModal = true;
    }

    public function createPurchaseOrder()
    {
        $this->authorizeInventoryAction('po.create');

        $this->validate([
            'poSupplierName' => 'required|string|min:3|max:120',
            'poTotalAmount' => 'required|numeric|min:100|max:9999999999.99',
            'poDate' => 'required|date',
        ]);

        $tenantId = Auth::user()->tenant_id;
        $poNumber = 'PO-'.now()->format('Ymd').'-'.Str::upper((string) Str::ulid());

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
        $this->authorizeInventoryAction('po.paid');

        $tenantId = Auth::user()->tenant_id;
        $paymentMethod = CashOnlyPayments::assert(CashOnlyPayments::CASH, 'purchase order settlement');

        // The PO row lock serialises concurrent clicks. The expense receipt
        // number (the PO number) is the idempotency key: a second call can
        // never post a duplicate ledger entry.
        $outcome = DB::transaction(function () use ($poId, $tenantId, $paymentMethod) {
            $po = PurchaseOrder::where('tenant_id', $tenantId)
                ->whereKey($poId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($po->status === 'PAID') {
                return ['result' => 'already', 'po' => $po];
            }

            if (! in_array($po->status, ['ORDERED', 'RECEIVED'], true)) {
                return ['result' => 'invalid', 'po' => $po];
            }

            $alreadyPosted = Expense::where('tenant_id', $tenantId)
                ->where('receipt_number', $po->po_number)
                ->exists();

            if (! $alreadyPosted) {
                // Auto-post expense to ERP ledger under RAW_FOOD_MEAT.
                Expense::create([
                    'tenant_id' => $tenantId,
                    'category' => 'RAW_FOOD_MEAT',
                    'title' => "Supplier Procurement - {$po->supplier_name} ({$po->po_number})",
                    'amount' => $po->total_amount,
                    'expense_date' => Carbon::today()->format('Y-m-d'),
                    'payee' => $po->supplier_name,
                    'payment_method' => $paymentMethod,
                    'receipt_number' => $po->po_number,
                    'notes' => $po->notes ?: 'Inventory procurement purchase order cleared.',
                    'created_by_user_id' => Auth::id(),
                ]);
            }

            $po->update(['status' => 'PAID']);

            AuditLog::log(
                'PO_PAID',
                "Purchase order [{$po->po_number}] of PKR ".number_format($po->total_amount).' marked as PAID and auto-logged to Expenses.',
                $tenantId,
                'PurchaseOrder',
                $po->id
            );

            return ['result' => $alreadyPosted ? 'recovered' : 'paid', 'po' => $po];
        });

        if ($outcome['result'] === 'invalid') {
            $this->errorMessage = "Purchase order [{$outcome['po']->po_number}] is {$outcome['po']->status} and cannot be paid.";

            return;
        }

        if ($outcome['result'] === 'already') {
            $this->errorMessage = "Purchase order [{$outcome['po']->po_number}] has already been paid. No second expense was posted.";

            return;
        }

        $this->feedbackMessage = $outcome['result'] === 'recovered'
            ? "Purchase order [{$outcome['po']->po_number}] was already posted to the ledger and is now correctly marked as PAID."
            : "Purchase order [{$outcome['po']->po_number}] marked as PAID and posted to ERP Expense Ledger.";
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
