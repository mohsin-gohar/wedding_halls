<div class="w-full max-w-2xl mx-auto">
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-8 sm:p-12 shadow-2xl space-y-8 relative overflow-hidden">
        
        <!-- Top Accent Line -->
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-[#C6A15B] via-[#E8DCBE] to-[#C6A15B]"></div>

        <!-- Header -->
        <div class="text-center space-y-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border border-[#C6A15B]/40 bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] text-[10px] font-bold uppercase tracking-widest font-mono">
                <span class="w-1.5 h-1.5 bg-[#C6A15B] rounded-full"></span>
                Venue Partner Registration
            </span>
            <h1 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                Register Your Wedding Hall
            </h1>
            <p class="text-xs text-stone-500 dark:text-stone-400 font-sans max-w-md mx-auto leading-relaxed">
                List your ballrooms, receive high-intent guest inquiries, and manage banquet operations with automated shift scheduling.
            </p>
        </div>

        @if(session('message'))
            <div class="p-4 bg-[#C6A15B]/15 border border-[#C6A15B]/60 text-[#8F7239] dark:text-[#C6A15B] text-xs text-center font-medium">
                {{ session('message') }}
            </div>
        @endif

        <!-- Registration Form -->
        <form wire:submit="register" class="space-y-6">
            
            <!-- Hall & Venue Details -->
            <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-6 space-y-4">
                <div class="flex items-center gap-2">
                    <span class="w-1.5 h-1.5 bg-[#C6A15B]"></span>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-stone-800 dark:text-stone-200 font-mono">
                        Venue &amp; Hall Information
                    </h3>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-mono">
                            Wedding Hall / Marquee Name *
                        </label>
                        <input type="text" wire:model="hallName" required placeholder="e.g. The Royal Crystal Ballroom"
                               class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                        @error('hallName') <span class="text-rose-500 text-xs mt-1 block font-mono">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-mono">
                            City / Location *
                        </label>
                        <input type="text" wire:model="city" required placeholder="Lahore, Islamabad, Karachi..."
                               class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                        @error('city') <span class="text-rose-500 text-xs mt-1 block font-mono">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-mono">
                            Max Guest Capacity *
                        </label>
                        <input type="number" wire:model="capacity" required placeholder="500" min="50" max="5000"
                               class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                        @error('capacity') <span class="text-rose-500 text-xs mt-1 block font-mono">{{ $message }}</span> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-mono">
                            Standard Base Shift Rental (PKR) *
                        </label>
                        <input type="number" wire:model="baseRent" required placeholder="150000" min="10000" step="5000"
                               class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm font-semibold w-full">
                        <span class="text-[11px] text-stone-400 dark:text-stone-500 mt-1 block">Indicative starting rental fee per shift (Afternoon/Evening)</span>
                        @error('baseRent') <span class="text-rose-500 text-xs mt-1 block font-mono">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Business Owner Account -->
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <span class="w-1.5 h-1.5 bg-[#C6A15B]"></span>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-stone-800 dark:text-stone-200 font-mono">
                        Owner Credentials &amp; Contact
                    </h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-mono">
                            Director / Owner Name *
                        </label>
                        <input type="text" wire:model="ownerName" required placeholder="e.g. Sheikh Ahsan Tariq"
                               class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                        @error('ownerName') <span class="text-rose-500 text-xs mt-1 block font-mono">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-mono">
                            Official Mobile / WhatsApp *
                        </label>
                        <input type="text" wire:model="phone" required placeholder="+92 300 8472910"
                               class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                        @error('phone') <span class="text-rose-500 text-xs mt-1 block font-mono">{{ $message }}</span> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-mono">
                            Official Business Email *
                        </label>
                        <input type="email" wire:model="email" required placeholder="management@royalballroom.pk"
                               class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                        @error('email') <span class="text-rose-500 text-xs mt-1 block font-mono">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-mono">
                            Portal Password *
                        </label>
                        <input type="password" wire:model="password" required placeholder="••••••••"
                               class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                        @error('password') <span class="text-rose-500 text-xs mt-1 block font-mono">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-mono">
                            Confirm Password *
                        </label>
                        <input type="password" wire:model="password_confirmation" required placeholder="••••••••"
                               class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                    </div>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" 
                        wire:loading.attr="disabled"
                        class="btn-gold !w-full !h-12 text-xs font-bold uppercase tracking-wider font-mono shadow-[0_0_20px_rgba(198,161,91,0.3)] hover:shadow-[0_0_30px_rgba(198,161,91,0.5)] transition-all duration-300 flex items-center justify-center gap-2">
                    <span wire:loading.remove>Register Hall &amp; Continue &rarr;</span>
                    <span wire:loading class="inline-flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Creating Venue Account...
                    </span>
                </button>
            </div>
        </form>

        <div class="pt-5 border-t border-[#E7E5E4] dark:border-white/10 text-center text-xs text-stone-500 dark:text-stone-400 font-sans">
            <span>Already have a venue or user account?</span>
            <a href="{{ route('login') }}" class="text-[#8F7239] dark:text-[#C6A15B] hover:underline font-bold ml-1 font-mono">
                Sign In &rarr;
            </a>
        </div>

    </div>
</div>
