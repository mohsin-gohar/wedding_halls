<div class="space-y-6 max-w-7xl mx-auto pb-16">

    <!-- Header Banner -->
    <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-6 sm:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6 shadow-xl transition-colors duration-200">
        <div class="space-y-1.5">
            <div class="flex items-center gap-2 font-mono text-[11px] text-[#8F7239] dark:text-[#C6A15B] uppercase tracking-widest font-bold">
                <span>Enterprise ERP</span>
                <span class="text-stone-400 dark:text-stone-600">•</span>
                <span>Procurement &amp; Kitchen Inventory</span>
            </div>
            <h1 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                Raw Materials &amp; Supply Inventory
            </h1>
            <p class="text-xs text-stone-600 dark:text-stone-300 max-w-2xl font-sans leading-relaxed">
                Real-time stock ledger for meats, basmati rice, cooking oil, generator diesel, crockery, and linen. Automated low-stock thresholds and supplier purchase order clearance.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            @if(\App\Support\PermissionMatrix::userCan(auth()->user(), 'inventory.manage'))
            <button wire:click="openCreateItemModal" class="btn-gold !h-9 !px-4 text-xs font-bold uppercase font-mono shadow-md">
                + Add Inventory Item
            </button>
            <button wire:click="openPoModal" class="btn-outline-gold !h-9 !px-4 text-xs font-mono uppercase font-bold">
                + Purchase Order
            </button>
            @endif
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if($feedbackMessage)
        <div class="p-4 bg-[#171513]/85 border border-[#C6A15B]/50 text-[#C6A15B] text-xs font-mono flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="font-bold">✓</span>
                <span>{{ $feedbackMessage }}</span>
            </div>
            <button wire:click="$set('feedbackMessage', null)" class="text-[#8F7239] hover:text-white">&times;</button>
        </div>
    @endif

    @if($errorMessage)
        <div class="p-4 bg-[#171513]/85 border border-[#C6A15B]/50 text-[#F8F5EF] text-xs font-mono flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="font-bold">⚠️</span>
                <span>{{ $errorMessage }}</span>
            </div>
            <button wire:click="$set('errorMessage', null)" class="text-stone-300 hover:text-white font-bold text-sm">&times;</button>
        </div>
    @endif

    <!-- Top KPI Metrics -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-4 space-y-1 shadow-sm dark:shadow-xl relative overflow-hidden group hover:border-[#C6A15B] transition duration-300">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <span class="text-[10px] uppercase font-mono tracking-widest text-stone-500 dark:text-stone-400 font-bold block">Total Catalog Items</span>
            <div class="font-cinzel text-xl text-[#171513] dark:text-white font-bold">{{ $items->count() }} Items</div>
        </div>

        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-4 space-y-1 shadow-sm dark:shadow-xl relative overflow-hidden group hover:border-[#C6A15B] transition duration-300">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <span class="text-[10px] uppercase font-mono tracking-widest text-stone-500 dark:text-stone-400 font-bold block">Total Valuation (PKR)</span>
            <div class="font-cinzel text-xl text-[#8F7239] dark:text-[#C6A15B] font-bold">PKR {{ number_format($totalInventoryValue) }}</div>
        </div>

        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-4 space-y-1 shadow-sm dark:shadow-xl relative overflow-hidden group hover:border-[#C6A15B] transition duration-300">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <span class="text-[10px] uppercase font-mono tracking-widest text-stone-500 dark:text-stone-400 font-bold block">Low Stock Alerts</span>
            <div class="font-cinzel text-xl {{ $lowStockCount > 0 ? 'text-[#171513] dark:text-[#F8F5EF] font-bold animate-pulse' : 'text-[#8F7239] dark:text-[#C6A15B] font-bold' }}">
                {{ $lowStockCount }} Critical Alert(s)
            </div>
        </div>

        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-4 space-y-1 shadow-sm dark:shadow-xl relative overflow-hidden group hover:border-[#C6A15B] transition duration-300">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <span class="text-[10px] uppercase font-mono tracking-widest text-stone-500 dark:text-stone-400 font-bold block">Purchase Orders</span>
            <div class="font-cinzel text-xl text-[#171513] dark:text-white font-bold">{{ $orders->total() }} Orders</div>
        </div>
    </div>

    <!-- Tab Strip -->
    <div class="flex border-b border-[#E7E5E4] dark:border-white/10 bg-white dark:bg-[#171513] text-xs font-mono uppercase overflow-x-auto">
        <button wire:click="setTab('stock')" 
                class="px-6 py-3.5 border-b-2 font-bold tracking-wider transition {{ $activeTab === 'stock' ? 'border-[#C6A15B] text-[#171513] dark:text-[#C6A15B] bg-[#F8F5EF] dark:bg-[#171513]' : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-[#171513] dark:hover:text-white' }}">
            📦 Current Stock Levels
        </button>
        <button wire:click="setTab('movement')" 
                class="px-6 py-3.5 border-b-2 font-bold tracking-wider transition {{ $activeTab === 'movement' ? 'border-[#C6A15B] text-[#171513] dark:text-[#C6A15B] bg-[#F8F5EF] dark:bg-[#171513]' : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-[#171513] dark:hover:text-white' }}">
            🔄 Stock Movement Audit
        </button>
        <button wire:click="setTab('procurement')" 
                class="px-6 py-3.5 border-b-2 font-bold tracking-wider transition {{ $activeTab === 'procurement' ? 'border-[#C6A15B] text-[#171513] dark:text-[#C6A15B] bg-[#F8F5EF] dark:bg-[#171513]' : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-[#171513] dark:hover:text-white' }}">
            📋 Supplier Purchase Orders
        </button>
    </div>

    <!-- ===================================================================
         TAB 1: CURRENT STOCK LEVELS
         =================================================================== -->
    @if($activeTab === 'stock')
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-6 space-y-6 shadow-sm dark:shadow-xl">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E7E5E4] pb-4">
                <div class="flex items-center gap-3">
                    <label class="text-xs font-mono uppercase text-stone-600 font-bold">Filter Category:</label>
                    <select wire:model.live="selectedCategory" 
                            class="bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/20 text-xs font-mono px-3 py-1.5 text-stone-900 dark:text-stone-100 outline-none focus:border-[#C6A15B]">
                        <option value="ALL">All Categories</option>
                        <option value="RAW_FOOD">Raw Food &amp; Meats</option>
                        <option value="BEVERAGES">Beverages &amp; Mineral Water</option>
                        <option value="FUEL">Generator Fuel &amp; Gas</option>
                        <option value="CROCKERY">Crockery &amp; Glassware</option>
                        <option value="LINEN">Linen &amp; Chair Covers</option>
                        <option value="CLEANING">Cleaning &amp; Janitorial</option>
                        <option value="OTHER">Other Supplies</option>
                    </select>
                </div>

                @if(\App\Support\PermissionMatrix::userCan(auth()->user(), 'inventory.manage'))
                <button wire:click="openCreateItemModal" class="btn-gold !h-8 !px-3 text-xs font-bold uppercase font-mono">
                    + New Inventory Item
                </button>
                @endif
            </div>

            <div class="table-wrapper-enterprise" tabindex="0" role="region"><table class="table-enterprise">
                    <thead>
                        <tr class="bg-[#F8F5EF] dark:bg-white/5 border-b border-[#E7E5E4] dark:border-white/10 text-[10px] uppercase font-bold tracking-wider text-stone-600 dark:text-stone-300 font-mono">
                            <th class="p-3">Item Name</th>
                            <th class="p-3">Category</th>
                            <th class="p-3">Current Stock</th>
                            <th class="p-3">Min Threshold</th>
                            <th class="p-3">Unit Cost (PKR)</th>
                            <th class="p-3">Total Value</th>
                            <th class="p-3">Preferred Supplier</th>
                            <th class="p-3 text-right">Quick Stock In / Out</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4]">
                        @forelse($items as $i)
                            <tr class="hover:bg-[#F8F5EF] dark:hover:bg-white/5 transition font-mono">
                                <td class="p-3 font-sans">
                                    <div class="font-bold text-[#171513] text-sm">{{ $i->name }}</div>
                                    <span class="text-[10px] text-stone-500 font-mono">Unit: {{ $i->unit }}</span>
                                </td>
                                <td class="p-3 text-stone-600 text-[10px]">
                                    <span class="px-2 py-0.5 bg-stone-100 border border-stone-200 uppercase font-semibold">
                                        {{ str_replace('_', ' ', $i->category) }}
                                    </span>
                                </td>
                                <td class="p-3">
                                    <div class="text-sm font-bold {{ $i->isLowStock() ? 'text-[#171513]' : 'text-[#8F7239]' }}">
                                        {{ number_format($i->current_stock, 1) }} {{ $i->unit }}
                                    </div>
                                    @if($i->isLowStock())
                                        <span class="text-[9px] font-bold text-[#171513] bg-[#171513]/8 px-1.5 py-0.5 border border-[#C6A15B]/40 uppercase">
                                            ⚠️ Low Stock
                                        </span>
                                    @endif
                                </td>
                                <td class="p-3 text-stone-600">
                                    {{ number_format($i->min_threshold, 1) }} {{ $i->unit }}
                                </td>
                                <td class="p-3 text-stone-800">
                                    PKR {{ number_format($i->unit_cost) }}
                                </td>
                                <td class="p-3 font-bold text-[#171513]">
                                    PKR {{ number_format($i->current_stock * $i->unit_cost) }}
                                </td>
                                <td class="p-3 text-stone-600 font-sans">
                                    {{ $i->supplier_name ?: 'Market Vendor' }}
                                </td>
                                <td class="p-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-1.5 font-sans">
                                        @if(\App\Support\PermissionMatrix::userCan(auth()->user(), 'inventory.manage'))
                                        <button wire:click="openMovementModal('{{ $i->id }}', 'STOCK_IN')" 
                                                class="tbtn tbtn-green" title="Add Stock In">
                                            + In
                                        </button>
                                        <button wire:click="openMovementModal('{{ $i->id }}', 'STOCK_OUT')" 
                                                class="tbtn tbtn-amber" title="Record Usage Deduction">
                                            - Out
                                        </button>
                                        <button wire:click="openEditItemModal('{{ $i->id }}')" 
                                                class="tbtn tbtn-outline" title="Edit Item Details">
                                            Edit
                                        </button>
                                        <button wire:click="deleteItem('{{ $i->id }}')" 
                                                onclick="return confirm('Delete this inventory item?') || event.stopImmediatePropagation()"
                                                class="tbtn tbtn-red" title="Delete Item">
                                            Del
                                        </button>
                                        @else
                                            <span class="text-[10px] font-mono text-stone-400">View only</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-8 text-center text-stone-400 font-sans">
                                    No inventory items found. Click "+ Add Inventory Item" to register your stock.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         TAB 2: STOCK MOVEMENT AUDIT
         =================================================================== -->
    @if($activeTab === 'movement')
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-6 space-y-6 shadow-sm dark:shadow-xl">
            <div class="border-b border-[#E7E5E4] pb-3">
                <h3 class="font-serif text-2xl text-[#171513] font-normal">Stock Movements &amp; Wastage Audit</h3>
                <p class="text-xs text-stone-500">Immutable audit log of all stock arrivals, event wedding consumption, and wastage.</p>
            </div>

            <div class="table-wrapper-enterprise" tabindex="0" role="region"><table class="table-enterprise">
                    <thead>
                        <tr class="bg-[#F8F5EF] dark:bg-white/5 border-b border-[#E7E5E4] dark:border-white/10 text-[10px] uppercase font-bold tracking-wider text-stone-600 dark:text-stone-300 font-mono">
                            <th class="p-3">Timestamp</th>
                            <th class="p-3">Item</th>
                            <th class="p-3">Movement Type</th>
                            <th class="p-3">Quantity</th>
                            <th class="p-3">Unit Cost</th>
                            <th class="p-3">Total Value</th>
                            <th class="p-3">Event Reference / Notes</th>
                            <th class="p-3 text-right">Logged By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4]">
                        @forelse($logs as $lg)
                            <tr class="hover:bg-[#F8F5EF] dark:hover:bg-white/5 transition font-mono">
                                <td class="p-3 text-stone-500">{{ $lg->created_at->format('d M Y, H:i') }}</td>
                                <td class="p-3 font-sans font-bold text-stone-900">{{ $lg->item->name ?? 'Deleted Item' }}</td>
                                <td class="p-3">
                                    @if($lg->type === 'STOCK_IN')
                                        <span class="px-2 py-0.5 bg-[#C6A15B]/20 text-[#8F7239] border border-[#C6A15B]/60 font-bold uppercase text-[10px]">+ STOCK IN</span>
                                    @elseif($lg->type === 'STOCK_OUT')
                                        <span class="px-2 py-0.5 bg-[#C6A15B]/20 text-[#8F7239] border border-[#C6A15B]/60 font-bold uppercase text-[10px]">- USAGE OUT</span>
                                    @elseif($lg->type === 'WASTAGE')
                                        <span class="px-2 py-0.5 bg-[#171513]/10 text-[#171513] border border-[#C6A15B]/50 font-bold uppercase text-[10px]">✕ WASTAGE</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-[#C6A15B]/15 text-[#8F7239] border border-[#C6A15B]/60 font-bold uppercase text-[10px]">ADJUSTMENT</span>
                                    @endif
                                </td>
                                <td class="p-3 font-bold text-stone-900">
                                    {{ number_format($lg->quantity, 1) }} {{ $lg->item->unit ?? '' }}
                                </td>
                                <td class="p-3 text-stone-700">
                                    PKR {{ number_format($lg->unit_price, 2) }}
                                </td>
                                <td class="p-3 font-bold text-[#171513]">
                                    PKR {{ number_format($lg->total_cost, 2) }}
                                </td>
                                <td class="p-3 font-sans text-stone-600 max-w-xs truncate">
                                    {{ $lg->reference_note ?: 'Direct adjustment' }}
                                </td>
                                <td class="p-3 text-right text-stone-500 font-sans">
                                    {{ $lg->logger->name ?? 'Staff' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-8 text-center text-stone-400 font-sans">
                                    No stock movements logged yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div>
                {{ $logs->links() }}
            </div>
        </div>
    @endif

    <!-- ===================================================================
         TAB 3: SUPPLIER PURCHASE ORDERS
         =================================================================== -->
    @if($activeTab === 'procurement')
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-6 space-y-6 shadow-sm dark:shadow-xl">
            <div class="flex items-center justify-between border-b border-[#E7E5E4] pb-3">
                <div>
                    <h3 class="font-serif text-2xl text-[#171513] font-normal">Supplier Purchase Orders</h3>
                    <p class="text-xs text-stone-500">Track vendor orders. Marking an order as PAID automatically posts it to the ERP Expense Ledger.</p>
                </div>
                @if(\App\Support\PermissionMatrix::userCan(auth()->user(), 'inventory.manage'))
                <button wire:click="openPoModal" class="btn-gold !h-9 !px-4 text-xs font-bold uppercase font-mono">
                    + New Purchase Order
                </button>
                @endif
            </div>

            <div class="table-wrapper-enterprise" tabindex="0" role="region"><table class="table-enterprise">
                    <thead>
                        <tr class="bg-[#F8F5EF] dark:bg-white/5 border-b border-[#E7E5E4] dark:border-white/10 text-[10px] uppercase font-bold tracking-wider text-stone-600 dark:text-stone-300 font-mono">
                            <th class="p-3">PO Number</th>
                            <th class="p-3">Order Date</th>
                            <th class="p-3">Supplier Name</th>
                            <th class="p-3">Total Amount (PKR)</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Notes</th>
                            <th class="p-3 text-right">Settlement</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4]">
                        @forelse($orders as $po)
                            <tr class="hover:bg-[#F8F5EF] dark:hover:bg-white/5 transition font-mono">
                                <td class="p-3 font-bold text-stone-900">{{ $po->po_number }}</td>
                                <td class="p-3 text-stone-500">{{ $po->order_date->format('d M Y') }}</td>
                                <td class="p-3 font-sans font-bold text-stone-900">{{ $po->supplier_name }}</td>
                                <td class="p-3 font-bold text-base text-[#171513]">PKR {{ number_format($po->total_amount) }}</td>
                                <td class="p-3">
                                    @if($po->status === 'PAID')
                                        <span class="px-2 py-0.5 bg-[#C6A15B]/20 text-[#8F7239] border border-[#C6A15B]/60 font-bold uppercase text-[10px]">
                                            ✓ PAID (IN EXPENSES)
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 bg-[#C6A15B]/20 text-[#8F7239] border border-[#C6A15B]/60 font-bold uppercase text-[10px]">
                                            ⏳ ORDERED / PENDING
                                        </span>
                                    @endif
                                </td>
                                <td class="p-3 font-sans text-stone-600 max-w-xs truncate">
                                    {{ $po->notes ?: 'General stock replenishment' }}
                                </td>
                                <td class="p-3 text-right">
                                    @if($po->status !== 'PAID' && \App\Support\PermissionMatrix::userCan(auth()->user(), 'expenses.manage'))
                                        <button wire:click="markPoPaid('{{ $po->id }}')" 
                                                class="px-3 py-1 bg-[#C6A15B] hover:bg-[#C6A15B] text-white font-bold text-xs uppercase shadow">
                                            Mark Paid &rarr;
                                        </button>
                                    @elseif($po->status === 'PAID')
                                        <span class="text-stone-400 text-[10px]">Settled &amp; Posted</span>
                                    @else
                                        <span class="text-stone-400 text-[10px]">View only</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-8 text-center text-stone-400 font-sans">
                                    No purchase orders recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div>
                {{ $orders->links() }}
            </div>
        </div>
    @endif

    <!-- ===================================================================
         MODAL 1: ADD / EDIT INVENTORY ITEM
         =================================================================== -->
    @if($showItemModal && \App\Support\PermissionMatrix::userCan(auth()->user(), 'inventory.manage'))
        <div class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4">
            <div class="bg-[#171513] border-2 border-[#C6A15B] max-w-lg w-full p-6 space-y-5 text-white shadow-2xl">
                <div class="border-b border-white/10 pb-3 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono uppercase text-[#C6A15B] tracking-widest font-bold">Catalog Management</span>
                        <h3 class="font-serif text-2xl text-white font-normal mt-0.5">
                            {{ $isEditingItem ? 'Edit Item' : 'New Inventory Item' }}
                        </h3>
                    </div>
                    <button wire:click="$set('showItemModal', false)" class="text-white/60 hover:text-white text-2xl font-mono leading-none">&times;</button>
                </div>

                <div class="space-y-4 font-sans text-xs">
                    <div>
                        <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Item Name *</label>
                        <input type="text" wire:model="itemName" placeholder="e.g. Mutton Goat (Fresh Cut)" 
                               class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                        @error('itemName') <span class="text-rose-400 text-[10px] font-mono mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Category *</label>
                            <select wire:model="itemCategory" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                                <option value="RAW_FOOD">Raw Food &amp; Meats</option>
                                <option value="BEVERAGES">Beverages &amp; Mineral Water</option>
                                <option value="FUEL">Generator Diesel &amp; Fuel</option>
                                <option value="CROCKERY">Crockery &amp; Glassware</option>
                                <option value="LINEN">Linen &amp; Chair Covers</option>
                                <option value="CLEANING">Cleaning &amp; Janitorial</option>
                                <option value="OTHER">Other Supplies</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Unit Measurement *</label>
                            <select wire:model="itemUnit" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                                <option value="KG">Kilograms (KG)</option>
                                <option value="LITRE">Litres (L)</option>
                                <option value="BAGS">Bags / Boray</option>
                                <option value="PIECES">Pieces (Pcs)</option>
                                <option value="CYLINDERS">Cylinders</option>
                                <option value="BOXES">Boxes / Cartons</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Initial Stock *</label>
                            <input type="number" step="0.5" wire:model="itemCurrentStock" 
                                   class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Min Alert Threshold *</label>
                            <input type="number" step="0.5" wire:model="itemMinThreshold" 
                                   class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Unit Cost (PKR) *</label>
                            <input type="number" step="10" wire:model="itemUnitCost" 
                                   class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Preferred Supplier</label>
                            <input type="text" wire:model="itemSupplierName" placeholder="e.g. Punjab Meat Mart" 
                                   class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                        </div>
                        <div>
                            <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Supplier Phone</label>
                            <input type="text" wire:model="itemSupplierPhone" placeholder="+92 300 1234567" 
                                   class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                        </div>
                    </div>
                </div>

                <div class="border-t border-white/10 pt-3 flex items-center justify-end gap-2 font-mono text-xs">
                    <button wire:click="$set('showItemModal', false)" class="px-4 py-2 border border-white/20 text-white">Cancel</button>
                    <button wire:click="saveItem" class="btn-gold !h-9 !px-5 text-xs font-bold uppercase">Save Item &rarr;</button>
                </div>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         MODAL 2: STOCK MOVEMENT (STOCK IN / OUT)
         =================================================================== -->
    @if($showMovementModal && \App\Support\PermissionMatrix::userCan(auth()->user(), 'inventory.manage'))
        <div class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4">
            <div class="bg-[#171513] border-2 border-[#C6A15B] max-w-md w-full p-6 space-y-5 text-white shadow-2xl">
                <div class="border-b border-white/10 pb-3 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono uppercase text-[#C6A15B] tracking-widest font-bold">Stock Ledger Update</span>
                        <h3 class="font-serif text-xl text-white font-normal mt-0.5">{{ $movementItemName }}</h3>
                    </div>
                    <button wire:click="$set('showMovementModal', false)" class="text-white/60 hover:text-white text-2xl font-mono leading-none">&times;</button>
                </div>

                <div class="space-y-4 font-sans text-xs">
                    <div>
                        <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Movement Action *</label>
                        <select wire:model="movementType" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                            <option value="STOCK_IN">+ Stock In (Arrival / Purchase)</option>
                            <option value="STOCK_OUT">- Stock Out (Kitchen / Event Consumption)</option>
                            <option value="WASTAGE">✕ Wastage / Spoilage</option>
                            <option value="ADJUSTMENT">= Physical Audit Adjustment</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Quantity *</label>
                            <input type="number" step="0.5" wire:model="movementQuantity" 
                                   class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Unit Price (PKR)</label>
                            <input type="number" step="10" wire:model="movementUnitPrice" 
                                   class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Event / Booking Reference / Reason</label>
                        <input type="text" wire:model="movementNote" placeholder="e.g. Walima dinner for 500 guests" 
                               class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                    </div>
                </div>

                <div class="border-t border-white/10 pt-3 flex items-center justify-end gap-2 font-mono text-xs">
                    <button wire:click="$set('showMovementModal', false)" class="px-4 py-2 border border-white/20 text-white">Cancel</button>
                    <button wire:click="recordStockMovement" class="btn-gold !h-9 !px-5 text-xs font-bold uppercase">Record Movement &rarr;</button>
                </div>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         MODAL 3: CREATE PURCHASE ORDER
         =================================================================== -->
    @if($showPoModal && \App\Support\PermissionMatrix::userCan(auth()->user(), 'inventory.manage'))
        <div class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4">
            <div class="bg-[#171513] border-2 border-[#C6A15B] max-w-md w-full p-6 space-y-5 text-white shadow-2xl">
                <div class="border-b border-white/10 pb-3 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono uppercase text-[#C6A15B] tracking-widest font-bold">Procurement Order</span>
                        <h3 class="font-serif text-xl text-white font-normal mt-0.5">New Purchase Order</h3>
                    </div>
                    <button wire:click="$set('showPoModal', false)" class="text-white/60 hover:text-white text-2xl font-mono leading-none">&times;</button>
                </div>

                <div class="space-y-4 font-sans text-xs">
                    <div>
                        <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Supplier / Vendor Name *</label>
                        <input type="text" wire:model="poSupplierName" placeholder="e.g. Punjab Meat Mart" 
                               class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                        @error('poSupplierName') <span class="text-rose-400 text-[10px] font-mono mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Total Amount (PKR) *</label>
                            <input type="number" step="500" wire:model="poTotalAmount" 
                                   class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Order Date *</label>
                            <input type="date" wire:model="poDate" 
                                   class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Items List / Details</label>
                        <textarea wire:model="poNotes" rows="3" placeholder="e.g. 50kg Mutton Goat, 30kg Chicken, 5 Bags Basmati Rice" 
                                  class="w-full bg-[#171513] border border-white/20 text-white text-xs p-2.5 outline-none focus:border-[#C6A15B]"></textarea>
                    </div>
                </div>

                <div class="border-t border-white/10 pt-3 flex items-center justify-end gap-2 font-mono text-xs">
                    <button wire:click="$set('showPoModal', false)" class="px-4 py-2 border border-white/20 text-white">Cancel</button>
                    <button wire:click="createPurchaseOrder" class="btn-gold !h-9 !px-5 text-xs font-bold uppercase">Generate PO &rarr;</button>
                </div>
            </div>
        </div>
    @endif

</div>
