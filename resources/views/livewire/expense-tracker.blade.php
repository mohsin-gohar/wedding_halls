<div class="max-w-7xl mx-auto space-y-8">
    
    <!-- Header -->
    <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-6 sm:p-8 flex flex-col sm:flex-row sm:items-center justify-between gap-6 shadow-xl transition-colors duration-200">
        <div class="space-y-1.5">
            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 border border-[#C6A15B]/40 bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] text-[10px] font-mono uppercase tracking-widest font-bold">
                ERP Financial Accounting
            </div>
            <h1 class="font-serif-luxury text-2xl sm:text-3xl text-[#171513] dark:text-white font-bold tracking-tight">
                Operational Expenses &amp; P&amp;L
            </h1>
            <p class="text-xs text-stone-600 dark:text-stone-300 max-w-xl font-normal leading-relaxed">
                Log generator fuel, electricity, raw catering provisions, and staff overheads to track real banquet net margins.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <div class="lux-btn-group">
                <button wire:click="$set('period', 'THIS_MONTH')" class="lux-btn lux-btn--sm {{ $period === 'THIS_MONTH' ? 'is-active' : '' }}">
                    This Month
                </button>
                <button wire:click="$set('period', 'LAST_MONTH')" class="lux-btn lux-btn--sm {{ $period === 'LAST_MONTH' ? 'is-active' : '' }}">
                    Last Month
                </button>
                <button wire:click="$set('period', 'ALL_TIME')" class="lux-btn lux-btn--sm {{ $period === 'ALL_TIME' ? 'is-active' : '' }}">
                    All Time
                </button>
            </div>
            <button wire:click="openCreateModal" class="lux-btn lux-btn--gold lux-btn--sm">
                + Log Expense
            </button>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if($feedbackMessage)
        <div class="p-4 bg-[#171513]/90 border border-[#C6A15B]/60 text-[#C6A15B] text-xs font-semibold flex justify-between items-center shadow-lg">
            <span>✓ {{ $feedbackMessage }}</span>
            <button wire:click="$set('feedbackMessage', null)" class="text-[#8F7239] font-bold">&times;</button>
        </div>
    @endif

    <!-- Financial KPI Summary Cards (P&L Strip) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Revenue -->
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl space-y-2 relative overflow-hidden group hover:border-[#C6A15B] transition duration-300">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <div class="text-[10px] uppercase font-mono tracking-wider text-stone-500 dark:text-stone-400 font-bold">
                Booking Inflows (GMV)
            </div>
            <div class="font-cinzel text-2xl sm:text-3xl font-bold text-[#171513] dark:text-white">
                PKR {{ number_format($totalRevenue) }}
            </div>
            <div class="text-[11px] text-[#8F7239] dark:text-[#C6A15B] font-medium font-mono">
                Confirmed banquet reservations
            </div>
        </div>

        <!-- Total Expenses -->
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl space-y-2 relative overflow-hidden group hover:border-[#C6A15B] transition duration-300">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <div class="text-[10px] uppercase font-mono tracking-wider text-stone-500 dark:text-stone-400 font-bold">
                Operating Overhead
            </div>
            <div class="font-cinzel text-2xl sm:text-3xl font-bold text-[#171513] dark:text-[#F8F5EF]">
                PKR {{ number_format($totalExpenses) }}
            </div>
            <div class="text-[11px] text-stone-600 dark:text-stone-400 font-medium font-mono">
                Procurements, utilities &amp; decor
            </div>
        </div>

        <!-- Net Profit -->
        <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-5 shadow-md dark:shadow-2xl space-y-2 relative overflow-hidden">
            <div class="text-[10px] uppercase font-mono tracking-wider text-[#8F7239] dark:text-[#C6A15B] font-bold">
                Net Operating EBITDA
            </div>
            <div class="font-cinzel text-2xl sm:text-3xl font-bold {{ $netProfit >= 0 ? 'text-[#171513] dark:text-[#C6A15B]' : 'text-[#171513] dark:text-[#F8F5EF]' }}">
                PKR {{ number_format($netProfit) }}
            </div>
            <div class="text-[11px] text-stone-600 dark:text-stone-400 font-medium font-mono">
                Net profit after operating costs
            </div>
        </div>

        <!-- Operating Margin % -->
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl space-y-2 relative overflow-hidden group hover:border-[#C6A15B] transition duration-300">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <div class="text-[10px] uppercase font-mono tracking-wider text-stone-500 dark:text-stone-400 font-bold">
                Operating Margin
            </div>
            <div class="font-cinzel text-2xl sm:text-3xl font-bold text-[#171513] dark:text-white">
                {{ $operatingMargin }}%
            </div>
            <div class="text-[11px] text-stone-600 dark:text-stone-400 font-medium font-mono">
                {{ $operatingMargin >= 30 ? 'Healthy industry margin' : 'Review operating costs' }}
            </div>
        </div>
    </div>

    <!-- Category Breakdown Ribbon -->
    @if(!empty($categoryBreakdown))
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-5 shadow-sm dark:shadow-xl space-y-3 transition-colors duration-200">
            <h4 class="font-serif-luxury text-xs font-bold uppercase tracking-wider text-[#171513] dark:text-white">
                Overhead Distribution by Category
            </h4>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                @foreach($categoryBreakdown as $catKey => $catAmt)
                    <div class="bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-3 hover:border-[#C6A15B] transition">
                        <span class="text-[10px] uppercase font-mono tracking-wider text-stone-500 dark:text-stone-400 block truncate">
                            {{ str_replace('_', ' ', $catKey) }}
                        </span>
                        <strong class="font-mono text-sm text-[#171513] dark:text-[#C6A15B] block mt-1">
                            PKR {{ number_format($catAmt) }}
                        </strong>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Expenses Table -->
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 shadow-sm dark:shadow-xl overflow-hidden transition-colors duration-200">
        <div class="px-6 py-4 border-b border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#171513] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h3 class="font-serif-luxury text-sm font-bold uppercase tracking-wider text-[#171513] dark:text-white">
                Itemized Expenses Register ({{ $expenses->count() }})
            </h3>
            
            <div class="flex items-center gap-3">
                <button type="button" wire:click="$toggle('showVoided')" class="border border-[#C6A15B]/40 px-3 py-1.5 text-xs font-bold uppercase tracking-wider text-[#8F7239] dark:text-[#C6A15B]">
                    {{ $showVoided ? 'Show active' : 'Show voided' }}
                </button>
                <select wire:model.live="filterCategory" class="text-xs bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 text-[#171513] dark:text-white py-1.5 px-3 font-mono font-medium outline-none focus:border-[#C6A15B]">
                    <option value="ALL">All Categories</option>
                    <option value="GENERATOR_DIESEL">Generator Diesel</option>
                    <option value="WAPDA_ELECTRICITY">WAPDA Electricity</option>
                    <option value="RAW_FOOD_MEAT">Raw Food &amp; Meat</option>
                    <option value="FRESH_FLORAL_DECOR">Floral &amp; Decor</option>
                    <option value="STAFF_WAGES">Staff Wages</option>
                    <option value="MAINTENANCE_REPAIRS">Maintenance</option>
                    <option value="CATERING_SUPPLIES">Catering Supplies</option>
                    <option value="MARKETING_ADS">Marketing &amp; Ads</option>
                    <option value="OTHER">Other Expense</option>
                </select>
            </div>
        </div>

        <div class="table-wrapper-enterprise" tabindex="0" role="region" aria-label="Operations Kharcha Tracker"><table class="table-enterprise">
                <thead>
                    <tr class="border-b border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#171513] text-[#78716C] dark:text-[#C6A15B] uppercase font-mono tracking-wider text-[10px]">
                        <th class="py-3 px-6 font-bold">Expense Details</th>
                        <th class="py-3 px-6 font-bold">Category</th>
                        <th class="py-3 px-6 font-bold">Date</th>
                        <th class="py-3 px-6 font-bold">Payee / Slip</th>
                        <th class="py-3 px-6 font-bold text-right">Amount (PKR)</th>
                        <th class="py-3 px-6 font-bold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E7E5E4] dark:divide-white/10 text-stone-800 dark:text-stone-200 font-medium">
                    @forelse($expenses as $exp)
                        <tr class="hover:bg-[#F8F5EF] dark:hover:bg-[#171513]/70 transition">
                            <td class="py-4 px-6">
                                <div class="font-bold text-[#171513] dark:text-white text-sm">{{ $exp->title }}</div>
                                @if($exp->hall)
                                    <span class="text-[10px] font-mono text-stone-500 dark:text-stone-400">
                                        Hall: {{ $exp->hall->name }}
                                    </span>
                                @endif
                                @if($exp->notes)
                                    <div class="text-[11px] text-stone-500 dark:text-stone-400 font-normal italic mt-0.5">{{ $exp->notes }}</div>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                <span class="px-2 py-0.5 bg-[#F8F5EF] dark:bg-white/10 border border-[#E7E5E4] dark:border-white/15 text-stone-700 dark:text-stone-300 font-mono text-[10px] uppercase font-bold">
                                    {{ str_replace('_', ' ', $exp->category) }}
                                </span>
                            </td>
                            <td class="py-4 px-6 font-mono text-stone-600 dark:text-stone-400">
                                {{ $exp->expense_date->format('d M Y') }}
                            </td>
                            <td class="py-4 px-6 space-y-0.5 text-stone-700 dark:text-stone-300">
                                <div>{{ $exp->payee ?? 'Front Desk Cash' }}</div>
                                @if($exp->receipt_number)
                                    <div class="text-[10px] font-mono text-stone-400 dark:text-stone-500">Slip: {{ $exp->receipt_number }}</div>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right font-mono font-bold text-sm text-[#171513] dark:text-[#F8F5EF]">
                                PKR {{ number_format($exp->amount) }}
                            </td>
                            <td class="py-4 px-6 text-right whitespace-nowrap space-x-2 font-mono">
                                @if($exp->is_voided)
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Voided</span>
                                    <div class="mt-1 max-w-[220px] whitespace-normal text-[10px] text-stone-500">{{ $exp->void_reason }}</div>
                                    <div class="mt-1 text-[10px] text-stone-400">
                                        {{ $exp->voidedBy?->name ?? 'Authorized user' }} · {{ $exp->voided_at?->format('d M Y, h:i A') }}
                                    </div>
                                @elseif($exp->payroll_id || $paidPoNumbers->contains($exp->receipt_number) || $utilityExpenseIds->contains($exp->id) || in_array($exp->category, ['GENERATOR_DIESEL', 'WAPDA_ELECTRICITY'], true))
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-stone-500">Source managed</span>
                                @else
                                    <button wire:click="openEditModal('{{ $exp->id }}')" class="border border-[#E7E5E4] px-2.5 py-1 text-xs font-semibold text-[#171513] dark:border-white/15 dark:text-stone-200">Edit</button>
                                    <button wire:click="openVoidModal('{{ $exp->id }}')" class="border border-rose-500/30 bg-rose-500/5 px-2 py-1 text-xs font-semibold text-rose-700 dark:text-rose-300">Void</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-stone-400 font-mono">
                                No operational expenses recorded for this date range. Click "+ Log Expense" above to record receipts.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Expense Modal -->
    @if($isCreating)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md overflow-y-auto">
            <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/60 gold-foil-border max-w-lg w-full p-6 sm:p-8 space-y-6 shadow-2xl text-stone-800 dark:text-stone-200 transition-colors">
                <div class="flex justify-between items-start border-b border-[#E7E5E4] dark:border-white/15 pb-4">
                    <div>
                        <span class="text-[10px] font-mono uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold block">
                            Outflow Procurement
                        </span>
                        <h2 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold mt-1">Log Operational Expense</h2>
                    </div>
                    <button wire:click="closeCreateModal" class="text-stone-400 hover:text-stone-700 dark:hover:text-white text-2xl leading-none">&times;</button>
                </div>

                <form wire:submit="createExpense" class="space-y-4 text-xs font-mono">
                    <div>
                        <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Expense Title / Description *</label>
                        <input type="text" wire:model="title" placeholder="e.g. Caterpillar Diesel 150L Refill" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 outline-none font-sans">
                        @error('title') <span class="text-[#171513] dark:text-[#F8F5EF] text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Expense Category *</label>
                            <select wire:model="category" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 font-medium outline-none">
                                <option value="GENERATOR_DIESEL">Generator Diesel / Fuel</option>
                                <option value="WAPDA_ELECTRICITY">WAPDA / Commercial Power</option>
                                <option value="RAW_FOOD_MEAT">Bawarchi Raw Meat &amp; Provisions</option>
                                <option value="FRESH_FLORAL_DECOR">Fresh Floral &amp; Stage Decor</option>
                                <option value="STAFF_WAGES">Staff Wages &amp; Daily Labor</option>
                                <option value="MAINTENANCE_REPAIRS">Hall AC &amp; Facility Maintenance</option>
                                <option value="CATERING_SUPPLIES">Catering &amp; Crockery Supplies</option>
                                <option value="MARKETING_ADS">Social Media Ads &amp; Marketing</option>
                                <option value="OTHER">Other Overhead Expense</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Amount (PKR) *</label>
                            <input type="number" step="0.01" wire:model="amount" placeholder="45000" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 font-mono font-bold outline-none">
                            @error('amount') <span class="text-[#171513] dark:text-[#F8F5EF] text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Expense Date *</label>
                            <input type="date" wire:model="expense_date" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 outline-none font-sans">
                            @error('expense_date') <span class="text-[#171513] dark:text-[#F8F5EF] text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Associated Hall (Optional)</label>
                            <select wire:model="hall_id" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 outline-none">
                                <option value="">General Venue Overhead</option>
                                @foreach($halls as $hall)
                                    <option value="{{ $hall->id }}">{{ $hall->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Vendor / Payee</label>
                            <input type="text" wire:model="payee" placeholder="e.g. PSO Petrol Station" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 outline-none font-sans">
                        </div>

                        <div>
                            <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Receipt / Slip Number</label>
                            <input type="text" wire:model="receipt_number" placeholder="e.g. REC-99210" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Notes / Internal Remarks</label>
                        <textarea wire:model="notes" rows="2" placeholder="Details about procurement or load shedding duration" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2 px-3 outline-none font-sans"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-[#E7E5E4] dark:border-white/15">
                        <button type="button" wire:click="closeCreateModal" class="border border-[#E7E5E4] dark:border-white/20 bg-transparent text-stone-700 dark:text-stone-300 hover:text-[#171513] dark:hover:text-white px-4 py-2 font-bold uppercase tracking-wider text-xs transition">
                            Cancel
                        </button>
                        <button type="submit" class="btn-gold !h-10 !px-5 text-xs font-bold uppercase tracking-wider shadow">
                            Record Outflow
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Edit Expense Modal -->
    @if($isEditing)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md overflow-y-auto">
            <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/60 gold-foil-border max-w-lg w-full p-6 sm:p-8 space-y-6 shadow-2xl text-stone-800 dark:text-stone-200 transition-colors">
                <div class="flex justify-between items-start border-b border-[#E7E5E4] dark:border-white/15 pb-4">
                    <div>
                        <span class="text-[10px] font-mono uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold block">
                            Outflow Procurement
                        </span>
                        <h2 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold mt-1">Edit Operational Expense</h2>
                    </div>
                    <button wire:click="closeEditModal" class="text-stone-400 hover:text-stone-700 dark:hover:text-white text-2xl leading-none">&times;</button>
                </div>

                <form wire:submit="updateExpense" class="space-y-4 text-xs font-mono">
                    <div>
                        <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Expense Title / Description *</label>
                        <input type="text" wire:model="edit_title" placeholder="e.g. Caterpillar Diesel 150L Refill" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 outline-none font-sans">
                        @error('edit_title') <span class="text-[#171513] dark:text-[#F8F5EF] text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Expense Category *</label>
                            <select wire:model="edit_category" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 font-medium outline-none">
                                <option value="GENERATOR_DIESEL">Generator Diesel / Fuel</option>
                                <option value="WAPDA_ELECTRICITY">WAPDA / Commercial Power</option>
                                <option value="RAW_FOOD_MEAT">Bawarchi Raw Meat &amp; Provisions</option>
                                <option value="FRESH_FLORAL_DECOR">Fresh Floral &amp; Stage Decor</option>
                                <option value="STAFF_WAGES">Staff Wages &amp; Daily Labor</option>
                                <option value="MAINTENANCE_REPAIRS">Hall AC &amp; Facility Maintenance</option>
                                <option value="CATERING_SUPPLIES">Catering &amp; Crockery Supplies</option>
                                <option value="MARKETING_ADS">Social Media Ads &amp; Marketing</option>
                                <option value="OTHER">Other Overhead Expense</option>
                            </select>
                            @error('edit_category') <span class="text-[#171513] dark:text-[#F8F5EF] text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Amount (PKR) *</label>
                            <input type="number" step="0.01" wire:model="edit_amount" placeholder="45000" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 font-mono font-bold outline-none">
                            @error('edit_amount') <span class="text-[#171513] dark:text-[#F8F5EF] text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Expense Date *</label>
                            <input type="date" wire:model="edit_expense_date" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 outline-none font-sans">
                            @error('edit_expense_date') <span class="text-[#171513] dark:text-[#F8F5EF] text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Associated Hall (Optional)</label>
                            <select wire:model="edit_hall_id" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 outline-none">
                                <option value="">General Venue Overhead</option>
                                @foreach($halls as $hall)
                                    <option value="{{ $hall->id }}">{{ $hall->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Vendor / Payee</label>
                            <input type="text" wire:model="edit_payee" placeholder="e.g. PSO Petrol Station" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 outline-none font-sans">
                            @error('edit_payee') <span class="text-[#171513] dark:text-[#F8F5EF] text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Receipt / Slip Number</label>
                            <input type="text" wire:model="edit_receipt_number" placeholder="e.g. REC-99210" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 outline-none">
                            @error('edit_receipt_number') <span class="text-[#171513] dark:text-[#F8F5EF] text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Payment Method</label>
                        <select wire:model="edit_payment_method" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 font-medium outline-none">
                            <option value="CASH">💵 Cash</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Notes / Internal Remarks</label>
                        <textarea wire:model="edit_notes" rows="2" placeholder="Details about procurement or load shedding duration" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2 px-3 outline-none font-sans"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-[#E7E5E4] dark:border-white/15">
                        <button type="button" wire:click="closeEditModal" class="border border-[#E7E5E4] dark:border-white/20 bg-transparent text-stone-700 dark:text-stone-300 hover:text-[#171513] dark:hover:text-white px-4 py-2 font-bold uppercase tracking-wider text-xs transition">
                            Cancel
                        </button>
                        <button type="submit" class="btn-gold !h-10 !px-5 text-xs font-bold uppercase tracking-wider shadow">
                            Update Expense
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif


    @if($voidingExpenseId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
            <div class="w-full max-w-lg space-y-5 border border-rose-500/40 bg-white p-6 shadow-2xl dark:bg-[#171513]">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-rose-600 dark:text-rose-400">Financial audit control</p>
                    <h3 class="mt-2 font-serif-luxury text-2xl text-[#171513] dark:text-white">Void expense record</h3>
                    <p class="mt-2 text-xs leading-relaxed text-stone-500">This does not delete the expense. It excludes the entry from financial totals while preserving its original values and audit history.</p>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">Reason for voiding</label>
                    <textarea wire:model="voidReason" rows="3" maxlength="500" class="w-full border border-[#E7E5E4] bg-[#F8F5EF] p-3 text-sm text-[#171513] outline-none focus:border-[#C6A15B] dark:border-white/15 dark:bg-[#171513] dark:text-white" placeholder="Explain why this expense is incorrect or being reversed..."></textarea>
                    @error('voidReason') <span class="mt-1 block text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>
                <div class="flex justify-end gap-3 border-t border-[#E7E5E4] pt-4 dark:border-white/10">
                    <button type="button" wire:click="cancelVoid" class="border border-[#E7E5E4] px-4 py-2 text-xs font-bold uppercase text-stone-600 dark:border-white/15 dark:text-stone-300">Cancel</button>
                    <button type="button" wire:click="voidExpense" class="border border-rose-500/50 bg-rose-600 px-4 py-2 text-xs font-bold uppercase text-white">Void and retain history</button>
                </div>
            </div>
        </div>
    @endif

</div>
