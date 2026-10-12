<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
    
    <!-- ===================================================================
         EXECUTIVE HEADER BANNER (Obsidian & Champagne Gold)
         =================================================================== -->
    <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-6 sm:p-8 relative overflow-hidden shadow-xl transition-colors duration-200">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 relative z-10">
            <div>
                <div class="flex items-center gap-2 text-xs font-mono text-[#8F7239] dark:text-[#C6A15B] uppercase tracking-widest mb-1.5 font-bold">
                    <span>Vendor Portal</span>
                    <span class="text-stone-400 dark:text-stone-600">/</span>
                    <span>Executive Operations</span>
                    <span class="text-stone-400 dark:text-stone-600">•</span>
                    <span class="text-stone-600 dark:text-stone-300 font-mono">{{ now()->format('l, d F Y') }}</span>
                </div>
                <h1 class="font-serif-luxury text-2xl sm:text-4xl text-[#171513] dark:text-white font-bold tracking-tight">
                    Banquet Operations &amp; Financial Analytics
                </h1>
                <p class="text-xs text-stone-600 dark:text-stone-300 font-sans mt-2 max-w-2xl leading-relaxed">
                    Real-time booking volume, PKR gross revenue realization, operational utility costs, and shift occupancy.
                </p>
            </div>

            <!-- Controls: Timeframe & Hall Selector (Strict Zero-Pill) -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Segmented Control -->
                <div class="bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/40 p-1 flex items-center">
                    <button wire:click="setTimeframe('WEEKLY')" 
                            class="px-3 py-1.5 text-xs font-mono uppercase tracking-wider transition {{ $timeframe === 'WEEKLY' ? 'bg-[#C6A15B] text-[#171513] font-bold shadow' : 'text-stone-700 dark:text-stone-300 hover:text-[#171513] dark:hover:text-[#C6A15B] hover:bg-stone-200/50 dark:hover:bg-white/5' }}">
                        Weekly
                    </button>
                    <button wire:click="setTimeframe('MONTHLY')" 
                            class="px-3 py-1.5 text-xs font-mono uppercase tracking-wider transition {{ $timeframe === 'MONTHLY' ? 'bg-[#C6A15B] text-[#171513] font-bold shadow' : 'text-stone-700 dark:text-stone-300 hover:text-[#171513] dark:hover:text-[#C6A15B] hover:bg-stone-200/50 dark:hover:bg-white/5' }}">
                        Monthly
                    </button>
                    <button wire:click="setTimeframe('QUARTERLY')" 
                            class="px-3 py-1.5 text-xs font-mono uppercase tracking-wider transition {{ $timeframe === 'QUARTERLY' ? 'bg-[#C6A15B] text-[#171513] font-bold shadow' : 'text-stone-700 dark:text-stone-300 hover:text-[#171513] dark:hover:text-[#C6A15B] hover:bg-stone-200/50 dark:hover:bg-white/5' }}">
                        90 Days
                    </button>
                    <button wire:click="setTimeframe('YEARLY')" 
                            class="px-3 py-1.5 text-xs font-mono uppercase tracking-wider transition {{ $timeframe === 'YEARLY' ? 'bg-[#C6A15B] text-[#171513] font-bold shadow' : 'text-stone-700 dark:text-stone-300 hover:text-[#171513] dark:hover:text-[#C6A15B] hover:bg-stone-200/50 dark:hover:bg-white/5' }}">
                        Annual
                    </button>
                </div>

                <!-- Hall Filter -->
                <select wire:model.live="selectedHallId" class="bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/40 text-[#171513] dark:text-stone-200 text-xs px-3 py-2 font-mono focus:border-[#C6A15B] outline-none">
                    <option value="ALL">All Managed Halls</option>
                    @foreach($halls as $h)
                        <option value="{{ $h->id }}">{{ $h->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    @if(auth()->user()->tenant && auth()->user()->tenant->verification_status === 'PENDING')
        <div class="bg-[#171513]/85 border border-[#C6A15B]/50 p-4 text-[#C6A15B] text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-lg">
            <div class="flex items-center gap-3">
                <span class="text-[#C6A15B] text-lg">⏳</span>
                <div>
                    <span class="font-bold uppercase tracking-wider text-[#C6A15B] font-mono">Account Under Review:</span>
                    <span>Your hall registration is currently pending Super Admin verification. You can configure your profile, halls, and photos, and it will be visible in the public marketplace once approved.</span>
                </div>
            </div>
            <span class="px-2.5 py-1 bg-[#C6A15B]/30 border border-[#C6A15B]/45 font-mono text-[11px] uppercase tracking-wider font-bold whitespace-nowrap self-start sm:self-auto">14-Day Free Trial Active</span>
        </div>
    @endif

    <!-- ===================================================================
         HALL OWNER DEDICATED WEBSITE & IDENTITY BAR
         =================================================================== -->
    @php
        $activeHall = ($selectedHallId !== 'ALL') 
            ? $halls->firstWhere('id', $selectedHallId) 
            : $halls->first();
    @endphp
    @if($activeHall)
        <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 shadow-xl relative overflow-hidden transition-colors duration-200">
            <div class="space-y-1.5 relative z-10">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#C6A15B] animate-pulse"></span>
                    <span class="text-[10px] font-mono uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold">
                        RENT ADMIN — DEDICATED HALL WEBSITE
                    </span>
                    <span class="text-stone-400 dark:text-stone-500">•</span>
                    <span class="text-xs text-stone-600 dark:text-stone-300 font-mono">{{ $activeHall->venue->city ?? 'Pakistan' }}</span>
                </div>
                <h2 class="font-serif-luxury text-xl sm:text-2xl text-[#171513] dark:text-white font-bold">
                    {{ $activeHall->name }}
                </h2>
                <div class="flex items-center gap-2 text-xs font-mono text-stone-600 dark:text-stone-300">
                    <span>Client Website Link:</span>
                    <a href="{{ route('hall.showcase', $activeHall->id) }}" target="_blank" class="text-[#8F7239] dark:text-[#C6A15B] underline hover:text-[#171513] dark:hover:text-[#D9C088] font-bold">
                        {{ route('hall.showcase', $activeHall->id) }}
                    </a>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 relative z-10">
                <a href="{{ route('hall.showcase', $activeHall->id) }}" target="_blank"
                   class="btn-outline-gold !h-9 !px-4 text-xs font-mono uppercase tracking-wider flex items-center gap-1.5 font-bold">
                    <span>Preview Hall Website ↗</span>
                </a>
                    <a href="{{ route('hall.media') }}" 
                       class="btn-gold !h-9 !px-4 text-xs font-mono uppercase tracking-wider font-bold flex items-center gap-1.5 shadow-[0_0_20px_rgba(198,161,91,0.4)] hover:shadow-[0_0_30px_rgba(198,161,91,0.55)] transition-all duration-300 group">
                        <span class="relative z-10">Edit Name &amp; Photos</span> ✦
                    </a>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         SAAS SUBSCRIPTION STATUS & ERP LIMITS RIBBON
         =================================================================== -->
    @php
        $tenant = auth()->user()->tenant;
        $plan = $tenant ? $tenant->subscriptionPlan : null;
        $planName = $plan ? $plan->name : ($tenant->subscription_plan ?? 'Executive Banquet Suite');
        $maxHalls = $plan ? $plan->max_halls : 3;
        $maxStaff = $plan ? $plan->max_staff : 10;
        $currentHalls = $tenant ? $tenant->halls()->count() : 1;
        $currentStaff = $tenant ? $tenant->users()->count() : 1;
        $isActive = $tenant ? $tenant->isSubscriptionActive() : true;
    @endphp
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/35 p-5 shadow-sm dark:shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4 text-xs font-mono transition-colors duration-200">
        <div class="flex flex-wrap items-center gap-6">
            <div class="space-y-0.5">
                <span class="text-[9px] uppercase tracking-wider text-stone-500 dark:text-stone-400 font-bold block">Current SaaS Plan</span>
                <div class="font-sans font-bold text-sm text-[#171513] dark:text-white">{{ $planName }}</div>
            </div>

            <div class="space-y-0.5">
                <span class="text-[9px] uppercase tracking-wider text-stone-500 dark:text-stone-400 font-bold block">Subscription Status</span>
                <div>
                    @if($isActive)
                        <span class="px-2.5 py-1 bg-[#C6A15B]/20 dark:bg-[#171513]/90 border border-[#C6A15B]/60 dark:border-[#C6A15B]/50 text-[#8F7239] dark:text-[#C6A15B] font-bold text-[10px] uppercase">
                            ● Active ({{ $tenant->subscription_status ?? 'ACTIVE' }})
                        </span>
                    @else
                        <span class="px-2.5 py-1 bg-[#171513]/10 dark:bg-[#171513]/90 border border-[#C6A15B]/50 dark:border-[#C6A15B]/50 text-[#171513] dark:text-[#F8F5EF] font-bold text-[10px] uppercase">
                            ● Expired / Renewal Due
                        </span>
                    @endif
                </div>
            </div>

            @if($tenant && $tenant->subscription_expires_at)
            <div class="space-y-0.5 hidden sm:block">
                <span class="text-[9px] uppercase tracking-wider text-stone-500 dark:text-stone-400 font-bold block">Valid Until</span>
                <span class="text-stone-800 dark:text-stone-200 font-bold">{{ $tenant->subscription_expires_at->format('d M Y') }}</span>
            </div>
            @endif

            <div class="space-y-0.5 hidden md:block">
                <span class="text-[9px] uppercase tracking-wider text-stone-500 dark:text-stone-400 font-bold block">Halls Limit</span>
                <span class="text-stone-700 dark:text-stone-300 font-bold">{{ $currentHalls }} / {{ $maxHalls === -1 ? 'Unlimited' : $maxHalls }} Used</span>
            </div>

            <div class="space-y-0.5 hidden md:block">
                <span class="text-[9px] uppercase tracking-wider text-stone-500 dark:text-stone-400 font-bold block">Staff Limit</span>
                <span class="text-stone-700 dark:text-stone-300 font-bold">{{ $currentStaff }} / {{ $maxStaff === -1 ? 'Unlimited' : $maxStaff }} Used</span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('expenses') }}" class="px-3.5 py-2 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 hover:border-[#C6A15B] text-stone-800 dark:text-stone-200 hover:text-[#171513] dark:hover:text-[#C6A15B] transition font-sans text-xs font-bold">
                📊 Expenses &amp; P&amp;L
            </a>
            <a href="{{ route('staff') }}" class="px-3.5 py-2 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 hover:border-[#C6A15B] text-stone-800 dark:text-stone-200 hover:text-[#171513] dark:hover:text-[#C6A15B] transition font-sans text-xs font-bold">
                👥 Manage Team
            </a>
        </div>
    </div>

    <!-- ===================================================================
         OPERATIONAL SNAPSHOT BAR (4 PILLARS)
         =================================================================== -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl relative overflow-hidden group hover:border-[#C6A15B] transition duration-300">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <span class="text-[10px] font-mono uppercase tracking-widest text-stone-500 dark:text-stone-400 block font-bold">Managed Halls</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="font-cinzel text-3xl text-[#171513] dark:text-white font-bold">{{ $totalHallsCount }}</span>
                <span class="text-[11px] font-mono text-[#C6A15B] font-bold">Active Spaces</span>
            </div>
        </div>

        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl relative overflow-hidden group hover:border-[#C6A15B] transition duration-300">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <span class="text-[10px] font-mono uppercase tracking-widest text-stone-500 dark:text-stone-400 block font-bold">Today's Functions</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="font-cinzel text-3xl {{ $todayEventsCount > 0 ? 'text-[#8F7239] dark:text-[#C6A15B] font-bold' : 'text-[#171513] dark:text-white' }}">{{ $todayEventsCount }}</span>
                <span class="text-[11px] font-mono {{ $todayEventsCount > 0 ? 'text-[#8F7239] dark:text-[#C6A15B] font-bold' : 'text-stone-400' }}">{{ $todayEventsCount > 0 ? 'In Execution' : 'None Today' }}</span>
            </div>
        </div>

        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl relative overflow-hidden group hover:border-[#C6A15B] transition duration-300">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <span class="text-[10px] font-mono uppercase tracking-widest text-stone-500 dark:text-stone-400 block font-bold">Upcoming Functions</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="font-cinzel text-3xl text-[#171513] dark:text-white font-bold">{{ $upcomingEventsCount }}</span>
                <span class="text-[11px] font-mono text-[#C6A15B] font-bold">Ahead on Calendar</span>
            </div>
        </div>

        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl relative overflow-hidden group hover:border-[#C6A15B] transition duration-300">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <span class="text-[10px] font-mono uppercase tracking-widest text-stone-500 dark:text-stone-400 block font-bold">Pending Inquiries</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="font-cinzel text-3xl text-[#8F7239] dark:text-[#C6A15B] font-bold">{{ $pendingInquiriesCount }}</span>
                <span class="text-[11px] font-mono text-[#8F7239] dark:text-[#C6A15B] font-bold">Awaiting Quote</span>
            </div>
        </div>
    </div>

    <!-- ===================================================================
         FINANCIAL & PERFORMANCE KPI CARDS
         =================================================================== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- KPI 1: Programs Count -->
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl hover:border-[#C6A15B] transition-all space-y-2 relative overflow-hidden group">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <div class="flex items-center justify-between text-[10px] font-mono uppercase tracking-wider text-stone-500 dark:text-stone-400 font-bold">
                <span>Functions Total</span>
                <span class="px-2 py-0.5 bg-stone-100 dark:bg-white/10 border border-stone-200 dark:border-white/15 text-stone-700 dark:text-stone-300 font-mono">
                    {{ $programsGrowth >= 0 ? '+' : '' }}{{ $programsGrowth }}%
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="font-cinzel text-3xl text-[#171513] dark:text-white font-bold">{{ $totalPrograms }}</span>
                <span class="text-xs text-stone-500 dark:text-stone-400">Events</span>
            </div>
            <div class="pt-3 border-t border-stone-100 dark:border-white/10 flex items-center justify-between text-xs text-stone-600 dark:text-stone-400 font-medium">
                <span>Confirmed: <strong class="text-[#8F7239] dark:text-[#C6A15B]">{{ $confirmedPrograms }}</strong></span>
                <span>On Hold: <strong class="text-[#8F7239] dark:text-[#C6A15B]">{{ $holdPrograms }}</strong></span>
            </div>
        </div>

        <!-- KPI 2: Gross Sales Turnover -->
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl hover:border-[#C6A15B] transition-all space-y-2 relative overflow-hidden group">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <div class="flex items-center justify-between text-[10px] font-mono uppercase tracking-wider text-[#C6A15B] font-bold">
                <span>Gross Turnover</span>
                <span class="px-2 py-0.5 bg-[#C6A15B]/15 dark:bg-[#C6A15B]/15 border border-[#C6A15B]/50 dark:border-[#C6A15B]/30 text-[#171513] dark:text-[#C6A15B] font-mono font-bold">
                    {{ $salesGrowth >= 0 ? '+' : '' }}{{ $salesGrowth }}%
                </span>
            </div>
            <div class="mt-2">
                <span class="font-cinzel text-2xl sm:text-3xl text-[#171513] dark:text-[#C6A15B] font-bold block">
                    PKR {{ number_format($grossSalesTurnover, 0) }}
                </span>
            </div>
            <div class="pt-3 border-t border-stone-100 dark:border-white/10 flex items-center justify-between text-xs text-stone-600 dark:text-stone-400 font-medium">
                <span>Avg / Program:</span>
                <strong class="text-[#171513] dark:text-white font-mono">PKR {{ $totalPrograms > 0 ? number_format($grossSalesTurnover / $totalPrograms, 0) : 0 }}</strong>
            </div>
        </div>

        <!-- KPI 3: Cash Deposits & Balance Due -->
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl hover:border-[#C6A15B] transition-all space-y-2 relative overflow-hidden group">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <div class="flex items-center justify-between text-[10px] font-mono uppercase tracking-wider text-stone-500 dark:text-stone-400 font-bold">
                <span>Deposits Collected</span>
                <span class="text-[#8F7239] dark:text-[#C6A15B] font-mono font-bold">
                    {{ $grossSalesTurnover > 0 ? round(($advanceCollected / $grossSalesTurnover) * 100) : 0 }}% Paid
                </span>
            </div>
            <div class="mt-2">
                <span class="font-cinzel text-2xl sm:text-3xl text-[#8F7239] dark:text-[#C6A15B] font-bold block">
                    PKR {{ number_format($advanceCollected, 0) }}
                </span>
            </div>
            <div class="pt-3 border-t border-stone-100 dark:border-white/10 flex items-center justify-between text-xs text-stone-600 dark:text-stone-400 font-medium">
                <span>Balance Due:</span>
                <strong class="text-[#171513] dark:text-[#F8F5EF] font-mono">PKR {{ number_format($totalBalanceDue, 0) }}</strong>
            </div>
        </div>

        <!-- KPI 4: Net Owner Profit & Margin -->
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl hover:border-[#C6A15B] transition-all space-y-2 relative overflow-hidden group">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <div class="flex items-center justify-between text-[10px] font-mono uppercase tracking-wider text-stone-500 dark:text-stone-400 font-bold">
                <span>Net Operating Profit</span>
                <span class="px-2 py-0.5 bg-[#C6A15B]/20 dark:bg-[#171513]/85 border border-[#C6A15B]/60 dark:border-[#C6A15B]/50 text-[#8F7239] dark:text-[#C6A15B] font-mono text-[10px] font-bold">
                    {{ $profitMarginPercent }}% Margin
                </span>
            </div>
            <div class="mt-2">
                <span class="font-cinzel text-2xl sm:text-3xl text-[#171513] dark:text-white font-bold block">
                    PKR {{ number_format($netOwnerProfit, 0) }}
                </span>
            </div>
            <div class="pt-3 border-t border-stone-100 dark:border-white/10 flex items-center justify-between text-xs text-stone-600 dark:text-stone-400 font-medium font-mono">
                <span>Food: ~{{ number_format($estimatedFoodCost / 1000) }}k</span>
                <span>Fuel: {{ number_format($totalUtilityCost / 1000) }}k</span>
            </div>
        </div>
    </div>

    <!-- ===================================================================
         INTERACTIVE REVENUE & BOOKINGS VISUALIZER
         =================================================================== -->
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 shadow-sm dark:shadow-xl space-y-6 transition-colors duration-200">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-[#E7E5E4] dark:border-white/10 pb-4">
            <div>
                <span class="text-[10px] font-mono uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold">Performance Trend</span>
                <h3 class="font-serif-luxury text-xl sm:text-2xl text-[#171513] dark:text-white font-bold mt-0.5">
                    {{ $timeframe === 'WEEKLY' ? 'Weekly Day-by-Day' : ($timeframe === 'YEARLY' ? 'Annual Month-by-Month' : 'Monthly 4-Week Distribution') }}
                </h3>
            </div>
            <div class="flex items-center gap-1 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-1">
                <button wire:click="setChartMetric('sales')" 
                        class="px-3 py-1.5 text-xs font-mono uppercase tracking-wider transition {{ $chartMetric === 'sales' ? 'bg-[#171513] dark:bg-[#C6A15B] text-[#C6A15B] dark:text-[#171513] font-bold shadow' : 'text-stone-700 dark:text-stone-300 hover:text-stone-900 dark:hover:text-white' }}">
                    Revenue (PKR)
                </button>
                <button wire:click="setChartMetric('programs')" 
                        class="px-3 py-1.5 text-xs font-mono uppercase tracking-wider transition {{ $chartMetric === 'programs' ? 'bg-[#171513] dark:bg-[#C6A15B] text-[#C6A15B] dark:text-[#171513] font-bold shadow' : 'text-stone-700 dark:text-stone-300 hover:text-stone-900 dark:hover:text-white' }}">
                    Volume (Functions)
                </button>
            </div>
        </div>

        <!-- High-Contrast Visual Bar Chart -->
        <div class="p-6 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 transition-colors duration-200">
            <div class="grid grid-cols-{{ count($chartData) }} gap-2 sm:gap-6 items-end h-56 pt-8">
                @foreach($chartData as $point)
                    <div class="flex flex-col items-center h-full justify-end group relative">
                        <!-- Tooltip on hover -->
                        <div class="opacity-0 group-hover:opacity-100 transition duration-150 pointer-events-none absolute -top-8 bg-white dark:bg-[#171513] border border-[#C6A15B] text-[#171513] dark:text-white text-[11px] px-2 py-0.5 shadow-xl whitespace-nowrap z-20 font-mono font-bold">
                            {{ $point['formatted'] }}
                        </div>

                        <!-- Bar element (Zero-Pill) -->
                        <div class="w-full max-w-[36px] transition-all duration-300 ease-out relative group-hover:brightness-110 shadow-sm
                            {{ $chartMetric === 'sales' ? 'bg-[#C6A15B]' : 'bg-[#C6A15B]' }}" 
                             style="height: {{ max(8, $point['percentage']) }}%;">
                        </div>

                        <!-- X-axis Label -->
                        <span class="text-[10px] text-stone-600 dark:text-stone-400 group-hover:text-[#171513] dark:group-hover:text-white mt-3 text-center truncate w-full font-mono font-semibold">
                            {{ $point['label'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- ===================================================================
         VENUE BREAKDOWN & SHIFT OCCUPANCY
         =================================================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Hall Comparison (2 cols) -->
        <div class="lg:col-span-2 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 shadow-sm dark:shadow-xl space-y-6 transition-colors duration-200">
            <div class="flex items-center justify-between border-b border-[#E7E5E4] dark:border-white/10 pb-4">
                <div>
                    <span class="text-[10px] font-mono uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold">Venue Distribution</span>
                    <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold mt-0.5">Hall-by-Hall Revenue Share</h3>
                </div>
                <span class="text-xs text-stone-500 dark:text-stone-400 font-mono">{{ $halls->count() }} Venues</span>
            </div>

            <div class="space-y-4">
                @forelse($hallPerformance as $hp)
                    <div class="p-4 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 hover:border-[#C6A15B] transition space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-serif-luxury text-base text-[#171513] dark:text-white font-bold">{{ $hp['name'] }}</span>
                            <span class="text-xs font-mono font-bold text-[#171513] dark:text-[#C6A15B]">PKR {{ number_format($hp['sales'], 0) }}</span>
                        </div>

                        @php
                            $hallPct = $grossSalesTurnover > 0 ? round(($hp['sales'] / $grossSalesTurnover) * 100) : 0;
                        @endphp
                        <div class="w-full h-1.5 bg-stone-200 dark:bg-stone-800 overflow-hidden">
                            <div class="h-full bg-[#C6A15B] transition-all duration-500" style="width: {{ $hallPct }}%;"></div>
                        </div>

                        <div class="flex items-center justify-between text-[11px] font-mono text-stone-600 dark:text-stone-400 pt-1">
                            <span>Functions: <strong class="text-[#171513] dark:text-white">{{ $hp['programs'] }}</strong></span>
                            <span>Guests: <strong class="text-[#171513] dark:text-white">{{ number_format($hp['guests']) }}</strong></span>
                            <span>Share: <strong class="text-[#C6A15B] font-bold">{{ $hallPct }}%</strong></span>
                            <span>Avg Event: <strong class="text-[#171513] dark:text-white">PKR {{ number_format($hp['avgPerProgram'] / 1000) }}k</strong></span>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-stone-400 text-xs font-mono">
                        No venue records configured for this tenant.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Shift Slot Occupancy & Categories (1 col) -->
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 shadow-sm dark:shadow-xl space-y-6 transition-colors duration-200">
            <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-4">
                <span class="text-[10px] font-mono uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold">Shift Utilization</span>
                <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold mt-0.5">Operating Shift Rhythms</h3>
            </div>

            <div class="space-y-4 font-mono text-xs">
                <div>
                    <div class="flex justify-between text-stone-800 dark:text-stone-200 mb-1">
                        <span>Evening (07:00 PM – 11:00 PM)</span>
                        <strong class="text-[#8F7239] dark:text-[#C6A15B]">{{ $shiftCounts['EVENING'] }} ({{ $shiftPercentages['EVENING'] }}%)</strong>
                    </div>
                    <div class="w-full h-1.5 bg-stone-200 dark:bg-stone-800">
                        <div class="h-full bg-[#C6A15B]" style="width: {{ $shiftPercentages['EVENING'] }}%;"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-stone-800 dark:text-stone-200 mb-1">
                        <span>Afternoon (12:00 PM – 04:00 PM)</span>
                        <strong class="text-[#171513] dark:text-white">{{ $shiftCounts['AFTERNOON'] }} ({{ $shiftPercentages['AFTERNOON'] }}%)</strong>
                    </div>
                    <div class="w-full h-1.5 bg-stone-200 dark:bg-stone-800">
                        <div class="h-full bg-[#C6A15B]" style="width: {{ $shiftPercentages['AFTERNOON'] }}%;"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-stone-800 dark:text-stone-200 mb-1">
                        <span>Late Night (11:30 PM – 03:30 AM)</span>
                        <strong class="text-stone-800 dark:text-stone-300">{{ $shiftCounts['NIGHT'] }} ({{ $shiftPercentages['NIGHT'] }}%)</strong>
                    </div>
                    <div class="w-full h-1.5 bg-stone-200 dark:bg-stone-800">
                        <div class="h-full bg-stone-500" style="width: {{ $shiftPercentages['NIGHT'] }}%;"></div>
                    </div>
                </div>
            </div>

            <!-- Categories -->
            <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10 space-y-2">
                <span class="text-[10px] font-mono uppercase tracking-wider text-stone-500 dark:text-stone-400 block font-bold">Functions by Event Type</span>
                <div class="flex flex-wrap gap-2">
                    @foreach($eventTypesBreakdown as $et)
                        <div class="px-2.5 py-1 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 text-xs text-stone-800 dark:text-stone-200 flex items-center gap-1.5 font-mono">
                            <span class="text-[#171513] dark:text-white font-bold">{{ $et['type'] }}:</span>
                            <strong class="text-[#C6A15B]">{{ $et['count'] }}</strong>
                            <span class="text-stone-500 dark:text-stone-400">({{ number_format($et['sales'] / 1000000, 1) }}M)</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================================
         UPCOMING FUNCTIONS SCHEDULE (NEXT 6 EVENTS)
         =================================================================== -->
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 shadow-sm dark:shadow-xl space-y-4 transition-colors duration-200">
        <div class="flex items-center justify-between border-b border-[#E7E5E4] dark:border-white/10 pb-4">
            <div>
                <span class="text-[10px] font-mono uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold">Schedule Horizon</span>
                <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold mt-0.5">Upcoming Marquee Functions</h3>
            </div>
            <a href="{{ route('calendar') }}" class="text-xs font-mono text-[#171513] dark:text-[#C6A15B] hover:text-[#8F7239] dark:hover:text-[#C6A15B] font-bold uppercase tracking-wider">
                Full Calendar Grid &rarr;
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($upcomingBookingsList as $ub)
                <div class="bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-4 space-y-2 hover:border-[#C6A15B] transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-mono text-[#171513] dark:text-[#C6A15B] font-bold">
                            {{ $ub->event_date->format('d M Y') }} • {{ $ub->slot_type }}
                        </span>
                        <span class="text-[10px] font-mono px-2 py-0.5 border {{ $ub->status === 'CONFIRMED' ? 'border-[#C6A15B]/60 bg-[#C6A15B]/15 dark:bg-[#171513]/85 dark:border-[#C6A15B]/50 text-[#8F7239] dark:text-[#C6A15B]' : 'border-[#C6A15B]/60 bg-[#C6A15B]/15 dark:bg-[#171513]/85 dark:border-[#C6A15B]/50 text-[#8F7239] dark:text-[#C6A15B]' }} font-bold">
                            {{ $ub->status }}
                        </span>
                    </div>
                    <div class="font-serif-luxury text-base text-[#171513] dark:text-white font-bold">
                        {{ $ub->event_type }} — {{ $ub->customer_name }}
                    </div>
                    <div class="flex items-center justify-between text-xs text-stone-600 dark:text-stone-400 font-mono pt-2 border-t border-stone-200 dark:border-white/10">
                        <span>{{ $ub->hall->name ?? 'Hall' }}</span>
                        <span>{{ $ub->guest_count }} Guests</span>
                    </div>
                    <div class="flex items-center justify-between text-xs font-mono pt-1">
                        <span class="text-stone-700 dark:text-stone-300 font-bold">Total: PKR {{ number_format($ub->grand_total, 0) }}</span>
                        <a href="{{ route('documents.agreement', $ub->id) }}" target="_blank" class="text-[#171513] dark:text-[#C6A15B] hover:text-[#C6A15B] underline font-bold text-[11px]">
                            View Contract
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-3 p-8 text-center text-stone-400 text-xs font-mono">
                    No upcoming functions scheduled in the near horizon.
                </div>
            @endforelse
        </div>
    </div>

    <!-- ===================================================================
         FULL RESERVATION & FINANCIAL LEDGER
         =================================================================== -->
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 shadow-sm dark:shadow-xl overflow-hidden transition-colors duration-200">
        <div class="p-6 border-b border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#171513] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="text-[10px] font-mono tracking-widest text-[#8F7239] dark:text-[#C6A15B] uppercase font-bold">Authoritative Ledger</span>
                <h2 class="font-serif-luxury text-xl sm:text-2xl text-[#171513] dark:text-white font-bold mt-0.5">
                    Programs Scheduled for {{ $periodLabel }} ({{ $allPeriodBookings->count() }})
                </h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('calendar') }}" class="px-3.5 py-2 border border-[#E7E5E4] dark:border-white/15 bg-white dark:bg-[#171513] text-xs font-mono text-stone-800 dark:text-stone-200 hover:border-[#C6A15B] uppercase tracking-wider font-bold transition">
                    Shift Matrix View
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-[#F8F5EF] dark:bg-[#171513] border-b border-[#E7E5E4] dark:border-white/10 text-[10px] font-mono uppercase tracking-wider text-[#78716C] dark:text-[#C6A15B]">
                    <tr>
                        <th class="p-4 font-bold">Date</th>
                        <th class="p-4 font-bold">Hall &amp; Slot</th>
                        <th class="p-4 font-bold">Program &amp; Host</th>
                        <th class="p-4 font-bold">Guests</th>
                        <th class="p-4 font-bold">Grand Total</th>
                        <th class="p-4 font-bold">Advance Paid</th>
                        <th class="p-4 font-bold">Balance</th>
                        <th class="p-4 font-bold">Status</th>
                        <th class="p-4 font-bold text-right">Legal Documents</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E7E5E4] dark:divide-white/10 text-stone-800 dark:text-stone-200">
                    @forelse($allPeriodBookings as $b)
                        <tr class="hover:bg-[#F8F5EF] dark:hover:bg-[#171513]/70 transition">
                            <td class="p-4 font-mono font-bold text-[#171513] dark:text-white whitespace-nowrap">{{ $b->event_date->format('d M Y') }}</td>
                            <td class="p-4">
                                <strong class="text-[#171513] dark:text-white block">{{ $b->hall->name }}</strong>
                                <span class="text-[10px] font-mono text-[#C6A15B] font-bold uppercase">{{ $b->slot_type }} SHIFT</span>
                            </td>
                            <td class="p-4">
                                <span class="text-[#171513] dark:text-white font-bold block">{{ $b->event_type }}</span>
                                <span class="text-[11px] text-stone-600 dark:text-stone-400 font-mono">{{ $b->customer_name }}</span>
                            </td>
                            <td class="p-4 font-mono font-bold text-[#171513] dark:text-white">{{ $b->guest_count }}</td>
                            <td class="p-4 font-mono font-bold text-[#171513] dark:text-white">PKR {{ number_format($b->grand_total, 0) }}</td>
                            <td class="p-4 font-mono font-bold text-[#8F7239] dark:text-[#C6A15B]">PKR {{ number_format($b->advance_paid, 0) }}</td>
                            <td class="p-4 font-mono font-bold text-[#171513] dark:text-[#F8F5EF]">PKR {{ number_format($b->balance_due, 0) }}</td>
                            <td class="p-4">
                                <span class="text-[10px] font-mono uppercase px-2.5 py-1 border {{ $b->status === 'CONFIRMED' ? 'border-[#C6A15B]/60 bg-[#C6A15B]/15 dark:bg-[#171513]/85 dark:border-[#C6A15B]/50 text-[#8F7239] dark:text-[#C6A15B]' : 'border-[#C6A15B]/60 bg-[#C6A15B]/15 dark:bg-[#171513]/85 dark:border-[#C6A15B]/50 text-[#8F7239] dark:text-[#C6A15B]' }} font-bold">
                                    {{ $b->status }}
                                </span>
                            </td>
                            <td class="p-4 text-right space-x-2 whitespace-nowrap font-mono text-[11px]">
                                <a href="{{ route('documents.agreement', $b->id) }}" target="_blank" class="text-stone-700 dark:text-stone-300 hover:text-[#171513] dark:hover:text-[#C6A15B] underline font-bold">Contract</a>
                                @if($b->payments->isNotEmpty())
                                    <span class="text-stone-300 dark:text-stone-600">•</span>
                                    <a href="{{ route('documents.receipt', $b->payments->first()->id) }}" target="_blank" class="text-[#8F7239] dark:text-[#C6A15B] hover:underline font-bold">Receipt</a>
                                 @endif
                                @if($b->kitchenKot)
                                    <span class="text-stone-300 dark:text-stone-600">•</span>
                                    <a href="{{ route('documents.kot', $b->kitchenKot->id) }}" target="_blank" class="text-[#171513] dark:text-[#C6A15B] hover:underline font-bold">KOT</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-stone-400 font-mono">No programs recorded for this timeframe.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
