<div class="w-full max-w-md mx-auto">
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-8 sm:p-10 shadow-2xl space-y-6 relative overflow-hidden">
        
        <!-- Top Accent Line -->
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-[#C6A15B] via-[#E8DCBE] to-[#C6A15B]"></div>

        <!-- Brand Logo -->
        <div class="flex justify-center -mb-1">
            <img src="{{ asset('images/eventhub-logo.png') }}" alt="EventHub Logo" class="h-14 w-auto object-contain rounded border border-[#C6A15B]/50 bg-[#171513] shadow-[0_0_20px_rgba(198,161,91,0.25)] p-1">
        </div>

        <!-- Header -->
        <div class="text-center space-y-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border border-[#C6A15B]/40 bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] text-[10px] font-bold uppercase tracking-widest font-mono">
                <span class="w-1.5 h-1.5 bg-[#C6A15B] rounded-full"></span>
                Host &amp; Client Access
            </span>
            <h1 class="font-serif-luxury text-2xl sm:text-3xl text-[#171513] dark:text-white font-normal">
                Create Customer Account
            </h1>
            <p class="text-xs text-stone-500 dark:text-stone-400 font-sans leading-relaxed">
                Save quotations, reserve wedding shifts, track advance payments, and download contracts.
            </p>
        </div>

        <!-- Form -->
        <form wire:submit="register" class="space-y-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-mono">
                    Your Full Name *
                </label>
                <input type="text" wire:model="name" required placeholder="e.g. Chaudhry Ahmad Tariq" 
                       class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                @error('name') <span class="text-rose-500 text-xs mt-1 block font-mono">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-mono">
                    Email Address *
                </label>
                <input type="email" wire:model="email" required placeholder="ahmad@example.com" 
                       class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                @error('email') <span class="text-rose-500 text-xs mt-1 block font-mono">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-mono">
                    Phone / WhatsApp *
                </label>
                <input type="text" wire:model="phone" required placeholder="+92 300 1234567" 
                       class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                @error('phone') <span class="text-rose-500 text-xs mt-1 block font-mono">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-mono">
                        Password *
                    </label>
                    <input type="password" wire:model="password" required placeholder="••••••••" 
                           class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                    @error('password') <span class="text-rose-500 text-xs mt-1 block font-mono">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-mono">
                        Confirm *
                    </label>
                    <input type="password" wire:model="password_confirmation" required placeholder="••••••••" 
                           class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" 
                        class="btn-gold !w-full !h-12 text-xs font-bold uppercase tracking-wider font-mono shadow-[0_0_20px_rgba(198,161,91,0.3)] hover:shadow-[0_0_30px_rgba(198,161,91,0.5)] transition-all duration-300">
                    Complete Registration &rarr;
                </button>
            </div>
        </form>

        <div class="pt-5 border-t border-[#E7E5E4] dark:border-white/10 text-center text-xs text-stone-500 dark:text-stone-400">
            <span>Already have an account?</span>
            <a href="{{ route('customer.login') }}" class="text-[#8F7239] dark:text-[#C6A15B] hover:underline font-bold ml-1 font-mono">
                Sign In &rarr;
            </a>
        </div>

    </div>
</div>
