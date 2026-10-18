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
                Guest &amp; Host Portal
            </span>
            <h1 class="font-serif-luxury text-2xl sm:text-3xl text-[#171513] dark:text-white font-normal">
                Customer Sign In
            </h1>
            <p class="text-xs text-stone-500 dark:text-stone-400 font-sans leading-relaxed">
                Track your wedding reservations, itemized quotations, and shift booking history.
            </p>
        </div>

        @if(session('message'))
            <div class="p-3 bg-[#C6A15B]/15 border border-[#C6A15B]/60 text-[#8F7239] dark:text-[#C6A15B] text-xs text-center font-medium">
                {{ session('message') }}
            </div>
        @endif

        @if(session('error'))
            <div class="p-3 bg-red-950/40 border border-red-700/60 text-red-300 text-xs text-center font-medium">
                {{ session('error') }}
            </div>
        @endif

        <!-- Form -->
        <form wire:submit="login" class="space-y-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-mono">
                    Email Address
                </label>
                <input type="email" wire:model="email" required autofocus placeholder="your.name@example.com" 
                       class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                @error('email') <span class="text-rose-500 text-xs mt-1 block font-mono">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-mono">
                    Password
                </label>
                <input type="password" wire:model="password" required placeholder="••••••••" 
                       class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                @error('password') <span class="text-rose-500 text-xs mt-1 block font-mono">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center justify-between text-xs pt-1">
                <label class="flex items-center gap-2 cursor-pointer text-stone-600 dark:text-stone-400">
                    <input type="checkbox" wire:model="remember" class="accent-[#C6A15B]">
                    <span>Remember me</span>
                </label>
                <a href="{{ route('login') }}" class="text-[#8F7239] dark:text-[#C6A15B] hover:underline font-mono text-[11px] font-semibold">
                    Unified Sign In &rarr;
                </a>
            </div>

            <div class="pt-2">
                <button type="submit" 
                        class="btn-gold !w-full !h-12 text-xs font-bold uppercase tracking-wider font-mono shadow-[0_0_20px_rgba(198,161,91,0.3)] hover:shadow-[0_0_30px_rgba(198,161,91,0.5)] transition-all duration-300">
                    Sign In &rarr;
                </button>
            </div>
        </form>

        <div class="pt-5 border-t border-[#E7E5E4] dark:border-white/10 text-center text-xs text-stone-500 dark:text-stone-400">
            <span>Don't have an account yet?</span>
            <a href="{{ route('customer.register') }}" class="text-[#8F7239] dark:text-[#C6A15B] hover:underline font-bold ml-1 font-mono">
                Create Account &rarr;
            </a>
        </div>

    </div>
</div>
