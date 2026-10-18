<div class="w-full max-w-md mx-auto">
    <!-- Card Container -->
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-8 sm:p-10 shadow-2xl space-y-6 relative overflow-hidden">
        
        <!-- Top Accent Line -->
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-[#C6A15B] via-[#E8DCBE] to-[#C6A15B]"></div>

        <!-- Heading Section -->
        <div class="text-center space-y-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border border-[#C6A15B]/40 bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] text-[10px] font-bold uppercase tracking-widest font-mono">
                <span class="w-1.5 h-1.5 bg-[#C6A15B] rounded-full animate-pulse"></span>
                Secure Access
            </span>
            <h1 class="font-serif-luxury text-2xl sm:text-3xl text-[#171513] dark:text-white font-normal">
                Sign In to EventHub
            </h1>
            <p class="text-xs text-stone-500 dark:text-stone-400 font-sans max-w-xs mx-auto leading-relaxed">
                Enter your credentials to access your dashboard, bookings, and reservations.
            </p>
        </div>

        <!-- Flash Messages -->
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

        <!-- Login Form -->
        <form wire:submit="login" class="space-y-4">
            <!-- Email -->
            <div>
                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-mono">
                    Email Address
                </label>
                <input id="email" 
                       type="email" 
                       wire:model="email" 
                       required 
                       autofocus
                       autocomplete="email"
                       placeholder="your-email@example.com"
                       class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                @error('email') 
                    <span class="text-rose-500 text-xs mt-1 block font-mono">{{ $message }}</span> 
                @enderror
            </div>

            <!-- Password -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 font-mono">
                        Password
                    </label>
                </div>
                <input id="password" 
                       type="password" 
                       wire:model="password" 
                       required 
                       autocomplete="current-password"
                       placeholder="••••••••"
                       class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                @error('password') 
                    <span class="text-rose-500 text-xs mt-1 block font-mono">{{ $message }}</span> 
                @enderror
            </div>

            <!-- Remember Me & Security -->
            <div class="flex items-center justify-between text-xs pt-1">
                <label class="flex items-center gap-2 cursor-pointer text-stone-600 dark:text-stone-400 select-none">
                    <input type="checkbox" wire:model="remember" class="accent-[#C6A15B] rounded">
                    <span>Keep me signed in</span>
                </label>
                <span class="text-stone-400 dark:text-stone-500 text-[10px] font-mono">🔒 SSL 256-bit</span>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button type="submit" 
                        wire:loading.attr="disabled"
                        class="btn-gold !w-full !h-12 text-xs font-bold uppercase tracking-wider font-mono shadow-[0_0_20px_rgba(198,161,91,0.3)] hover:shadow-[0_0_30px_rgba(198,161,91,0.5)] transition-all duration-300 flex items-center justify-center gap-2">
                    <span wire:loading.remove>Sign In &rarr;</span>
                    <span wire:loading class="inline-flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Authenticating...
                    </span>
                </button>
            </div>
        </form>

        <!-- Alternative Options / Registration Links -->
        <div class="border-t border-[#E7E5E4] dark:border-white/10 pt-5 space-y-3">
            <p class="text-[11px] text-center text-stone-500 dark:text-stone-400 font-sans">
                Don't have an account yet?
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-center text-xs">
                <a href="{{ route('register') }}" 
                   class="p-2.5 rounded border border-[#C6A15B]/30 hover:border-[#C6A15B] bg-[#C6A15B]/5 hover:bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] font-mono text-[11px] font-bold uppercase transition-colors">
                    Register Venue
                </a>
                <a href="{{ route('customer.register') }}" 
                   class="p-2.5 rounded border border-stone-200 dark:border-white/10 hover:border-stone-400 dark:hover:border-white/30 bg-stone-50 dark:bg-white/5 text-stone-700 dark:text-stone-300 font-mono text-[11px] font-bold uppercase transition-colors">
                    Customer Sign Up
                </a>
            </div>
        </div>

    </div>
</div>
