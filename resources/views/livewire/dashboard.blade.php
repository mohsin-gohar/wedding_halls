<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
    
    <!-- ===================================================================
         EXECUTIVE HEADER BANNER (Obsidian & Champagne Gold)
         =================================================================== -->
    <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/40 rounded-2xl p-6 sm:p-8 relative overflow-hidden shadow-lg transition-colors duration-200">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 relative z-10">
            <div>
                <div class="flex items-center gap-2 text-xs font-sans text-[#8F7239] dark:text-[#C6A15B] uppercase tracking-widest mb-1.5 font-bold">
                    <span>Vendor Portal</span>
                    <span class="text-stone-400 dark:text-stone-600">/</span>
                    <span>Executive Operations</span>
                    <span class="text-stone-400 dark:text-stone-600">•</span>
                    <span class="text-stone-600 dark:text-stone-300 font-sans">{{ now()->format('l, d F Y') }}</span>
                </div>
                <h1 class="font-serif-luxury text-2xl sm:text-4xl text-[#171513] dark:text-white font-bold tracking-tight">
                    Banquet Operations &amp; Financial Analytics
                </h1>
                <p class="text-xs text-stone-600 dark:text-stone-300 font-sans mt-2 max-w-2xl leading-relaxed">
                    Real-time booking volume, PKR gross revenue realization, operational utility costs, and shift occupancy.
                </p>
            </div>

            <!-- Controls: Timeframe & Hall Selector -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Segmented Control -->
                <div class="bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-lg p-1 flex items-center">
                    <button wire:click="setTimeframe('WEEKLY')" 
                            class="px-3 py-1.5 rounded-md text-xs font-sans uppercase tracking-wider transition {{ $timeframe === 'WEEKLY' ? 'bg-[#C6A15B] text-[#171513] font-bold shadow-sm' : 'text-stone-700 dark:text-stone-300 hover:text-[#171513] dark:hover:text-[#C6A15B] hover:bg-stone-200/50 dark:hover:bg-white/5' }}">
                        Weekly
                    </button>
                    <button wire:click="setTimeframe('MONTHLY')" 
                            class="px-3 py-1.5 rounded-md text-xs font-sans uppercase tracking-wider transition {{ $timeframe === 'MONTHLY' ? 'bg-[#C6A15B] text-[#171513] font-bold shadow-sm' : 'text-stone-700 dark:text-stone-300 hover:text-[#171513] dark:hover:text-[#C6A15B] hover:bg-stone-200/50 dark:hover:bg-white/5' }}">
                        Monthly
                    </button>
                    <button wire:click="setTimeframe('QUARTERLY')" 
                            class="px-3 py-1.5 rounded-md text-xs font-sans uppercase tracking-wider transition {{ $timeframe === 'QUARTERLY' ? 'bg-[#C6A15B] text-[#171513] font-bold shadow-sm' : 'text-stone-700 dark:text-stone-300 hover:text-[#171513] dark:hover:text-[#C6A15B] hover:bg-stone-200/50 dark:hover:bg-white/5' }}">
                        90 Days
                    </button>
                    <button wire:click="setTimeframe('YEARLY')" 
                            class="px-3 py-1.5 rounded-md text-xs font-sans uppercase tracking-wider transition {{ $timeframe === 'YEARLY' ? 'bg-[#C6A15B] text-[#171513] font-bold shadow-sm' : 'text-stone-700 dark:text-stone-300 hover:text-[#171513] dark:hover:text-[#C6A15B] hover:bg-stone-200/50 dark:hover:bg-white/5' }}">
                        Annual
                    </button>
                </div>

                <!-- Hall Filter -->
                <select wire:model.live="selectedHallId" class="bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-lg text-[#171513] dark:text-stone-200 text-xs px-3 py-2 font-sans focus:border-[#C6A15B] outline-none">
                    <option value="ALL">All Managed Halls</option>
                    @foreach($halls as $h)
                        <option value="{{ $h->id }}">{{ $h->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    @if(auth()->user()->tenant && auth()->user()->tenant->verification_status === 'PENDING')
        <div class="bg-[#171513]/90 border border-[#C6A15B]/40 rounded-xl p-4 text-[#C6A15B] text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-md">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-[#C6A15B] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <span class="font-bold uppercase tracking-wider text-[#C6A15B] font-sans">Account Under Review:</span>
                    <span>Your hall registration is currently pending Super Admin verification. You can configure your profile, halls, and photos, and it will be visible in the public marketplace once approved.</span>
                </div>
            </div>
            <span class="px-2.5 py-1 rounded-md bg-[#C6A15B]/20 border border-[#C6A15B]/40 font-sans text-[11px] uppercase tracking-wider font-bold whitespace-nowrap self-start sm:self-auto">{{ (int) config('platform.free_access_days', 30) }}-Day Free Access Active</span>
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
        <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/40 rounded-2xl p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 shadow-md relative overflow-hidden transition-colors duration-200">
            <div class="space-y-1.5 relative z-10">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#C6A15B]"></span>
                    <span class="text-[10px] font-sans uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold">
                        RENT ADMIN — DEDICATED HALL WEBSITE
                    </span>
                    <span class="text-stone-400 dark:text-stone-500">•</span>
                    <span class="text-xs text-stone-600 dark:text-stone-300 font-sans">{{ $activeHall->venue->city ?? 'Pakistan' }}</span>
                </div>
                <h2 class="font-serif-luxury text-xl sm:text-2xl text-[#171513] dark:text-white font-bold">
                    {{ $activeHall->name }}
                </h2>
                <div class="flex items-center gap-2 text-xs font-sans text-stone-600 dark:text-stone-300">
                    <span>Client Website Link:</span>
                    <a href="{{ route('hall.showcase', $activeHall->slug) }}" target="_blank" class="text-[#8F7239] dark:text-[#C6A15B] underline hover:text-[#171513] dark:hover:text-[#D9C088] font-bold">
                        {{ route('hall.showcase', $activeHall->slug) }}
                    </a>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 relative z-10">
                <a href="{{ route('hall.showcase', $activeHall->slug) }}" target="_blank"
                   class="btn-outline-gold !h-9 !px-4 rounded-lg text-xs font-sans uppercase tracking-wider flex items-center gap-1.5 font-bold">
                    <span>Preview Hall Website ↗</span>
                </a>
                <a href="{{ route('hall.media') }}" 
                   class="btn-gold !h-9 !px-4 rounded-lg text-xs font-sans uppercase tracking-wider font-bold flex items-center gap-1.5 shadow-sm transition-all duration-200 group">
                    <span class="relative z-10">Edit Name &amp; Photos</span>
                </a>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         SaaS SUBSCRIPTION STATUS & ERP LIMITS RIBBON
         =================================================================== -->
    @if(config('platform.billing_enabled'))
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
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-xl p-5 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4 text-xs font-sans transition-colors duration-200">
        <div class="flex flex-wrap items-center gap-6">
            <div class="space-y-0.5">
                <span class="text-[9px] uppercase tracking-wider text-stone-500 dark:text-stone-400 font-bold block">Current SaaS Plan</span>
                <div class="font-sans font-bold text-sm text-[#171513] dark:text-white">{{ $planName }}</div>
            </div>

            <div class="space-y-0.5">
                <span class="text-[9px] uppercase tracking-wider text-stone-500 dark:text-stone-400 font-bold block">Subscription Status</span>
                <div>
                    @if($isActive)
                        <span class="px-2.5 py-1 rounded-md bg-[#C6A15B]/15 dark:bg-[#171513]/90 border border-[#C6A15B]/50 text-[#8F7239] dark:text-[#C6A15B] font-bold text-[10px] uppercase">
                            ● Active ({{ $tenant->subscription_status ?? 'ACTIVE' }})
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded-md bg-[#171513]/10 dark:bg-[#171513]/90 border border-[#C6A15B]/50 text-[#171513] dark:text-[#F8F5EF] font-bold text-[10px] uppercase">
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
            <a href="{{ route('expenses') }}" class="px-3.5 py-2 rounded-lg bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 hover:border-[#C6A15B] text-stone-800 dark:text-stone-200 hover:text-[#171513] dark:hover:text-[#C6A15B] transition font-sans text-xs font-bold">
                Expenses &amp; P&amp;L
            </a>
            <a href="{{ route('staff') }}" class="px-3.5 py-2 rounded-lg bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 hover:border-[#C6A15B] text-stone-800 dark:text-stone-200 hover:text-[#171513] dark:hover:text-[#C6A15B] transition font-sans text-xs font-bold">
                Manage Team
            </a>
        </div>
    </div>
    @else
    <!-- Launch Promo: Complimentary Access Ribbon (billing disabled) -->
    <div class="relative overflow-hidden bg-[#171513] border border-[#C6A15B]/40 rounded-xl p-5 shadow-md flex flex-col md:flex-row md:items-center justify-between gap-4 text-xs font-sans">
        <div class="absolute inset-0 opacity-20 pointer-events-none" style="background: radial-gradient(circle at 15% 20%, #C6A15B 0%, transparent 45%), radial-gradient(circle at 85% 80%, #8F7239 0%, transparent 50%);"></div>
        <div class="relative z-10 flex items-start sm:items-center gap-4">
            <span class="w-11 h-11 shrink-0 rounded-xl bg-[#C6A15B]/15 border border-[#C6A15B]/40 flex items-center justify-center text-lg">🎁</span>
            <div class="space-y-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2 py-0.5 rounded bg-[#C6A15B] text-[#171513] font-sans text-[9px] font-extrabold uppercase tracking-widest">Launch Offer</span>
                    <span class="text-[#C6A15B] font-bold uppercase tracking-wider text-[11px]">Free Launch Access — {{ (int) config('platform.free_access_days', 30) }} Days Complimentary</span>
                </div>
                <p class="text-stone-300 text-[11px] leading-relaxed max-w-xl">
                    Full platform access with zero subscription charges. Publish your halls, receive bookings and manage your team — completely free during our launch period.
                </p>
            </div>
        </div>
        <div class="relative z-10 flex items-center gap-3">
            <div class="text-right hidden sm:block">
                <span class="block text-[9px] uppercase tracking-wider text-stone-400 font-bold">Access Status</span>
                <span class="text-[#C6A15B] font-bold text-xs">● Active &amp; Unlimited</span>
            </div>
            <a href="{{ route('staff') }}" class="px-3.5 py-2 rounded-lg border border-[#C6A15B]/50 text-[#C6A15B] hover:bg-[#C6A15B] hover:text-[#171513] transition font-sans text-xs font-bold uppercase tracking-wider">
                Manage Team
            </a>
        </div>
    </div>
    @endif

    <!-- ===================================================================
         OPERATIONAL SNAPSHOT BAR (4 PILLARS)
         =================================================================== -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/25 rounded-xl p-5 shadow-sm relative overflow-hidden group hover:border-[#C6A15B]/60 transition duration-200">
            <span class="text-[10px] font-sans uppercase tracking-widest text-stone-500 dark:text-stone-400 block font-bold">Managed Halls</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="font-cinzel text-3xl text-[#171513] dark:text-white font-bold">{{ $totalHallsCount }}</span>
                <span class="text-[11px] font-sans text-[#C6A15B] font-bold">Active Spaces</span>
            </div>
        </div>

        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/25 rounded-xl p-5 shadow-sm relative overflow-hidden group hover:border-[#C6A15B]/60 transition duration-200">
            <span class="text-[10px] font-sans uppercase tracking-widest text-stone-500 dark:text-stone-400 block font-bold">Today's Functions</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="font-cinzel text-3xl {{ $todayEventsCount > 0 ? 'text-[#8F7239] dark:text-[#C6A15B] font-bold' : 'text-[#171513] dark:text-white' }}">{{ $todayEventsCount }}</span>
                <span class="text-[11px] font-sans {{ $todayEventsCount > 0 ? 'text-[#8F7239] dark:text-[#C6A15B] font-bold' : 'text-stone-400' }}">{{ $todayEventsCount > 0 ? 'In Execution' : 'None Today' }}</span>
            </div>
        </div>

        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/25 rounded-xl p-5 shadow-sm relative overflow-hidden group hover:border-[#C6A15B]/60 transition duration-200">
            <span class="text-[10px] font-sans uppercase tracking-widest text-stone-500 dark:text-stone-400 block font-bold">Upcoming Functions</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="font-cinzel text-3xl text-[#171513] dark:text-white font-bold">{{ $upcomingEventsCount }}</span>
                <span class="text-[11px] font-sans text-[#C6A15B] font-bold">Ahead on Calendar</span>
            </div>
        </div>

        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/25 rounded-xl p-5 shadow-sm relative overflow-hidden group hover:border-[#C6A15B]/60 transition duration-200">
            <span class="text-[10px] font-sans uppercase tracking-widest text-stone-500 dark:text-stone-400 block font-bold">Pending Inquiries</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="font-cinzel text-3xl text-[#8F7239] dark:text-[#C6A15B] font-bold">{{ $pendingInquiriesCount }}</span>
                <span class="text-[11px] font-sans text-[#8F7239] dark:text-[#C6A15B] font-bold">Awaiting Quote</span>
            </div>
        </div>
    </div>

    <!-- ===================================================================
         ENTERPRISE ERP OPERATIONS COCKPIT (Modular Mission Control)
         =================================================================== -->
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-[#E7E5E4] dark:border-white/10 pb-2">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 bg-[#C6A15B] rounded-full"></span>
                <h3 class="font-cinzel text-lg font-bold text-[#171513] dark:text-white uppercase tracking-wider">
                    Banquet ERP Modular Mission Control
                </h3>
            </div>
            <span class="text-[11px] font-sans text-stone-500 dark:text-stone-400">
                Live Interconnected Engine • Real-Time Database Sync
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- 1. Culinary & Kitchen KOT Engine -->
            @if($kitchenEnabled)
            <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/25 rounded-xl p-5 shadow-sm space-y-4 hover:border-[#C6A15B]/60 transition duration-200 flex flex-col justify-between">
                <div class="space-y-2">
                    <div class="flex items-center justify-between border-b border-[#E7E5E4] dark:border-white/10 pb-2">
                        <span class="text-[10px] font-sans uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-[#C6A15B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            <span>Kitchen KOTs</span>
                        </span>
                        @if($todayKitchenKotsCount > 0)
                            <span class="px-2 py-0.5 rounded bg-[#C6A15B]/20 text-[#8F7239] dark:text-[#C6A15B] font-sans text-[10px] font-bold">Active Today</span>
                        @else
                            <span class="text-stone-400 font-sans text-[10px]">No Shift Today</span>
                        @endif
                    </div>
                    <div>
                        <div class="text-2xl font-cinzel font-bold text-[#171513] dark:text-white">
                            {{ $todayKitchenKotsCount }} <span class="text-xs font-sans font-normal text-stone-500">Orders</span>
                        </div>
                        <p class="text-xs text-stone-600 dark:text-stone-300 font-sans mt-1">
                            @if($todayKitchenKotsCount > 0)
                                Feeding <strong class="text-[#C6A15B]">{{ number_format($todayTotalDiners) }}</strong> guests today. Req: {{ number_format($todayMeatNeeded, 1) }}kg Meat, {{ number_format($todayRiceNeeded, 1) }}kg Rice.
                            @else
                                Bawarchi kitchen ready for upcoming feast batches.
                            @endif
                        </p>
                    </div>
                </div>
                <a href="{{ route('kitchen.board') }}" class="w-full py-2 rounded-lg bg-[#F8F5EF] dark:bg-[#1f1c18] border border-[#E7E5E4] dark:border-white/10 hover:border-[#C6A15B] text-[#171513] dark:text-[#C6A15B] text-center text-xs font-sans font-bold uppercase tracking-wider transition flex items-center justify-center gap-1.5">
                    <span>Chef KOT Board ↗</span>
                </a>
            </div>
            @endif

            @if($canViewInventory)
            <!-- 2. Bawarchi Pantry & Inventory Health -->
            <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/25 rounded-xl p-5 shadow-sm space-y-4 hover:border-[#C6A15B]/60 transition duration-200 flex flex-col justify-between">
                <div class="space-y-2">
                    <div class="flex items-center justify-between border-b border-[#E7E5E4] dark:border-white/10 pb-2">
                        <span class="text-[10px] font-sans uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-[#C6A15B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            <span>Inventory Vault</span>
                        </span>
                        @if($lowStockInventoryCount > 0)
                            <span class="px-2 py-0.5 rounded bg-red-500/20 text-red-500 font-sans text-[10px] font-bold">{{ $lowStockInventoryCount }} Low Stock</span>
                        @else
                            <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-500 font-sans text-[10px] font-bold">✓ Stock Healthy</span>
                        @endif
                    </div>
                    <div>
                        <div class="text-2xl font-cinzel font-bold text-[#171513] dark:text-white">
                            {{ $totalInventoryItems }} <span class="text-xs font-sans font-normal text-stone-500">Tracked Items</span>
                        </div>
                        <p class="text-xs text-stone-600 dark:text-stone-300 font-sans mt-1">
                            Raw food staples, crockery sets, table linens, and cooking oil reserves.
                        </p>
                    </div>
                </div>
                <a href="{{ route('inventory') }}" class="w-full py-2 rounded-lg bg-[#F8F5EF] dark:bg-[#1f1c18] border border-[#E7E5E4] dark:border-white/10 hover:border-[#C6A15B] text-[#171513] dark:text-[#C6A15B] text-center text-xs font-sans font-bold uppercase tracking-wider transition flex items-center justify-center gap-1.5">
                    <span>Inventory Desk ↗</span>
                </a>
            </div>

            @endif

            @if($canViewAttendance)
            <!-- 3. Workforce & Duty Roster -->
            <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/25 rounded-xl p-5 shadow-sm space-y-4 hover:border-[#C6A15B]/60 transition duration-200 flex flex-col justify-between">
                <div class="space-y-2">
                    <div class="flex items-center justify-between border-b border-[#E7E5E4] dark:border-white/10 pb-2">
                        <span class="text-[10px] font-sans uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-[#C6A15B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>Workforce Duty</span>
                        </span>
                        <span class="text-stone-500 dark:text-stone-400 font-sans text-[10px] font-bold">
                            {{ $periodStaffPresentCount }} Present-days / {{ $todayStaffTotalCount }} Staff
                        </span>
                    </div>
                    <div>
                        <div class="text-2xl font-cinzel font-bold text-[#171513] dark:text-white">
                            {{ $periodOvertimeHours }} <span class="text-xs font-sans font-normal text-stone-500">OT Hours ({{ $periodLabel }})</span>
                        </div>
                        <p class="text-xs text-stone-600 dark:text-stone-300 font-sans mt-1">
                            Chefs, waitstaff, and cleaners. 1-click filter for individual attendance &amp; salary slips.
                        </p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('staff.attendance') }}" class="py-2 rounded-lg bg-[#F8F5EF] dark:bg-[#1f1c18] border border-[#E7E5E4] dark:border-white/10 hover:border-[#C6A15B] text-[#171513] dark:text-[#C6A15B] text-center text-[11px] font-sans font-bold uppercase tracking-wider transition">
                        Attendance
                    </a>
                    <a href="{{ route('staff.payroll') }}" class="py-2 rounded-lg bg-[#F8F5EF] dark:bg-[#1f1c18] border border-[#E7E5E4] dark:border-white/10 hover:border-[#C6A15B] text-[#171513] dark:text-[#C6A15B] text-center text-[11px] font-sans font-bold uppercase tracking-wider transition">
                        Payroll
                    </a>
                </div>
            </div>

            @endif

            <!-- 4. Energy Fleet & Daily Kharcha -->
            <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/25 rounded-xl p-5 shadow-sm space-y-4 hover:border-[#C6A15B]/60 transition duration-200 flex flex-col justify-between">
                <div class="space-y-2">
                    <div class="flex items-center justify-between border-b border-[#E7E5E4] dark:border-white/10 pb-2">
                        <span class="text-[10px] font-sans uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-[#C6A15B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <span>Utility Fleet</span>
                        </span>
                        <span class="text-[#8F7239] dark:text-[#C6A15B] font-sans text-[10px] font-bold">
                            {{ number_format($periodDieselLiters, 1) }}L Diesel (period)
                        </span>
                    </div>
                    <div>
                        <div class="text-2xl font-cinzel font-bold text-[#171513] dark:text-white">
                            {{ $periodGeneratorHours }} <span class="text-xs font-sans font-normal text-stone-500">Gen Hours ({{ $periodLabel }})</span>
                        </div>
                        <p class="text-xs text-stone-600 dark:text-stone-300 font-sans mt-1">
                            Period total: {{ number_format($periodWapdaUnits, 1) }} WAPDA units • {{ number_format($periodDieselLiters, 1) }}L diesel.
                        </p>
                    </div>
                </div>
                <a href="{{ route('utility.fuel') }}" class="w-full py-2 rounded-lg bg-[#F8F5EF] dark:bg-[#1f1c18] border border-[#E7E5E4] dark:border-white/10 hover:border-[#C6A15B] text-[#171513] dark:text-[#C6A15B] text-center text-xs font-sans font-bold uppercase tracking-wider transition flex items-center justify-center gap-1.5">
                    <span>Fuel &amp; Utility Desk ↗</span>
                </a>
            </div>

        </div>
    </div>

    <!-- ===================================================================
         HALL ADMIN: DAILY OPERATIONAL KHARCHA & GENERATOR FUEL DESK
         =================================================================== -->
    <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 rounded-2xl shadow-lg p-6 relative overflow-hidden space-y-6">

        @if($expenseFeedback)
            <div class="p-3 rounded-lg bg-[#171513]/90 border border-[#C6A15B]/60 text-[#C6A15B] text-xs font-semibold shadow-sm flex justify-between items-center">
                <span>✓ {{ $expenseFeedback }}</span>
                <button wire:click="$set('expenseFeedback', null)" class="text-[#C6A15B] font-bold text-sm">&times;</button>
            </div>
        @endif

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-[#E7E5E4] dark:border-white/10">
            <div>
                <div class="inline-flex items-center gap-2 text-[10px] font-sans uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold">
                    <span class="w-2 h-2 rounded-full bg-[#C6A15B]"></span>
                    <span>Daily Hall Operations Ledger</span>
                    <span>•</span>
                    <span>{{ now()->format('d M Y') }}</span>
                </div>
                <h2 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-normal mt-0.5">
                    Daily Kharcha &amp; Generator Fuel Manager
                </h2>
                <p class="text-xs text-stone-500 dark:text-stone-400 font-sans mt-0.5">
                    Log generator diesel liters, daily staff dihaari, cleaning chemicals, and commercial utility bills in real-time.
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                <button wire:click="openDailyExpenseModal('GENERATOR_DIESEL')" 
                        class="btn-gold !h-9 !px-4 rounded-lg text-xs font-bold uppercase font-sans shadow-sm transition flex items-center gap-2">
                    <span>+ Log Generator Fuel</span>
                </button>
                <button wire:click="openDailyExpenseModal('STAFF_DAILY_WAGES')" 
                        class="btn-outline-gold !h-9 !px-4 rounded-lg text-xs font-bold uppercase font-sans shadow-sm hover:bg-[#C6A15B]/10 transition flex items-center gap-2">
                    <span>+ Add Daily Kharcha</span>
                </button>
            </div>
        </div>

        <!-- 4 Daily Operational Pillars -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- 1. Generator Fuel -->
            <div class="p-4 rounded-xl bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-1">
                <div class="flex items-center justify-between text-[10px] font-sans uppercase tracking-wider text-stone-500 dark:text-stone-400 font-bold">
                    <span>Generator Fuel Today</span>
                    <span class="text-[#8F7239] dark:text-[#C6A15B]">{{ $todayDieselLiters > 0 ? $todayDieselLiters . ' L' : '0 L' }}</span>
                </div>
                <div class="text-2xl font-cinzel font-bold text-[#171513] dark:text-white">
                    Rs {{ number_format($todayDieselCost) }}
                </div>
                <span class="text-[10px] text-stone-500 font-sans block">Diesel refill &amp; standby gen-set</span>
            </div>

            <!-- 2. Total Kharcha Today -->
            <div class="p-4 rounded-xl bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-1">
                <div class="flex items-center justify-between text-[10px] font-sans uppercase tracking-wider text-stone-500 dark:text-stone-400 font-bold">
                    <span>Total Daily Kharcha</span>
                    <span class="text-rose-500 font-bold">Outflow</span>
                </div>
                <div class="text-2xl font-cinzel font-bold text-rose-600 dark:text-rose-400">
                    Rs {{ number_format($todayExpensesSum) }}
                </div>
                <span class="text-[10px] text-stone-500 font-sans block">Staff wages: Rs {{ number_format($todayWagesCost) }}</span>
            </div>

            <!-- 3. Cash Inflow Today -->
            <div class="p-4 rounded-xl bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-1">
                <div class="flex items-center justify-between text-[10px] font-sans uppercase tracking-wider text-stone-500 dark:text-stone-400 font-bold">
                    <span>Token Advance Inflow</span>
                    <span class="text-emerald-500 font-bold">Inflow</span>
                </div>
                <div class="text-2xl font-cinzel font-bold text-emerald-600 dark:text-emerald-400">
                    Rs {{ number_format($todayCashInflow) }}
                </div>
                <span class="text-[10px] text-stone-500 font-sans block">Shift booking deposits</span>
            </div>

            <!-- 4. Net Daily Balance -->
            <div class="p-4 rounded-xl bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-1">
                <div class="flex items-center justify-between text-[10px] font-sans uppercase tracking-wider text-stone-500 dark:text-stone-400 font-bold">
                    <span>Net Daily Drawer Balance</span>
                    <span class="font-sans">{{ $todayNetBalance >= 0 ? '+' : '' }}</span>
                </div>
                <div class="text-2xl font-cinzel font-bold {{ $todayNetBalance >= 0 ? 'text-[#8F7239] dark:text-[#C6A15B]' : 'text-rose-500' }}">
                    Rs {{ number_format($todayNetBalance) }}
                </div>
                <span class="text-[10px] text-stone-500 font-sans block">Cash in drawer minus kharcha</span>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3" aria-label="Monthly operations summary">
            <div class="p-4 rounded-xl bg-[#211e1a] border border-[#C6A15B]/25">
                <div class="text-[10px] uppercase tracking-wider text-stone-400">Kharcha · {{ $operationsMonthTitle }}</div>
                <div class="mt-1 text-xl font-cinzel font-bold text-white">Rs {{ number_format($operationsExpensesTotal) }}</div>
                <div class="mt-1 text-[10px] text-stone-400">{{ $operationsExpenseCount }} recorded entries</div>
            </div>
            <div class="p-4 rounded-xl bg-[#211e1a] border border-[#C6A15B]/25">
                <div class="text-[10px] uppercase tracking-wider text-stone-400">Generator fuel expenses</div>
                <div class="mt-1 text-xl font-cinzel font-bold text-[#C6A15B]">Rs {{ number_format($operationsDieselCost) }}</div>
                <div class="mt-1 text-[10px] text-stone-400">From dated expense records</div>
            </div>
            <div class="p-4 rounded-xl bg-[#211e1a] border border-[#C6A15B]/25">
                <div class="text-[10px] uppercase tracking-wider text-stone-400">Staff wages · cleaning</div>
                <div class="mt-1 text-xl font-cinzel font-bold text-white">Rs {{ number_format($operationsWagesCost + $operationsCleaningCost) }}</div>
                <div class="mt-1 text-[10px] text-stone-400">Wages Rs {{ number_format($operationsWagesCost) }} · Cleaning Rs {{ number_format($operationsCleaningCost) }}</div>
            </div>
            <div class="p-4 rounded-xl bg-[#211e1a] border border-[#C6A15B]/25">
                <div class="text-[10px] uppercase tracking-wider text-stone-400">Monthly cash movement</div>
                <div class="mt-1 text-xl font-cinzel font-bold {{ $operationsNetBalance >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">Rs {{ number_format($operationsNetBalance) }}</div>
                <div class="mt-1 text-[10px] text-stone-400">Received Rs {{ number_format($operationsCashInflow) }} · spent Rs {{ number_format($operationsExpensesTotal) }}</div>
            </div>
        </div>

        <!-- Recent Daily Expenses Table -->
        <div class="space-y-3 pt-2">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <span class="text-xs font-sans font-bold uppercase tracking-wider text-[#8F7239] dark:text-[#C6A15B]">
                    Daily Expenses &amp; Fuel Entries · {{ $operationsMonthTitle }}
                </span>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" wire:click="previousOperationsMonth" aria-label="Previous month" class="h-9 w-9 rounded-lg border border-[#C6A15B]/40 text-[#C6A15B] hover:bg-[#C6A15B]/10">&larr;</button>
                    <label for="operations-month" class="sr-only">Choose expense month</label>
                    <input id="operations-month" type="month" wire:model.live="operationsMonth" class="h-9 rounded-lg border border-[#C6A15B]/40 bg-[#171513] px-3 text-xs text-white">
                    <button type="button" wire:click="nextOperationsMonth" aria-label="Next month" class="h-9 w-9 rounded-lg border border-[#C6A15B]/40 text-[#C6A15B] hover:bg-[#C6A15B]/10">&rarr;</button>
                    <a href="{{ route('expenses') }}" class="ml-1 text-xs text-[#8F7239] dark:text-[#C6A15B] hover:underline font-sans font-semibold">Full Financial Register &rarr;</a>
                </div>
            </div>

            <div tabindex="0" role="region" aria-label="Daily Expenses Register" class="table-wrapper-enterprise">
                <table class="table-enterprise">
                    <thead>
                        <tr class="bg-[#F8F5EF] dark:bg-white/5 border-b border-[#E7E5E4] dark:border-white/10 text-[10px] font-sans uppercase tracking-wider text-stone-600 dark:text-stone-400">
                            <th class="p-3">Date</th>
                            <th class="p-3">Category</th>
                            <th class="p-3">Description / Hall</th>
                            <th class="p-3">Payee / Vendor</th>
                            <th class="p-3">Payment Mode</th>
                            <th class="p-3 text-right">Amount (PKR)</th>
                            <th class="p-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4] dark:divide-white/10 font-sans">
                        @forelse($recentExpenses as $exp)
                            <tr class="hover:bg-stone-50 dark:hover:bg-white/5 transition">
                                <td class="p-3 font-sans text-[11px] text-stone-600 dark:text-stone-400 whitespace-nowrap">
                                    {{ $exp->expense_date ? $exp->expense_date->format('d M Y') : '—' }}
                                </td>
                                <td class="p-3 whitespace-nowrap">
                                    @php
                                        $catBadge = match($exp->category) {
                                            'GENERATOR_DIESEL' => 'bg-amber-500/15 text-amber-600 border-amber-500/30',
                                            'STAFF_DAILY_WAGES' => 'bg-blue-500/15 text-blue-600 border-blue-500/30',
                                            'CLEANING_HYGIENE' => 'bg-emerald-500/15 text-emerald-600 border-emerald-500/30',
                                            'WAPDA_ELECTRICITY' => 'bg-yellow-500/15 text-yellow-600 border-yellow-500/30',
                                            'CHILLER_AC' => 'bg-cyan-500/15 text-cyan-600 border-cyan-500/30',
                                            'KITCHEN_GAS' => 'bg-rose-500/15 text-rose-600 border-rose-500/30',
                                            default => 'bg-stone-500/15 text-stone-600 border-stone-500/30',
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 rounded text-[9px] font-sans uppercase font-bold border {{ $catBadge }}">
                                        {{ str_replace('_', ' ', $exp->category) }}
                                    </span>
                                </td>
                                <td class="p-3">
                                    <div class="font-bold text-[#171513] dark:text-white">{{ $exp->title }}</div>
                                    @if($exp->hall)
                                        <span class="text-[10px] text-[#8F7239] dark:text-[#C6A15B] font-sans block">{{ $exp->hall->name }}</span>
                                    @endif
                                </td>
                                <td class="p-3 text-stone-600 dark:text-stone-300 font-sans text-[11px]">
                                    {{ $exp->payee ?? '—' }}
                                </td>
                                <td class="p-3 text-stone-600 dark:text-stone-400 font-sans text-[11px]">
                                    {{ $exp->payment_method ?? 'CASH' }}
                                </td>
                                <td class="p-3 text-right font-sans font-bold text-sm text-[#171513] dark:text-white whitespace-nowrap">
                                    Rs {{ number_format($exp->amount) }}
                                </td>
                                <td class="p-3 text-right">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-8 text-center text-xs text-stone-500 dark:text-stone-400 font-sans">
                                    No expenses recorded for {{ $operationsMonthTitle }}. Select another month or log an expense to start this ledger.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($operationsExpensePages > 1)
                <div class="flex items-center justify-between text-xs text-stone-400">
                    <span>Page {{ $operationsPage }} of {{ $operationsExpensePages }} · {{ $operationsExpenseCount }} records</span>
                    <div class="flex gap-2">
                        <button type="button" wire:click="$set('operationsPage', {{ max(1, $operationsPage - 1) }})" @disabled($operationsPage <= 1) class="rounded-lg border border-white/15 px-3 py-1.5 disabled:opacity-40">Previous</button>
                        <button type="button" wire:click="$set('operationsPage', {{ min($operationsExpensePages, $operationsPage + 1) }})" @disabled($operationsPage >= $operationsExpensePages) class="rounded-lg border border-white/15 px-3 py-1.5 disabled:opacity-40">Next</button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- ===================================================================
         EXECUTIVE $90K FINANCIAL & PERFORMANCE ANALYTICS COCKPIT
         (Structured strictly matching Executive Dashboard Architecture)
         =================================================================== -->

    <!-- ROW 1: TOP 4 STAT CARDS (Side by side with Hero Earning Card) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. HERO EARNING CARD (Obsidian Dark with Gold Accent & Glow) -->
        <div class="bg-[#171513] text-white border border-[#C6A15B] rounded-2xl p-6 shadow-xl relative overflow-hidden group">
            <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-[#C6A15B]/10 rounded-full blur-2xl group-hover:bg-[#C6A15B]/20 transition-all pointer-events-none"></div>
            
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-sans font-bold uppercase tracking-widest text-[#F2DE9A]">
                    Earning / Gross
                </span>
                <span class="w-8 h-8 rounded-lg bg-[#C6A15B]/20 border border-[#C6A15B]/50 flex items-center justify-center text-[#F2DE9A]">
                    <svg class="w-4 h-4 text-[#C6A15B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>

            <div class="mt-4">
                <span class="font-cinzel text-2xl sm:text-3xl text-white font-bold tracking-tight block">
                    PKR {{ number_format($grossSalesTurnover, 0) }}
                </span>
            </div>

            <div class="mt-3 pt-3 border-t border-white/10 flex items-center justify-between text-xs text-stone-300 font-sans">
                <span class="text-stone-400">Advance Paid:</span>
                <span class="text-[#F2DE9A] font-semibold">PKR {{ number_format($advanceCollected / 1000) }}k</span>
                <span class="px-2 py-0.5 rounded bg-[#C6A15B]/25 text-[#F2DE9A] text-[10px] font-bold">
                    {{ $salesGrowth >= 0 ? '+' : '' }}{{ $salesGrowth }}%
                </span>
            </div>
        </div>

        <!-- 2. SHARE / INQUIRIES CARD -->
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-2xl p-6 shadow-md hover:border-[#C6A15B]/70 transition-all duration-200 space-y-3 relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-sans font-bold uppercase tracking-widest text-stone-500 dark:text-stone-400">
                    Inquiries &amp; Views
                </span>
                <span class="w-8 h-8 rounded-lg bg-stone-100 dark:bg-white/5 border border-stone-200 dark:border-white/10 flex items-center justify-center text-[#8F7239] dark:text-[#C6A15B]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                </span>
            </div>

            <div class="mt-4">
                <span class="font-cinzel text-2xl sm:text-3xl text-[#171513] dark:text-white font-bold tracking-tight block">
                    @if($canViewLeads){{ number_format($allLeads->count()) }}@else{{ number_format($pendingInquiriesCount) }}@endif
                </span>
            </div>

            <div class="mt-3 pt-3 border-t border-stone-100 dark:border-white/10 flex items-center justify-between text-xs text-stone-500 dark:text-stone-400 font-sans">
                <span>Total Hall Views:</span>
                <strong class="text-[#171513] dark:text-stone-200 font-semibold">{{ number_format($totalHallViews) }}</strong>
                <span class="px-2 py-0.5 rounded bg-amber-500/15 text-amber-700 dark:text-amber-400 text-[10px] font-bold">
                    {{ $pendingInquiriesCount }} Quotes
                </span>
            </div>
        </div>

        <!-- 3. SALES / BOOKINGS CARD -->
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-2xl p-6 shadow-md hover:border-[#C6A15B]/70 transition-all duration-200 space-y-3 relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-sans font-bold uppercase tracking-widest text-stone-500 dark:text-stone-400">
                    Confirmed Sales
                </span>
                <span class="w-8 h-8 rounded-lg bg-stone-100 dark:bg-white/5 border border-stone-200 dark:border-white/10 flex items-center justify-center text-[#8F7239] dark:text-[#C6A15B]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>

            <div class="mt-4 flex items-baseline gap-2">
                <span class="font-cinzel text-2xl sm:text-3xl text-[#171513] dark:text-white font-bold tracking-tight">
                    {{ $confirmedPrograms }}
                </span>
                <span class="text-xs font-sans text-stone-500 dark:text-stone-400 font-normal">
                    / {{ $totalPrograms }} Total
                </span>
            </div>

            <div class="mt-3 pt-3 border-t border-stone-100 dark:border-white/10 flex items-center justify-between text-xs text-stone-500 dark:text-stone-400 font-sans">
                <span>Diners Expected:</span>
                <strong class="text-[#171513] dark:text-stone-200 font-semibold">{{ number_format($totalGuestsServed) }}</strong>
                <span class="px-2 py-0.5 rounded bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 text-[10px] font-bold">
                    {{ $holdPrograms }} On Hold
                </span>
            </div>
        </div>

        <!-- 4. RATING & NET MARGIN CARD -->
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-2xl p-6 shadow-md hover:border-[#C6A15B]/70 transition-all duration-200 space-y-3 relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-sans font-bold uppercase tracking-widest text-stone-500 dark:text-stone-400">
                    Margin &amp; Rating
                </span>
                <span class="w-8 h-8 rounded-lg bg-stone-100 dark:bg-white/5 border border-stone-200 dark:border-white/10 flex items-center justify-center text-[#8F7239] dark:text-[#C6A15B]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                </span>
            </div>

            <div class="mt-4 flex items-baseline gap-2">
                <span class="font-cinzel text-2xl sm:text-3xl text-[#171513] dark:text-white font-bold tracking-tight">
                    {{ $profitMarginPercent }}%
                </span>
                <span class="text-xs font-sans text-[#8F7239] dark:text-[#C6A15B] font-bold">
                    ★ 8.9 / 10
                </span>
            </div>

            <div class="mt-3 pt-3 border-t border-stone-100 dark:border-white/10 flex items-center justify-between text-xs text-stone-500 dark:text-stone-400 font-sans">
                <span>Net Profit:</span>
                <strong class="text-[#8F7239] dark:text-[#C6A15B] font-bold">PKR {{ number_format($netOwnerProfit / 1000) }}k</strong>
                <span class="px-2 py-0.5 rounded bg-[#C6A15B]/15 text-[#8F7239] dark:text-[#C6A15B] text-[10px] font-bold">
                    Op Margin
                </span>
            </div>
        </div>
    </div>

    <!-- ROW 2: MAIN DUAL-BAR CHART & CIRCULAR DONUT RING GAUGE -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
        
        <!-- LEFT: DUAL-BAR TREND CHART (8 cols) -->
        <div class="lg:col-span-8 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-2xl p-6 sm:p-7 shadow-md flex flex-col justify-between space-y-6 transition-colors duration-200">
            <!-- Header & Controls -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E7E5E4] dark:border-white/10 pb-4">
                <div>
                    <span class="text-[10px] font-sans font-bold uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B]">
                        Performance Dynamics
                    </span>
                    <h3 class="font-serif-luxury text-xl sm:text-2xl text-[#171513] dark:text-white font-bold mt-0.5">
                        {{ $timeframe === 'WEEKLY' ? 'Weekly Shift & Revenue Activity' : ($timeframe === 'YEARLY' ? 'Annual Month-by-Month Distribution' : 'Monthly 4-Week Shift Cadence') }}
                    </h3>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <!-- Metric Buttons -->
                    <div class="inline-flex rounded-lg bg-[#F8F5EF] dark:bg-white/5 border border-[#E7E5E4] dark:border-white/10 p-1">
                        <button wire:click="setChartMetric('sales')" 
                                class="px-3 py-1 rounded-md text-[11px] font-sans uppercase tracking-wider transition {{ $chartMetric === 'sales' ? 'bg-[#171513] dark:bg-[#C6A15B] text-[#C6A15B] dark:text-[#171513] font-bold shadow-sm' : 'text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:hover:text-white' }}">
                            Sales (PKR)
                        </button>
                        <button wire:click="setChartMetric('programs')" 
                                class="px-3 py-1 rounded-md text-[11px] font-sans uppercase tracking-wider transition {{ $chartMetric === 'programs' ? 'bg-[#171513] dark:bg-[#C6A15B] text-[#C6A15B] dark:text-[#171513] font-bold shadow-sm' : 'text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:hover:text-white' }}">
                            Volume
                        </button>
                    </div>

                    <!-- Legend -->
                    <div class="hidden sm:flex items-center gap-3 text-[11px] font-sans text-stone-500 dark:text-stone-400 pl-2">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-sm bg-[#C6A15B]"></span>
                            <span>Evening Shift</span>
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-sm bg-slate-700 dark:bg-stone-600"></span>
                            <span>Afternoon Shift</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Bar Chart Surface -->
            <div class="relative pt-6 pb-2">
                <!-- Subtle Horizontal Grid Lines -->
                <div class="absolute inset-x-0 top-6 bottom-10 flex flex-col justify-between pointer-events-none opacity-30 dark:opacity-20">
                    <div class="border-b border-dashed border-stone-400 dark:border-stone-500 w-full"></div>
                    <div class="border-b border-dashed border-stone-400 dark:border-stone-500 w-full"></div>
                    <div class="border-b border-dashed border-stone-400 dark:border-stone-500 w-full"></div>
                    <div class="border-b border-dashed border-stone-400 dark:border-stone-500 w-full"></div>
                </div>

                <!-- Paired Bars Container -->
                <div class="relative z-10 grid gap-2 sm:gap-6 items-end h-64 pt-6" style="grid-template-columns: repeat({{ max(1, count($chartData)) }}, minmax(0, 1fr));">
                    @foreach($chartData as $point)
                        <div class="flex flex-col items-center h-full justify-end group relative">
                            
                            <!-- Floating Peak Pill (as seen in user reference image above the peak bar) -->
                            @if(!empty($point['isPeak']))
                                <div class="absolute -top-7 left-1/2 -translate-x-1/2 px-2 py-0.5 rounded-md bg-[#171513] dark:bg-white text-[#F2DE9A] dark:text-[#171513] text-[10px] font-bold font-sans shadow-lg border border-[#C6A15B] whitespace-nowrap z-30 flex items-center gap-1 animate-pulse">
                                    <span>★ {{ $point['peakPill'] ?? $point['formatted'] }}</span>
                                </div>
                            @endif

                            <!-- Hover Tooltip -->
                            <div class="opacity-0 group-hover:opacity-100 transition duration-150 pointer-events-none absolute -top-10 rounded-lg bg-[#171513] border border-[#C6A15B] text-white text-[11px] px-2.5 py-1 shadow-2xl whitespace-nowrap z-40 font-sans">
                                <div class="font-bold text-[#F2DE9A]">{{ $point['fullLabel'] ?? $point['label'] }}</div>
                                <div class="text-[10px] text-stone-300">Evening: {{ $point['formatted'] }}</div>
                                <div class="text-[10px] text-stone-400">Afternoon: {{ $point['secondaryFormatted'] ?? '0' }}</div>
                            </div>

                            <!-- Paired Bars (Side-by-Side: Gold & Slate) -->
                            <div class="flex items-end gap-1 sm:gap-1.5 w-full justify-center h-full pb-1">
                                <!-- Evening Bar (Champagne Gold) -->
                                <div class="w-full max-w-[14px] sm:max-w-[20px] rounded-t-sm transition-all duration-300 ease-out bg-[#C6A15B] group-hover:brightness-110 shadow-sm"
                                     style="height: {{ max(8, $point['percentage']) }}%;">
                                </div>

                                <!-- Afternoon Bar (Slate/Obsidian) -->
                                <div class="w-full max-w-[14px] sm:max-w-[20px] rounded-t-sm transition-all duration-300 ease-out bg-slate-700 dark:bg-stone-600 group-hover:brightness-125 shadow-sm"
                                     style="height: {{ max(6, $point['secondaryPercentage'] ?? 10) }}%;">
                                </div>
                            </div>

                            <!-- X-Axis Label -->
                            <span class="text-[10px] text-stone-600 dark:text-stone-400 group-hover:text-[#171513] dark:group-hover:text-white mt-2 text-center truncate w-full font-sans font-semibold">
                                {{ $point['label'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Footer Meta -->
            <div class="pt-3 border-t border-[#E7E5E4] dark:border-white/10 flex flex-wrap items-center justify-between text-xs text-stone-500 dark:text-stone-400 font-sans">
                <span>Peak Shift: <strong class="text-[#8F7239] dark:text-[#C6A15B] font-bold">Evening Slots (78% Utilization)</strong></span>
                <span>Realized Cashflow: <strong class="text-[#171513] dark:text-white font-bold">PKR {{ number_format($grossSalesTurnover, 0) }}</strong></span>
            </div>
        </div>

        <!-- RIGHT: CIRCULAR DONUT RING GAUGE (4 cols - Shift Occupancy) -->
        <div class="lg:col-span-4 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-2xl p-6 sm:p-7 shadow-md flex flex-col justify-between space-y-5 transition-colors duration-200">
            <div>
                <span class="text-[10px] font-sans font-bold uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B]">
                    Shift Utilization
                </span>
                <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold mt-0.5">
                    Banquet Occupancy
                </h3>
            </div>

            <!-- Circular SVG Ring Gauge (Exact Match to User Image's 45% Gauge) -->
            <div class="flex flex-col items-center justify-center relative py-2">
                @php
                    $gaugePct = $shiftPercentages['EVENING'] > 0 ? $shiftPercentages['EVENING'] : 45;
                    $circumference = 339.29; // 2 * pi * 54
                    $offset = $circumference * (1 - ($gaugePct / 100));
                @endphp
                <svg viewBox="0 0 140 140" class="w-36 h-36 transform -rotate-90">
                    <defs>
                        <linearGradient id="goldDonutGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#F2DE9A" />
                            <stop offset="100%" stop-color="#C6A15B" />
                        </linearGradient>
                    </defs>
                    <!-- Background Ring -->
                    <circle cx="70" cy="70" r="54" 
                            stroke="currentColor" 
                            class="text-stone-200 dark:text-white/10" 
                            stroke-width="12" 
                            fill="none" />
                    <!-- Afternoon Segment -->
                    <circle cx="70" cy="70" r="54" 
                            stroke="#334155" 
                            stroke-width="12" 
                            fill="none" 
                            stroke-dasharray="{{ $circumference }}" 
                            stroke-dashoffset="{{ $circumference * (1 - (($shiftPercentages['AFTERNOON'] ?? 25) / 100)) }}" />
                    <!-- Primary Evening Segment (Gold) -->
                    <circle cx="70" cy="70" r="54" 
                            stroke="url(#goldDonutGrad)" 
                            stroke-width="12" 
                            fill="none" 
                            stroke-linecap="round"
                            stroke-dasharray="{{ $circumference }}" 
                            stroke-dashoffset="{{ $offset }}" />
                </svg>

                <!-- Center Text Overlay -->
                <div class="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none">
                    <span class="font-cinzel text-3xl font-bold text-[#171513] dark:text-white leading-none">
                        {{ $gaugePct }}%
                    </span>
                    <span class="text-[10px] font-sans font-bold uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] mt-1">
                        Utilized
                    </span>
                </div>
            </div>

            <!-- Segment List Details -->
            <div class="space-y-2.5 font-sans text-xs border-t border-[#E7E5E4] dark:border-white/10 pt-4">
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-2 text-stone-700 dark:text-stone-300">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#C6A15B]"></span>
                        <span>Evening Shift (7 PM - 11 PM)</span>
                    </span>
                    <strong class="text-[#8F7239] dark:text-[#C6A15B] font-bold">{{ $shiftCounts['EVENING'] }} ({{ $shiftPercentages['EVENING'] }}%)</strong>
                </div>

                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-2 text-stone-700 dark:text-stone-300">
                        <span class="w-2.5 h-2.5 rounded-full bg-slate-700 dark:bg-stone-500"></span>
                        <span>Afternoon Shift (12 PM - 4 PM)</span>
                    </span>
                    <strong class="text-stone-800 dark:text-stone-200 font-bold">{{ $shiftCounts['AFTERNOON'] }} ({{ $shiftPercentages['AFTERNOON'] }}%)</strong>
                </div>

                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-2 text-stone-500 dark:text-stone-400">
                        <span class="w-2.5 h-2.5 rounded-full bg-stone-300 dark:bg-white/20"></span>
                        <span>Open Booking Slots</span>
                    </span>
                    <strong class="text-stone-500 dark:text-stone-400 font-medium">{{ max(0, 100 - $shiftPercentages['EVENING'] - $shiftPercentages['AFTERNOON']) }}%</strong>
                </div>
            </div>

            <!-- Action Button -->
            <a href="{{ route('calendar') }}" 
               class="btn-gold !w-full !h-10 text-xs font-bold uppercase tracking-wider font-sans rounded-lg shadow-sm transition-all duration-200 flex items-center justify-center gap-2">
                <span>View Shift Calendar &rarr;</span>
            </a>
        </div>
    </div>

    <!-- ROW 3: AREA SPLINE WAVE CHART & MINI SHIFT MATRIX CALENDAR -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
        
        <!-- LEFT: AREA SPLINE WAVE CHART (8 cols - Cashflow Inflow vs Kharcha) -->
        <div class="lg:col-span-8 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-2xl p-6 sm:p-7 shadow-md flex flex-col justify-between space-y-5 transition-colors duration-200">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-[#E7E5E4] dark:border-white/10 pb-4">
                <div>
                    <span class="text-[10px] font-sans font-bold uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B]">
                        Financial Liquidity &amp; Burn
                    </span>
                    <h3 class="font-serif-luxury text-xl sm:text-2xl text-[#171513] dark:text-white font-bold mt-0.5">
                        Operating Cashflow &amp; Kharcha Dynamics
                    </h3>
                </div>

                <div class="flex items-center gap-4 text-xs font-sans">
                    <span class="inline-flex items-center gap-1.5 text-stone-700 dark:text-stone-300">
                        <span class="w-3 h-3 rounded-sm bg-[#C6A15B]"></span>
                        <span>Gross Inflow (PKR {{ number_format(($waveData['totalInflow'] ?? 0) / 1000) }}k)</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-stone-500 dark:text-stone-400">
                        <span class="w-3 h-3 rounded-sm bg-slate-700 dark:bg-stone-600"></span>
                        <span>Operating Kharcha (PKR {{ number_format(($waveData['totalExpense'] ?? 0) / 1000) }}k)</span>
                    </span>
                </div>
            </div>

            <!-- SVG Spline Wave Chart (Dual Gradient Fill matching user image) -->
            <div class="relative w-full overflow-hidden pt-2">
                <svg viewBox="0 0 600 150" class="w-full h-44 overflow-visible" preserveAspectRatio="none">
                    <defs>
                        <!-- Gold Gradient for Inflow -->
                        <linearGradient id="waveGoldGradient" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#C6A15B" stop-opacity="0.45" />
                            <stop offset="50%" stop-color="#C6A15B" stop-opacity="0.15" />
                            <stop offset="100%" stop-color="#C6A15B" stop-opacity="0.0" />
                        </linearGradient>
                        <!-- Slate/Navy Gradient for Expenses -->
                        <linearGradient id="waveSlateGradient" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#334155" stop-opacity="0.5" />
                            <stop offset="50%" stop-color="#334155" stop-opacity="0.2" />
                            <stop offset="100%" stop-color="#334155" stop-opacity="0.0" />
                        </linearGradient>
                    </defs>

                    <!-- Background Grid Lines -->
                    <line x1="20" y1="30" x2="580" y2="30" stroke="currentColor" class="text-stone-200 dark:text-white/5" stroke-dasharray="4 4" stroke-width="1" />
                    <line x1="20" y1="75" x2="580" y2="75" stroke="currentColor" class="text-stone-200 dark:text-white/5" stroke-dasharray="4 4" stroke-width="1" />
                    <line x1="20" y1="120" x2="580" y2="120" stroke="currentColor" class="text-stone-300 dark:text-white/10" stroke-width="1" />

                    <!-- Inflow Wave Area & Path -->
                    @if(!empty($waveData['inflowArea']))
                        <path d="{{ $waveData['inflowArea'] }}" fill="url(#waveGoldGradient)" />
                        <path d="{{ $waveData['inflowPath'] }}" fill="none" stroke="#C6A15B" stroke-width="3" stroke-linecap="round" />
                    @endif

                    <!-- Expense Wave Area & Path -->
                    @if(!empty($waveData['expenseArea']))
                        <path d="{{ $waveData['expenseArea'] }}" fill="url(#waveSlateGradient)" />
                        <path d="{{ $waveData['expensePath'] }}" fill="none" stroke="#64748B" stroke-width="2.5" stroke-linecap="round" stroke-dasharray="2 0" />
                    @endif

                    <!-- Coordinate Markers on Inflow -->
                    @foreach($waveData['inflowCoords'] ?? [] as $ic)
                        <circle cx="{{ $ic['x'] }}" cy="{{ $ic['y'] }}" r="4" fill="#C6A15B" stroke="#171513" stroke-width="2" class="cursor-pointer hover:r-6 transition-all" />
                        <text x="{{ $ic['x'] }}" y="136" text-anchor="middle" font-size="9" font-family="sans-serif" class="fill-stone-500 dark:fill-stone-400">{{ $ic['label'] }}</text>
                    @endforeach
                </svg>
            </div>

            <!-- Wave Summary Ticker -->
            <div class="pt-3 border-t border-[#E7E5E4] dark:border-white/10 flex flex-wrap items-center justify-between text-xs text-stone-600 dark:text-stone-400 font-sans">
                <span>Net Retention: <strong class="text-[#8F7239] dark:text-[#C6A15B] font-bold">PKR {{ number_format((($waveData['totalInflow'] ?? 0) - ($waveData['totalExpense'] ?? 0)), 0) }}</strong></span>
                <span>Fuel &amp; Utility Cost: <strong class="text-stone-800 dark:text-stone-200 font-semibold">PKR {{ number_format($totalUtilityCost, 0) }}</strong></span>
            </div>
        </div>

        <!-- RIGHT: MINI BANQUET SHIFT CALENDAR (4 cols - matching user image) -->
        <div class="lg:col-span-4 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-2xl p-6 sm:p-7 shadow-md flex flex-col justify-between space-y-4 transition-colors duration-200">
            <div class="flex items-center justify-between border-b border-[#E7E5E4] dark:border-white/10 pb-3">
                <div>
                    <span class="text-[10px] font-sans font-bold uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B]">
                        Shift Matrix
                    </span>
                    <h3 class="font-serif-luxury text-lg text-[#171513] dark:text-white font-bold">
                        {{ $miniCalendar['monthName'] ?? now()->format('F Y') }}
                    </h3>
                </div>
                <div class="flex items-center gap-2 text-[10px] font-sans">
                    <span class="inline-flex items-center gap-1 text-[#8F7239] dark:text-[#C6A15B]">
                        <span class="w-2 h-2 rounded-full bg-[#C6A15B]"></span> Eve
                    </span>
                    <span class="inline-flex items-center gap-1 text-slate-600 dark:text-slate-400">
                        <span class="w-2 h-2 rounded-full bg-slate-700"></span> Aft
                    </span>
                </div>
            </div>

            <!-- Calendar Days Matrix -->
            <div class="space-y-1">
                <!-- Day Names Header -->
                <div class="grid grid-cols-7 text-center text-[10px] font-sans font-bold uppercase tracking-wider text-stone-400 dark:text-stone-500 mb-1">
                    <span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span><span>S</span>
                </div>

                <!-- Calendar Days Grid -->
                <div class="grid grid-cols-7 gap-1 text-center font-sans">
                    <!-- Leading Blanks -->
                    @for($b = 0; $b < ($miniCalendar['leadBlanks'] ?? 0); $b++)
                        <div class="h-8"></div>
                    @endfor

                    <!-- Days of Month -->
                    @foreach($miniCalendar['days'] ?? [] as $day)
                        <div class="h-8 flex flex-col items-center justify-center rounded-lg text-xs transition relative group
                                    {{ $day['isToday'] ? 'bg-[#C6A15B]/20 border border-[#C6A15B] font-bold text-[#8F7239] dark:text-[#F2DE9A]' : 'hover:bg-stone-100 dark:hover:bg-white/5 text-stone-700 dark:text-stone-300' }}">
                            <span class="leading-none text-[11px]">{{ $day['day'] }}</span>
                            
                            <!-- Dots for shifts -->
                            <div class="flex items-center gap-0.5 mt-0.5">
                                @if($day['hasEvening'])
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#C6A15B]"></span>
                                @endif
                                @if($day['hasAfternoon'])
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-700 dark:bg-stone-500"></span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Next Confirmed Event Strip -->
            @if(!empty($miniCalendar['nextBooking']))
                <div class="p-3 rounded-xl bg-[#F8F5EF] dark:bg-white/5 border border-[#C6A15B]/30 flex items-center justify-between text-xs font-sans">
                    <div class="truncate pr-2">
                        <span class="text-[10px] uppercase font-bold text-[#8F7239] dark:text-[#C6A15B] block">Next Reservation</span>
                        <strong class="text-[#171513] dark:text-white truncate block">{{ $miniCalendar['nextBooking']['date'] }} ({{ $miniCalendar['nextBooking']['slot'] }})</strong>
                        <span class="text-[11px] text-stone-500 dark:text-stone-400 truncate block">{{ $miniCalendar['nextBooking']['hall'] }} • {{ $miniCalendar['nextBooking']['client'] }}</span>
                    </div>
                    <a href="{{ route('calendar') }}" class="px-2.5 py-1.5 rounded-lg bg-[#C6A15B] text-[#171513] font-bold text-[10px] uppercase tracking-wider shrink-0">
                        View &rarr;
                    </a>
                </div>
            @else
                <div class="p-3 rounded-xl bg-[#F8F5EF] dark:bg-white/5 border border-[#E7E5E4] dark:border-white/10 text-center text-xs text-stone-500 font-sans">
                    <span>No upcoming bookings this week</span>
                </div>
            @endif
        </div>
    </div>

    <!-- ===================================================================
         VENUE BREAKDOWN & SHIFT OCCUPANCY
         =================================================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Hall Comparison (2 cols) -->
        <div class="lg:col-span-2 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/25 rounded-2xl p-6 shadow-sm space-y-6 transition-colors duration-200">
            <div class="flex items-center justify-between border-b border-[#E7E5E4] dark:border-white/10 pb-4">
                <div>
                    <span class="text-[10px] font-sans uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold">Venue Distribution</span>
                    <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold mt-0.5">Hall-by-Hall Revenue Share</h3>
                </div>
                <span class="text-xs text-stone-500 dark:text-stone-400 font-sans">{{ $halls->count() }} Venues</span>
            </div>

            <div class="space-y-4">
                @forelse($hallPerformance as $hp)
                    <div class="p-4 rounded-xl bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 hover:border-[#C6A15B]/60 transition space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-serif-luxury text-base text-[#171513] dark:text-white font-bold">{{ $hp['name'] }}</span>
                            <span class="text-xs font-sans font-bold text-[#171513] dark:text-[#C6A15B]">PKR {{ number_format($hp['sales'], 0) }}</span>
                        </div>

                        @php
                            $hallPct = $grossSalesTurnover > 0 ? round(($hp['sales'] / $grossSalesTurnover) * 100) : 0;
                        @endphp
                        <div class="w-full h-1.5 rounded-full bg-stone-200 dark:bg-stone-800 overflow-hidden">
                            <div class="h-full rounded-full bg-[#C6A15B] transition-all duration-500" style="width: {{ $hallPct }}%;"></div>
                        </div>

                        <div class="flex items-center justify-between text-[11px] font-sans text-stone-600 dark:text-stone-400 pt-1">
                            <span>Functions: <strong class="text-[#171513] dark:text-white">{{ $hp['programs'] }}</strong></span>
                            <span>Guests: <strong class="text-[#171513] dark:text-white">{{ number_format($hp['guests']) }}</strong></span>
                            <span>Share: <strong class="text-[#C6A15B] font-bold">{{ $hallPct }}%</strong></span>
                            <span>Avg Event: <strong class="text-[#171513] dark:text-white">PKR {{ number_format($hp['avgPerProgram'] / 1000) }}k</strong></span>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-stone-400 text-xs font-sans">
                        No venue records configured for this tenant.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Shift Slot Occupancy & Categories (1 col) -->
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/25 rounded-2xl p-6 shadow-sm space-y-6 transition-colors duration-200">
            <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-4">
                <span class="text-[10px] font-sans uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold">Shift Utilization</span>
                <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold mt-0.5">Operating Shift Rhythms</h3>
            </div>

            <div class="space-y-4 font-sans text-xs">
                <div>
                    <div class="flex justify-between text-stone-800 dark:text-stone-200 mb-1">
                        <span>Evening (07:00 PM – 11:00 PM)</span>
                        <strong class="text-[#8F7239] dark:text-[#C6A15B]">{{ $shiftCounts['EVENING'] }} ({{ $shiftPercentages['EVENING'] }}%)</strong>
                    </div>
                    <div class="w-full h-1.5 rounded-full bg-stone-200 dark:bg-stone-800 overflow-hidden">
                        <div class="h-full rounded-full bg-[#C6A15B]" style="width: {{ $shiftPercentages['EVENING'] }}%;"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-stone-800 dark:text-stone-200 mb-1">
                        <span>Afternoon (12:00 PM – 04:00 PM)</span>
                        <strong class="text-[#171513] dark:text-white">{{ $shiftCounts['AFTERNOON'] }} ({{ $shiftPercentages['AFTERNOON'] }}%)</strong>
                    </div>
                    <div class="w-full h-1.5 rounded-full bg-stone-200 dark:bg-stone-800 overflow-hidden">
                        <div class="h-full rounded-full bg-[#C6A15B]" style="width: {{ $shiftPercentages['AFTERNOON'] }}%;"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-stone-800 dark:text-stone-200 mb-1">
                        <span>Late Night (11:30 PM – 03:30 AM)</span>
                        <strong class="text-stone-800 dark:text-stone-300">{{ $shiftCounts['NIGHT'] }} ({{ $shiftPercentages['NIGHT'] }}%)</strong>
                    </div>
                    <div class="w-full h-1.5 rounded-full bg-stone-200 dark:bg-stone-800 overflow-hidden">
                        <div class="h-full rounded-full bg-stone-500" style="width: {{ $shiftPercentages['NIGHT'] }}%;"></div>
                    </div>
                </div>
            </div>

            <!-- Categories -->
            <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10 space-y-2">
                <span class="text-[10px] font-sans uppercase tracking-wider text-stone-500 dark:text-stone-400 block font-bold">Functions by Event Type</span>
                <div class="flex flex-wrap gap-2">
                    @foreach($eventTypesBreakdown as $et)
                        <div class="px-2.5 py-1 rounded-md bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 text-xs text-stone-800 dark:text-stone-200 flex items-center gap-1.5 font-sans">
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
         TODAY'S LIVE WEDDING FUNCTIONS (IN EXECUTION TODAY)
         =================================================================== -->
    <div class="bg-white dark:bg-[#171513] border {{ $todayBookings->count() > 0 ? 'border-[#C6A15B]/60' : 'border-[#E7E5E4] dark:border-[#C6A15B]/25' }} rounded-2xl p-6 shadow-sm space-y-4 transition-colors duration-200 relative overflow-hidden">

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-[#E7E5E4] dark:border-white/10 pb-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full {{ $todayBookings->count() > 0 ? 'bg-emerald-500' : 'bg-stone-400' }}"></span>
                    <span class="text-[10px] font-sans uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold">
                        Today's Floor Operations • {{ now()->format('l, d F Y') }}
                    </span>
                </div>
                <h3 class="font-serif-luxury text-xl sm:text-2xl text-[#171513] dark:text-white font-bold mt-0.5">
                    Today's Live Functions ({{ $todayBookings->count() }})
                </h3>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="Livewire.dispatch('openWalkInModal')" class="btn-gold !h-8 !px-3 rounded-lg text-xs font-sans font-bold uppercase shadow-sm">
                    + Add New Booking
                </button>
                <a href="{{ route('calendar') }}" class="btn-outline-gold !h-8 !px-3 rounded-lg text-xs font-sans font-bold uppercase">
                    Shift Matrix ↗
                </a>
            </div>
        </div>

        @if($todayBookings->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($todayBookings as $tb)
                    <div class="bg-[#F8F5EF] dark:bg-[#1a1714] border border-[#C6A15B]/35 rounded-xl p-4 space-y-3 relative overflow-hidden shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded bg-[#C6A15B]/20 text-[#8F7239] dark:text-[#C6A15B] text-xs font-sans font-bold uppercase">
                                {{ $tb->slot_type }} Shift
                            </span>
                            <span class="text-[10px] font-sans font-bold px-2 py-0.5 rounded border border-emerald-500/40 bg-emerald-500/10 text-emerald-500">
                                {{ $tb->status }}
                            </span>
                        </div>
                        <div>
                            <div class="font-serif-luxury text-lg text-[#171513] dark:text-white font-bold">
                                {{ $tb->event_type }} — {{ $tb->customer_name }}
                            </div>
                            <div class="text-xs text-stone-600 dark:text-stone-300 font-sans mt-0.5">
                                {{ $tb->customer_phone ?? '—' }} • {{ $tb->guest_count }} Guests
                            </div>
                        </div>

                        <div class="pt-2 border-t border-stone-200 dark:border-white/10 flex items-center justify-between text-xs font-sans">
                            <div>
                                <span class="text-[10px] text-stone-500 block">Grand Total</span>
                                <strong class="text-[#171513] dark:text-white">Rs {{ number_format($tb->grand_total) }}</strong>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] text-stone-500 block">Balance Due</span>
                                <strong class="{{ $tb->balance_due > 0 ? 'text-amber-500' : 'text-emerald-500' }}">
                                    Rs {{ number_format($tb->balance_due) }}
                                </strong>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-stone-200 dark:border-white/10 flex items-center justify-between gap-2 text-[11px] font-sans font-bold">
                            @if($kitchenEnabled)
                            <a href="{{ route('kitchen.board') }}" class="text-[#8F7239] dark:text-[#C6A15B] hover:underline flex items-center gap-1">
                                <span>KOT Slip</span>
                            </a>
                            @endif
                            <a href="{{ route('documents.agreement', $tb->id) }}" target="_blank" class="text-stone-600 dark:text-stone-300 hover:text-[#C6A15B] underline">
                                Contract
                            </a>
@if($tb->payments->isNotEmpty())
                                <a href="{{ route('documents.receipt', $tb->payments->first()->id) }}" target="_blank" class="text-stone-600 dark:text-stone-300 hover:text-[#C6A15B] underline">
                                    Receipt
                                </a>
                            @else
                                <span class="text-stone-400 dark:text-stone-500">No Receipt</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-6 rounded-xl text-center bg-[#F8F5EF] dark:bg-white/[0.02] border border-dashed border-stone-300 dark:border-white/10 space-y-2">
                <p class="text-xs text-stone-600 dark:text-stone-400 font-sans font-medium">
                    No wedding functions scheduled for today ({{ now()->format('d M Y') }}). Hall floor is prepared for upcoming bookings.
                </p>
                <button type="button" onclick="Livewire.dispatch('openWalkInModal')" class="btn-gold !h-8 !px-4 rounded-lg text-xs font-sans uppercase tracking-wider font-bold">
                    + Book Walk-in Function for Today
                </button>
            </div>
        @endif
    </div>

    <!-- ===================================================================
         UPCOMING FUNCTIONS SCHEDULE (NEXT 6 EVENTS)
         =================================================================== -->
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/25 rounded-2xl p-6 shadow-sm space-y-4 transition-colors duration-200">
        <div class="flex items-center justify-between border-b border-[#E7E5E4] dark:border-white/10 pb-4">
            <div>
                <span class="text-[10px] font-sans uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold">Schedule Horizon</span>
                <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold mt-0.5">Upcoming Marquee Functions</h3>
            </div>
            <a href="{{ route('calendar') }}" class="text-xs font-sans text-[#171513] dark:text-[#C6A15B] hover:text-[#8F7239] dark:hover:text-[#C6A15B] font-bold uppercase tracking-wider">
                Full Calendar Grid &rarr;
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($upcomingBookingsList as $ub)
                <div class="bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 rounded-xl p-4 space-y-2 hover:border-[#C6A15B]/60 transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-sans text-[#171513] dark:text-[#C6A15B] font-bold">
                            {{ $ub->event_date->format('d M Y') }} • {{ $ub->slot_type }}
                        </span>
                        <span class="text-[10px] font-sans px-2 py-0.5 rounded border border-[#C6A15B]/50 bg-[#C6A15B]/15 dark:bg-[#171513]/85 text-[#8F7239] dark:text-[#C6A15B] font-bold">
                            {{ $ub->status }}
                        </span>
                    </div>
                    <div class="font-serif-luxury text-base text-[#171513] dark:text-white font-bold">
                        {{ $ub->event_type }} — {{ $ub->customer_name }}
                    </div>
                    <div class="flex items-center justify-between text-xs text-stone-600 dark:text-stone-400 font-sans pt-2 border-t border-stone-200 dark:border-white/10">
                        <span>{{ $ub->hall->name ?? 'Hall' }}</span>
                        <span>{{ $ub->guest_count }} Guests</span>
                    </div>
                    <div class="flex items-center justify-between text-xs font-sans pt-1">
                        <span class="text-stone-700 dark:text-stone-300 font-bold">Total: PKR {{ number_format($ub->grand_total, 0) }}</span>
                        <a href="{{ route('documents.agreement', $ub->id) }}" target="_blank" class="text-[#171513] dark:text-[#C6A15B] hover:text-[#C6A15B] underline font-bold text-[11px]">
                            View Contract
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-3 p-8 text-center text-stone-400 text-xs font-sans">
                    No upcoming functions scheduled in the near horizon.
                </div>
            @endforelse
        </div>
    </div>

    <!-- ===================================================================
         INQUIRY & LEAD TRACKING CRM (Interactive Kanban Pipeline)
         =================================================================== -->
    @if($canViewLeads)
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/25 rounded-2xl p-6 shadow-sm space-y-6 transition-colors duration-200">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-[#E7E5E4] dark:border-white/10 pb-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#C6A15B]"></span>
                    <span class="text-[10px] font-sans uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold">
                        Lead Management CRM
                    </span>
                </div>
                <h3 class="font-serif-luxury text-xl sm:text-2xl text-[#171513] dark:text-white font-bold mt-0.5">
                    Interactive Inquiries Pipeline (Kanban)
                </h3>
                <p class="text-xs text-stone-600 dark:text-stone-400 font-sans mt-0.5">
                    Move prospects between stages: New Inquiry &rarr; Site Visit &rarr; Quotation Sent &rarr; Confirmed Booking.
                </p>
            </div>
            @if($crmFeedback)
                <div class="px-3.5 py-1.5 rounded-lg bg-[#171513] border border-[#C6A15B] text-[#C6A15B] text-xs font-sans font-bold flex items-center gap-2">
                    <span>✓ {{ $crmFeedback }}</span>
                    <button wire:click="$set('crmFeedback', null)" class="text-white hover:text-[#C6A15B]">&times;</button>
                </div>
            @endif
        </div>

        <!-- 4 Column Kanban Board -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- COLUMN 1: NEW INQUIRY -->
            <div class="bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 rounded-xl p-4 space-y-3">
                <div class="flex items-center justify-between pb-3 border-b border-stone-200 dark:border-white/10">
                    <span class="text-xs font-sans uppercase font-bold text-stone-800 dark:text-stone-200">1. New Inquiry</span>
                    <span class="px-2 py-0.5 rounded bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 font-sans text-[10px] font-bold">
                        {{ $kanbanColumns['NEW_INQUIRY']->count() }}
                    </span>
                </div>
                <div class="space-y-3 max-h-[600px] overflow-y-auto pr-1">
                    @forelse($kanbanColumns['NEW_INQUIRY'] as $lead)
                        <div class="bg-white dark:bg-[#171513] border border-stone-200 dark:border-white/15 rounded-xl p-3.5 space-y-2 hover:border-[#C6A15B]/60 transition shadow-sm">
                            <div class="flex items-start justify-between gap-2">
                                <strong class="font-serif-luxury text-sm text-[#171513] dark:text-white">{{ $lead->customer_name }}</strong>
                                <span class="text-[9px] font-sans uppercase px-1.5 py-0.5 rounded bg-stone-100 dark:bg-white/10 text-stone-600 dark:text-stone-300">
                                    {{ $lead->event_type }}
                                </span>
                            </div>
                            <div class="text-[11px] font-sans text-stone-600 dark:text-stone-400 space-y-0.5">
                                <div>{{ $lead->event_date ? $lead->event_date->format('d M Y') : 'Date TBD' }} ({{ $lead->slot_type }})</div>
                                <div>{{ $lead->guest_count }} Guests • {{ $lead->hall->name ?? 'Hall' }}</div>
                                <div class="font-bold text-[#8F7239] dark:text-[#C6A15B]">PKR {{ number_format($lead->grand_total) }}</div>
                            </div>
                            <div class="pt-2 border-t border-stone-100 dark:border-white/10 flex items-center justify-between text-[11px] font-sans">
                                <button wire:click="openCustomerDetails('{{ $lead->id }}')" class="tbtn tbtn-outline">
                                    Details &rarr;
                                </button>
                                @if($canManageLeads)
<button wire:click="updateLeadStage('{{ $lead->id }}', 'SITE_VISIT')" class="tbtn tbtn-dark">
                                    Visit &rarr;
                                </button>
@endif
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-stone-400 text-xs font-sans">No new inquiries</div>
                    @endforelse
                </div>
            </div>

            <!-- COLUMN 2: SITE VISIT -->
            <div class="bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 rounded-xl p-4 space-y-3">
                <div class="flex items-center justify-between pb-3 border-b border-stone-200 dark:border-white/10">
                    <span class="text-xs font-sans uppercase font-bold text-stone-800 dark:text-stone-200">2. Site Visit</span>
                    <span class="px-2 py-0.5 rounded bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 font-sans text-[10px] font-bold">
                        {{ $kanbanColumns['SITE_VISIT']->count() }}
                    </span>
                </div>
                <div class="space-y-3 max-h-[600px] overflow-y-auto pr-1">
                    @forelse($kanbanColumns['SITE_VISIT'] as $lead)
                        <div class="bg-white dark:bg-[#171513] border border-amber-300/60 dark:border-amber-500/30 rounded-xl p-3.5 space-y-2 hover:border-[#C6A15B]/60 transition shadow-sm">
                            <div class="flex items-start justify-between gap-2">
                                <strong class="font-serif-luxury text-sm text-[#171513] dark:text-white">{{ $lead->customer_name }}</strong>
                                <span class="text-[9px] font-sans uppercase px-1.5 py-0.5 rounded bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300">
                                    {{ $lead->event_type }}
                                </span>
                            </div>
                            <div class="text-[11px] font-sans text-stone-600 dark:text-stone-400 space-y-0.5">
                                <div>{{ $lead->event_date ? $lead->event_date->format('d M Y') : 'Date TBD' }} ({{ $lead->slot_type }})</div>
                                <div>{{ $lead->guest_count }} Guests • {{ $lead->hall->name ?? 'Hall' }}</div>
                                <div class="font-bold text-[#8F7239] dark:text-[#C6A15B]">PKR {{ number_format($lead->grand_total) }}</div>
                            </div>
                            <div class="pt-2 border-t border-stone-100 dark:border-white/10 flex items-center justify-between text-[11px] font-sans">
                                <button wire:click="openCustomerDetails('{{ $lead->id }}')" class="tbtn tbtn-outline">
                                    Details &rarr;
                                </button>
                                <div class="flex items-center gap-1">
                                    @if($canManageLeads)
<button wire:click="updateLeadStage('{{ $lead->id }}', 'NEW_INQUIRY')" title="Move Back" class="tbtn tbtn-icon tbtn-outline">
                                        &larr;
                                    </button>
@endif
                                    @if($canManageLeads)
<button wire:click="updateLeadStage('{{ $lead->id }}', 'QUOTATION')" class="tbtn tbtn-dark">
                                        Quote &rarr;
                                    </button>
@endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-stone-400 text-xs font-sans">No visits scheduled</div>
                    @endforelse
                </div>
            </div>

            <!-- COLUMN 3: QUOTATION SENT -->
            <div class="bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 rounded-xl p-4 space-y-3">
                <div class="flex items-center justify-between pb-3 border-b border-stone-200 dark:border-white/10">
                    <span class="text-xs font-sans uppercase font-bold text-stone-800 dark:text-stone-200">3. Quotation Sent</span>
                    <span class="px-2 py-0.5 rounded bg-purple-100 dark:bg-purple-900/40 text-purple-800 dark:text-purple-300 font-sans text-[10px] font-bold">
                        {{ $kanbanColumns['QUOTATION']->count() }}
                    </span>
                </div>
                <div class="space-y-3 max-h-[600px] overflow-y-auto pr-1">
                    @forelse($kanbanColumns['QUOTATION'] as $lead)
                        <div class="bg-white dark:bg-[#171513] border border-purple-300/60 dark:border-purple-500/30 rounded-xl p-3.5 space-y-2 hover:border-[#C6A15B]/60 transition shadow-sm">
                            <div class="flex items-start justify-between gap-2">
                                <strong class="font-serif-luxury text-sm text-[#171513] dark:text-white">{{ $lead->customer_name }}</strong>
                                <span class="text-[9px] font-sans uppercase px-1.5 py-0.5 rounded bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300">
                                    {{ $lead->event_type }}
                                </span>
                            </div>
                            <div class="text-[11px] font-sans text-stone-600 dark:text-stone-400 space-y-0.5">
                                <div>{{ $lead->event_date ? $lead->event_date->format('d M Y') : 'Date TBD' }} ({{ $lead->slot_type }})</div>
                                <div>{{ $lead->guest_count }} Guests • {{ $lead->hall->name ?? 'Hall' }}</div>
                                <div class="font-bold text-[#8F7239] dark:text-[#C6A15B]">PKR {{ number_format($lead->grand_total) }}</div>
                            </div>
                            <div class="pt-2 border-t border-stone-100 dark:border-white/10 flex items-center justify-between text-[11px] font-sans">
                                <button wire:click="openCustomerDetails('{{ $lead->id }}')" class="tbtn tbtn-outline">
                                    Details &rarr;
                                </button>
                                <div class="flex items-center gap-1">
                                    @if($canManageLeads)
<button wire:click="updateLeadStage('{{ $lead->id }}', 'SITE_VISIT')" title="Move Back" class="tbtn tbtn-icon tbtn-outline">
                                        &larr;
                                    </button>
@endif
                                    @if($canManageLeads)
<button wire:click="updateLeadStage('{{ $lead->id }}', 'CONFIRMED')" class="tbtn tbtn-gold">
                                        Confirm ✓
                                    </button>
@endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-stone-400 text-xs font-sans">No open quotations</div>
                    @endforelse
                </div>
            </div>

            <!-- COLUMN 4: CONFIRMED -->
            <div class="bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 rounded-xl p-4 space-y-3">
                <div class="flex items-center justify-between pb-3 border-b border-stone-200 dark:border-white/10">
                    <span class="text-xs font-sans uppercase font-bold text-stone-800 dark:text-stone-200">4. Confirmed</span>
                    <span class="px-2 py-0.5 rounded bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300 font-sans text-[10px] font-bold">
                        {{ $kanbanColumns['CONFIRMED']->count() }}
                    </span>
                </div>
                <div class="space-y-3 max-h-[600px] overflow-y-auto pr-1">
                    @forelse($kanbanColumns['CONFIRMED'] as $lead)
                        <div class="bg-white dark:bg-[#171513] border border-emerald-300/60 dark:border-emerald-500/30 rounded-xl p-3.5 space-y-2 hover:border-[#C6A15B]/60 transition shadow-sm">
                            <div class="flex items-start justify-between gap-2">
                                <strong class="font-serif-luxury text-sm text-[#171513] dark:text-white">{{ $lead->customer_name }}</strong>
                                <span class="text-[9px] font-sans uppercase px-1.5 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300">
                                    {{ $lead->event_type }}
                                </span>
                            </div>
                            <div class="text-[11px] font-sans text-stone-600 dark:text-stone-400 space-y-0.5">
                                <div>{{ $lead->event_date ? $lead->event_date->format('d M Y') : 'Date TBD' }} ({{ $lead->slot_type }})</div>
                                <div>{{ $lead->guest_count }} Guests • {{ $lead->hall->name ?? 'Hall' }}</div>
                                <div class="font-bold text-[#8F7239] dark:text-[#C6A15B]">
                                    Adv: PKR {{ number_format($lead->advance_paid) }} / {{ number_format($lead->grand_total) }}
                                </div>
                            </div>
                            <div class="pt-2 border-t border-stone-100 dark:border-white/10 flex items-center justify-between text-[11px] font-sans">
                                <button wire:click="openCustomerDetails('{{ $lead->id }}')" class="tbtn tbtn-outline">
                                    Details &rarr;
                                </button>
                                <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-300 text-[10px] font-bold font-sans uppercase">
                                    Booked ✓
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-stone-400 text-xs font-sans">No confirmed leads</div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>

    <!-- ===================================================================
         LIVE GUEST CONCIERGE INQUIRIES & CHATBOT LEADS (Hall Owner Messenger)
         =================================================================== -->
    @endif
    @if($canViewMessages)
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-2xl p-6 shadow-sm space-y-4 transition-colors duration-200">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-[#E7E5E4] dark:border-white/10 pb-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-[10px] font-sans uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold">
                        Live Digital Concierge
                    </span>
                </div>
                <h3 class="font-serif-luxury text-xl sm:text-2xl text-[#171513] dark:text-white font-bold mt-0.5">
                    Live Guest Inquiries &amp; Chatbot Messages
                </h3>
                <p class="text-xs text-stone-600 dark:text-stone-400 font-sans mt-0.5">
                    Direct inquiries submitted by visitors via your venue showcase page chatbot. Reply instantly to convert prospects into confirmed bookings.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1.5 rounded-lg bg-[#C6A15B]/15 border border-[#C6A15B]/40 text-[#8F7239] dark:text-[#C6A15B] text-xs font-sans font-bold">
                    {{ $liveChatInquiries->count() }} Active Inquiries
                </span>
            </div>
        </div>

        @if($liveChatInquiries->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($liveChatInquiries as $inquiry)
                    <div class="bg-[#F8F5EF] dark:bg-[#1a1714] border border-[#E7E5E4] dark:border-white/10 rounded-xl p-4 space-y-3 hover:border-[#C6A15B]/60 transition shadow-sm flex flex-col justify-between">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-serif-luxury text-sm font-bold text-[#171513] dark:text-white truncate">
                                    {{ $inquiry->hall->name ?? 'Venue Inquiry' }}
                                </span>
                                <span class="px-2 py-0.5 rounded bg-emerald-500/15 border border-emerald-500/30 text-emerald-700 dark:text-emerald-400 font-sans text-[10px] font-bold shrink-0">
                                    {{ $inquiry->message_count }} msg{{ $inquiry->message_count > 1 ? 's' : '' }}
                                </span>
                            </div>

                            <div class="text-[11px] font-sans text-stone-600 dark:text-stone-400 space-y-1">
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-stone-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span class="truncate">Guest (IP: {{ $inquiry->ip_address }})</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-stone-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>{{ \Carbon\Carbon::parse($inquiry->last_message_at)->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-stone-200 dark:border-white/10 flex items-center justify-between">
                            <button wire:click="openChatModal('{{ $inquiry->hall_id }}', '{{ $inquiry->ip_address }}', '{{ $inquiry->session_id }}')"
                                    class="w-full btn-gold !h-8 !px-3 rounded-lg text-xs font-sans uppercase tracking-wider font-bold flex items-center justify-center gap-1.5 shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                <span>Open Chat &amp; Reply &rarr;</span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-8 rounded-xl text-center bg-[#F8F5EF] dark:bg-white/[0.02] border border-dashed border-stone-300 dark:border-white/10 space-y-2">
                <div class="w-10 h-10 mx-auto rounded-full bg-[#C6A15B]/15 text-[#8F7239] dark:text-[#C6A15B] flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                </div>
                <p class="text-xs text-stone-600 dark:text-stone-400 font-sans font-medium">
                    No active chatbot inquiries right now. Inquiries initiated by visitors on your hall showcase page will appear here instantly.
                </p>
            </div>
        @endif
    </div>
    @endif
         =================================================================== -->
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/25 rounded-2xl shadow-sm overflow-hidden transition-colors duration-200">
        <div class="p-6 border-b border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#171513] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="text-[10px] font-sans tracking-widest text-[#8F7239] dark:text-[#C6A15B] uppercase font-bold">Authoritative Ledger</span>
                <h2 class="font-serif-luxury text-xl sm:text-2xl text-[#171513] dark:text-white font-bold mt-0.5">
                    Programs Scheduled for {{ $periodLabel }} ({{ $allPeriodBookings->count() }})
                </h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('calendar') }}" class="px-3.5 py-2 rounded-lg border border-[#E7E5E4] dark:border-white/15 bg-white dark:bg-[#171513] text-xs font-sans text-stone-800 dark:text-stone-200 hover:border-[#C6A15B] uppercase tracking-wider font-bold transition">
                    Shift Matrix View
                </a>
            </div>
        </div>

        <div tabindex="0" role="region" aria-label="Scheduled Banquets Ledger" class="table-wrapper-enterprise">
            <table class="table-enterprise">
                <thead class="bg-[#F8F5EF] dark:bg-[#171513] border-b border-[#E7E5E4] dark:border-white/10 text-[10px] font-sans uppercase tracking-wider text-[#78716C] dark:text-[#C6A15B]">
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
                            <td class="p-4 font-sans font-bold text-[#171513] dark:text-white whitespace-nowrap">{{ $b->event_date->format('d M Y') }}</td>
                            <td class="p-4">
                                <strong class="text-[#171513] dark:text-white block">{{ $b->hall->name }}</strong>
                                <span class="text-[10px] font-sans text-[#C6A15B] font-bold uppercase">{{ $b->slot_type }} SHIFT</span>
                            </td>
                            <td class="p-4">
                                <span class="text-[#171513] dark:text-white font-bold block">{{ $b->event_type }}</span>
                                <span class="text-[11px] text-stone-600 dark:text-stone-400 font-sans">{{ $b->customer_name }}</span>
                            </td>
                            <td class="p-4 font-sans font-bold text-[#171513] dark:text-white">{{ $b->guest_count }}</td>
                            <td class="p-4 font-sans font-bold text-[#171513] dark:text-white">PKR {{ number_format($b->grand_total, 0) }}</td>
                            <td class="p-4 font-sans font-bold text-[#8F7239] dark:text-[#C6A15B]">PKR {{ number_format($b->advance_paid, 0) }}</td>
                            <td class="p-4 font-sans font-bold text-[#171513] dark:text-[#F8F5EF]">PKR {{ number_format($b->balance_due, 0) }}</td>
                            <td class="p-4">
                                <span class="text-[10px] font-sans uppercase px-2.5 py-1 rounded border border-[#C6A15B]/50 bg-[#C6A15B]/15 dark:bg-[#171513]/85 text-[#8F7239] dark:text-[#C6A15B] font-bold">
                                    {{ $b->status }}
                                </span>
                            </td>
                            <td class="p-4 text-right whitespace-nowrap font-sans text-xs">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    <a href="{{ route('documents.agreement', $b->id) }}" target="_blank" class="tbtn tbtn-outline" title="Official Event Contract">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span>Contract</span>
                                    </a>
                                    @if($b->payments->isNotEmpty())
                                        <a href="{{ route('documents.receipt', $b->payments->first()->id) }}" target="_blank" class="tbtn tbtn-gold" title="Payment Receipt">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                                            <span>Receipt</span>
                                        </a>
                                    @endif
                                    @if($kitchenEnabled && $b->kitchenKot)
                                        <a href="{{ route('documents.kot', $b->kitchenKot->id) }}" target="_blank" class="tbtn tbtn-dark" title="Kitchen Order Ticket">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                            <span>KOT</span>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-stone-400 font-sans">No programs recorded for this timeframe.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===================================================================
         HALL ANALYTICS: Views, Wishlists & Bookings Per Hall
         =================================================================== -->
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/25 rounded-2xl p-6 shadow-sm space-y-6 transition-colors duration-200">
        <div class="flex items-center justify-between border-b border-[#E7E5E4] dark:border-white/10 pb-4">
            <div>
                <span class="text-[10px] font-sans uppercase tracking-wider text-[#8F7239] dark:text-[#C6A15B] font-bold block">Hall Performance Insights</span>
                <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold mt-0.5">Views, Wishlists &amp; Bookings Per Hall</h3>
            </div>
        </div>

        <!-- Analytics KPI Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/25 rounded-xl p-5 shadow-sm space-y-1 relative overflow-hidden group hover:border-[#C6A15B]/60 transition">
                <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 block font-sans">Total Hall Views</span>
                <strong class="font-cinzel text-2xl text-[#171513] dark:text-white">{{ $totalHallViews }}</strong>
                <span class="text-[11px] text-stone-400 block font-sans">Guest Page Views</span>
            </div>
            <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/25 rounded-xl p-5 shadow-sm space-y-1 relative overflow-hidden group hover:border-[#C6A15B]/60 transition">
                <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 block font-sans">Wishlists Saved</span>
                <strong class="font-cinzel text-2xl text-[#171513] dark:text-white">{{ $totalHallWishlists }}</strong>
                <span class="text-[11px] text-stone-400 block font-sans">Guest Shortlists</span>
            </div>
            <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/25 rounded-xl p-5 shadow-sm space-y-1 relative overflow-hidden group hover:border-[#C6A15B]/60 transition">
                <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 block font-sans">Total Bookings</span>
                <strong class="font-cinzel text-2xl text-[#171513] dark:text-white">{{ $totalHallBookings }}</strong>
                <span class="text-[11px] text-stone-400 block font-sans">Reserved Functions</span>
            </div>
            <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/45 rounded-xl p-5 shadow-sm space-y-1 relative overflow-hidden group">
                <span class="text-[10px] uppercase font-bold tracking-wider text-[#8F7239] dark:text-[#C6A15B] block font-sans">Conversion Rate</span>
                <strong class="font-cinzel text-xl text-[#171513] dark:text-[#C6A15B] block font-bold">
                    {{ $totalHallViews > 0 ? round(($totalHallBookings / $totalHallViews) * 100, 1) : 0 }}%</strong>
                <span class="text-[10px] text-stone-500 dark:text-stone-400 block font-sans">View to Booking</span>
            </div>
        </div>

        <!-- Traffic Pulse: 14-Day Hall View Graph -->
        <div class="bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/25 rounded-xl p-5 shadow-sm space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <span class="text-[10px] uppercase font-bold tracking-wider text-[#8F7239] dark:text-[#C6A15B] block font-sans">Traffic Pulse</span>
                    <h4 class="font-serif-luxury text-lg text-[#171513] dark:text-white font-bold">Guest Visits — Last 14 Days</h4>
                </div>
                @php
                    $pulseTotal = collect($trafficPulse)->sum('views');
                    $pulsePeak = max(1, (int) collect($trafficPulse)->max('views'));
                @endphp
                <div class="text-right">
                    <span class="font-cinzel text-xl text-[#171513] dark:text-white font-bold">{{ number_format($pulseTotal) }}</span>
                    <span class="block text-[10px] uppercase tracking-wider text-stone-500 dark:text-stone-400 font-sans font-bold">Total Visits</span>
                </div>
            </div>
            <div class="flex items-end gap-1.5 sm:gap-2 h-40 pt-2">
                @foreach($trafficPulse as $pulse)
                    @php $barHeight = max(4, (int) round(($pulse['views'] / $pulsePeak) * 100)); @endphp
                    <div class="flex-1 h-full flex flex-col items-center justify-end gap-1.5 group" title="{{ $pulse['date'] }} — {{ $pulse['views'] }} visits">
                        <span class="text-[9px] font-sans font-bold text-stone-500 dark:text-stone-400 opacity-0 group-hover:opacity-100 transition">{{ $pulse['views'] }}</span>
                        <div class="w-full rounded-t bg-gradient-to-t from-[#8F7239] to-[#C6A15B] hover:from-[#C6A15B] hover:to-[#F2DE9A] transition-all duration-200" style="height: {{ $barHeight }}%;"></div>
                        <span class="text-[8px] sm:text-[9px] font-sans uppercase tracking-tight text-stone-400 whitespace-nowrap">{{ $pulse['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Per-Hall Analytics Table -->
        <div tabindex="0" role="region" aria-label="Hall Analytics Directory" class="table-wrapper-enterprise">
            <table class="table-enterprise">
                <thead class="bg-[#F8F5EF] dark:bg-[#171513] border-b border-[#E7E5E4] dark:border-white/10 text-[10px] font-sans uppercase tracking-wider text-[#78716C] dark:text-[#C6A15B]">
                    <tr>
                        <th class="p-4 font-bold">Hall Name</th>
                        <th class="p-4 font-bold text-right">Views</th>
                        <th class="p-4 font-bold text-right">Wishlists</th>
                        <th class="p-4 font-bold text-right">Total Bookings</th>
                        <th class="p-4 font-bold text-right">Confirmed</th>
                        <th class="p-4 font-bold text-right">Revenue (PKR)</th>
                        <th class="p-4 font-bold text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E7E5E4] dark:divide-white/10 text-stone-800 dark:text-stone-200">
                    @forelse($hallAnalytics as $ha)
                        <tr class="hover:bg-[#F8F5EF] dark:hover:bg-[#171513]/70 transition">
                            <td class="p-4">
                                <div class="font-bold text-[#171513] dark:text-white">{{ $ha['name'] }}</div>
                                <span class="text-[10px] text-stone-500 font-sans">{{ $ha['slug'] }}</span>
                            </td>
                            <td class="p-4 font-sans font-bold text-[#171513] dark:text-white text-right">{{ $ha['views'] }}</td>
                            <td class="p-4 font-sans font-bold text-[#8F7239] dark:text-[#C6A15B] text-right">{{ $ha['wishlists'] }}</td>
                            <td class="p-4 font-sans font-bold text-stone-600 dark:text-stone-300 text-right">{{ $ha['total_bookings'] }}</td>
                            <td class="p-4 font-sans font-bold text-stone-600 dark:text-stone-300 text-right">{{ $ha['confirmed_bookings'] }}</td>
                            <td class="p-4 font-sans font-bold text-[#171513] dark:text-white text-right">PKR {{ number_format($ha['total_revenue'], 0) }}</td>
                            <td class="p-4 text-right">
                                <a href="{{ route('hall.showcase', $ha['slug']) }}" target="_blank" class="text-xs font-sans text-[#C6A15B] dark:text-[#8F7239] hover:underline font-bold">
                                    View Live Page ↗
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-stone-400 font-sans">No halls configured yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===================================================================
         CUSTOMER DETAILS & INTERACTION HISTORY MODAL
         =================================================================== -->
    @if($showCustomerModal && $selectedLead)
        <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
            <div class="bg-[#171513] border border-[#C6A15B]/60 rounded-2xl max-w-2xl w-full p-6 sm:p-8 space-y-6 shadow-2xl text-white my-8 relative">
                <!-- Modal Header -->
                <div class="border-b border-white/10 pb-4 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-[#C6A15B]"></span>
                            <span class="text-[10px] font-sans uppercase tracking-widest text-[#C6A15B] font-bold">
                                Customer Details &amp; Lead Record
                            </span>
                        </div>
                        <h3 class="font-serif-luxury text-2xl text-white font-bold mt-0.5">
                            {{ $selectedLead->customer_name }}
                        </h3>
                        <span class="text-xs text-stone-400 font-sans">
                            Ref: {{ $selectedLead->booking_reference ?? $selectedLead->id }} • {{ $selectedLead->event_type }}
                        </span>
                    </div>
                        @endif
                    <button wire:click="closeCustomerDetails" class="text-stone-400 hover:text-white text-2xl font-sans leading-none">&times;</button>
                </div>

                <!-- Quick Action Buttons: WhatsApp & Call -->
                <div class="flex flex-wrap items-center gap-3">
                    @php
                        $cleanPhone = preg_replace('/[^0-9]/', '', $selectedLead->customer_phone ?? '');
                        if (str_starts_with($cleanPhone, '03')) {
                            $cleanPhone = '92' . substr($cleanPhone, 1);
                        }
                    @endphp
                    @if($cleanPhone)
                        <a href="https://wa.me/{{ $cleanPhone }}?text=Assalamu%20Alaikum%20{{ urlencode($selectedLead->customer_name) }},%20this%20is%20regarding%20your%20event%20inquiry%20for%20{{ urlencode($selectedLead->hall->name ?? 'our venue') }}."
                           target="_blank"
                           class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-sans font-bold uppercase tracking-wider flex items-center gap-2 shadow-sm transition">
                            <span>WhatsApp Customer</span>
                        </a>
                    @endif
                    @if($selectedLead->customer_phone)
                        <a href="tel:{{ $selectedLead->customer_phone }}"
                           class="px-4 py-2 rounded-lg bg-stone-800 hover:bg-stone-700 border border-white/20 text-white text-xs font-sans font-bold uppercase tracking-wider flex items-center gap-2 transition">
                            <span>Call {{ $selectedLead->customer_phone }}</span>
                        </a>
                    @endif
                </div>

                <!-- Customer Details Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 rounded-xl bg-white/5 p-4 border border-white/10 font-sans text-xs">
                    <div>
                        <span class="text-stone-400 text-[10px] uppercase block">Email Address</span>
                        <span class="text-white select-all">{{ $selectedLead->customer_email ?? 'Not provided' }}</span>
                    </div>
                    <div>
                        <span class="text-stone-400 text-[10px] uppercase block">CNIC / ID</span>
                        <span class="text-white">{{ $selectedLead->customer_cnic ?? 'Not provided' }}</span>
                    </div>
                    <div>
                        <span class="text-stone-400 text-[10px] uppercase block">Assigned Hall</span>
                        <strong class="text-[#C6A15B]">{{ $selectedLead->hall->name ?? 'Unassigned' }}</strong>
                    </div>
                    <div>
                        <span class="text-stone-400 text-[10px] uppercase block">Event Date</span>
                        <span class="text-white">{{ $selectedLead->event_date ? $selectedLead->event_date->format('d M Y') : 'Date TBD' }}</span>
                    </div>
                    <div>
                        <span class="text-stone-400 text-[10px] uppercase block">Shift Slot</span>
                        <span class="text-white">{{ $selectedLead->slot_type }}</span>
                    </div>
                    <div>
                        <span class="text-stone-400 text-[10px] uppercase block">Guests</span>
                        <span class="text-white font-bold">{{ $selectedLead->guest_count }} Guests</span>
                    </div>
                    <div>
                        <span class="text-stone-400 text-[10px] uppercase block">Grand Total</span>
                        <strong class="text-white text-sm">PKR {{ number_format($selectedLead->grand_total) }}</strong>
                    </div>
                    <div>
                        <span class="text-stone-400 text-[10px] uppercase block">Advance Paid</span>
                        <strong class="text-emerald-400">PKR {{ number_format($selectedLead->advance_paid) }}</strong>
                    </div>
                    <div>
                        <span class="text-stone-400 text-[10px] uppercase block">Balance Due</span>
                        <strong class="text-amber-400">PKR {{ number_format($selectedLead->balance_due) }}</strong>
                    </div>
                </div>

                <!-- CRM Pipeline Stage Control -->
                <div class="space-y-1.5 font-sans">
                    <label class="block text-[11px] uppercase tracking-wider text-[#C6A15B] font-bold">
                        Pipeline CRM Stage:
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        @foreach(['NEW_INQUIRY' => '1. New Inquiry', 'SITE_VISIT' => '2. Site Visit', 'QUOTATION' => '3. Quotation', 'CONFIRMED' => '4. Confirmed'] as $stageKey => $stageLabel)
                            @if($canManageLeads)
<button wire:click="updateLeadStage('{{ $selectedLead->id }}', '{{ $stageKey }}')"
                                    class="px-3 py-2 rounded-lg text-xs font-sans font-bold uppercase transition border {{ ($selectedLead->crm_stage === $stageKey || ($stageKey === 'NEW_INQUIRY' && empty($selectedLead->crm_stage) && $selectedLead->status !== 'CONFIRMED')) ? 'bg-[#C6A15B] text-[#171513] border-[#C6A15B]' : 'bg-white/5 text-stone-300 border-white/10 hover:border-[#C6A15B]' }}">
                                {{ $stageLabel }}
                            </button>
@endif
                        @endforeach
                    </div>
                </div>

                <!-- Interaction History & Notes Log -->
                <div class="space-y-3 font-sans">
                    <label class="block text-[11px] uppercase tracking-wider text-[#C6A15B] font-bold">
                        Customer Interaction History &amp; Staff Notes:
                    </label>
                    <div class="p-3 rounded-lg bg-black/60 border border-white/10 max-h-40 overflow-y-auto text-xs text-stone-300 space-y-1 whitespace-pre-line">
                        @if(!empty($selectedLead->notes))
                            {{ $selectedLead->notes }}
                        @else
                            <span class="text-stone-500 italic">No previous interaction notes recorded for this customer.</span>
                        @endif
                    </div>

                    <!-- Add Note Input -->
                    <div class="space-y-2 pt-2">
                        @if($canManageLeads)
                        <textarea wire:model="leadNoteText" rows="2" placeholder="Record new customer interaction note (e.g. called client, discussed menu customization, agreed on 500 guests)..."
                                  class="w-full rounded-lg bg-[#171513] border border-white/20 text-white text-xs p-3 font-sans outline-none focus:border-[#C6A15B]"></textarea>
                        <div class="flex justify-end">
                            <button wire:click="addLeadNote" class="btn-gold !h-8 !px-4 rounded-lg text-xs font-bold uppercase font-sans shadow-sm">
                                + Save Interaction Note
                            </button>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Footer -->
                <div class="pt-4 border-t border-white/10 flex justify-between items-center text-xs font-sans">
                    <span class="text-stone-500">Created: {{ $selectedLead->created_at->format('d M Y, H:i') }}</span>
                    <button wire:click="closeCustomerDetails" class="px-4 py-2 rounded-lg border border-white/20 hover:border-white text-white text-xs uppercase font-sans font-bold">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         MODAL: QUICK DAILY KHARCHA & GENERATOR FUEL ENTRY
         =================================================================== -->
    @if($showDailyExpenseModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm overflow-y-auto">
            <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/60 rounded-2xl max-w-xl w-full p-6 sm:p-8 space-y-6 shadow-2xl relative my-8">
                <button wire:click="closeDailyExpenseModal" class="absolute top-4 right-4 text-stone-400 hover:text-stone-700 dark:hover:text-white text-xl font-sans">&times;</button>

                <div class="space-y-1">
                    <span class="text-[10px] font-sans uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold block">
                        Operational Kharcha Desk
                    </span>
                    <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-normal">
                        Record Daily Expense / Generator Fuel
                    </h3>
                    <p class="text-xs text-stone-500 dark:text-stone-400 font-sans">
                        Logged amounts automatically sync with P&amp;L reports, cash drawers, and generator logs.
                    </p>
                </div>

                @if($expenseError)
                    <div class="p-3 rounded-lg bg-red-950/40 border border-red-700/60 text-red-300 text-xs font-sans font-medium">
                        {{ $expenseError }}
                    </div>
                @endif

                <form wire:submit="saveDailyExpense" class="space-y-4">
                    <!-- Category Selector -->
                    <div class="space-y-1.5">
                        <label class="block text-[11px] font-sans font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Expense Category *
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs font-sans">
                            @foreach([
                                'GENERATOR_DIESEL' => 'Gen Fuel',
                                'STAFF_DAILY_WAGES' => 'Staff Wages',
                                'CLEANING_HYGIENE' => 'Cleaning',
                                'WAPDA_ELECTRICITY' => 'WAPDA / Power',
                                'CHILLER_AC' => 'AC / Chiller',
                                'KITCHEN_GAS' => 'Kitchen Gas',
                                'MISC_OPERATIONAL' => 'Misc Other'
                            ] as $catKey => $catLabel)
                                <button type="button" 
                                        wire:click="$set('expenseCategory', '{{ $catKey }}'); syncCategoryAmounts();"
                                        class="px-2.5 py-2 rounded-lg text-xs font-sans font-bold uppercase border transition text-left truncate {{ $expenseCategory === $catKey ? 'bg-[#C6A15B] text-[#171513] border-[#C6A15B]' : 'bg-stone-50 dark:bg-white/5 text-stone-700 dark:text-stone-300 border-stone-200 dark:border-white/10 hover:border-[#C6A15B]' }}">
                                    {{ $catLabel }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Category Specific: Generator Fuel Inputs -->
                    @if($expenseCategory === 'GENERATOR_DIESEL')
                        <div class="p-4 rounded-xl bg-[#C6A15B]/10 border border-[#C6A15B]/40 space-y-3 font-sans">
                            <span class="text-[10px] uppercase font-bold text-[#8F7239] dark:text-[#C6A15B] block">
                                Standby Generator Consumption Calculator:
                            </span>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[10px] text-stone-600 dark:text-stone-400 uppercase">Diesel Liters</label>
                                    <input type="number" step="0.5" min="1" wire:model.live="fuelDieselLiters" 
                                           class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-xs w-full">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-stone-600 dark:text-stone-400 uppercase">Rate/Liter (PKR)</label>
                                    <input type="number" step="1" min="1" wire:model.live="fuelRatePerLiter" 
                                           class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-xs w-full">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-stone-600 dark:text-stone-400 uppercase">Run Hours</label>
                                    <input type="number" step="0.5" min="0" wire:model="fuelRunHours" 
                                           class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-xs w-full">
                                </div>
                            </div>
                            <div class="text-[11px] text-stone-600 dark:text-stone-300 font-sans">
                                Calculated Fuel Cost: <strong class="text-[#8F7239] dark:text-[#C6A15B] font-sans">Rs {{ number_format($fuelDieselLiters * $fuelRatePerLiter) }}</strong> ({{ $fuelDieselLiters }}L × Rs {{ $fuelRatePerLiter }})
                            </div>
                        </div>
                    @endif

                    <!-- Category Specific: WAPDA Inputs -->
                    @if($expenseCategory === 'WAPDA_ELECTRICITY')
                        <div class="p-4 rounded-xl bg-[#C6A15B]/10 border border-[#C6A15B]/40 space-y-3 font-sans">
                            <span class="text-[10px] uppercase font-bold text-[#8F7239] dark:text-[#C6A15B] block">
                                Commercial Electricity Calculator:
                            </span>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] text-stone-600 dark:text-stone-400 uppercase">Units Consumed</label>
                                    <input type="number" step="1" min="1" wire:model.live="electricityUnits" 
                                           class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-xs w-full">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-stone-600 dark:text-stone-400 uppercase">Unit Tariff (PKR)</label>
                                    <input type="number" step="1" min="1" wire:model.live="electricityUnitRate" 
                                           class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-xs w-full">
                                </div>
                            </div>
                            <div class="text-[11px] text-stone-600 dark:text-stone-300 font-sans">
                                Calculated Power Cost: <strong class="text-[#8F7239] dark:text-[#C6A15B] font-sans">Rs {{ number_format($electricityUnits * $electricityUnitRate) }}</strong>
                            </div>
                        </div>
                    @endif

                    <!-- General Inputs: Title & Amount -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-sans font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1">
                                Expense Description / Item Title *
                            </label>
                            <input type="text" wire:model="expenseTitle" required placeholder="e.g. Generator Diesel Refill, 10 Waiters Barat Shift Dihaari..." 
                                   class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-xs w-full">
                            @error('expenseTitle') <span class="text-rose-500 text-xs font-sans">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-sans font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1">
                                Total Amount (PKR) *
                            </label>
                            <input type="number" step="1" min="1" wire:model="expenseAmount" required 
                                   class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-xs font-bold w-full">
                            @error('expenseAmount') <span class="text-rose-500 text-xs font-sans">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-sans font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1">
                                Expense Date *
                            </label>
                            <input type="date" wire:model="expenseDate" required 
                                   class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-xs w-full">
                            @error('expenseDate') <span class="text-rose-500 text-xs font-sans">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-sans font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1">
                                Hall / Space
                            </label>
                            <select wire:model="expenseHallId" class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-xs w-full">
                                <option value="">General Hall Expense</option>
                                @foreach($halls as $hall)
                                    <option value="{{ $hall->id }}">{{ $hall->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-sans font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1">
                                Payment Mode *
                            </label>
                            <select wire:model="expensePaymentMethod" class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-xs w-full">
                                <option value="CASH">Cash from Drawer</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-sans font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1">
                                Payee / Supplier Name
                            </label>
                            <input type="text" wire:model="expensePayee" placeholder="e.g. PSO Petrol Pump, Ustad Aslam Bawarchi, Staff..." 
                                   class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-xs w-full">
                        </div>

                        <div>
                            <label class="block text-[11px] font-sans font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1">
                                Bill / Receipt #
                            </label>
                            <input type="text" wire:model="expenseReceiptNumber" placeholder="e.g. PSO-8891, CASH-04" 
                                   class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-xs w-full">
                        </div>
                    </div>

                    <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10 flex justify-end gap-3">
                        <button type="button" wire:click="closeDailyExpenseModal" 
                                class="px-4 py-2 rounded-lg border border-stone-300 dark:border-white/20 text-xs font-sans uppercase font-bold text-stone-600 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-white/5">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="btn-gold !h-10 !px-6 rounded-lg text-xs font-bold uppercase font-sans shadow-sm transition">
                            Save Daily Kharcha Record &rarr;
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         MODAL: CONCIERGE CHAT INQUIRY & REAL-TIME REPLY
         =================================================================== -->
    @if($canViewMessages && $showChatModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm overflow-y-auto">
            <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/60 rounded-2xl max-w-2xl w-full p-6 sm:p-7 space-y-4 shadow-2xl relative my-8">
                <button wire:click="closeChatModal" class="absolute top-4 right-4 text-stone-400 hover:text-stone-700 dark:hover:text-white text-xl font-sans">&times;</button>

                <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span class="text-[10px] font-sans uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold">
                            Live Guest Inquiry Transcript
                        </span>
                    </div>
                    <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold mt-0.5">
                        Guest Communication Desk
                    </h3>
                    <p class="text-[11px] font-sans text-stone-500 dark:text-stone-400">
                        IP: <code class="text-[#8F7239] dark:text-[#C6A15B]">{{ $selectedChatIp }}</code>
                    </p>
                </div>

                <!-- Chat transcript messages -->
                <div class="space-y-3 max-h-96 overflow-y-auto p-3 bg-stone-50 dark:bg-[#11100e] rounded-xl border border-stone-200 dark:border-white/10">
                    @forelse($chatMessages as $msg)
                        @php
                            $msgSender = is_array($msg) ? ($msg['sender_type'] ?? '') : $msg->sender_type;
                            $msgType = is_array($msg) ? ($msg['message_type'] ?? 'text') : $msg->message_type;
                            $msgText = is_array($msg) ? ($msg['message'] ?? '') : $msg->message;
                            $msgVoice = is_array($msg) ? ($msg['voice_file'] ?? null) : $msg->voice_file;
                            $msgTime = is_array($msg) 
                                ? (isset($msg['created_at']) ? \Carbon\Carbon::parse($msg['created_at'])->format('h:i A') : '') 
                                : ($msg->created_at ? $msg->created_at->format('h:i A') : '');
                        @endphp
                        <div class="flex flex-col {{ $msgSender === 'hall_owner' ? 'items-end' : 'items-start' }}">
                            <div class="flex items-center gap-1.5 text-[10px] text-stone-400 font-sans mb-0.5">
                                @if($msgSender === 'hall_owner')
                                    <span class="font-bold text-[#8F7239] dark:text-[#C6A15B]">Hall Admin (You)</span>
                                @elseif($msgSender === 'bot' || $msgSender === 'ai_concierge')
                                    <span class="font-bold text-amber-600 dark:text-amber-400">AI Concierge</span>
                                @else
                                    <span class="font-bold text-stone-600 dark:text-stone-300">Guest Customer</span>
                                @endif
                                @if($msgTime)
                                    <span>• {{ $msgTime }}</span>
                                @endif
                            </div>

                            <div class="max-w-[85%] rounded-2xl p-3 text-xs font-sans {{ $msgSender === 'hall_owner' ? 'bg-[#C6A15B] text-[#171513] font-medium' : (($msgSender === 'bot' || $msgSender === 'ai_concierge') ? 'bg-amber-100/70 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 text-stone-800 dark:text-stone-200' : 'bg-white dark:bg-[#1f1c18] border border-stone-200 dark:border-white/10 text-[#171513] dark:text-stone-200 shadow-sm') }}">
                                @if($msgType === 'voice' && $msgVoice)
                                    <div class="space-y-1">
                                        <audio controls class="w-full max-w-xs h-8">
                                            <source src="{{ asset('storage/' . $msgVoice) }}" type="audio/webm">
                                            Your browser does not support the audio element.
                                        </audio>
                                        @if($msgText)
                                            <p class="text-[11px] italic mt-1">{{ $msgText }}</p>
                                        @endif
                                    </div>
                                @else
                                    <p class="whitespace-pre-wrap leading-relaxed">{{ $msgText }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-stone-400 text-xs font-sans">
                            No messages in this inquiry thread yet.
                        </div>
                    @endforelse
                </div>

                <!-- Reply Composer -->
                <form wire:submit.prevent="sendChatReply" class="space-y-3 pt-2">
                    <div class="flex items-center gap-2">
                        <textarea wire:model="chatReplyText"
                                  rows="2"
                                  placeholder="Type your official reply to this customer..."
                                  class="w-full text-xs font-sans rounded-xl border border-stone-300 dark:border-white/15 bg-white dark:bg-[#171513] text-[#171513] dark:text-white p-3 focus:outline-none focus:border-[#C6A15B]"></textarea>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] text-stone-400 font-sans">Reply is instantly delivered to the guest's venue chat widget.</span>
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="closeChatModal" class="px-3.5 py-1.5 rounded-lg border border-stone-300 dark:border-white/20 text-xs font-sans font-bold text-stone-600 dark:text-stone-300">
                                Close
                            </button>
                            <button type="submit" class="btn-gold !h-8 !px-4 rounded-lg text-xs font-sans uppercase tracking-wider font-bold flex items-center gap-1.5 shadow-sm">
                                <span>Send Reply &rarr;</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>

