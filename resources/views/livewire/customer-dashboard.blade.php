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
                    <a href="{{ route('public.concierge') }}" 
                       class="btn-outline-gold !h-10 !px-4 text-xs font-bold font-sans uppercase tracking-wider rounded-lg shadow-sm transition-all duration-200 flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#C6A15B]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                        </svg>
                        <span>AI Concierge Chatbot</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs: Bookings vs Profile -->
        <div class="flex items-center gap-3 border-b border-[#E7E5E4] dark:border-white/10 pb-3">
            <button type="button" wire:click="switchTab('bookings')" 
                    class="px-4 py-2 text-xs font-sans font-bold uppercase tracking-wider rounded-xl transition flex items-center gap-2 cursor-pointer {{ $activeTab === 'bookings' ? 'bg-[#C6A15B] text-[#171513] shadow-md font-extrabold' : 'bg-stone-100 dark:bg-white/5 text-stone-600 dark:text-stone-300 hover:text-[#C6A15B]' }}">
                <span>📋 My Reservations &amp; Contracts ({{ $bookings->count() }})</span>
            </button>
            <button type="button" wire:click="switchTab('profile')" 
                    class="px-4 py-2 text-xs font-sans font-bold uppercase tracking-wider rounded-xl transition flex items-center gap-2 cursor-pointer {{ $activeTab === 'profile' ? 'bg-[#C6A15B] text-[#171513] shadow-md font-extrabold' : 'bg-stone-100 dark:bg-white/5 text-stone-600 dark:text-stone-300 hover:text-[#C6A15B]' }}">
                <span>👤 My Profile &amp; Account Settings</span>
            </button>
        </div>

        @if($profileFeedback)
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-xs font-bold shadow-md flex items-center justify-between">
                <span>✓ {{ $profileFeedback }}</span>
                <button type="button" wire:click="$set('profileFeedback', null)" class="text-emerald-500 text-lg leading-none cursor-pointer">&times;</button>
            </div>
        @endif

        @if($activeTab === 'profile')
            <!-- ===================================================================
                 CUSTOMER PROFILE & CREDENTIAL SUITE
                 =================================================================== -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                <!-- Left: Luxury Customer Identity Card (5 cols) -->
                <div class="lg:col-span-5 bg-white dark:bg-[#171513] border border-[#C6A15B]/40 rounded-2xl p-6 sm:p-7 shadow-md space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-[#D4AF67] via-[#C6A15B] to-[#8F7239] text-[#171513] font-cinzel text-2xl font-bold flex items-center justify-center shadow-lg shadow-[#C6A15B]/20 shrink-0">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                        <div>
                            <span class="text-[10px] font-mono uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold block">
                                Registered Member
                            </span>
                            <h3 class="font-serif-luxury text-xl sm:text-2xl text-[#171513] dark:text-white font-bold leading-tight">
                                {{ auth()->user()->name }}
                            </h3>
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-sans font-bold uppercase bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 mt-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                VIP Client Account
                            </span>
                        </div>
                    </div>

                    <div class="space-y-3 pt-4 border-t border-[#E7E5E4] dark:border-white/10 text-xs font-sans">
                        <div class="flex justify-between py-1.5 border-b border-stone-100 dark:border-white/5">
                            <span class="text-stone-500 dark:text-stone-400">Email Address:</span>
                            <strong class="text-[#171513] dark:text-white font-mono">{{ auth()->user()->email }}</strong>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-stone-100 dark:border-white/5">
                            <span class="text-stone-500 dark:text-stone-400">Phone Number:</span>
                            <strong class="text-[#171513] dark:text-white font-mono">{{ auth()->user()->phone ?? 'Not specified' }}</strong>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-stone-100 dark:border-white/5">
                            <span class="text-stone-500 dark:text-stone-400">Account Created:</span>
                            <strong class="text-[#171513] dark:text-white">{{ auth()->user()->created_at->format('d M Y') }}</strong>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-stone-100 dark:border-white/5">
                            <span class="text-stone-500 dark:text-stone-400">Total Bookings:</span>
                            <strong class="text-[#8F7239] dark:text-[#C6A15B] font-bold">{{ $bookings->count() }} Functions</strong>
                        </div>
                        <div class="flex justify-between py-1.5">
                            <span class="text-stone-500 dark:text-stone-400">Total Spent:</span>
                            <strong class="text-emerald-600 dark:text-emerald-400 font-bold font-mono">PKR {{ number_format($totalSpent) }}</strong>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-[#F8F5EF] dark:bg-white/5 border border-[#C6A15B]/20 text-[11px] text-stone-600 dark:text-stone-400 space-y-1">
                        <strong class="text-[#171513] dark:text-white block font-bold">🔒 Privacy & Data Guarantee</strong>
                        <p>Your contact details are strictly confidential and only shared with verified hall managers for your booked dates.</p>
                    </div>
                </div>

                <!-- Right: Profile Edit & Password Change (7 cols) -->
                <div class="lg:col-span-7 space-y-6">
                    
                    <!-- Edit Details Form -->
                    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-2xl p-6 sm:p-7 shadow-md space-y-5">
                        <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-3">
                            <h4 class="font-serif-luxury text-lg text-[#171513] dark:text-white font-bold">Personal Contact Profile</h4>
                            <p class="text-xs text-stone-500 dark:text-stone-400">Update your primary name and phone number for booking confirmations.</p>
                        </div>

                        <form wire:submit.prevent="updateProfile" class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 uppercase tracking-wider mb-1">Full Name</label>
                                    <input type="text" wire:model="profileName" class="w-full bg-[#F8F5EF] dark:bg-[#1f1b17] border border-[#E7E5E4] dark:border-white/15 rounded-xl px-3.5 py-2.5 text-xs text-[#171513] dark:text-white outline-none focus:border-[#C6A15B] transition">
                                    @error('profileName') <span class="text-rose-500 text-[11px] font-bold block mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 uppercase tracking-wider mb-1">Mobile Phone (WhatsApp)</label>
                                    <input type="text" wire:model="profilePhone" class="w-full bg-[#F8F5EF] dark:bg-[#1f1b17] border border-[#E7E5E4] dark:border-white/15 rounded-xl px-3.5 py-2.5 text-xs text-[#171513] dark:text-white font-mono outline-none focus:border-[#C6A15B] transition">
                                    @error('profilePhone') <span class="text-rose-500 text-[11px] font-bold block mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 uppercase tracking-wider mb-1">Account Email (Immutable ID)</label>
                                <input type="email" value="{{ auth()->user()->email }}" disabled class="w-full bg-stone-100 dark:bg-white/5 border border-stone-200 dark:border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-stone-500 dark:text-stone-400 font-mono cursor-not-allowed">
                                <span class="text-[10px] text-stone-400 block mt-1">To change your registered email, contact platform concierge.</span>
                            </div>

                            <div class="flex justify-end pt-2">
                                <button type="submit" class="tbtn tbtn-gold !h-10 !px-5 text-xs font-bold uppercase tracking-wider">
                                    <span>💾 Save Profile Changes</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Change Password Form -->
                    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-2xl p-6 sm:p-7 shadow-md space-y-5">
                        <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-3">
                            <h4 class="font-serif-luxury text-lg text-[#171513] dark:text-white font-bold">Security &amp; Password Update</h4>
                            <p class="text-xs text-stone-500 dark:text-stone-400">Ensure your account has a strong passphrase of at least 6 characters.</p>
                        </div>

                        <form wire:submit.prevent="updatePassword" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 uppercase tracking-wider mb-1">Current Password</label>
                                <input type="password" wire:model="currentPassword" class="w-full bg-[#F8F5EF] dark:bg-[#1f1b17] border border-[#E7E5E4] dark:border-white/15 rounded-xl px-3.5 py-2.5 text-xs text-[#171513] dark:text-white outline-none focus:border-[#C6A15B] transition">
                                @error('currentPassword') <span class="text-rose-500 text-[11px] font-bold block mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 uppercase tracking-wider mb-1">New Password</label>
                                    <input type="password" wire:model="newPassword" class="w-full bg-[#F8F5EF] dark:bg-[#1f1b17] border border-[#E7E5E4] dark:border-white/15 rounded-xl px-3.5 py-2.5 text-xs text-[#171513] dark:text-white outline-none focus:border-[#C6A15B] transition">
                                    @error('newPassword') <span class="text-rose-500 text-[11px] font-bold block mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 uppercase tracking-wider mb-1">Confirm New Password</label>
                                    <input type="password" wire:model="newPassword_confirmation" class="w-full bg-[#F8F5EF] dark:bg-[#1f1b17] border border-[#E7E5E4] dark:border-white/15 rounded-xl px-3.5 py-2.5 text-xs text-[#171513] dark:text-white outline-none focus:border-[#C6A15B] transition">
                                </div>
                            </div>

                            <div class="flex justify-end pt-2">
                                <button type="submit" class="tbtn tbtn-outline !h-10 !px-5 text-xs font-bold uppercase tracking-wider">
                                    <span>🔑 Update Password</span>
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        @else
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
                            Reserved banquet shifts on Riwaj Grand.
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

                                    <td class="text-right whitespace-nowrap">
                                        <div class="inline-flex items-center justify-end gap-1.5 font-sans">
                                            <button type="button" wire:click="viewBooking('{{ $b->id }}')" class="tbtn tbtn-gold" title="View Reservation Details">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                <span>Details</span>
                                            </button>
                                            <a href="{{ route('documents.agreement', $b->id) }}" target="_blank" class="tbtn tbtn-outline" title="Download Official A4 Contract">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                <span>Contract ↗</span>
                                            </a>
                                            <a href="{{ route('documents.invoice', $b->id) }}" target="_blank" class="tbtn tbtn-dark" title="Download Formal Tax Invoice">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                                                <span>Invoice ↗</span>
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
        @endif

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
