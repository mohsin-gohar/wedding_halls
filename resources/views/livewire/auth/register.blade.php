<div class="w-full max-w-2xl mx-auto">
    <!-- Card Container -->
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 rounded-2xl p-7 sm:p-10 shadow-xl space-y-6 relative overflow-hidden">

        <!-- Brand Logo -->
        <div class="flex justify-center -mb-1">
            <img src="{{ asset('images/eventhub-logo.png') }}" width="56" height="56" fetchpriority="high" decoding="async" alt="EventHub Logo" class="h-14 w-14 object-contain rounded-xl border border-[#C6A15B]/40 bg-[#171513] shadow-sm p-1">
        </div>

        <!-- Heading Section (Clean, Universal Luxury) -->
        <div class="text-center space-y-1.5">
            <h1 class="font-serif-luxury text-2xl sm:text-3xl text-[#171513] dark:text-white font-normal tracking-tight">
                Register Your Venue
            </h1>
            <p class="text-xs text-stone-500 dark:text-stone-400 font-sans max-w-md mx-auto leading-relaxed">
                List your ballrooms and manage banquet operations on Pakistan's premier venue network.
            </p>
            @if(! config('platform.billing_enabled'))
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#C6A15B]/10 border border-[#C6A15B]/40 text-[#8F7239] dark:text-[#C6A15B] text-[10px] font-sans font-bold uppercase tracking-wider">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#C6A15B]"></span>
                    Free Launch Access — {{ (int) config('platform.free_access_days', 30) }} Days Complimentary
                </div>
            @endif
        </div>

        <!-- Flash Messages -->
        @if(session('message'))
            <div class="p-3 rounded-lg bg-[#C6A15B]/15 border border-[#C6A15B]/50 text-[#8F7239] dark:text-[#C6A15B] text-xs text-center font-medium">
                {{ session('message') }}
            </div>
        @endif

        @if(session('error'))
            <div class="p-3 rounded-lg bg-red-950/40 border border-red-700/60 text-red-300 text-xs text-center font-medium">
                {{ session('error') }}
            </div>
        @endif

        <!-- Registration Form -->
        <form wire:submit="register" class="space-y-6">
            
            <!-- Venue & Hall Details -->
            <div class="space-y-3.5 pb-2 border-b border-[#E7E5E4] dark:border-white/10">
                <div class="text-xs font-semibold uppercase tracking-wider text-stone-700 dark:text-stone-300 font-sans flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#C6A15B]"></span>
                    <span>Venue Information</span>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div class="sm:col-span-2">
                        <label for="hall-name" class="block text-xs font-semibold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-sans">
                            Wedding Hall / Marquee Name *
                        </label>
                        <input id="hall-name" 
                               name="hallName" 
                               type="text" 
                               wire:model="hallName" 
                               required 
                               autofocus
                               placeholder="e.g. The Royal Crystal Ballroom"
                               class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                        @error('hallName') <span class="text-rose-500 text-xs mt-1 block font-sans">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="city" class="block text-xs font-semibold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-sans">
                            City / Location *
                        </label>
                        <input id="city" 
                               name="city" 
                               type="text" 
                               wire:model="city" 
                               required 
                               placeholder="Lahore, Islamabad, Karachi..."
                               class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                        @error('city') <span class="text-rose-500 text-xs mt-1 block font-sans">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="capacity" class="block text-xs font-semibold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-sans">
                            Max Guest Capacity *
                        </label>
                        <input id="capacity" 
                               name="capacity" 
                               type="number" 
                               wire:model="capacity" 
                               required 
                               min="50" 
                               max="5000" 
                               placeholder="500"
                               class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                        @error('capacity') <span class="text-rose-500 text-xs mt-1 block font-sans">{{ $message }}</span> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="base-rent" class="block text-xs font-semibold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-sans">
                            Standard Base Shift Rental (PKR) *
                        </label>
                        <input id="base-rent" 
                               name="baseRent" 
                               type="number" 
                               wire:model="baseRent" 
                               required 
                               min="10000" 
                               step="5000" 
                               placeholder="150000"
                               class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm font-semibold w-full">
                        <span class="text-[11px] text-stone-400 dark:text-stone-500 mt-1 block font-sans">Starting shift rental fee (Afternoon or Evening)</span>
                        @error('baseRent') <span class="text-rose-500 text-xs mt-1 block font-sans">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Owner & Account Details -->
            <div class="space-y-3.5">
                <div class="text-xs font-semibold uppercase tracking-wider text-stone-700 dark:text-stone-300 font-sans flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#C6A15B]"></span>
                    <span>Account &amp; Management</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label for="owner-name" class="block text-xs font-semibold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-sans">
                            Owner / Director Name *
                        </label>
                        <input id="owner-name" 
                               name="ownerName" 
                               type="text" 
                               wire:model="ownerName" 
                               required 
                               autocomplete="name" 
                               placeholder="Sheikh Ahsan Tariq"
                               class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                        @error('ownerName') <span class="text-rose-500 text-xs mt-1 block font-sans">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="owner-phone" class="block text-xs font-semibold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-sans">
                            Official Mobile / WhatsApp *
                        </label>
                        <input id="owner-phone" 
                               name="phone" 
                               type="tel" 
                               wire:model="phone" 
                               required 
                               autocomplete="tel" 
                               placeholder="+92 300 1234567"
                               class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                        @error('phone') <span class="text-rose-500 text-xs mt-1 block font-sans">{{ $message }}</span> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="owner-email" class="block text-xs font-semibold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-sans">
                            Official Business Email *
                        </label>
                        <input id="owner-email" 
                               name="email" 
                               type="email" 
                               wire:model="email" 
                               required 
                               autocomplete="email" 
                               placeholder="management@royalballroom.pk"
                               class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                        @error('email') <span class="text-rose-500 text-xs mt-1 block font-sans">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="owner-password" class="block text-xs font-semibold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-sans">
                            Portal Password *
                        </label>
                        <input id="owner-password" 
                               name="password" 
                               type="password" 
                               wire:model="password" 
                               required 
                               autocomplete="new-password" 
                               placeholder="••••••••"
                               class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                        @error('password') <span class="text-rose-500 text-xs mt-1 block font-sans">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="owner-password-confirm" class="block text-xs font-semibold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-sans">
                            Confirm Password *
                        </label>
                        <input id="owner-password-confirm" 
                               name="password_confirmation" 
                               type="password" 
                               wire:model="password_confirmation" 
                               required 
                               autocomplete="new-password" 
                               placeholder="••••••••"
                               class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button type="submit" 
                        wire:loading.attr="disabled"
                        class="btn-gold !w-full !h-11 text-xs font-bold uppercase tracking-wider font-sans rounded-lg shadow-sm transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer">
                    <span wire:loading.remove>Register Venue &rarr;</span>
                    <span wire:loading class="inline-flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-[#171513]" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span>Creating Venue Account...</span>
                    </span>
                </button>
            </div>
        </form>

        <!-- Clean Footer Link -->
        <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10 text-center text-xs text-stone-500 dark:text-stone-400 space-y-2">
            <div>
                <span>Already have an account?</span>
                <a href="{{ route('login') }}" class="text-[#8F7239] dark:text-[#C6A15B] hover:underline font-semibold ml-1">
                    Sign in &rarr;
                </a>
            </div>
            <div class="text-[11px] pt-1">
                <span>Looking to book a wedding hall as a guest?</span>
                <a href="{{ route('customer.register') }}" class="text-stone-500 dark:text-stone-400 hover:text-[#8F7239] dark:hover:text-[#C6A15B] underline ml-1">
                    Create guest account &rarr;
                </a>
            </div>
        </div>

    </div>
</div>
