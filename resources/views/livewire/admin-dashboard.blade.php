<div class="max-w-7xl mx-auto space-y-8">

    <!-- ===================================================================
         HEADER BANNER (Unified Luxury Obsidian)
         =================================================================== -->
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-2xl p-6 sm:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6 shadow-sm transition-colors duration-200">
        <div class="space-y-1.5">
            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full border border-[#C6A15B]/40 bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] text-[10px] font-sans uppercase tracking-widest font-bold">
                Platform Owner Suite
            </div>
            <h1 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                Super Admin Console
            </h1>
            <p class="text-xs text-stone-600 dark:text-stone-300 font-sans max-w-2xl leading-relaxed">
                Nationwide vendor onboarding, hall catalog quality, public marketplace controls, concierge inquiries, and security audit log.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button wire:click="openProvisionModal" class="btn-gold !h-10 !px-5 text-xs font-bold font-sans tracking-wide rounded-lg shadow-sm transition-all duration-200">
                <span>+ Register &amp; Provision Hall</span>
            </button>
            @if($billingEnabled)
            <button wire:click="openCreatePlanModal" class="px-4 py-2 rounded-lg bg-stone-100 dark:bg-[#1a1714] border border-[#E7E5E4] dark:border-white/20 hover:border-[#C6A15B] text-[#171513] dark:text-white text-xs font-sans transition font-semibold">
                + New SaaS Plan
            </button>
            @endif
        </div>
    </div>

    <!-- Feedback Message Alerts -->
    @if($feedbackMessage)
        <div class="p-4 rounded-xl bg-[#171513]/90 border border-[#C6A15B]/60 text-[#C6A15B] text-xs font-semibold shadow-md flex justify-between items-center">
            <span>✓ {{ $feedbackMessage }}</span>
            <button wire:click="$set('feedbackMessage', null)" class="text-[#C6A15B] font-bold text-sm">&times;</button>
        </div>
    @endif

    @if($errorMessage)
        <div class="p-4 rounded-xl bg-[#171513]/90 border border-[#C6A15B]/60 text-[#F8F5EF] text-xs font-semibold shadow-md flex justify-between items-center">
            <span>{{ $errorMessage }}</span>
            <button wire:click="$set('errorMessage', null)" class="text-[#F8F5EF] font-bold text-sm">&times;</button>
        </div>
    @endif

    <!-- Handover Credentials Card for Rent Admin -->
    @if($provisionedCredentials)
        <div class="bg-[#171513] border border-[#C6A15B]/60 rounded-2xl p-6 text-white space-y-4 shadow-xl relative">
            <button wire:click="dismissCredentials" class="absolute top-4 right-4 text-white/60 hover:text-white text-lg font-sans">&times;</button>
            
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 bg-[#C6A15B] rounded-full"></span>
                <span class="text-xs font-sans font-bold uppercase tracking-widest text-[#C6A15B]">Hall Account Provisioned — Handover Credentials</span>
            </div>
            
            <p class="text-xs text-stone-300 font-sans">
                Share these login credentials with the hall owner. They will use them to sign in to their private Rent Admin portal and manage their hall's photos, videos, and services catalog.
            </p>

            <div class="mb-4 p-4 rounded-xl bg-[#C6A15B]/10 border border-[#C6A15B]/60 text-[#C6A15B] text-xs font-semibold shadow-md">
                <span class="block mb-1">✓ One-time password — copy it now. It is not shown again after you dismiss this card.</span>
                <code class="select-all font-mono text-sm text-white break-all">{{ $provisionedCredentials['password'] ?? '' }}</code>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 bg-[#1E1A16] rounded-xl p-4 border border-white/10 font-sans text-xs">
                <div>
                    <span class="text-stone-400 text-[10px] uppercase block">Business / Hall</span>
                    <strong class="text-white text-sm">{{ $provisionedCredentials['business_name'] }}</strong>
                    <span class="text-stone-400 text-[11px] block mt-0.5">{{ $provisionedCredentials['owner_name'] }} • {{ $provisionedCredentials['city'] }}</span>
                </div>
                <div>
                    <span class="text-stone-400 text-[10px] uppercase block">Login Email</span>
                    <strong class="text-[#C6A15B] select-all">{{ $provisionedCredentials['email'] }}</strong>
                </div>
                <div>
                    <span class="text-stone-400 text-[10px] uppercase block">Direct Login URL</span>
                    <span class="text-stone-300 text-[11px] truncate block select-all">{{ $provisionedCredentials['login_url'] }}</span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 pt-2">
                    <button type="button" 
                            onclick="const text = [
                                'Assalamu Alaikum ' + @js($provisionedCredentials['owner_name']) + '!',
                                'Your Riwaj Grand Luxury ERP portal for *' + @js($provisionedCredentials['business_name']) + '* is ready.',
                                '',
                                'Login URL: ' + @js($provisionedCredentials['login_url']),
                                'Email: ' + @js($provisionedCredentials['email']),
                                '',
                                'Live Hall Website: ' + @js($provisionedCredentials['hall_url']),
                                '',
                                'Please sign in to customize your hall photos, services catalog, and shifts.'
                            ].join('\\n'); navigator.clipboard.writeText(text); window.luxuryToast('WhatsApp credentials message copied to clipboard!', 'gold');"
                            class="btn-gold !h-9 !px-4 text-xs font-bold font-sans tracking-wide rounded-lg shadow-sm transition-all duration-200">
                        <span>Copy WhatsApp Handover Message</span>
                    </button>

                    <a href="{{ $provisionedCredentials['hall_url'] }}" target="_blank" class="btn-outline-gold !h-9 !px-4 text-xs font-semibold font-sans rounded-lg shadow-sm transition-all duration-200">
                        View Live Hall Website &rarr;
                    </a>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         EXECUTIVE PLATFORM ANALYTICS COCKPIT ($90,000 Cockpit Architecture)
         =================================================================== -->
    <!-- ROW 1: TOP 4 LUXURY EXECUTIVE METRIC PILLARS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- 1. Platform GMV Hero Card (Obsidian Dark with Gold Glow) -->
        <div class="bg-[#171513] text-white border border-[#C6A15B]/50 rounded-2xl p-6 shadow-xl relative overflow-hidden group">
            <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-[#C6A15B]/15 rounded-full blur-2xl pointer-events-none"></div>
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-sans font-bold uppercase tracking-widest text-[#C6A15B]">
                    Platform Turnover
                </span>
                <span class="px-2 py-0.5 rounded-full bg-[#C6A15B]/20 text-[#F2DE9A] border border-[#C6A15B]/40 text-[9px] font-bold font-sans uppercase">
                    ★ Gross GMV
                </span>
            </div>
            <div class="mt-3">
                <div class="font-cinzel text-2xl sm:text-3xl text-white font-bold tracking-tight">
                    PKR {{ number_format($totalGmv) }}
                </div>
                <p class="text-xs text-stone-300 font-sans mt-1">
                    Cumulative bookings volume across partner venues.
                </p>
            </div>
            <div class="mt-4 pt-3 border-t border-white/10 flex items-center justify-between text-xs text-stone-400 font-sans">
                <span>Total Bookings:</span>
                <strong class="text-[#F2DE9A] font-bold">{{ $totalBookings }} Functions</strong>
            </div>
        </div>

        <!-- 2. Marketplace Footfall & Real-Time Traffic -->
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-2xl p-6 shadow-sm hover:border-[#C6A15B]/60 transition duration-200 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-sans font-bold uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B]">
                        Platform Footfall
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-[#C6A15B]/15 text-[#8F7239] dark:text-[#F2DE9A] border border-[#C6A15B]/40 text-[9px] font-bold font-sans uppercase">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#C6A15B] animate-ping"></span>
                        <span>{{ $activeSessionsCount }} Live</span>
                    </span>
                </div>
                <div class="mt-3">
                    <div class="font-cinzel text-2xl sm:text-3xl text-[#171513] dark:text-white font-bold tracking-tight">
                        {{ number_format($totalPageVisits) }}
                    </div>
                    <p class="text-xs text-stone-500 dark:text-stone-400 font-sans mt-1">
                        {{ $todayVisits }} visitor impressions recorded today.
                    </p>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-stone-100 dark:border-white/10 flex items-center justify-between text-xs text-stone-500 dark:text-stone-400 font-sans">
                <span>Unique Visitors:</span>
                <strong class="text-[#8F7239] dark:text-[#C6A15B] font-bold">{{ number_format($uniqueVisitorsCount) }}</strong>
            </div>
        </div>

        <!-- 3. Nationwide Venue Network -->
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-2xl p-6 shadow-sm hover:border-[#C6A15B]/60 transition duration-200 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-sans font-bold uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B]">
                        Partner Network
                    </span>
                    <span class="px-2 py-0.5 rounded-full bg-stone-100 dark:bg-white/10 text-stone-700 dark:text-stone-300 border border-stone-200 dark:border-white/20 text-[9px] font-bold font-sans uppercase">
                        {{ $totalTenants }} Tenancies
                    </span>
                </div>
                <div class="mt-3">
                    <div class="font-cinzel text-2xl sm:text-3xl text-[#171513] dark:text-white font-bold tracking-tight">
                        {{ $totalHalls }} <span class="text-xs font-sans font-normal text-stone-500">Halls</span>
                    </div>
                    <p class="text-xs text-stone-500 dark:text-stone-400 font-sans mt-1">
                        Active banquet halls &amp; marquees across Pakistan.
                    </p>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-stone-100 dark:border-white/10 flex items-center justify-between text-xs text-stone-500 dark:text-stone-400 font-sans">
                <span>Pending Approvals:</span>
                <strong class="text-[#8F7239] dark:text-[#C6A15B] font-bold">{{ $pendingTenants }} Venues</strong>
            </div>
        </div>

        <!-- 4. Registered Accounts & Governance -->
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-2xl p-6 shadow-sm hover:border-[#C6A15B]/60 transition duration-200 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-sans font-bold uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B]">
                        User Directory
                    </span>
                    <span class="px-2 py-0.5 rounded-full bg-[#C6A15B]/15 text-[#8F7239] dark:text-[#F2DE9A] border border-[#C6A15B]/40 text-[9px] font-bold font-sans uppercase">
                        Platform Users
                    </span>
                </div>
                <div class="mt-3">
                    <div class="font-cinzel text-2xl sm:text-3xl text-[#171513] dark:text-white font-bold tracking-tight">
                        {{ $totalRegisteredUsers }}
                    </div>
                    <p class="text-xs text-stone-500 dark:text-stone-400 font-sans mt-1">
                        {{ $totalCustomers }} Customers • {{ $totalVendors }} Hall Partners
                    </p>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-stone-100 dark:border-white/10 flex items-center justify-between text-xs text-stone-500 dark:text-stone-400 font-sans">
                <span>Suspended Accounts:</span>
                <strong class="text-stone-700 dark:text-stone-300 font-bold">{{ $totalSuspendedUsers }}</strong>
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
                        {{ $chartTimeframe === 'WEEKLY' ? 'Weekly Shift & GMV Activity' : ($chartTimeframe === 'YEARLY' ? 'Annual Month-by-Month Distribution' : 'Monthly 4-Week Shift Cadence') }}
                    </h3>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <!-- Timeframe Buttons -->
                    <div class="inline-flex rounded-lg bg-[#F8F5EF] dark:bg-white/5 border border-[#E7E5E4] dark:border-white/10 p-1">
                        <button wire:click="setChartTimeframe('WEEKLY')" 
                                class="px-2.5 py-1 rounded-md text-[10px] font-sans uppercase tracking-wider transition {{ $chartTimeframe === 'WEEKLY' ? 'bg-[#171513] dark:bg-[#C6A15B] text-[#C6A15B] dark:text-[#171513] font-bold shadow-sm' : 'text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:hover:text-white' }}">
                            Week
                        </button>
                        <button wire:click="setChartTimeframe('MONTHLY')" 
                                class="px-2.5 py-1 rounded-md text-[10px] font-sans uppercase tracking-wider transition {{ $chartTimeframe === 'MONTHLY' ? 'bg-[#171513] dark:bg-[#C6A15B] text-[#C6A15B] dark:text-[#171513] font-bold shadow-sm' : 'text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:hover:text-white' }}">
                            Month
                        </button>
                        <button wire:click="setChartTimeframe('YEARLY')" 
                                class="px-2.5 py-1 rounded-md text-[10px] font-sans uppercase tracking-wider transition {{ $chartTimeframe === 'YEARLY' ? 'bg-[#171513] dark:bg-[#C6A15B] text-[#C6A15B] dark:text-[#171513] font-bold shadow-sm' : 'text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:hover:text-white' }}">
                            Year
                        </button>
                    </div>

                    <!-- Metric Buttons -->
                    <div class="inline-flex rounded-lg bg-[#F8F5EF] dark:bg-white/5 border border-[#E7E5E4] dark:border-white/10 p-1">
                        <button wire:click="setChartMetric('sales')" 
                                class="px-3 py-1 rounded-md text-[11px] font-sans uppercase tracking-wider transition {{ $chartMetric === 'sales' ? 'bg-[#171513] dark:bg-[#C6A15B] text-[#C6A15B] dark:text-[#171513] font-bold shadow-sm' : 'text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:hover:text-white' }}">
                            GMV (PKR)
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
                <div class="relative z-10 grid gap-2 sm:gap-6 items-end h-64 pt-6" style="grid-template-columns: repeat({{ max(1, count($adminChartData)) }}, minmax(0, 1fr));">
                    @foreach($adminChartData as $point)
                        <div class="flex flex-col items-center h-full justify-end group relative">
                            
                            <!-- Floating Peak Pill (as seen in reference design above peak bar) -->
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

                                <!-- Afternoon Bar (Slate/Stone) -->
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
                <span>Nationwide Peak: <strong class="text-[#8F7239] dark:text-[#C6A15B] font-bold">Evening Slots ({{ $adminShiftPercentages['EVENING'] }}% Share)</strong></span>
                <span>Cumulative GMV: <strong class="text-[#171513] dark:text-white font-bold">PKR {{ number_format($totalGmv) }}</strong></span>
            </div>
        </div>

        <!-- RIGHT: CIRCULAR DONUT RING GAUGE (4 cols - Shift Occupancy) -->
        <div class="lg:col-span-4 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-2xl p-6 sm:p-7 shadow-md flex flex-col justify-between space-y-5 transition-colors duration-200">
            <div>
                <span class="text-[10px] font-sans font-bold uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B]">
                    Shift Distribution
                </span>
                <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold mt-0.5">
                    Nationwide Occupancy
                </h3>
            </div>

            <!-- Circular SVG Ring Gauge (Matching Reference Design 45% Gauge Ring) -->
            <div class="flex flex-col items-center justify-center relative py-2">
                @php
                    $adminGaugePct = $adminShiftPercentages['EVENING'] > 0 ? $adminShiftPercentages['EVENING'] : 45;
                    $adminCircumference = 339.29; // 2 * pi * 54
                    $adminOffset = $adminCircumference * (1 - ($adminGaugePct / 100));
                @endphp
                <svg viewBox="0 0 140 140" class="w-36 h-36 transform -rotate-90">
                    <defs>
                        <linearGradient id="adminGoldDonutGrad" x1="0%" y1="0%" x2="100%" y2="100%">
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
                            stroke-dasharray="{{ $adminCircumference }}" 
                            stroke-dashoffset="{{ $adminCircumference * (1 - (($adminShiftPercentages['AFTERNOON'] ?? 25) / 100)) }}" />
                    <!-- Primary Evening Segment (Gold) -->
                    <circle cx="70" cy="70" r="54" 
                            stroke="url(#adminGoldDonutGrad)" 
                            stroke-width="12" 
                            fill="none" 
                            stroke-linecap="round"
                            stroke-dasharray="{{ $adminCircumference }}" 
                            stroke-dashoffset="{{ $adminOffset }}" />
                </svg>

                <!-- Center Text Overlay -->
                <div class="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none">
                    <span class="font-cinzel text-3xl font-bold text-[#171513] dark:text-white leading-none">
                        {{ $adminGaugePct }}%
                    </span>
                    <span class="text-[10px] font-sans font-bold uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] mt-1">
                        Evening Share
                    </span>
                </div>
            </div>

            <!-- Segment List Details -->
            <div class="space-y-2.5 font-sans text-xs border-t border-[#E7E5E4] dark:border-white/10 pt-4">
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-2 text-stone-700 dark:text-stone-300">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#C6A15B]"></span>
                        <span>Evening Functions</span>
                    </span>
                    <strong class="text-[#8F7239] dark:text-[#C6A15B] font-bold">{{ $adminShiftCounts['EVENING'] }} ({{ $adminShiftPercentages['EVENING'] }}%)</strong>
                </div>

                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-2 text-stone-700 dark:text-stone-300">
                        <span class="w-2.5 h-2.5 rounded-full bg-slate-700 dark:bg-stone-500"></span>
                        <span>Afternoon Functions</span>
                    </span>
                    <strong class="text-stone-800 dark:text-stone-200 font-bold">{{ $adminShiftCounts['AFTERNOON'] }} ({{ $adminShiftPercentages['AFTERNOON'] }}%)</strong>
                </div>

                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-2 text-stone-500 dark:text-stone-400">
                        <span class="w-2.5 h-2.5 rounded-full bg-stone-300 dark:bg-white/20"></span>
                        <span>Open National Slots</span>
                    </span>
                    <strong class="text-stone-500 dark:text-stone-400 font-medium">{{ max(0, 100 - $adminShiftPercentages['EVENING'] - $adminShiftPercentages['AFTERNOON']) }}%</strong>
                </div>
            </div>

            <!-- Action Button -->
            <button wire:click="setTab('bookings')" 
                    class="btn-gold !w-full !h-10 text-xs font-bold uppercase tracking-wider font-sans rounded-lg shadow-sm transition-all duration-200 flex items-center justify-center gap-2">
                <span>View All Bookings ({{ $totalBookings }}) &rarr;</span>
            </button>
        </div>
    </div>

    <!-- ROW 3: AREA SPLINE WAVE CHART & MINI SHIFT MATRIX CALENDAR -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
        
        <!-- LEFT: AREA SPLINE WAVE CHART (8 cols - Cashflow Inflow vs Subscriptions) -->
        <div class="lg:col-span-8 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-2xl p-6 sm:p-7 shadow-md flex flex-col justify-between space-y-5 transition-colors duration-200">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-[#E7E5E4] dark:border-white/10 pb-4">
                <div>
                    <span class="text-[10px] font-sans font-bold uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B]">
                        Platform Liquidity &amp; Clearances
                    </span>
                    <h3 class="font-serif-luxury text-xl sm:text-2xl text-[#171513] dark:text-white font-bold mt-0.5">
                        {{ $billingEnabled ? 'Booking Volume vs Subscription Revenue' : 'Nationwide Booking Volume' }}
                    </h3>
                </div>

                <div class="flex items-center gap-4 text-xs font-sans">
                    <span class="inline-flex items-center gap-1.5 text-stone-700 dark:text-stone-300">
                        <span class="w-3 h-3 rounded-sm bg-[#C6A15B]"></span>
                        <span>Gross GMV (PKR {{ number_format(($adminWaveData['totalInflow'] ?? 0) / 1000) }}k)</span>
                    </span>
                    @if($billingEnabled)
                    <span class="inline-flex items-center gap-1.5 text-stone-500 dark:text-stone-400">
                        <span class="w-3 h-3 rounded-sm bg-slate-700 dark:bg-stone-600"></span>
                        <span>SaaS Clearances (PKR {{ number_format(($adminWaveData['totalSubscription'] ?? 0) / 1000) }}k)</span>
                    </span>
                    @endif
                </div>
            </div>

            <!-- SVG Spline Wave Chart (Dual Gradient Fill matching reference image) -->
            <div class="relative w-full overflow-hidden pt-2">
                <svg viewBox="0 0 600 150" class="w-full h-44 overflow-visible" preserveAspectRatio="none">
                    <defs>
                        <!-- Gold Gradient for Inflow -->
                        <linearGradient id="adminWaveGoldGradient" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#C6A15B" stop-opacity="0.45" />
                            <stop offset="50%" stop-color="#C6A15B" stop-opacity="0.15" />
                            <stop offset="100%" stop-color="#C6A15B" stop-opacity="0.0" />
                        </linearGradient>
                        <!-- Slate/Navy Gradient for Subscriptions -->
                        <linearGradient id="adminWaveSlateGradient" x1="0" y1="0" x2="0" y2="1">
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
                    @if(!empty($adminWaveData['inflowArea']))
                        <path d="{{ $adminWaveData['inflowArea'] }}" fill="url(#adminWaveGoldGradient)" />
                        <path d="{{ $adminWaveData['inflowPath'] }}" fill="none" stroke="#C6A15B" stroke-width="3" stroke-linecap="round" />
                    @endif

                    <!-- Subscription Wave Area & Path -->
                    @if(!empty($adminWaveData['subscriptionArea']))
                        <path d="{{ $adminWaveData['subscriptionArea'] }}" fill="url(#adminWaveSlateGradient)" />
                        <path d="{{ $adminWaveData['subscriptionPath'] }}" fill="none" stroke="#64748B" stroke-width="2.5" stroke-linecap="round" stroke-dasharray="2 0" />
                    @endif

                    <!-- Coordinate Markers on Inflow -->
                    @foreach($adminWaveData['inflowCoords'] ?? [] as $ic)
                        <circle cx="{{ $ic['x'] }}" cy="{{ $ic['y'] }}" r="4" fill="#C6A15B" stroke="#171513" stroke-width="2" class="cursor-pointer hover:r-6 transition-all" />
                        <text x="{{ $ic['x'] }}" y="136" text-anchor="middle" font-size="9" font-family="sans-serif" class="fill-stone-500 dark:fill-stone-400">{{ $ic['label'] }}</text>
                    @endforeach
                </svg>
            </div>

            <!-- Wave Summary Ticker -->
            <div class="pt-3 border-t border-[#E7E5E4] dark:border-white/10 flex flex-wrap items-center justify-between text-xs text-stone-600 dark:text-stone-400 font-sans">
                <span>Platform Flow: <strong class="text-[#8F7239] dark:text-[#C6A15B] font-bold">PKR {{ number_format($adminWaveData['totalInflow'] ?? 0) }}</strong></span>
                @if($billingEnabled)
                    <span>SaaS Subscriptions Collected: <strong class="text-stone-800 dark:text-stone-200 font-semibold">PKR {{ number_format($adminWaveData['totalSubscription'] ?? 0) }}</strong></span>
                @else
                    <span class="inline-flex items-center gap-1.5 font-semibold text-emerald-600 dark:text-emerald-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Free Launch Period — No Subscription Charges
                    </span>
                @endif
            </div>
        </div>

        <!-- RIGHT: MINI BANQUET SHIFT CALENDAR (4 cols) -->
        <div class="lg:col-span-4 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-2xl p-6 sm:p-7 shadow-md flex flex-col justify-between space-y-4 transition-colors duration-200">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-sans font-bold uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B]">
                        Shift Matrix
                    </span>
                    <span class="text-[11px] font-sans font-bold text-stone-600 dark:text-stone-300">
                        {{ $adminMiniCalendar['monthName'] ?? now()->format('F Y') }}
                    </span>
                </div>
                <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold mt-0.5">
                    Banquet Shift Cadence
                </h3>
            </div>

            <!-- 7-Column Mini Calendar Matrix -->
            <div class="space-y-2">
                <!-- Day-of-week header -->
                <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-sans font-bold uppercase text-stone-400">
                    <div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div><div>Su</div>
                </div>

                <!-- Days Matrix -->
                <div class="grid grid-cols-7 gap-1 text-center font-sans">
                    @for($b = 0; $b < ($adminMiniCalendar['leadBlanks'] ?? 0); $b++)
                        <div class="h-8 rounded-md bg-transparent"></div>
                    @endfor

                    @foreach($adminMiniCalendar['days'] ?? [] as $cd)
                        <div class="h-8 rounded-md flex flex-col items-center justify-center relative text-xs transition {{ $cd['isToday'] ? 'bg-[#171513] text-[#F2DE9A] dark:bg-[#C6A15B] dark:text-[#171513] font-bold shadow-sm' : 'hover:bg-[#F8F5EF] dark:hover:bg-white/5 text-stone-700 dark:text-stone-300' }}">
                            <span>{{ $cd['day'] }}</span>
                            @if($cd['hasEvening'] || $cd['hasAfternoon'])
                                <div class="flex items-center gap-0.5 -mt-0.5">
                                    @if($cd['hasEvening'])
                                        <span class="w-1 h-1 rounded-full bg-[#C6A15B]"></span>
                                    @endif
                                    @if($cd['hasAfternoon'])
                                        <span class="w-1 h-1 rounded-full bg-slate-700 dark:bg-stone-400"></span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Upcoming Scheduled Banquet -->
            @if(!empty($adminMiniCalendar['nextBooking']))
                <div class="p-3 rounded-xl bg-[#F8F5EF] dark:bg-white/5 border border-[#E7E5E4] dark:border-white/10 text-xs font-sans space-y-1">
                    <div class="text-[9px] uppercase tracking-wider text-[#8F7239] dark:text-[#C6A15B] font-bold">
                        Next Nationwide Banquet
                    </div>
                    <div class="font-bold text-[#171513] dark:text-white truncate">
                        {{ $adminMiniCalendar['nextBooking']['client'] }} • {{ $adminMiniCalendar['nextBooking']['hall'] }}
                    </div>
                    <div class="text-stone-500 dark:text-stone-400 text-[11px]">
                        {{ $adminMiniCalendar['nextBooking']['date'] }} ({{ $adminMiniCalendar['nextBooking']['slot'] }})
                    </div>
                </div>
            @endif

            <!-- Calendar Action Link -->
            <button wire:click="setTab('bookings')" 
                    class="btn-outline-gold !w-full !h-9 text-xs font-bold uppercase tracking-wider font-sans rounded-lg shadow-sm transition flex items-center justify-center gap-2">
                <span>Inspect Platform Schedule &rarr;</span>
            </button>
        </div>
    </div>

        <!-- ===================================================================
         EXECUTIVE NAVIGATION SEGMENTED TABS (,000,000 SaaS Control Bar)
         =================================================================== -->
    <div class="scrollbar-none overflow-x-auto flex items-center gap-2 p-1.5 bg-stone-100 dark:bg-[#14120E] border border-stone-200 dark:border-[#C6A15B]/25 rounded-2xl shadow-inner font-sans text-xs">
        <button wire:click="setTab('vendors')" 
                class="px-4 py-2.5 rounded-xl font-bold uppercase tracking-wider transition-all duration-200 whitespace-nowrap flex items-center gap-2 {{ $activeTab === 'vendors' ? 'bg-[#C6A15B] text-[#171513] shadow-md shadow-[#C6A15B]/25 border border-[#F2DE9A]/50' : 'text-stone-600 dark:text-stone-300 hover:text-[#C6A15B] hover:bg-black/5 dark:hover:bg-white/5' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <span>Vendors &amp; Subscriptions</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $activeTab === 'vendors' ? 'bg-[#171513]/20 text-[#171513]' : 'bg-black/10 dark:bg-white/10 text-stone-500 dark:text-stone-400' }}">{{ $totalTenants }}</span>
        </button>

        <button wire:click="setTab('tasks')" 
                class="px-4 py-2.5 rounded-xl font-bold uppercase tracking-wider transition-all duration-200 whitespace-nowrap flex items-center gap-2 {{ $activeTab === 'tasks' ? 'bg-[#C6A15B] text-[#171513] shadow-md shadow-[#C6A15B]/25 border border-[#F2DE9A]/50' : 'text-stone-600 dark:text-stone-300 hover:text-[#C6A15B] hover:bg-black/5 dark:hover:bg-white/5' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            <span>Platform Tasks</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $activeTab === 'tasks' ? 'bg-[#171513]/20 text-[#171513]' : 'bg-black/10 dark:bg-white/10 text-stone-500 dark:text-stone-400' }}">{{ $pendingTasksCount }}</span>
        </button>

        <button wire:click="setTab('traffic')" 
                class="px-4 py-2.5 rounded-xl font-bold uppercase tracking-wider transition-all duration-200 whitespace-nowrap flex items-center gap-2 {{ $activeTab === 'traffic' ? 'bg-[#C6A15B] text-[#171513] shadow-md shadow-[#C6A15B]/25 border border-[#F2DE9A]/50' : 'text-stone-600 dark:text-stone-300 hover:text-[#C6A15B] hover:bg-black/5 dark:hover:bg-white/5' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <span>Traffic &amp; Controls</span>
            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] {{ $activeTab === 'traffic' ? 'bg-[#171513]/20 text-[#171513]' : 'bg-emerald-500/15 text-emerald-500' }}">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>{{ $activeSessionsCount }} Live</span>
            </span>
        </button>

        @if($billingEnabled)
        <button wire:click="setTab('plans')" 
                class="px-4 py-2.5 rounded-xl font-bold uppercase tracking-wider transition-all duration-200 whitespace-nowrap flex items-center gap-2 {{ $activeTab === 'plans' ? 'bg-[#C6A15B] text-[#171513] shadow-md shadow-[#C6A15B]/25 border border-[#F2DE9A]/50' : 'text-stone-600 dark:text-stone-300 hover:text-[#C6A15B] hover:bg-black/5 dark:hover:bg-white/5' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>SaaS Plans</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $activeTab === 'plans' ? 'bg-[#171513]/20 text-[#171513]' : 'bg-black/10 dark:bg-white/10 text-stone-500 dark:text-stone-400' }}">{{ $totalPlans }}</span>
        </button>

        <button wire:click="setTab('payments')" 
                class="px-4 py-2.5 rounded-xl font-bold uppercase tracking-wider transition-all duration-200 whitespace-nowrap flex items-center gap-2 {{ $activeTab === 'payments' ? 'bg-[#C6A15B] text-[#171513] shadow-md shadow-[#C6A15B]/25 border border-[#F2DE9A]/50' : 'text-stone-600 dark:text-stone-300 hover:text-[#C6A15B] hover:bg-black/5 dark:hover:bg-white/5' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            <span>Payment Approvals</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $activeTab === 'payments' ? 'bg-[#171513]/20 text-[#171513]' : 'bg-black/10 dark:bg-white/10 text-stone-500 dark:text-stone-400' }}">{{ $totalSubscriptionPayments }}</span>
        </button>
        @endif

        <button wire:click="setTab('halls')" 
                class="px-4 py-2.5 rounded-xl font-bold uppercase tracking-wider transition-all duration-200 whitespace-nowrap flex items-center gap-2 {{ $activeTab === 'halls' ? 'bg-[#C6A15B] text-[#171513] shadow-md shadow-[#C6A15B]/25 border border-[#F2DE9A]/50' : 'text-stone-600 dark:text-stone-300 hover:text-[#C6A15B] hover:bg-black/5 dark:hover:bg-white/5' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M4 18h16M6 18V9m4 9V9m4 9V9m4 9V9M3 9l9-5 9 5H3z"/></svg>
            <span>Halls Catalog</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $activeTab === 'halls' ? 'bg-[#171513]/20 text-[#171513]' : 'bg-black/10 dark:bg-white/10 text-stone-500 dark:text-stone-400' }}">{{ $totalHalls }}</span>
        </button>

        <button wire:click="setTab('bookings')" 
                class="px-4 py-2.5 rounded-xl font-bold uppercase tracking-wider transition-all duration-200 whitespace-nowrap flex items-center gap-2 {{ $activeTab === 'bookings' ? 'bg-[#C6A15B] text-[#171513] shadow-md shadow-[#C6A15B]/25 border border-[#F2DE9A]/50' : 'text-stone-600 dark:text-stone-300 hover:text-[#C6A15B] hover:bg-black/5 dark:hover:bg-white/5' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>All Bookings</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $activeTab === 'bookings' ? 'bg-[#171513]/20 text-[#171513]' : 'bg-black/10 dark:bg-white/10 text-stone-500 dark:text-stone-400' }}">{{ $totalBookings }}</span>
        </button>

        <button wire:click="setTab('users')" 
                class="px-4 py-2.5 rounded-xl font-bold uppercase tracking-wider transition-all duration-200 whitespace-nowrap flex items-center gap-2 {{ $activeTab === 'users' ? 'bg-[#C6A15B] text-[#171513] shadow-md shadow-[#C6A15B]/25 border border-[#F2DE9A]/50' : 'text-stone-600 dark:text-stone-300 hover:text-[#C6A15B] hover:bg-black/5 dark:hover:bg-white/5' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span>Users Directory</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $activeTab === 'users' ? 'bg-[#171513]/20 text-[#171513]' : 'bg-black/10 dark:bg-white/10 text-stone-500 dark:text-stone-400' }}">{{ $totalRegisteredUsers }}</span>
        </button>

        <button wire:click="setTab('analytics')" 
                class="px-4 py-2.5 rounded-xl font-bold uppercase tracking-wider transition-all duration-200 whitespace-nowrap flex items-center gap-2 {{ $activeTab === 'analytics' ? 'bg-[#C6A15B] text-[#171513] shadow-md shadow-[#C6A15B]/25 border border-[#F2DE9A]/50' : 'text-stone-600 dark:text-stone-300 hover:text-[#C6A15B] hover:bg-black/5 dark:hover:bg-white/5' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <span>Visitor Analytics</span>
        </button>

        <button wire:click="setTab('audit')" 
                class="px-4 py-2.5 rounded-xl font-bold uppercase tracking-wider transition-all duration-200 whitespace-nowrap flex items-center gap-2 {{ $activeTab === 'audit' ? 'bg-[#C6A15B] text-[#171513] shadow-md shadow-[#C6A15B]/25 border border-[#F2DE9A]/50' : 'text-stone-600 dark:text-stone-300 hover:text-[#C6A15B] hover:bg-black/5 dark:hover:bg-white/5' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            <span>Security Audit Log</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $activeTab === 'audit' ? 'bg-[#171513]/20 text-[#171513]' : 'bg-black/10 dark:bg-white/10 text-stone-500 dark:text-stone-400' }}">{{ $totalAuditLogs }}</span>
        </button>
    </div>

    <!-- ===================================================================
         TAB: PLATFORM TASKS & SUPER ADMIN TO-DO HUB
         =================================================================== -->
    @if($activeTab === 'tasks')
        <div class="space-y-6">
            <!-- Automated Alert Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Alert 1: Pending Approvals -->
                <div class="bg-white dark:bg-[#171513] rounded-xl border {{ $pendingApprovalsCount > 0 ? 'border-amber-500/60' : 'border-[#E7E5E4] dark:border-white/10' }} p-5 shadow-sm space-y-2">
                    <div class="flex items-center justify-between text-xs font-sans uppercase font-bold text-stone-600 dark:text-stone-400">
                        <span>Pending Hall Verifications</span>
                        <span class="px-2 py-0.5 rounded font-bold text-[10px] {{ $pendingApprovalsCount > 0 ? 'bg-amber-500/20 text-amber-600 dark:text-amber-400' : 'bg-stone-100 dark:bg-white/5 text-stone-500' }}">
                            {{ $pendingApprovalsCount }} Action Needed
                        </span>
                    </div>
                    <div class="text-2xl font-cinzel font-bold text-[#171513] dark:text-white">
                        {{ $pendingApprovalsCount }} Marquees
                    </div>
                    <p class="text-xs text-stone-500 dark:text-stone-400 font-sans">
                        Halls awaiting Super Admin background verification &amp; commercial KYC.
                    </p>
                    @if($pendingApprovalsCount > 0)
                        <button wire:click="setTab('vendors')" class="text-xs text-[#8F7239] dark:text-[#C6A15B] font-mono font-bold hover:underline block pt-1">
                            Review &amp; Approve Now &rarr;
                        </button>
                    @endif
                </div>

                <!-- Alert 2: Public concierge inquiries (or SaaS receipts when billing is on) -->
                @if($billingEnabled)
                <div class="bg-white dark:bg-[#171513] border {{ $pendingSubscriptionPaymentsCount > 0 ? 'border-[#C6A15B]/70 shadow-sm' : 'border-[#E7E5E4] dark:border-white/10' }} p-5 shadow-sm space-y-2">
                    <div class="flex items-center justify-between text-xs font-mono uppercase font-bold text-stone-600 dark:text-stone-400">
                        <span>💳 Subscription Receipts</span>
                        <span class="px-2.5 py-0.5 rounded-md font-bold text-[10px] {{ $pendingSubscriptionPaymentsCount > 0 ? 'bg-[#C6A15B]/20 text-[#8F7239] dark:text-[#F2DE9A] border border-[#C6A15B]/40' : 'bg-stone-100 dark:bg-white/5 text-stone-500' }}">
                            {{ $pendingSubscriptionPaymentsCount }} Pending
                        </span>
                    </div>
                    <div class="text-2xl font-cinzel font-bold text-[#171513] dark:text-white">
                        {{ $pendingSubscriptionPaymentsCount }} Payments
                    </div>
                    <p class="text-xs text-stone-500 dark:text-stone-400 font-sans">
                        Manual cash receipts submitted by hall owners for tier activation.
                    </p>
                    @if($pendingSubscriptionPaymentsCount > 0)
                        <button wire:click="setTab('payments')" class="text-xs text-[#8F7239] dark:text-[#C6A15B] font-mono font-bold hover:underline block pt-1">
                            Verify Cash Receipts →
                        </button>
                    @endif
                </div>
                @else
                <div class="bg-white dark:bg-[#171513] border {{ $newContactInquiriesCount > 0 ? 'border-[#C6A15B]/70 shadow-sm' : 'border-[#E7E5E4] dark:border-white/10' }} p-5 shadow-sm space-y-2">
                    <div class="flex items-center justify-between text-xs font-mono uppercase font-bold text-stone-600 dark:text-stone-400">
                        <span>Concierge Inquiries</span>
                        <span class="px-2.5 py-0.5 rounded-md font-bold text-[10px] {{ $newContactInquiriesCount > 0 ? 'bg-[#C6A15B]/20 text-[#8F7239] dark:text-[#F2DE9A] border border-[#C6A15B]/40' : 'bg-stone-100 dark:bg-white/5 text-stone-500' }}">
                            {{ $newContactInquiriesCount }} New
                        </span>
                    </div>
                    <div class="text-2xl font-cinzel font-bold text-[#171513] dark:text-white">
                        {{ $newContactInquiriesCount }} Couples
                    </div>
                    <p class="text-xs text-stone-500 dark:text-stone-400 font-sans">
                        Public contact-form leads waiting for Riwaj Grand concierge follow-up.
                    </p>
                </div>
                @endif

                <!-- Alert 3: Task Completion Rate -->
                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-5 shadow-sm space-y-2">
                    <div class="flex items-center justify-between text-xs font-mono uppercase font-bold text-stone-600 dark:text-stone-400">
                        <span>📋 Super Admin Tasks</span>
                        <span class="px-2 py-0.5 rounded font-bold text-[10px] bg-[#C6A15B]/20 text-[#8F7239] dark:text-[#C6A15B]">
                            {{ $pendingTasksCount }} Open / {{ $completedTasksCount }} Done
                        </span>
                    </div>
                    <div class="text-2xl font-cinzel font-bold text-[#171513] dark:text-white">
                        {{ $totalPlatformTasks }} Total Tasks
                    </div>
                    <p class="text-xs text-stone-500 dark:text-stone-400 font-sans">
                        Operational to-do items and custom reminders for the platform owner.
                    </p>
                </div>
            </div>

            <!-- Task Creator & Register -->
            <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-6 space-y-6 shadow-sm">
                <div class="pb-4 border-b border-[#E7E5E4] dark:border-white/10">
                    <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-normal">
                        Super Admin Platform To-Do &amp; Task Desk
                    </h3>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-0.5">
                        Platform owner work only: hall KYC, marketplace quality, concierge inquiries, and site controls. Hall kitchen, payroll, and seating stay on the Rent Admin dashboard.
                    </p>
                </div>

                <!-- Add Task Form -->
                <form wire:submit="addTask" class="p-4 bg-[#F8F5EF] dark:bg-white/5 border border-[#E7E5E4] dark:border-white/10 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                        <div class="sm:col-span-5">
                            <label class="block text-[11px] font-mono font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1">
                                Task Title / Action Item *
                            </label>
                            <input type="text" wire:model="newTaskTitle" required placeholder="e.g. Call new hall owner for KYC, Reply to Lahore concierge inquiry..."
                                   class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-xs w-full">
                            @error('newTaskTitle') <span class="text-rose-500 text-xs font-mono">{{ $message }}</span> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-mono font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1">
                                Category
                            </label>
                            <select wire:model="newTaskCategory" class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-xs w-full">
                                <option value="GENERAL">General Platform</option>
                                <option value="VENDOR_ONBOARDING">Vendor Onboarding</option>
                                <option value="CUSTOMER_INQUIRY">Customer Inquiry</option>
                                <option value="TRAFFIC_MONITOR">Traffic &amp; SEO</option>
                                @if($billingEnabled)
                                <option value="PAYMENT_REVIEW">Payment Review</option>
                                @endif
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-mono font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1">
                                Priority
                            </label>
                            <select wire:model="newTaskPriority" class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-xs w-full">
                                <option value="HIGH">🔥 High</option>
                                <option value="MEDIUM">⚡ Medium</option>
                                <option value="LOW">🌱 Low</option>
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-mono font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1">
                                Due Date
                            </label>
                            <input type="date" wire:model="newTaskDueDate" 
                                   class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-xs w-full">
                        </div>

                        <div class="sm:col-span-1">
                            <button type="submit" 
                                    class="btn-gold !h-10 !w-full text-xs font-bold uppercase font-mono shadow">
                                + Add
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Tasks List -->
                <div class="space-y-2">
                    @forelse($platformTasks as $task)
                        <div class="p-3.5 border {{ $task->is_completed ? 'bg-stone-50 dark:bg-white/[0.02] border-stone-200 dark:border-white/5 opacity-60' : 'bg-white dark:bg-[#171513] border-[#E7E5E4] dark:border-white/10 hover:border-[#C6A15B]' }} transition flex items-center justify-between gap-4 font-mono text-xs">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <button type="button" wire:click="toggleTask('{{ $task->id }}')" 
                                        class="w-5 h-5 rounded border flex items-center justify-center transition shrink-0 {{ $task->is_completed ? 'bg-[#C6A15B] border-[#C6A15B] text-[#171513] font-bold' : 'border-stone-400 dark:border-white/30 hover:border-[#C6A15B]' }}">
                                    @if($task->is_completed)
                                        ✓
                                    @endif
                                </button>

                                <div class="min-w-0 flex-1">
                                    <div class="font-bold text-sm {{ $task->is_completed ? 'line-through text-stone-500' : 'text-[#171513] dark:text-white' }} truncate">
                                        {{ $task->title }}
                                    </div>
                                    @if($task->notes)
                                        <p class="text-[11px] text-stone-500 dark:text-stone-400 mt-0.5 line-clamp-2">{{ $task->notes }}</p>
                                    @endif
                                    <div class="flex items-center gap-2 text-[10px] text-stone-500 dark:text-stone-400 mt-0.5">
                                        <span>Category: {{ str_replace('_', ' ', $task->category) }}</span>
                                        <span>•</span>
                                        @if($task->due_date)
                                             <span>Due: {{ $task->due_date->format('d M Y') }}</span>
                                             <span>•</span>
                                        @endif
                                        <span>Created: {{ $task->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 shrink-0">
                                @php
                                    $prioBadge = match($task->priority) {
                                        'HIGH' => 'bg-[#171513] text-[#F2DE9A] border-[#C6A15B]/50',
                                        'MEDIUM' => 'bg-[#C6A15B]/15 text-[#8F7239] dark:text-[#F2DE9A] border-[#C6A15B]/40',
                                        default => 'bg-stone-100 dark:bg-white/10 text-stone-600 dark:text-stone-300 border-stone-200 dark:border-white/20',
                                    };
                                @endphp
                                <span class="px-2 py-0.5 text-[9px] uppercase font-bold border {{ $prioBadge }}">
                                    {{ $task->priority }}
                                </span>

                                <button wire:click="deleteTask('{{ $task->id }}')"
                                        onclick="return confirm('Delete this task?')"
                                        class="tbtn tbtn-icon tbtn-red">
                                    &times;
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-xs text-stone-500 font-sans">
                            No custom tasks created yet. Use the form above to add your first administrative task.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         TAB: WEBSITE TRAFFIC & PLATFORM CONTROL CENTER
         =================================================================== -->
    @if($activeTab === 'traffic')
        <div class="space-y-6">
            <!-- Platform Master Switches & Controls -->
            <div class="bg-white dark:bg-[#171513] border-2 border-[#C6A15B] p-6 shadow-2xl relative space-y-6">
                <div class="w-full h-1 bg-gradient-to-r from-[#C6A15B] via-[#E8DCBE] to-[#C6A15B] absolute top-0 left-0"></div>

                <div class="pb-4 border-b border-[#E7E5E4] dark:border-white/10">
                    <span class="text-[10px] font-mono uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold block">
                        Platform Traffic &amp; System Gates
                    </span>
                    <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-normal mt-0.5">
                        Website Traffic Control &amp; Ingestion Switches
                    </h3>
                    <p class="text-xs text-stone-500 dark:text-stone-400 font-sans mt-0.5">
                        Control public search discovery, vendor onboarding portals, and live platform banners.
                    </p>
                </div>

                <!-- Switches Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Switch 1: Public Marketplace Status -->
                    <div class="p-5 bg-[#F8F5EF] dark:bg-white/5 border border-[#E7E5E4] dark:border-white/10 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-mono font-bold uppercase text-stone-800 dark:text-stone-200">
                                🌐 Public Marketplace Catalog
                            </span>
                            <span class="px-2.5 py-1 text-[9px] font-mono uppercase font-bold border rounded-md {{ $marketplaceEnabled ? 'bg-[#C6A15B]/15 text-[#8F7239] dark:text-[#F2DE9A] border-[#C6A15B]/40' : 'bg-stone-200/60 dark:bg-stone-800 text-stone-600 dark:text-stone-300 border-stone-300 dark:border-stone-700' }}">
                                {{ $marketplaceEnabled ? 'ACTIVE / DISCOVERABLE' : 'PAUSED' }}
                            </span>
                        </div>
                        <p class="text-xs text-stone-500 dark:text-stone-400 font-sans">
                            Controls whether guests can browse, search, and view wedding halls on the public marketplace (`/venues`).
                        </p>
                        <button wire:click="toggleMarketplaceStatus" 
                                class="px-4 py-2 text-xs font-sans font-bold uppercase rounded-lg border transition {{ $marketplaceEnabled ? 'border-stone-300 dark:border-white/10 hover:border-[#C6A15B] text-stone-700 dark:text-stone-300 hover:text-[#C6A15B]' : 'btn-gold shadow-sm' }}">
                            {{ $marketplaceEnabled ? 'Pause Marketplace Access' : 'Activate Marketplace Catalog' }}
                        </button>
                    </div>

                    <!-- Switch 2: Vendor Self-Registration -->
                    <div class="p-5 bg-[#F8F5EF] dark:bg-white/5 border border-[#E7E5E4] dark:border-white/10 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-mono font-bold uppercase text-stone-800 dark:text-stone-200">
                                🏛️ Vendor Self-Registration Portal
                            </span>
                            <span class="px-2.5 py-1 text-[9px] font-mono uppercase font-bold border rounded-md {{ $vendorRegistrationOpen ? 'bg-[#C6A15B]/15 text-[#8F7239] dark:text-[#F2DE9A] border-[#C6A15B]/40' : 'bg-stone-200/60 dark:bg-stone-800 text-stone-600 dark:text-stone-300 border-stone-300 dark:border-stone-700' }}">
                                {{ $vendorRegistrationOpen ? 'OPEN FOR NEW HALLS' : 'INVITE ONLY' }}
                            </span>
                        </div>
                        <p class="text-xs text-stone-500 dark:text-stone-400 font-sans">
                            Allows or pauses public wedding hall owner registration from `/register`. When paused, only Super Admin can provision new venues.
                        </p>
                        <button wire:click="toggleRegistrationStatus" 
                                class="px-4 py-2 text-xs font-sans font-bold uppercase rounded-lg border transition {{ $vendorRegistrationOpen ? 'border-stone-300 dark:border-white/10 hover:border-[#C6A15B] text-stone-700 dark:text-stone-300 hover:text-[#C6A15B]' : 'btn-gold shadow-sm' }}">
                            {{ $vendorRegistrationOpen ? 'Close Public Registration' : 'Open Public Registration' }}
                        </button>
                    </div>
                </div>

                <!-- Live Platform Announcement Banner -->
                <div class="p-5 bg-[#F8F5EF] dark:bg-white/5 border border-[#E7E5E4] dark:border-white/10 space-y-3">
                    <span class="text-xs font-mono font-bold uppercase text-stone-800 dark:text-stone-200 block">
                        📢 Global Platform Announcement Banner (Public Header)
                    </span>
                    <p class="text-xs text-stone-500 dark:text-stone-400 font-sans">
                        Broadcast system updates, peak wedding season notices, or maintenance schedules across all public pages.
                    </p>
                    <div class="flex gap-2">
                        <input type="text" wire:model="announcementBanner" placeholder="e.g. Wedding Season 2026 Inquiries Open • Guaranteed Shift Locking with Zero Double-Booking"
                               class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-xs flex-1">
                        <button wire:click="saveAnnouncement" class="btn-gold !h-10 !px-5 text-xs font-mono font-bold uppercase shadow">
                            Save Banner
                        </button>
                    </div>
                </div>
            </div>

            <!-- Real-Time Traffic & Visitor Analytics -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="p-5 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-1">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-stone-500 font-bold block">Live Visitors (15m)</span>
                    <div class="text-3xl font-cinzel font-bold text-[#8F7239] dark:text-[#C6A15B]">{{ $activeSessionsCount }}</div>
                    <span class="text-[10px] text-stone-400 font-mono">Active browsing sessions</span>
                </div>
                <div class="p-5 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-1">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-stone-500 font-bold block">Today's Page Views</span>
                    <div class="text-3xl font-cinzel font-bold text-[#8F7239] dark:text-[#C6A15B]">{{ $todayVisits }}</div>
                    <span class="text-[10px] text-stone-400 font-mono">Page hits in last 24h</span>
                </div>
                <div class="p-5 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-1">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-stone-500 font-bold block">Unique Visitors</span>
                    <div class="text-3xl font-cinzel font-bold text-[#171513] dark:text-white">{{ $uniqueVisitorsCount }}</div>
                    <span class="text-[10px] text-stone-400 font-mono">Unique IP addresses</span>
                </div>
                <div class="p-5 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-1">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-stone-500 font-bold block">Total Page Views</span>
                    <div class="text-3xl font-cinzel font-bold text-[#171513] dark:text-white">{{ $totalPageVisits }}</div>
                    <span class="text-[10px] text-stone-400 font-mono">All-time recorded visits</span>
                </div>
            </div>

            <!-- Top Performing Halls by Views -->
            <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-6 space-y-4 shadow-sm">
                <h4 class="text-sm font-mono font-bold uppercase tracking-wider text-[#8F7239] dark:text-[#C6A15B]">
                    Top 10 Most Visited Wedding Marquees &amp; Halls
                </h4>
                <div class="overflow-x-auto border border-[#E7E5E4] dark:border-white/10">
                    <table class="w-full text-xs text-left border-collapse">
                        <thead>
                            <tr class="bg-[#F8F5EF] dark:bg-white/5 border-b border-[#E7E5E4] dark:border-white/10 text-[10px] font-mono uppercase tracking-wider text-stone-600 dark:text-stone-400">
                                <th class="p-3">Hall Name &amp; Venue</th>
                                <th class="p-3">City</th>
                                <th class="p-3 text-right">Showcase Views</th>
                                <th class="p-3 text-right">Wishlists</th>
                                <th class="p-3 text-right">Public Link</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E7E5E4] dark:divide-white/10 font-sans">
                            @foreach($topHallsByViews as $th)
                                <tr class="hover:bg-stone-50 dark:hover:bg-white/5 transition">
                                    <td class="p-3">
                                        <div class="font-bold text-[#171513] dark:text-white">{{ $th->name }}</div>
                                        <span class="text-[10px] text-stone-500 font-mono">{{ $th->venue?->name ?? 'Venue' }}</span>
                                    </td>
                                    <td class="p-3 font-mono text-stone-600 dark:text-stone-400">{{ $th->venue?->city ?? '—' }}</td>
                                    <td class="p-3 text-right font-mono font-bold text-sm text-[#8F7239] dark:text-[#C6A15B]">{{ $th->total_views }}</td>
                                    <td class="p-3 text-right font-mono text-stone-600 dark:text-stone-300">{{ $th->hall_wishlists_count ?? 0 }}</td>
                                    <td class="p-3 text-right">
                                        <a href="{{ route('hall.showcase', $th->slug ?? $th->id) }}" target="_blank" 
                                           class="text-[#8F7239] dark:text-[#C6A15B] hover:underline font-mono text-[11px]">
                                            View Showcase &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         TAB 1: VENDORS & SUBSCRIPTIONS (Full CRUD)
         =================================================================== -->
    @if($activeTab === 'vendors')
        <div class="space-y-4">
            <!-- Header Card with Live Action -->
            <div class="bg-white dark:bg-[#1A1714] border border-[#E7E5E4] dark:border-[#C6A15B]/25 rounded-2xl p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-colors">
                <div>
                    <div class="inline-flex items-center gap-2 text-[10px] font-sans font-bold uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B]">
                        <span>Enterprise Tenant Registry</span>
                        <span>•</span>
                        <span>Multi-Tenant Banquet Engine</span>
                    </div>
                    <h3 class="font-serif-luxury text-2xl sm:text-3xl text-[#171513] dark:text-white font-normal mt-1">
                        Wedding Hall Vendors &amp; SaaS Subscriptions
                    </h3>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-1">
                        Displaying {{ $totalTenants }} active banquet groups with tenant credentials, plan expiries, and capacity metrics.
                    </p>
                </div>
                <button wire:click="openProvisionModal" class="btn-gold !h-10 !px-5 text-xs font-bold uppercase font-mono shadow-md shrink-0 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    <span>Register &amp; Provision Hall</span>
                </button>
            </div>

            <!-- Responsive Enterprise Table Container -->
            <div tabindex="0" role="region" aria-label="Vendors & Subscriptions Directory" class="table-wrapper-enterprise">
                <table class="table-enterprise">
                    <thead>
                        <tr>
                            <th>Business / Venue Complex</th>
                            <th>Owner Contact</th>
                            @if($billingEnabled)
                                <th>Assigned SaaS Plan</th>
                            @endif
                            <th>Capacity Specs</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tenants as $t)
                            @php
                                $cleanPhone = preg_replace('/[^0-9]/', '', $t->phone);
                            @endphp
                            <tr>
                                <!-- Business Details -->
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="font-bold text-sm text-[#171513] dark:text-white">
                                            {{ $t->name }}
                                        </div>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-mono font-bold bg-[#C6A15B]/15 text-[#8F7239] dark:text-[#F2DE9A] border border-[#C6A15B]/30">
                                            {{ $t->city ?? 'Lahore' }}
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-stone-500 dark:text-stone-400 font-sans mt-1 flex items-center gap-1 truncate max-w-xs">
                                        <svg class="w-3 h-3 text-[#C6A15B] shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                                        <span class="truncate">{{ $t->address }}</span>
                                    </div>
                                </td>

                                <!-- Owner Contact -->
                                <td>
                                    <div class="font-medium text-stone-800 dark:text-stone-200 text-xs">
                                        {{ $t->email }}
                                    </div>
                                    @if($t->phone)
                                        <a href="https://wa.me/{{ $cleanPhone }}?text=Assalamu%20Alaikum%20{{ urlencode($t->name) }}%20Owner" target="_blank"
                                           class="inline-flex items-center gap-1 text-[11px] text-stone-500 dark:text-stone-400 hover:text-emerald-500 font-mono transition mt-0.5">
                                            <span class="text-emerald-500">💬</span>
                                            <span>{{ $t->phone }}</span>
                                        </a>
                                    @endif
                                </td>

                                <!-- SaaS Plan -->
                                @if($billingEnabled)
                                <td>
                                    <div class="font-bold text-xs text-[#8F7239] dark:text-[#C6A15B] flex items-center gap-1.5">
                                        <span>💎</span>
                                        <span>{{ $t->subscriptionPlan->name ?? ($t->subscription_plan ?? 'Starter Marquee') }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-[10px] font-mono mt-1">
                                        <span class="text-stone-500">Status:</span>
                                        <span class="font-bold {{ $t->isSubscriptionActive() ? 'text-emerald-500' : 'text-rose-500' }}">
                                            {{ $t->subscription_status }}
                                        </span>
                                        @if($t->subscription_expires_at)
                                            <span class="text-stone-400">• Exp: {{ $t->subscription_expires_at->format('d M Y') }}</span>
                                        @endif
                                    </div>
                                </td>
                                @endif

                                <!-- Capacity Specs -->
                                <td>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-1 rounded-md bg-stone-100 dark:bg-white/5 border border-stone-200 dark:border-white/10 text-[11px] font-mono font-semibold text-stone-700 dark:text-stone-300">
                                            🏛️ {{ $t->halls_count }} Halls
                                        </span>
                                        <span class="px-2.5 py-1 rounded-md bg-stone-100 dark:bg-white/5 border border-stone-200 dark:border-white/10 text-[11px] font-mono font-semibold text-stone-700 dark:text-stone-300">
                                            👥 {{ $t->users_count }} Staff
                                        </span>
                                    </div>
                                </td>

                                <!-- Verification Status -->
                                <td>
                                    @if($t->verification_status === 'APPROVED')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-sans font-bold uppercase tracking-wider bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            <span>✓ Approved</span>
                                        </span>
                                    @elseif($t->verification_status === 'SUSPENDED')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-sans font-bold uppercase tracking-wider bg-stone-200/60 dark:bg-stone-800 text-stone-700 dark:text-stone-300 border border-stone-300 dark:border-stone-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-stone-400"></span>
                                            <span>✕ Suspended</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-sans font-bold uppercase tracking-wider bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            <span>⏳ Pending</span>
                                        </span>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td class="text-right">
                                    <div class="flex flex-wrap items-center justify-end gap-1.5 font-sans">
                                        <button wire:click="openEditVendorModal('{{ $t->id }}')" class="tbtn tbtn-gold">
                                            Edit
                                        </button>

                                        @if($t->verification_status !== 'APPROVED')
                                            <button wire:click="approveVendor('{{ $t->id }}')" class="tbtn tbtn-green">
                                                Approve
                                            </button>
                                        @else
                                            <button wire:click="suspendVendor('{{ $t->id }}')" class="tbtn tbtn-amber">
                                                Suspend
                                            </button>
                                        @endif

                                        <button wire:click="toggleTenantStatus('{{ $t->id }}')" class="tbtn tbtn-outline">
                                            {{ $t->is_active ? 'Disable' : 'Enable' }}
                                        </button>

                                        <button wire:click="deleteVendor('{{ $t->id }}')"
                                                onclick="return confirm('Are you sure you want to delete this vendor and all its halls?') || event.stopImmediatePropagation()"
                                                class="tbtn tbtn-red">
                                            Del
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $billingEnabled ? 6 : 5 }}" class="p-10 text-center text-stone-400 font-sans">
                                    No banquet vendors registered on the platform yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         TAB 2: SAAS PRICING PLANS (Full CRUD)
         =================================================================== -->
    @if($activeTab === 'plans' && $billingEnabled)
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 space-y-6 shadow-sm dark:shadow-xl">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-[#E7E5E4] dark:border-white/10 gap-4">
                <div>
                    <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-normal">
                        Commercial SaaS Subscription Plans (PKR)
                    </h3>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-0.5">Configure monthly and yearly rates, venue limits, and ERP module privileges.</p>
                </div>

                <button wire:click="openCreatePlanModal" class="btn-gold !h-9 !px-4 text-xs font-bold uppercase font-mono shadow-md">
                    + Add New SaaS Plan
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($plans as $p)
                    <div class="p-6 border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-white/5 flex flex-col justify-between space-y-4 hover:border-[#C6A15B] transition">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between border-b border-[#E7E5E4] dark:border-white/10 pb-2">
                                <span class="text-[10px] font-mono font-bold uppercase text-[#8F7239] dark:text-[#C6A15B]">Tier: {{ $p->slug }}</span>
                                <span class="text-xs font-mono font-semibold text-stone-600 dark:text-stone-300">{{ $p->tenants_count }} Subscribers</span>
                            </div>

                            <h4 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-medium">{{ $p->name }}</h4>
                            
                            <div class="space-y-1">
                                <div class="text-lg font-cinzel font-bold text-[#171513] dark:text-white">
                                    PKR {{ number_format($p->price_monthly) }} <span class="text-xs font-normal text-stone-500 dark:text-stone-400 font-sans">/ month</span>
                                </div>
                                <div class="text-[11px] font-mono text-stone-600 dark:text-stone-300 flex items-center justify-between">
                                    <span>PKR {{ number_format($p->price_weekly ?? 5000) }} / week</span>
                                    <span>PKR {{ number_format($p->price_yearly) }} / year</span>
                                </div>
                            </div>

                            <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed font-normal">
                                {{ $p->description }}
                            </p>

                            <div class="pt-3 border-t border-[#E7E5E4] dark:border-white/10 space-y-1.5 text-xs font-mono text-stone-700 dark:text-stone-300">
                                <div>• Max Halls: <strong class="text-[#171513] dark:text-white">{{ $p->max_halls === -1 ? 'Unlimited' : $p->max_halls }}</strong></div>
                                <div>• Staff Accounts: <strong class="text-[#171513] dark:text-white">{{ $p->max_staff === -1 ? 'Unlimited' : $p->max_staff }}</strong></div>
                                <div>• Monthly Bookings: <strong class="text-[#171513] dark:text-white">{{ $p->max_monthly_bookings === -1 ? 'Unlimited' : $p->max_monthly_bookings }}</strong></div>
                                <div>• Kitchen KOT: <strong class="text-[#171513] dark:text-white">{{ $p->has_kitchen_kot ? 'Included' : 'No' }}</strong></div>
                                <div>• WhatsApp Bot: <strong class="text-[#171513] dark:text-white">{{ $p->has_whatsapp_automation ? 'Included' : 'No' }}</strong></div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10 flex items-center justify-between gap-2 font-mono text-xs">
                            <button wire:click="openEditPlanModal({{ $p->id }})" class="tbtn tbtn-dark">
                                Edit Plan
                            </button>
                            <button wire:click="deletePlan({{ $p->id }})"
                                    onclick="return confirm('Delete this subscription plan?') || event.stopImmediatePropagation()"
                                    class="tbtn tbtn-red">
                                Delete
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- ===================================================================
         TAB 3: SUBSCRIPTION PAYMENT CLEARANCE
         =================================================================== -->
    @if($activeTab === 'payments' && $billingEnabled)
        <div class="space-y-4">
            <div class="bg-white dark:bg-[#1A1714] border border-[#E7E5E4] dark:border-[#C6A15B]/25 rounded-2xl p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-colors">
                <div>
                    <div class="inline-flex items-center gap-2 text-[10px] font-sans font-bold uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B]">
                        <span>Financial Liquidity</span>
                        <span>•</span>
                        <span>Direct Cash Settlements</span>
                    </div>
                    <h3 class="font-serif-luxury text-2xl sm:text-3xl text-[#171513] dark:text-white font-normal mt-1">
                        Cash Receipt Approvals
                    </h3>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-1">Verify vendor cash handovers and extend SaaS subscription periods.</p>
                </div>
            </div>

            <div tabindex="0" role="region" aria-label="Subscription Payments Table" class="table-wrapper-enterprise">
                <table class="table-enterprise">
                    <thead>
                        <tr>
                            <th>Deposit Date</th>
                            <th>Vendor Tenant</th>
                            <th>Plan &amp; Cycle</th>
                            <th>Amount (PKR)</th>
                            <th>Cash Receipt Reference</th>
                            <th>Status</th>
                            <th class="text-right">Verification</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subscriptionPayments as $sp)
                            <tr>
                                <td>
                                    <span class="text-stone-600 dark:text-stone-400 font-mono text-xs">
                                        {{ $sp->created_at->format('d M Y') }}
                                    </span>
                                </td>
                                <td>
                                    <div class="font-bold text-sm text-stone-800 dark:text-stone-200">
                                        {{ $sp->tenant->name ?? 'N/A' }}
                                    </div>
                                </td>
                                <td>
                                    <span class="px-2.5 py-1 rounded-md bg-[#C6A15B]/15 text-[#8F7239] dark:text-[#F2DE9A] border border-[#C6A15B]/30 text-xs font-mono font-bold">
                                        {{ $sp->plan->name ?? 'Plan' }} ({{ $sp->billing_cycle }})
                                    </span>
                                </td>
                                <td>
                                    <strong class="font-cinzel text-base text-[#171513] dark:text-[#C6A15B]">
                                        PKR {{ number_format($sp->amount) }}
                                    </strong>
                                </td>
                                <td>
                                    <span class="text-stone-600 dark:text-stone-300 font-mono text-xs select-all bg-stone-100 dark:bg-white/5 px-2 py-1 rounded border border-stone-200 dark:border-white/10">
                                        {{ $sp->transaction_reference ?: 'Cash Counter' }}
                                    </span>
                                </td>
                                <td>
                                    @if($sp->status === 'APPROVED')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-sans font-bold uppercase bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            <span>Approved</span>
                                        </span>
                                    @elseif($sp->status === 'REJECTED')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-sans font-bold uppercase bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            <span>Rejected</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-sans font-bold uppercase bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            <span>Pending</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    @if($sp->status === 'PENDING')
                                        <div class="flex flex-wrap items-center justify-end gap-2 font-sans">
                                            <button wire:click="approveSubscriptionPayment('{{ $sp->id }}')" class="tbtn tbtn-green">
                                                Approve
                                            </button>
                                            <button wire:click="rejectSubscriptionPayment('{{ $sp->id }}')" class="tbtn tbtn-red">
                                                Reject
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-stone-400 text-xs font-mono">Processed</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-10 text-center text-stone-400 font-sans">
                                    No offline subscription payment receipts submitted yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         TAB 4: HALLS CATALOG (Full CRUD)
         =================================================================== -->
    @if($activeTab === 'halls')
        <div class="space-y-4">
            <div class="bg-white dark:bg-[#1A1714] border border-[#E7E5E4] dark:border-[#C6A15B]/25 rounded-2xl p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-colors">
                <div>
                    <div class="inline-flex items-center gap-2 text-[10px] font-sans font-bold uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B]">
                        <span>Venue Inventory</span>
                        <span>•</span>
                        <span>Verified Halls &amp; Marquees</span>
                    </div>
                    <h3 class="font-serif-luxury text-2xl sm:text-3xl text-[#171513] dark:text-white font-normal mt-1">
                        Platform Halls &amp; Marquees Directory
                    </h3>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-1">
                        Manage public visibility, edit shift pricing, and audit all listed banquet spaces.
                    </p>
                </div>
                <button wire:click="openProvisionModal" class="btn-gold !h-10 !px-5 text-xs font-bold font-sans tracking-wide rounded-lg shadow-sm transition-all duration-200 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    <span>Register Hall</span>
                </button>
            </div>

            <div tabindex="0" role="region" aria-label="Platform Halls Directory" class="table-wrapper-enterprise">
                <table class="table-enterprise">
                    <thead>
                        <tr>
                            <th>Hall / Venue Complex</th>
                            <th>City</th>
                            <th>Capacity Specs</th>
                            <th>Shift Base Rent</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($halls as $hl)
                            @php
                                $thumb = !empty($hl->cover_image) ? $hl->cover_image : ($hl->images[0] ?? 'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=200&auto=format&fit=crop');
                            @endphp
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $thumb }}" alt="{{ $hl->name }}" class="w-11 h-11 rounded-xl object-cover border border-[#C6A15B]/30 shadow-xs shrink-0">
                                        <div>
                                            <div class="font-bold text-[#171513] dark:text-white text-sm">{{ $hl->name }}</div>
                                            <span class="text-[11px] text-stone-500 dark:text-stone-400 font-sans block">{{ $hl->tenant->name ?? 'Venue Complex' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-[#C6A15B]/15 text-[#8F7239] dark:text-[#F2DE9A] border border-[#C6A15B]/30">
                                        {{ $hl->venue->city ?? 'Lahore' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-xs text-stone-700 dark:text-stone-300 font-sans font-medium">
                                        {{ $hl->min_capacity }} - {{ $hl->max_capacity }} Heads
                                    </span>
                                </td>
                                <td>
                                    <strong class="font-cinzel text-base text-[#171513] dark:text-[#C6A15B]">
                                        PKR {{ number_format($hl->base_rent) }}
                                    </strong>
                                </td>
                                <td>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-[10px] font-sans font-bold uppercase rounded-full {{ $hl->is_active ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30' : 'bg-stone-200/60 dark:bg-stone-800 text-stone-600 dark:text-stone-400 border border-stone-300 dark:border-stone-700' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $hl->is_active ? 'bg-emerald-500 animate-pulse' : 'bg-stone-400' }}"></span>
                                        <span>{{ $hl->is_active ? 'Published' : 'Hidden' }}</span>
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="flex flex-wrap items-center justify-end gap-1.5 font-sans text-xs">
                                         <a href="{{ route('hall.showcase', $hl->slug) }}" target="_blank" class="tbtn tbtn-outline">
                                             View ↗
                                         </a>
                                         <button wire:click="openEditHallModal('{{ $hl->id }}')" class="tbtn tbtn-gold">
                                             Edit
                                         </button>
                                         <button wire:click="toggleHallStatus('{{ $hl->id }}')" class="tbtn {{ $hl->is_active ? 'tbtn-amber' : 'tbtn-green' }}">
                                             {{ $hl->is_active ? 'Hide' : 'Publish' }}
                                         </button>
                                         <button wire:click="deleteHall('{{ $hl->id }}')"
                                                 onclick="return confirm('Delete hall {{ $hl->name }}?') || event.stopImmediatePropagation()"
                                                 class="tbtn tbtn-red">
                                             Del
                                         </button>
                                     </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         TAB 5: ALL PLATFORM BOOKINGS (CRUD & Status)
         =================================================================== -->
    @if($activeTab === 'bookings')
        <div class="space-y-4">
            <!-- Header Card with Live Metrics & Controls -->
            <div class="bg-white dark:bg-[#1A1714] border border-[#E7E5E4] dark:border-[#C6A15B]/25 rounded-2xl p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-colors">
                <div>
                    <div class="inline-flex items-center gap-2 text-[10px] font-sans font-bold uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B]">
                        <span>Platform Booking Flow</span>
                        <span>•</span>
                        <span>Zero Overbooking Concurrency</span>
                    </div>
                    <h3 class="font-serif-luxury text-2xl sm:text-3xl text-[#171513] dark:text-white font-normal mt-1">
                        Functions &amp; Banquets Directory
                    </h3>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-1">
                        Displaying {{ $totalBookings }} reservations with shift occupancy, guest volume, and legal contracts.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-3.5 py-1.5 rounded-xl bg-stone-100 dark:bg-white/5 border border-stone-200 dark:border-white/10 text-xs font-mono font-semibold text-stone-700 dark:text-stone-300">
                        Total Volume: <strong class="text-[#8F7239] dark:text-[#C6A15B]">PKR {{ number_format($totalGmv) }}</strong>
                    </span>
                </div>
            </div>

            <!-- Responsive Enterprise Table Container -->
            <div tabindex="0" role="region" aria-label="Platform Bookings Directory" class="table-wrapper-enterprise">
                <table class="table-enterprise">
                    <thead>
                        <tr>
                            <th>Booking Ref &amp; Date</th>
                            <th>Customer Host</th>
                            <th>Hall &amp; Complex</th>
                            <th>Shift &amp; Headcount</th>
                            <th>Grand Total</th>
                            <th>Status</th>
                            <th class="text-right">Legal Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentBookings as $b)
                            @php
                                $cleanPhone = preg_replace('/[^0-9]/', '', $b->customer_phone);
                            @endphp
                            <tr>
                                <!-- Ref & Date -->
                                <td>
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#C6A15B]/15 text-[#8F7239] dark:text-[#F2DE9A] border border-[#C6A15B]/30 font-mono text-[11px] font-bold">
                                        {{ $b->booking_reference }}
                                    </div>
                                    <div class="flex items-center gap-1.5 text-xs text-stone-600 dark:text-stone-300 font-sans font-medium mt-1.5">
                                        <svg class="w-3.5 h-3.5 text-[#C6A15B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span>{{ $b->event_date->format('d M Y') }}</span>
                                        <span class="text-[10px] text-stone-400 font-mono">({{ $b->event_date->format('D') }})</span>
                                    </div>
                                </td>

                                <!-- Customer Host -->
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[#C6A15B]/30 to-[#171513] text-[#F2DE9A] border border-[#C6A15B]/40 flex items-center justify-center font-bold text-xs uppercase font-cinzel shrink-0 shadow-sm">
                                            {{ substr($b->customer_name, 0, 2) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-bold text-sm text-[#171513] dark:text-white truncate">
                                                {{ $b->customer_name }}
                                            </div>
                                            @if($b->customer_phone)
                                                <a href="https://wa.me/{{ $cleanPhone }}?text=Hello%20{{ urlencode($b->customer_name) }},%20regarding%20your%20Riwaj%20Grand%20reservation%20{{ $b->booking_reference }}" target="_blank"
                                                   class="inline-flex items-center gap-1 text-[11px] text-stone-500 dark:text-stone-400 hover:text-emerald-500 font-mono transition mt-0.5">
                                                    <span class="text-emerald-500">💬</span>
                                                    <span>{{ $b->customer_phone }}</span>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- Hall & Complex -->
                                <td>
                                    <div class="font-serif-luxury text-sm font-medium text-[#171513] dark:text-[#F8F5EF]">
                                        {{ $b->hall->name ?? 'Grand Ballroom' }}
                                    </div>
                                    <div class="flex items-center gap-1 text-[11px] text-stone-500 dark:text-stone-400 font-sans mt-0.5">
                                        <svg class="w-3 h-3 text-[#C6A15B]" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                                        <span>{{ $b->tenant->name ?? 'Venue Partner' }}</span>
                                    </div>
                                </td>

                                <!-- Shift & Heads -->
                                <td>
                                    <div class="flex items-center gap-1.5">
                                        @if($b->slot_type === 'EVENING')
                                            <span class="px-2.5 py-1 rounded-md text-[10px] font-sans font-bold uppercase tracking-wider bg-[#C6A15B]/15 text-[#8F7239] dark:text-[#F2DE9A] border border-[#C6A15B]/30 flex items-center gap-1">
                                                <span>🌄</span>
                                                <span>Evening Dinner</span>
                                            </span>
                                        @elseif($b->slot_type === 'AFTERNOON')
                                            <span class="px-2.5 py-1 rounded-md text-[10px] font-sans font-bold uppercase tracking-wider bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30 flex items-center gap-1">
                                                <span>☀️</span>
                                                <span>Lunch</span>
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-md text-[10px] font-sans font-bold uppercase tracking-wider bg-purple-500/15 text-purple-600 dark:text-purple-400 border border-purple-500/30 flex items-center gap-1">
                                                <span>🌙</span>
                                                <span>Mehndi Night</span>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-stone-500 dark:text-stone-400 font-sans mt-1 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <strong class="text-stone-700 dark:text-stone-200">{{ number_format($b->guest_count) }}</strong> Heads
                                    </div>
                                </td>

                                <!-- Grand Total -->
                                <td>
                                    <div class="font-cinzel text-base font-bold text-[#171513] dark:text-[#C6A15B]">
                                        PKR {{ number_format($b->grand_total) }}
                                    </div>
                                    <span class="text-[10px] text-stone-500 dark:text-stone-400 font-mono block mt-0.5">
                                        Adv: PKR {{ number_format($b->advance_paid ?? 0) }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td>
                                    @if($b->status === 'CONFIRMED')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-sans font-bold uppercase tracking-wider bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            <span>Confirmed</span>
                                        </span>
                                    @elseif($b->status === 'COMPLETED')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-sans font-bold uppercase tracking-wider bg-stone-100 dark:bg-white/10 text-stone-700 dark:text-stone-300 border border-stone-200 dark:border-white/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-stone-400"></span>
                                            <span>Completed</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-sans font-bold uppercase tracking-wider bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            <span>{{ $b->status }}</span>
                                        </span>
                                    @endif
                                </td>

                                <!-- Legal Actions -->
                                <td class="text-right">
                                    <div class="flex flex-wrap items-center justify-end gap-2 font-sans">
                                        <a href="{{ route('documents.agreement', $b->id) }}" target="_blank" class="tbtn tbtn-outline">
                                            <span>A4 Agreement</span>
                                            <span>↗</span>
                                        </a>
                                        <a href="{{ route('documents.invoice', $b->id) }}" target="_blank" class="tbtn tbtn-outline">
                                            <span>Tax Invoice</span>
                                            <span>↗</span>
                                        </a>
                                        @if($b->status !== 'CONFIRMED')
                                            <button wire:click="updateBookingStatus('{{ $b->id }}', 'CONFIRMED')" class="tbtn tbtn-gold">
                                                Confirm
                                            </button>
                                        @endif
                                        <button wire:click="deleteBooking('{{ $b->id }}')"
                                                onclick="return confirm('Delete booking {{ $b->booking_reference }}?') || event.stopImmediatePropagation()"
                                                class="tbtn tbtn-red">
                                            Del
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-10 text-center text-stone-400 font-sans">
                                    No reservations placed on the platform yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         TAB 6: SECURITY AUDIT LOG
         =================================================================== -->
    @if($activeTab === 'audit')
        <div class="space-y-4">
            <div class="bg-white dark:bg-[#1A1714] border border-[#E7E5E4] dark:border-[#C6A15B]/25 rounded-2xl p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-colors">
                <div>
                    <div class="inline-flex items-center gap-2 text-[10px] font-sans font-bold uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B]">
                        <span>Security Audit Trail</span>
                        <span>•</span>
                        <span>Immutable Platform History</span>
                    </div>
                    <h3 class="font-serif-luxury text-2xl sm:text-3xl text-[#171513] dark:text-white font-normal mt-1">
                        System Security &amp; Concurrency Audit Trail
                    </h3>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-1">Immutable record of tenant registrations, payment approvals, and administrative events.</p>
                </div>
            </div>

            <div tabindex="0" role="region" aria-label="Security Audit Log Table" class="table-wrapper-enterprise">
                <table class="table-enterprise">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Action</th>
                            <th>User / IP Address</th>
                            <th>Tenant Context</th>
                            <th>Audit Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($auditLogs as $log)
                            <tr>
                                <td>
                                    <span class="text-stone-500 dark:text-stone-400 font-mono text-xs">
                                        {{ $log->created_at->format('d M Y H:i:s') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="px-2.5 py-0.5 rounded-md bg-[#C6A15B]/15 text-[#8F7239] dark:text-[#F2DE9A] border border-[#C6A15B]/30 font-bold font-mono text-[10px] uppercase">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td>
                                    <div class="font-bold text-xs text-[#171513] dark:text-white">{{ $log->user->name ?? 'System' }}</div>
                                    <span class="text-[10px] text-stone-400 font-mono">IP: {{ $log->ip_address ?? '127.0.0.1' }}</span>
                                </td>
                                <td>
                                    <span class="font-medium text-xs text-stone-700 dark:text-stone-300">
                                        {{ $log->tenant->name ?? 'Platform Global' }}
                                    </span>
                                </td>
                                <td>
                                    <p class="text-stone-700 dark:text-stone-300 font-sans text-xs max-w-md">
                                        {{ $log->description }}
                                    </p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-10 text-center text-stone-400 font-sans">
                                    No audit records logged yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         TAB 7: WEBSITE ANALYTICS (Super Admin Platform Insights)
         =================================================================== -->
    <!-- ===================================================================
         TAB: USERS DIRECTORY (Platform Accounts & Access Control)
         =================================================================== -->
    @if($activeTab === 'users')
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 space-y-6 shadow-sm dark:shadow-xl">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-[#E7E5E4] dark:border-white/10 gap-4">
                <div>
                    <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-normal">
                        Platform User Directory &amp; Access Control
                    </h3>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-0.5">Manage customer accounts, hall vendors, staff permissions, and account status (Active, Pending, Suspended).</p>
                </div>
            </div>

            <!-- User Metric Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm space-y-1">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 font-mono block">Total Accounts</span>
                    <strong class="font-cinzel text-2xl text-[#171513] dark:text-white">{{ $totalRegisteredUsers }}</strong>
                    <span class="text-[11px] text-stone-400 block font-sans">All Users</span>
                </div>
                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm space-y-1">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 font-mono block">Customers</span>
                    <strong class="font-cinzel text-2xl text-[#8F7239] dark:text-[#C6A15B]">{{ $totalCustomers }}</strong>
                    <span class="text-[11px] text-stone-400 block font-sans">Event Organizers</span>
                </div>
                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm space-y-1">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 font-mono block">Hall Vendors</span>
                    <strong class="font-cinzel text-2xl text-[#171513] dark:text-white">{{ $totalVendors }}</strong>
                    <span class="text-[11px] text-stone-400 block font-sans">Marquee Owners</span>
                </div>
                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm space-y-1">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 font-mono block">Hall Staff</span>
                    <strong class="font-cinzel text-2xl text-[#171513] dark:text-white">{{ $totalStaff }}</strong>
                    <span class="text-[11px] text-stone-400 block font-sans">Managers &amp; KOT</span>
                </div>
                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm space-y-1">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 font-mono block">Suspended Accounts</span>
                    <strong class="font-cinzel text-2xl text-stone-700 dark:text-stone-300">{{ $totalSuspendedUsers }}</strong>
                    <span class="text-[11px] text-stone-400 block font-sans">Restricted Access</span>
                </div>
            </div>

            <!-- Search & Filters -->
            <div class="flex flex-col sm:flex-row items-center gap-3 bg-[#F8F5EF] dark:bg-white/5 p-4 border border-[#E7E5E4] dark:border-white/10">
                <div class="w-full sm:w-1/2">
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-stone-600 dark:text-stone-300 font-bold mb-1">Search User Directory</label>
                    <input type="text" wire:model.live.debounce.300ms="userSearch" placeholder="Search by name, email, or phone..."
                           class="w-full bg-white dark:bg-[#171513] text-[#171513] dark:text-white border border-[#E7E5E4] dark:border-white/20 text-xs px-3.5 py-2 font-mono outline-none focus:border-[#C6A15B]">
                </div>
                <div class="w-full sm:w-1/4">
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-stone-600 dark:text-stone-300 font-bold mb-1">Role Filter</label>
                    <select wire:model.live="userRoleFilter" class="w-full bg-white dark:bg-[#171513] text-[#171513] dark:text-white border border-[#E7E5E4] dark:border-white/20 text-xs px-3 py-2 font-mono outline-none focus:border-[#C6A15B]">
                        <option value="ALL">All Roles</option>
                        <option value="ROLE_CUSTOMER">Customer / Client</option>
                        <option value="ROLE_TENANT_OWNER">Hall Owner</option>
                        <option value="ROLE_MANAGER">Hall Manager</option>
                        <option value="ROLE_ACCOUNTANT">Accountant</option>
                        <option value="ROLE_FRONT_DESK">Front Desk</option>
                        @if(config('platform.kitchen_module_enabled'))
                            <option value="ROLE_CHEF">Chef</option>
                        @endif
                        <option value="ROLE_FLOOR_STAFF">Floor Staff</option>
                        <option value="ROLE_SUPER_ADMIN">Super Admin</option>
                    </select>
                </div>
                <div class="w-full sm:w-1/4">
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-stone-600 dark:text-stone-300 font-bold mb-1">Account Status</label>
                    <select wire:model.live="userStatusFilter" class="w-full bg-white dark:bg-[#171513] text-[#171513] dark:text-white border border-[#E7E5E4] dark:border-white/20 text-xs px-3 py-2 font-mono outline-none focus:border-[#C6A15B]">
                        <option value="ALL">All Statuses</option>
                        <option value="ACTIVE">Active</option>
                        <option value="PENDING">Pending</option>
                        <option value="SUSPENDED">Suspended</option>
                    </select>
                </div>
            </div>

            <!-- Users Table (Enterprise Luxury Standard) -->
            <div class="table-wrapper-enterprise" tabindex="0" role="region" aria-label="Platform Users Ledger">
                <table class="table-enterprise">
                    <thead>
                        <tr>
                            <th>User Profile</th>
                            <th>Platform Role</th>
                            <th>Organization / Venue</th>
                            <th>Registered On</th>
                            <th>Last Active</th>
                            <th>Account Status</th>
                            <th class="text-right">Administration</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $u)
                            <tr class="{{ $u->status === 'SUSPENDED' ? 'opacity-70 bg-stone-100/40 dark:bg-white/[0.02]' : '' }}">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#171513] to-[#2A241E] text-[#DCC796] border border-[#C6A15B]/50 flex items-center justify-center font-bold text-xs uppercase font-cinzel shrink-0 shadow-sm ring-2 ring-[#C6A15B]/20">
                                            {{ substr($u->name, 0, 2) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-bold text-[#171513] dark:text-white text-sm truncate">{{ $u->name }}</div>
                                            <span class="text-[11px] text-stone-500 dark:text-stone-400 font-mono block truncate select-all">{{ $u->email }}</span>
                                            @if($u->phone)
                                                <span class="text-[10px] text-stone-400 dark:text-stone-500 font-mono block">{{ $u->phone }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($u->isSuperAdmin())
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-sans font-bold uppercase bg-[#171513] text-[#F2DE9A] border border-[#C6A15B]/60 shadow-sm">
                                            <span class="text-[#C6A15B]">★</span>
                                            <span>Super Admin</span>
                                        </span>
                                    @elseif(in_array($u->role, ['VENDOR', 'HALL_OWNER', 'ADMIN']))
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-sans font-bold uppercase bg-[#C6A15B]/15 text-[#8F7239] dark:text-[#F2DE9A] border border-[#C6A15B]/40">
                                            <span>🏛️</span>
                                            <span>Hall Vendor</span>
                                        </span>
                                    @elseif($u->role === 'CUSTOMER')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-sans font-bold uppercase bg-stone-100 dark:bg-white/10 text-stone-700 dark:text-stone-300 border border-stone-200 dark:border-white/15">
                                            <span>👤</span>
                                            <span>Customer</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-sans font-bold uppercase bg-stone-100 dark:bg-white/10 text-stone-700 dark:text-stone-300 border border-stone-200 dark:border-white/15">
                                            <span>{{ $u->role }}</span>
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="font-sans font-medium text-stone-800 dark:text-stone-200">
                                        {{ $u->tenant->name ?? 'Platform Marketplace' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="font-mono text-stone-600 dark:text-stone-400 text-xs">
                                        {{ $u->created_at->format('d M Y') }}
                                    </span>
                                </td>
                                <td>
                                    @if($u->last_login_at)
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            <span class="text-stone-800 dark:text-stone-200 font-sans font-medium text-xs">{{ $u->last_login_at->diffForHumans() }}</span>
                                        </div>
                                        <span class="text-[10px] text-stone-400 dark:text-stone-500 font-mono block pl-3">{{ $u->last_login_at->format('d M, H:i') }}</span>
                                    @else
                                        <span class="text-stone-400 dark:text-stone-500 italic text-xs">Never signed in</span>
                                    @endif
                                </td>
                                <td>
                                    @if($u->status === 'ACTIVE')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-sans font-bold uppercase bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            <span>Active</span>
                                        </span>
                                    @elseif($u->status === 'SUSPENDED')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-sans font-bold uppercase bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30">
                                            <span>Suspended</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-sans font-bold uppercase bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30">
                                            <span>Pending</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if(!$u->isSuperAdmin() || $u->id !== auth()->id())
                                            @if($u->status === 'SUSPENDED')
                                                <button wire:click="toggleUserStatus({{ $u->id }})" class="tbtn tbtn-green" title="Activate Account">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    <span>Activate</span>
                                                </button>
                                            @else
                                                <button wire:click="toggleUserStatus({{ $u->id }})" wire:confirm="Are you sure you want to suspend this user account?" class="tbtn tbtn-amber" title="Suspend Account">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                    <span>Suspend</span>
                                                </button>
                                            @endif
                                            <button wire:click="resetUserPassword({{ $u->id }})" wire:confirm="Reset the password for {{ $u->name }}? A new random password will be generated and shown once." class="tbtn tbtn-outline" title="Reset User Password">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                                <span>Reset Pwd</span>
                                            </button>
                                        @else
                                            <span class="text-[10px] text-stone-400 dark:text-stone-500 italic font-sans">Self Admin</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-10 text-center text-stone-400 font-sans">
                                    No registered platform accounts match your search criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($activeTab === 'analytics')
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-6 space-y-6 shadow-sm dark:shadow-xl">
            <div class="border-b border-[#E7E5E4] pb-4 flex items-center justify-between">
                <div>
                    <h3 class="font-serif text-2xl text-[#171513] font-normal">
                        Platform Website Analytics
                    </h3>
                    <p class="text-xs text-stone-500 mt-0.5">Track visitor traffic, hall views, wishlists, and user engagement across the marketplace.</p>
                </div>
            </div>

            <!-- Analytics Metric Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="bg-[#171513] border border-[#C6A15B] p-5 shadow-xl space-y-1 relative overflow-hidden group">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-[#C6A15B] animate-ping"></span>
                        <span class="text-[10px] uppercase font-bold tracking-wider text-[#C6A15B] block font-mono">Live Sessions</span>
                    </div>
                    <strong class="font-cinzel text-2xl text-white block">{{ $activeSessionsCount }}</strong>
                    <span class="text-[10px] text-stone-300 block font-sans">Active (15 min)</span>
                </div>

                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl space-y-1 relative overflow-hidden group hover:border-[#C6A15B] transition">
                    <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
                    <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 block font-mono">Unique Visitors</span>
                    <strong class="font-cinzel text-2xl text-[#171513] dark:text-white">{{ $uniqueVisitorsCount }}</strong>
                    <span class="text-[11px] text-stone-400 block font-sans">Distinct IPs</span>
                </div>

                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl space-y-1 relative overflow-hidden group hover:border-[#C6A15B] transition">
                    <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
                    <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 block font-mono">Total Page Views</span>
                    <strong class="font-cinzel text-2xl text-[#171513] dark:text-white">{{ $totalPageVisits }}</strong>
                    <span class="text-[11px] text-stone-400 block font-sans">Marketplace Hits</span>
                </div>

                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl space-y-1 relative overflow-hidden group hover:border-[#C6A15B] transition">
                    <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
                    <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 block font-mono">Hall Detail Views</span>
                    <strong class="font-cinzel text-2xl text-[#171513] dark:text-white">{{ $totalHallViews }}</strong>
                    <span class="text-[11px] text-stone-400 block font-sans">Hall Page Views</span>
                </div>

                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl space-y-1 relative overflow-hidden group hover:border-[#C6A15B] transition">
                    <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
                    <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 block font-mono">Wishlists Saved</span>
                    <strong class="font-cinzel text-2xl text-[#171513] dark:text-white">{{ $totalWishlists }}</strong>
                    <span class="text-[11px] text-stone-400 block font-sans">Guest Shortlists</span>
                </div>

                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl space-y-1 relative overflow-hidden group hover:border-[#C6A15B] transition">
                    <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
                    <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 block font-mono">Registered Users</span>
                    <strong class="font-cinzel text-2xl text-[#171513] dark:text-white">{{ $totalRegisteredUsers }}</strong>
                    <span class="text-[11px] text-stone-400 block font-sans">Customer Accounts</span>
                </div>

                <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-5 shadow-sm dark:shadow-xl space-y-1 relative overflow-hidden group">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-[#8F7239] dark:text-[#C6A15B] block font-mono">Today's Visits</span>
                    <strong class="font-cinzel text-xl text-[#171513] dark:text-[#C6A15B] block font-bold">{{ $todayVisits }}</strong>
                    <span class="text-[10px] text-stone-500 dark:text-stone-400 block font-mono">Last 24 Hours</span>
                </div>
            </div>

            <!-- Top Halls by Views and Wishlists -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 shadow-sm dark:shadow-xl space-y-6">
                    <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-4">
                        <span class="text-[10px] font-mono uppercase tracking-wider text-[#8F7239] font-bold block">Most Viewed Halls</span>
                        <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold mt-0.5">Top Venues by Traffic</h3>
                    </div>
                    <div class="space-y-4">
                        @forelse($topHallsByViews as $hall)
                            <div class="flex items-center justify-between p-3 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-full bg-[#C6A15B]/20 flex items-center justify-center text-[#8F7239] text-xs font-bold">{{ $loop->iteration }}</span>
                                    <div>
                                        <div class="font-bold text-[#171513] dark:text-white text-sm">{{ $hall->name }}</div>
                                        <span class="text-[10px] text-stone-500 font-mono">{{ $hall->venue->city ?? 'Pakistan' }}</span>
                                    </div>
                                </div>
                                <span class="font-cinzel text-lg text-[#171513] dark:text-[#C6A15B] font-bold">{{ $hall->total_views }} Views</span>
                            </div>
                        @empty
                            <div class="p-8 text-center text-stone-400 text-xs font-mono">No hall views recorded yet.</div>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 shadow-sm dark:shadow-xl space-y-6">
                    <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-4">
                        <span class="text-[10px] font-mono uppercase tracking-wider text-[#8F7239] font-bold block">Most Wishlisted Halls</span>
                        <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold mt-0.5">Guest Favorites</h3>
                    </div>
                    <div class="space-y-4">
                        @forelse($topHallsByWishlists as $hall)
                            <div class="flex items-center justify-between p-3 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-full bg-[#C6A15B]/20 flex items-center justify-center text-[#8F7239] text-xs font-bold">{{ $loop->iteration }}</span>
                                    <div>
                                        <div class="font-bold text-[#171513] dark:text-white text-sm">{{ $hall->name }}</div>
                                        <span class="text-[10px] text-stone-500 font-mono">{{ $hall->venue->city ?? 'Pakistan' }}</span>
                                    </div>
                                </div>
                                <span class="font-cinzel text-lg text-[#171513] dark:text-[#C6A15B] font-bold">{{ $hall->total_wishlists }} Saved</span>
                            </div>
                        @empty
                            <div class="p-8 text-center text-stone-400 text-xs font-mono">No wishlists recorded yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Recent Visitor Activity -->
            <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 shadow-sm dark:shadow-xl">
                <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-4 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono uppercase tracking-wider text-stone-500 dark:text-stone-400 block font-bold">Recent Visitor Activity</span>
                        <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold mt-0.5">Live Traffic Feed</h3>
                    </div>
                </div>
                <div class="table-wrapper-enterprise" tabindex="0" role="region" aria-label="Live Traffic Feed">
                    <table class="table-enterprise">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Page Endpoint</th>
                                <th>Visitor / Identity</th>
                                <th>Client IP</th>
                                <th class="text-right">Device Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentVisits as $visit)
                                <tr>
                                    <td>
                                        <span class="font-mono text-stone-500 dark:text-stone-400 text-xs">{{ $visit->visited_at->format('d M Y, H:i:s') }}</span>
                                    </td>
                                    <td>
                                        <code class="px-2 py-0.5 rounded bg-stone-100 dark:bg-white/5 border border-stone-200 dark:border-white/10 text-xs font-mono text-[#8F7239] dark:text-[#F2DE9A]">{{ $visit->path }}</code>
                                    </td>
                                    <td>
                                        @if($visit->user)
                                            <div class="font-sans font-bold text-stone-900 dark:text-white text-xs">{{ $visit->user->name }}</div>
                                            <div class="text-[10px] text-stone-400 font-mono select-all">{{ $visit->user->email }}</div>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-stone-500 font-sans text-xs">
                                                <span class="w-1.5 h-1.5 rounded-full bg-stone-400"></span>
                                                <span>Guest Visitor</span>
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="font-mono text-stone-600 dark:text-stone-400 text-xs">{{ $visit->ip_address ?? '-' }}</span>
                                    </td>
                                    <td class="text-right">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-sans font-bold uppercase {{ str_contains($visit->user_agent ?? '', 'Mobile') ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/30' : 'bg-stone-100 dark:bg-white/10 text-stone-600 dark:text-stone-300 border border-stone-200 dark:border-white/10' }}">
                                            <span>{{ str_contains($visit->user_agent ?? '', 'Mobile') ? '📱 Mobile' : '💻 Desktop' }}</span>
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-stone-400 font-sans">No visitor activity recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         MODAL 1: REGISTER & PROVISION NEW HALL (Super Admin Onboarding)
         =================================================================== -->
    @if($showProvisionModal)
        <div class="fixed inset-0 z-50 bg-black/85 flex items-center justify-center p-4 overflow-y-auto">
            <div class="bg-[#171513] border-2 border-[#C6A15B] max-w-2xl w-full p-6 sm:p-8 space-y-6 shadow-2xl text-white my-8">
                <div class="border-b border-white/10 pb-4 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono uppercase tracking-widest text-[#C6A15B] block font-bold">Platform Owner Tool</span>
                        <h3 class="font-serif text-2xl text-white font-normal mt-0.5">Register &amp; Provision New Wedding Hall</h3>
                    </div>
                    <button wire:click="closeProvisionModal" class="text-white/60 hover:text-white text-2xl font-mono leading-none">&times;</button>
                </div>

                <div class="space-y-4 font-sans text-xs">
                    <p class="text-stone-300">
                        Enter the hall details and set the email and password for the Rent Admin. They will use these exact credentials to log in and manage their hall's images, videos, and services catalog.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">
                                Hall / Business Name *
                            </label>
                            <input type="text" wire:model="newBusinessName" placeholder="e.g. Shehnai Royal Palace"
                                   class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B]">
                            @error('newBusinessName') <span class="text-[#F8F5EF] text-[10px] font-mono mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">
                                City *
                            </label>
                            <select wire:model="newCity" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B]">
                                <option value="Lahore">Lahore</option>
                                <option value="Karachi">Karachi</option>
                                <option value="Islamabad">Islamabad</option>
                                <option value="Rawalpindi">Rawalpindi</option>
                                <option value="Faisalabad">Faisalabad</option>
                                <option value="Multan">Multan</option>
                                <option value="Peshawar">Peshawar</option>
                                <option value="Sialkot">Sialkot</option>
                                <option value="Gujranwala">Gujranwala</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">
                                Physical Address / Area
                            </label>
                            <input type="text" wire:model="newAddress" placeholder="e.g. Main Canal Road, Near Mall of Lahore"
                                   class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B]">
                        </div>

                        <div>
                            <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">
                                Tagline
                            </label>
                            <input type="text" wire:model="newTagline" placeholder="e.g. The Pinnacle of Royal Banqueting"
                                   class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B]">
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">
                                Min Capacity *
                            </label>
                            <input type="number" wire:model="newMinCapacity" step="25"
                                   class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>

                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">
                                Max Capacity *
                            </label>
                            <input type="number" wire:model="newMaxCapacity" step="50"
                                   class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>

                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">
                                Base Shift Rent (PKR) *
                            </label>
                            <input type="number" wire:model="newBaseRent" step="5000"
                                   class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                    </div>

                    <!-- Rent Admin Credentials Section -->
                    <div class="p-4 bg-[#171513] border border-[#C6A15B]/50 space-y-3">
                        <span class="text-[10px] font-mono uppercase tracking-widest text-[#C6A15B] block font-bold">
                            Rent Admin Login Credentials (To Hand Over)
                        </span>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-mono uppercase tracking-wider text-stone-300 mb-1">
                                    Rent Admin Full Name *
                                </label>
                                <input type="text" wire:model="newOwnerName" placeholder="e.g. Malik Jahangir"
                                       class="w-full bg-[#171513] border border-white/15 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                                @error('newOwnerName') <span class="text-[#F8F5EF] text-[10px] font-mono mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-[10px] font-mono uppercase tracking-wider text-stone-300 mb-1">
                                    WhatsApp / Phone *
                                </label>
                                <input type="text" wire:model="newOwnerPhone" placeholder="e.g. +92 300 1234567"
                                       class="w-full bg-[#171513] border border-white/15 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                                @error('newOwnerPhone') <span class="text-[#F8F5EF] text-[10px] font-mono mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-mono uppercase tracking-wider text-stone-300 mb-1">
                                    Login Email *
                                </label>
                                <input type="email" wire:model="newOwnerEmail" placeholder="e.g. owner@shehnaipalace.pk"
                                       class="w-full bg-[#171513] border border-white/15 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                                @error('newOwnerEmail') <span class="text-[#F8F5EF] text-[10px] font-mono mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-[10px] font-mono uppercase tracking-wider text-stone-300 mb-1">
                                    Password to Set *
                                </label>
                                <input type="text" wire:model="newOwnerPassword" placeholder="e.g. Shehnai2026!"
                                       class="w-full bg-[#171513] border border-white/15 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B] font-mono">
                                @error('newOwnerPassword') <span class="text-[#F8F5EF] text-[10px] font-mono mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        @if($billingEnabled)
                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-stone-300 mb-1">
                                Initial SaaS Subscription Tier
                            </label>
                            <select wire:model="newPlanId" class="w-full bg-[#171513] border border-white/15 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                                @foreach($plans as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }} — PKR {{ number_format($p->price_monthly) }}/mo</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="border-t border-white/10 pt-4 flex items-center justify-end gap-3">
                    <button wire:click="closeProvisionModal" class="px-4 py-2 border border-white/20 text-white text-xs font-mono uppercase hover:bg-white/10 transition">
                        Cancel
                    </button>
                    <button wire:click="provisionHallAccount" class="btn-gold !h-9 !px-5 text-xs font-bold font-sans tracking-wide rounded-lg shadow-sm transition-all duration-200">
                        <span>Provision Hall &amp; Generate Credentials &rarr;</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         MODAL 2: EDIT VENDOR DETAILS
         =================================================================== -->
    @if($showEditVendorModal)
        <div class="fixed inset-0 z-50 bg-black/85 flex items-center justify-center p-4 overflow-y-auto">
            <div class="bg-[#171513] border-2 border-[#C6A15B] max-w-lg w-full p-6 sm:p-8 space-y-6 shadow-2xl text-white my-8">
                <div class="border-b border-white/10 pb-4 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono uppercase tracking-widest text-[#C6A15B] block font-bold">Vendor Record Edit</span>
                        <h3 class="font-serif text-2xl text-white font-normal mt-0.5">Edit Business Details</h3>
                    </div>
                    <button wire:click="closeEditVendorModal" class="text-white/60 hover:text-white text-2xl font-mono leading-none">&times;</button>
                </div>

                <div class="space-y-4 font-sans text-xs">
                    <div>
                        <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Business / Venue Name *</label>
                        <input type="text" wire:model="editVendorName" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B]">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">City</label>
                            <input type="text" wire:model="editVendorCity" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B]">
                        </div>
                        <div>
                            <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Phone / WhatsApp</label>
                            <input type="text" wire:model="editVendorPhone" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B]">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Login / Notification Email</label>
                        <input type="email" wire:model="editVendorEmail" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B]">
                    </div>

                    @if($billingEnabled)
                    <div>
                        <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Assigned Plan</label>
                        <select wire:model="editVendorPlanId" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B]">
                            @foreach($plans as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Subscription Expiry Date</label>
                        <input type="date" wire:model="editVendorExpiresAt" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B]">
                    </div>
                    @endif
                </div>

                <div class="border-t border-white/10 pt-4 flex items-center justify-end gap-3 font-mono">
                    <button wire:click="closeEditVendorModal" class="px-4 py-2 border border-white/20 text-white text-xs uppercase">Cancel</button>
                    <button wire:click="updateVendor" class="btn-gold !h-9 !px-5 text-xs font-bold font-sans tracking-wide rounded-lg shadow-sm transition-all duration-200">
                        <span>Save Changes &rarr;</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         MODAL 3: CREATE / EDIT SAAS SUBSCRIPTION PLAN
         =================================================================== -->
    @if($showPlanModal && config('platform.billing_enabled'))
        <div class="fixed inset-0 z-50 bg-black/85 flex items-center justify-center p-4 overflow-y-auto">
            <div class="bg-[#171513] border-2 border-[#C6A15B] max-w-lg w-full p-6 sm:p-8 space-y-6 shadow-2xl text-white my-8">
                <div class="border-b border-white/10 pb-4 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono uppercase tracking-widest text-[#C6A15B] block font-bold">SaaS Tier Engine</span>
                        <h3 class="font-serif text-2xl text-white font-normal mt-0.5">
                            {{ $isEditingPlan ? 'Edit SaaS Plan' : 'Create New SaaS Plan' }}
                        </h3>
                    </div>
                    <button wire:click="closePlanModal" class="text-white/60 hover:text-white text-2xl font-mono leading-none">&times;</button>
                </div>

                <div class="space-y-4 font-sans text-xs">
                    <div>
                        <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Plan Name *</label>
                        <input type="text" wire:model="planName" placeholder="e.g. Royal Marquee Pro" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B]">
                        @error('planName') <span class="text-[#F8F5EF] text-[10px] font-mono mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Weekly (PKR)</label>
                            <input type="number" wire:model="planPriceWeekly" step="500" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-2.5 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Monthly (PKR) *</label>
                            <input type="number" wire:model="planPriceMonthly" step="1000" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-2.5 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Yearly (PKR) *</label>
                            <input type="number" wire:model="planPriceYearly" step="5000" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-2.5 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Max Halls (-1 unlim)</label>
                            <input type="number" wire:model="planMaxHalls" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Max Staff (-1 unlim)</label>
                            <input type="number" wire:model="planMaxStaff" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Max Bookings/Mo</label>
                            <input type="number" wire:model="planMaxBookings" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Plan Description</label>
                        <textarea wire:model="planDescription" rows="2" class="w-full bg-[#171513] border border-white/20 text-white text-xs p-2.5 outline-none focus:border-[#C6A15B]"></textarea>
                    </div>

                    <!-- Features Switches -->
                    <div class="pt-2 border-t border-white/10 grid grid-cols-3 gap-3">
                        <label class="flex items-center gap-2 cursor-pointer font-mono text-[11px]">
                            <input type="checkbox" wire:model="planHasKot" class="accent-[#C6A15B] w-4 h-4">
                            <span>Kitchen KOT</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer font-mono text-[11px]">
                            <input type="checkbox" wire:model="planHasWhatsapp" class="accent-[#C6A15B] w-4 h-4">
                            <span>WhatsApp Bot</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer font-mono text-[11px]">
                            <input type="checkbox" wire:model="planHasAccounting" class="accent-[#C6A15B] w-4 h-4">
                            <span>Accounting</span>
                        </label>
                    </div>
                </div>

                <div class="border-t border-white/10 pt-4 flex items-center justify-end gap-3 font-mono">
                    <button wire:click="closePlanModal" class="px-4 py-2 border border-white/20 text-white text-xs uppercase">Cancel</button>
                    <button wire:click="savePlan" class="btn-gold !h-9 !px-5 text-xs font-bold font-sans tracking-wide rounded-lg shadow-sm transition-all duration-200">
                        <span>Save Plan &rarr;</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         MODAL 4: EDIT PLATFORM HALL DETAILS
         =================================================================== -->
    @if($showEditHallModal)
        <div class="fixed inset-0 z-50 bg-black/85 flex items-center justify-center p-4 overflow-y-auto">
            <div class="bg-[#171513] border-2 border-[#C6A15B] max-w-md w-full p-6 sm:p-8 space-y-6 shadow-2xl text-white my-8">
                <div class="border-b border-white/10 pb-4 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono uppercase tracking-widest text-[#C6A15B] block font-bold">Hall Registry Edit</span>
                        <h3 class="font-serif text-2xl text-white font-normal mt-0.5">Edit Banquet Hall</h3>
                    </div>
                    <button wire:click="closeEditHallModal" class="text-white/60 hover:text-white text-2xl font-mono leading-none">&times;</button>
                </div>

                <div class="space-y-4 font-sans text-xs">
                    <div>
                        <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Hall Name *</label>
                        <input type="text" wire:model="editHallName" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B]">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Min Capacity</label>
                            <input type="number" wire:model="editHallMinCapacity" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                        <div>
                            <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Max Capacity</label>
                            <input type="number" wire:model="editHallMaxCapacity" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Base Shift Rent (PKR)</label>
                        <input type="number" wire:model="editHallBaseRent" step="5000" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B] font-mono">
                    </div>

                    <div class="pt-2">
                        <label class="flex items-center gap-2 cursor-pointer font-mono text-xs">
                            <input type="checkbox" wire:model="editHallIsActive" class="accent-[#C6A15B] w-4 h-4">
                            <span>Published on Public Marketplace</span>
                        </label>
                    </div>
                </div>

                <div class="border-t border-white/10 pt-4 flex items-center justify-end gap-3 font-mono">
                    <button wire:click="closeEditHallModal" class="px-4 py-2 border border-white/20 text-white text-xs uppercase">Cancel</button>
                    <button wire:click="updateHall" class="btn-gold !h-9 !px-5 text-xs font-bold font-sans tracking-wide rounded-lg shadow-sm transition-all duration-200">
                        <span>Update Hall &rarr;</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
