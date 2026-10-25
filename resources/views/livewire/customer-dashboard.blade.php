<div class="py-10 bg-[#F8F5EF] dark:bg-[#171513] text-[#171513] dark:text-[#F8F5EF] min-h-screen transition-colors duration-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">

        <!-- ===================================================================
             1. VIP EVENT CONCIERGE HERO BANNER (Obsidian & Champagne Gold)
             =================================================================== -->
        <div class="bg-[#171513] text-white border border-[#C6A15B]/50 rounded-2xl p-6 sm:p-8 shadow-2xl relative overflow-hidden group">
            <!-- Subtle Gold Radial Vignette -->
            <div class="absolute -right-12 -bottom-12 w-56 h-56 bg-[#C6A15B]/15 rounded-full blur-3xl pointer-events-none"></div>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                <div class="space-y-2 max-w-2xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-[#C6A15B]/40 bg-[#C6A15B]/10 text-[#F2DE9A] text-[10px] font-sans uppercase tracking-widest font-bold">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#C6A15B]"></span>
                        <span>Private Event Concierge Suite</span>
                        <span>•</span>
                        <span>VIP Host Status</span>
                    </div>
                    <h1 class="font-serif-luxury text-3xl sm:text-4xl lg:text-5xl text-white font-normal leading-tight">
                        My Wedding &amp; Event Reservations
                    </h1>
                    <p class="text-xs sm:text-sm text-stone-300 font-sans leading-relaxed">
                        Welcome, <strong class="text-[#F2DE9A]">{{ auth()->user()->name }}</strong>. Access formal banquet agreements, stamped tax invoices, real-time payment receipts, and guest management tools.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3 shrink-0">
                    <a href="{{ route('public.venues') }}" class="btn-gold !h-10 !px-5 text-xs font-bold font-sans uppercase tracking-wider rounded-lg shadow-sm transition-all duration-200">
                        <span>+ Explore More Venues</span>
                    </a>
                    <a href="https://wa.me/923001234567?text={{ urlencode('Assalamu Alaikum! I am an EventHub customer (' . auth()->user()->name . ') inquiring about my wedding bookings.') }}" 
                       target="_blank" 
                       class="btn-outline-gold !h-10 !px-4 text-xs font-bold font-sans uppercase tracking-wider rounded-lg shadow-sm transition-all duration-200 flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#C6A15B]" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.771-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.698.077-1.13-.062-.272-.088-.621-.248-1.074-.446-1.92-1.054-3.16-3.042-3.256-3.17-.096-.128-.778-1.036-.778-1.975 0-.94.492-1.403.668-1.595.176-.192.384-.24.512-.24.128 0 .256.002.368.008.12.006.28-.046.438.334.16.384.544 1.328.592 1.424.048.096.08.208.016.336-.064.128-.096.208-.192.32-.096.112-.202.25-.288.336-.096.096-.197.2-.085.392.112.192.498.822 1.069 1.33.734.654 1.352.856 1.544.952.192.096.304.08.416-.048.112-.128.48-.56.608-.752.128-.192.256-.16.432-.096.176.064 1.12.528 1.312.624.192.096.32.144.368.224.048.08.048.464-.096.869z"/></svg>
                        <span>Concierge Desk</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Feedback & Status Flash Messages -->
        @if(session('message'))
            <div class="p-4 rounded-xl bg-[#171513]/90 border border-[#C6A15B]/60 text-[#C6A15B] text-xs font-semibold shadow-md flex items-center justify-between">
                <span>✓ {{ session('message') }}</span>
                <span class="text-[10px] text-stone-400 font-sans uppercase">Notification</span>
            </div>
        @endif

        @if($feedbackMessage)
            <div class="p-4 rounded-xl bg-[#171513]/90 border border-[#C6A15B]/60 text-[#C6A15B] text-xs font-semibold shadow-md flex items-center justify-between">
                <span>✓ {{ $feedbackMessage }}</span>
                <button wire:click="$set('feedbackMessage', null)" class="text-[#C6A15B] text-lg font-bold leading-none">&times;</button>
            </div>
        @endif

        <!-- ===================================================================
             2. TOP 4 LUXURY CONCIERGE METRIC CARDS
             =================================================================== -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- 1. Total Reservations -->
            <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-2xl p-6 shadow-sm hover:border-[#C6A15B]/60 transition duration-200 flex flex-col justify-between">
                <div>
                    <span class="text-[10px] uppercase font-bold tracking-widest text-[#8F7239] dark:text-[#C6A15B] block font-sans">
                        Confirmed Functions
                    </span>
                    <div class="mt-3">
                        <strong class="font-cinzel text-3xl sm:text-4xl text-[#171513] dark:text-white font-bold">
                            {{ $bookings->count() }}
                        </strong>
                        <p class="text-xs text-stone-500 dark:text-stone-400 font-sans mt-1">
                            Reserved banquet shifts on EventHub.
                        </p>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-stone-100 dark:border-white/10 flex items-center justify-between text-xs text-stone-500 dark:text-stone-400 font-sans">
                    <span>Status:</span>
                    <span class="text-[#8F7239] dark:text-[#C6A15B] font-bold">● Active Host</span>
                </div>
            </div>

            <!-- 2. Advance Tokens Cleared -->
            <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-2xl p-6 shadow-sm hover:border-[#C6A15B]/60 transition duration-200 flex flex-col justify-between">
                <div>
                    <span class="text-[10px] uppercase font-bold tracking-widest text-[#8F7239] dark:text-[#C6A15B] block font-sans">
                        Cleared Advance Tokens
                    </span>
                    <div class="mt-3">
                        <strong class="font-cinzel text-2xl sm:text-3xl text-[#8F7239] dark:text-[#C6A15B] font-bold">
                            PKR {{ number_format($totalSpent) }}
                        </strong>
                        <p class="text-xs text-stone-500 dark:text-stone-400 font-sans mt-1">
                            Secured deposits &amp; token payments.
                        </p>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-stone-100 dark:border-white/10 flex items-center justify-between text-xs text-stone-500 dark:text-stone-400 font-sans">
                    <span>Receipts:</span>
                    <span class="text-stone-700 dark:text-stone-300 font-bold">Tax Verified</span>
                </div>
            </div>

            <!-- 3. Remaining Settlement Due -->
            <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-2xl p-6 shadow-sm hover:border-[#C6A15B]/60 transition duration-200 flex flex-col justify-between">
                <div>
                    <span class="text-[10px] uppercase font-bold tracking-widest text-[#8F7239] dark:text-[#C6A15B] block font-sans">
                        Settlement Balance
                    </span>
                    <div class="mt-3">
                        <strong class="font-cinzel text-2xl sm:text-3xl text-[#171513] dark:text-white font-bold">
                            PKR {{ number_format($totalDue) }}
                        </strong>
                        <p class="text-xs text-stone-500 dark:text-stone-400 font-sans mt-1">
                            Payable 48h prior to wedding shift.
                        </p>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-stone-100 dark:border-white/10 flex items-center justify-between text-xs text-stone-500 dark:text-stone-400 font-sans">
                    <span>Clearance:</span>
                    <span class="text-stone-700 dark:text-stone-300 font-bold">Bank Transfer / Cash</span>
                </div>
            </div>

            <!-- 4. Total Honored Guests -->
            <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-2xl p-6 shadow-sm hover:border-[#C6A15B]/60 transition duration-200 flex flex-col justify-between">
                <div>
                    <span class="text-[10px] uppercase font-bold tracking-widest text-[#8F7239] dark:text-[#C6A15B] block font-sans">
                        Honored Guests
                    </span>
                    <div class="mt-3">
                        <strong class="font-cinzel text-3xl sm:text-4xl text-[#171513] dark:text-white font-bold">
                            {{ number_format($totalGuests) }}
                        </strong>
                        <p class="text-xs text-stone-500 dark:text-stone-400 font-sans mt-1">
                            Total guest heads invited across events.
                        </p>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-stone-100 dark:border-white/10 flex items-center justify-between text-xs text-stone-500 dark:text-stone-400 font-sans">
                    <span>Catering Scale:</span>
                    <span class="text-[#8F7239] dark:text-[#C6A15B] font-bold">Silver &amp; Gold Banquets</span>
                </div>
            </div>
        </div>

        <!-- ===================================================================
             3. UPCOMING EVENT MILESTONE TIMELINE (If an upcoming booking exists)
             =================================================================== -->
        @if($upcomingBooking)
            @php
                $eventDate = $upcomingBooking->event_date;
                $daysAway = (int) now()->startOfDay()->diffInDays($eventDate->copy()->startOfDay(), false);
                $hasAdvance = $upcomingBooking->advance_paid > 0;
                $hasMenu = !empty($upcomingBooking->package_id);
            @endphp
            <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/40 rounded-2xl p-6 sm:p-8 shadow-md space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E7E5E4] dark:border-white/10 pb-4">
                    <div>
                        <div class="inline-flex items-center gap-2 text-[10px] font-sans uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold">
                            <span class="w-2 h-2 rounded-full bg-[#C6A15B]"></span>
                            <span>Next Function Milestone</span>
                            <span>•</span>
                            <span>{{ $eventDate->format('l, d F Y') }}</span>
                        </div>
                        <h2 class="font-serif-luxury text-2xl sm:text-3xl text-[#171513] dark:text-white font-normal mt-1">
                            {{ $upcomingBooking->event_type }} at {{ $upcomingBooking->hall->name ?? 'Ballroom' }}
                        </h2>
                        <p class="text-xs text-stone-500 dark:text-stone-400 font-sans mt-0.5">
                            {{ $upcomingBooking->hall->venue->city ?? 'Lahore' }} • {{ $upcomingBooking->slot_type }} Shift ({{ $upcomingBooking->guest_count }} Guests)
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="px-3.5 py-1.5 rounded-full bg-[#C6A15B]/15 text-[#8F7239] dark:text-[#F2DE9A] border border-[#C6A15B]/40 font-cinzel text-xs font-bold uppercase tracking-wider">
                            @if($daysAway === 0)
                                ★ Today's Wedding Function
                            @elseif($daysAway === 1)
                                ★ Tomorrow Night
                            @elseif($daysAway > 1)
                                ★ {{ $daysAway }} Days Remaining
                            @else
                                Concluded Function
                            @endif
                        </span>
                        <a href="{{ route('documents.agreement', $upcomingBooking->id) }}" target="_blank" 
                           class="btn-outline-gold !h-9 !px-3.5 text-xs font-bold font-sans uppercase rounded-lg">
                            Contract ↗
                        </a>
                    </div>
                </div>

                <!-- 5-Stage Visual Progress Stepper -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 pt-2">
                    <!-- Stage 1: Reservation Placed -->
                    <div class="p-4 rounded-xl bg-[#F8F5EF] dark:bg-white/5 border border-[#C6A15B]/30 space-y-2">
                        <div class="flex items-center justify-between text-xs font-bold text-[#8F7239] dark:text-[#C6A15B]">
                            <span>Step 1</span>
                            <span>✓ Done</span>
                        </div>
                        <div class="font-cinzel text-sm font-bold text-[#171513] dark:text-white">Reservation Confirmed</div>
                        <p class="text-[11px] text-stone-500 dark:text-stone-400 font-sans">Shift locked exclusively in database.</p>
                    </div>

                    <!-- Stage 2: Advance Token -->
                    <div class="p-4 rounded-xl {{ $hasAdvance ? 'bg-[#F8F5EF] dark:bg-white/5 border-[#C6A15B]/30' : 'bg-white dark:bg-[#171513] border-[#E7E5E4] dark:border-white/10 opacity-70' }} border space-y-2">
                        <div class="flex items-center justify-between text-xs font-bold {{ $hasAdvance ? 'text-[#8F7239] dark:text-[#C6A15B]' : 'text-stone-400' }}">
                            <span>Step 2</span>
                            <span>{{ $hasAdvance ? '✓ Paid' : 'Pending' }}</span>
                        </div>
                        <div class="font-cinzel text-sm font-bold text-[#171513] dark:text-white">Token Deposit</div>
                        <p class="text-[11px] text-stone-500 dark:text-stone-400 font-sans">PKR {{ number_format($upcomingBooking->advance_paid) }} cleared.</p>
                    </div>

                    <!-- Stage 3: Culinary & Menu -->
                    <div class="p-4 rounded-xl {{ $hasMenu ? 'bg-[#F8F5EF] dark:bg-white/5 border-[#C6A15B]/30' : 'bg-white dark:bg-[#171513] border-[#E7E5E4] dark:border-white/10 opacity-70' }} border space-y-2">
                        <div class="flex items-center justify-between text-xs font-bold {{ $hasMenu ? 'text-[#8F7239] dark:text-[#C6A15B]' : 'text-stone-400' }}">
                            <span>Step 3</span>
                            <span>{{ $hasMenu ? '✓ Fixed' : 'Customizing' }}</span>
                        </div>
                        <div class="font-cinzel text-sm font-bold text-[#171513] dark:text-white">Mughlai Feast</div>
                        <p class="text-[11px] text-stone-500 dark:text-stone-400 font-sans">{{ $upcomingBooking->package->name ?? 'Bespoke Menu' }}</p>
                    </div>

                    <!-- Stage 4: Stage Decor & Layout -->
                    <div class="p-4 rounded-xl bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-2">
                        <div class="flex items-center justify-between text-xs font-bold text-[#8F7239] dark:text-[#C6A15B]">
                            <span>Step 4</span>
                            <span>Active</span>
                        </div>
                        <div class="font-cinzel text-sm font-bold text-[#171513] dark:text-white">Floor &amp; Stage Decor</div>
                        <p class="text-[11px] text-stone-500 dark:text-stone-400 font-sans">Layout arranged by hall team.</p>
                    </div>

                    <!-- Stage 5: Execution -->
                    <div class="p-4 rounded-xl bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-2">
                        <div class="flex items-center justify-between text-xs font-bold text-stone-400">
                            <span>Step 5</span>
                            <span>{{ $eventDate->format('d M') }}</span>
                        </div>
                        <div class="font-cinzel text-sm font-bold text-[#171513] dark:text-white">Grand Celebration</div>
                        <p class="text-[11px] text-stone-500 dark:text-stone-400 font-sans">Shift handover &amp; reception.</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- ===================================================================
             4. OPULENT BOOKINGS TABLE & LEGAL ACTION SUITE
             =================================================================== -->
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-2xl p-6 sm:p-8 space-y-6 shadow-sm dark:shadow-xl transition-colors">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E7E5E4] dark:border-white/10 pb-4">
                <div>
                    <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-normal">
                        All Reservation Records &amp; Digital Contracts
                    </h3>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-0.5">
                        Download formal A4 banquet agreements, tax receipts, and access direct concierge assistance.
                    </p>
                </div>

                <div class="text-xs font-mono text-stone-500 dark:text-stone-400">
                    Total Records: <strong class="text-[#171513] dark:text-white">{{ $bookings->count() }}</strong>
                </div>
            </div>

            @if($bookings->isEmpty())
                <div class="text-center py-20 space-y-4">
                    <div class="w-16 h-16 mx-auto rounded-full bg-[#C6A15B]/10 border border-[#C6A15B]/30 flex items-center justify-center text-2xl text-[#C6A15B]">
                        🏛️
                    </div>
                    <p class="font-serif-luxury text-2xl text-stone-700 dark:text-stone-300">You do not have any active reservations yet.</p>
                    <p class="text-xs text-stone-500 dark:text-stone-400 max-w-sm mx-auto">
                        Explore our luxury wedding hall marketplace to reserve an Afternoon, Evening, or Night shift with single-host exclusivity.
                    </p>
                    <a href="{{ route('public.venues') }}" class="btn-gold !h-10 !px-6 text-xs font-bold uppercase tracking-wider inline-flex items-center gap-2">
                        <span>Explore Wedding Venues Now &rarr;</span>
                    </a>
                </div>
            @else
                <div class="table-wrapper-enterprise" tabindex="0" role="region" aria-label="Customer Reservations">
                    <table class="table-enterprise">
                        <thead>
                            <tr>
                                <th>Grand Venue &amp; Hall</th>
                                <th>Date &amp; Shift</th>
                                <th>Celebration Details</th>
                                <th>Guest Scale</th>
                                <th>Total Budget</th>
                                <th>Advance Paid</th>
                                <th>Balance Due</th>
                                <th>Booking Status</th>
                                <th class="text-right">Concierge Documents</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bookings as $b)
                                <tr>
                                    <td>
                                        <div class="font-bold text-[#171513] dark:text-white text-sm">{{ $b->hall->name ?? 'Grand Ballroom' }}</div>
                                        <div class="inline-flex items-center gap-1 text-[11px] text-stone-500 dark:text-stone-400 font-sans">
                                            <span>📍</span>
                                            <span>{{ $b->hall->venue->city ?? 'Lahore' }}</span>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="font-bold text-[#171513] dark:text-white font-mono">{{ $b->event_date->format('d M Y') }}</div>
                                        <span class="inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-sans font-bold uppercase bg-[#C6A15B]/15 text-[#8F7239] dark:text-[#F2DE9A] border border-[#C6A15B]/30">
                                            @if($b->slot_type === 'LUNCH') ☀️ Lunch
                                            @elseif($b->slot_type === 'DINNER') 🌄 Dinner
                                            @elseif($b->slot_type === 'MEHNDI') 🌙 Mehndi
                                            @else {{ $b->slot_type }}
                                            @endif
                                        </span>
                                    </td>

                                    <td>
                                        <div class="font-semibold text-[#171513] dark:text-white font-sans">{{ $b->event_type }}</div>
                                        <span class="text-[10px] text-stone-400 dark:text-stone-500 font-mono select-all tracking-wider">{{ $b->booking_reference }}</span>
                                    </td>

                                    <td>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-mono font-bold bg-stone-100 dark:bg-white/5 text-stone-700 dark:text-stone-300 border border-stone-200 dark:border-white/10">
                                            <span>👥</span>
                                            <span>{{ number_format($b->guest_count) }}</span>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="font-mono font-bold text-[#171513] dark:text-white text-sm">
                                            PKR {{ number_format($b->grand_total) }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400 text-xs">
                                            PKR {{ number_format($b->advance_paid) }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="font-mono font-bold {{ $b->balance_due > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-stone-400' }} text-xs">
                                            PKR {{ number_format($b->balance_due) }}
                                        </span>
                                    </td>

                                    <td>
                                        @if($b->status === 'CONFIRMED')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-sans font-bold uppercase bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                <span>Confirmed</span>
                                            </span>
                                        @elseif($b->status === 'COMPLETED')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-sans font-bold uppercase bg-stone-100 dark:bg-white/10 text-stone-700 dark:text-stone-300 border border-stone-200 dark:border-white/20">
                                                <span>Completed</span>
                                            </span>
                                        @elseif($b->status === 'CANCELLED')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-sans font-bold uppercase bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30">
                                                <span>Cancelled</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-sans font-bold uppercase bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30">
                                                <span>{{ $b->status }}</span>
                                            </span>
                                        @endif
                                    </td>

                                    <td class="text-right">
                                        <div class="flex items-center justify-end gap-1.5 font-sans">
                                            <button wire:click="viewBooking('{{ $b->id }}')" 
                                                    class="btn-outline-gold !h-7 !px-2.5 text-[11px] font-bold rounded-lg uppercase">
                                                Details
                                            </button>
                                            <a href="{{ route('documents.agreement', $b->id) }}" target="_blank" 
                                               class="px-2.5 py-1 rounded-lg border border-stone-200 dark:border-white/10 hover:border-[#C6A15B] text-stone-700 dark:text-stone-300 hover:text-[#C6A15B] transition font-bold text-[10px] uppercase">
                                                A4 Contract ↗
                                            </a>
                                            <a href="{{ route('documents.invoice', $b->id) }}" target="_blank" 
                                               class="px-2.5 py-1 rounded-lg border border-stone-200 dark:border-white/10 hover:border-[#C6A15B] text-stone-700 dark:text-stone-300 hover:text-[#C6A15B] transition font-bold text-[10px] uppercase">
                                                Tax Invoice ↗
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- ===================================================================
             5. ITEMIZED FINANCIAL & LEGAL BREAKDOWN MODAL
             =================================================================== -->
        @if($viewingBooking)
            <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
                <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/60 rounded-2xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl space-y-6 max-h-[92vh] overflow-y-auto text-stone-800 dark:text-stone-200 transition-colors">
                    
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between border-b border-[#E7E5E4] dark:border-white/15 pb-4">
                        <div class="space-y-0.5">
                            <span class="text-[10px] uppercase font-bold tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-sans">
                                Official Reservation Summary
                            </span>
                            <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-normal">
                                {{ $viewingBooking->hall->name ?? 'Ballroom' }}
                            </h3>
                            <span class="text-xs text-stone-500 dark:text-stone-400 font-mono">Ref: {{ $viewingBooking->booking_reference }}</span>
                        </div>
                        <button wire:click="closeBookingModal" class="text-stone-400 hover:text-stone-700 dark:hover:text-white text-3xl font-light leading-none cursor-pointer">
                            &times;
                        </button>
                    </div>

                    <!-- Itemized Breakdown -->
                    <div class="space-y-2.5 text-xs font-mono bg-[#F8F5EF] dark:bg-white/5 p-4 rounded-xl border border-[#E7E5E4] dark:border-white/10">
                        <div class="flex justify-between py-1 border-b border-stone-200 dark:border-white/10">
                            <span class="text-stone-600 dark:text-stone-400">Event Date &amp; Shift:</span>
                            <span class="font-semibold text-stone-900 dark:text-white">{{ $viewingBooking->event_date->format('d M Y') }} ({{ $viewingBooking->slot_type }})</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-stone-200 dark:border-white/10">
                            <span class="text-stone-600 dark:text-stone-400">Catering Per-Head ({{ $viewingBooking->guest_count }} guests @ PKR {{ number_format($viewingBooking->per_head_rate) }}):</span>
                            <span class="font-semibold text-stone-900 dark:text-white">PKR {{ number_format($viewingBooking->guest_count * $viewingBooking->per_head_rate) }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-stone-200 dark:border-white/10">
                            <span class="text-stone-600 dark:text-stone-400">Base Hall Rental:</span>
                            <span class="font-semibold text-stone-900 dark:text-white">PKR {{ number_format($viewingBooking->hall_rent) }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-stone-200 dark:border-white/10">
                            <span class="text-stone-600 dark:text-stone-400">Add-ons &amp; Stage Decor:</span>
                            <span class="font-semibold text-stone-900 dark:text-white">PKR {{ number_format($viewingBooking->addons_total) }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-stone-200 dark:border-white/10">
                            <span class="text-stone-600 dark:text-stone-400">Discount Applied:</span>
                            <span class="font-semibold text-stone-900 dark:text-white">- PKR {{ number_format($viewingBooking->discount_amount) }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-stone-200 dark:border-white/10">
                            <span class="text-stone-600 dark:text-stone-400">Provincial Services Tax (PRA):</span>
                            <span class="font-semibold text-stone-900 dark:text-white">PKR {{ number_format($viewingBooking->tax_amount) }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-t-2 border-[#C6A15B] text-sm">
                            <span class="font-bold text-[#171513] dark:text-white">Grand Total:</span>
                            <strong class="font-cinzel text-lg text-[#171513] dark:text-[#C6A15B] font-bold">PKR {{ number_format($viewingBooking->grand_total) }}</strong>
                        </div>
                        <div class="flex justify-between py-1 text-xs">
                            <span class="text-[#8F7239] dark:text-[#C6A15B] font-semibold">Advance Token Cleared:</span>
                            <strong class="text-[#8F7239] dark:text-[#C6A15B]">PKR {{ number_format($viewingBooking->advance_paid) }}</strong>
                        </div>
                        <div class="flex justify-between py-1 text-xs">
                            <span class="text-[#171513] dark:text-[#F8F5EF] font-semibold">Remaining Balance Due:</span>
                            <strong class="text-[#171513] dark:text-[#F8F5EF]">PKR {{ number_format($viewingBooking->balance_due) }}</strong>
                        </div>
                    </div>

                    <!-- Payment Receipts Downloads -->
                    @if($viewingBooking->payments->isNotEmpty())
                        <div class="pt-2 space-y-2">
                            <span class="text-[10px] font-sans font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400 block">
                                Cleared Payment Receipts
                            </span>
                            <div class="space-y-1.5">
                                @foreach($viewingBooking->payments as $p)
                                    <div class="flex items-center justify-between text-xs bg-[#F8F5EF] dark:bg-white/5 p-3 rounded-lg border border-[#E7E5E4] dark:border-white/10 font-mono">
                                        <div>
                                            <strong class="text-[#171513] dark:text-white">{{ $p->receipt_no }}</strong>
                                            <span class="text-[11px] text-stone-500 dark:text-stone-400 ml-2">({{ $p->payment_type }}) — PKR {{ number_format($p->amount) }}</span>
                                        </div>
                                        <a href="{{ route('documents.receipt', $p->id) }}" target="_blank" 
                                           class="text-[#8F7239] dark:text-[#C6A15B] hover:underline font-semibold font-sans">
                                            Download Receipt ↗
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Direct Fast Links -->
                    <div class="flex flex-wrap items-center gap-2 pt-2">
                        <a href="{{ route('documents.agreement', $viewingBooking->id) }}" target="_blank" 
                           class="btn-gold !h-9 !px-4 text-xs font-bold font-sans uppercase rounded-lg">
                            <span>Open Legal Agreement (A4) ↗</span>
                        </a>
                        <a href="{{ route('documents.invoice', $viewingBooking->id) }}" target="_blank" 
                           class="btn-outline-gold !h-9 !px-4 text-xs font-bold font-sans uppercase rounded-lg">
                            <span>Tax Invoice ↗</span>
                        </a>
                    </div>

                    <!-- Cancellation & Close Actions -->
                    <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="space-y-2 w-full sm:w-auto">
                            <input type="text" wire:model="cancellationReason" placeholder="Reason for reschedule or cancellation..." 
                                   class="input-luxury text-xs w-full sm:w-72 dark:bg-[#171513] dark:text-white dark:border-white/15 rounded-lg">
                            <button type="button" wire:click="requestCancellation('{{ $viewingBooking->id }}')" 
                                    class="text-xs text-stone-600 dark:text-stone-400 hover:text-red-500 dark:hover:text-red-400 block font-semibold transition">
                                Request Reschedule or Cancellation
                            </button>
                        </div>
                        <button type="button" wire:click="closeBookingModal" 
                                class="btn-outline-gold !h-9 !px-5 text-xs font-bold uppercase rounded-lg self-end sm:self-auto">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>
