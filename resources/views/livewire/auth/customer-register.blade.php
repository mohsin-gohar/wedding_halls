<div class="w-full max-w-md">
    <!-- Card Container -->
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 rounded-2xl p-7 sm:p-9 shadow-xl space-y-6 relative overflow-hidden">

        <!-- Brand Logo -->
        <div class="flex justify-center -mb-1">
            <img src="{{ asset('images/riwaj-grand-mark.png') }}" width="56" height="56" fetchpriority="high" decoding="async" alt="Riwaj Grand Logo" class="h-14 w-14 object-contain rounded-xl border border-[#C6A15B]/40 bg-[#171513] shadow-sm p-1">
        </div>

        <!-- Heading Section (Clean, Universal Luxury) -->
        <div class="text-center space-y-1.5">
            <h1 class="font-serif-luxury text-2xl sm:text-3xl text-[#171513] dark:text-white font-normal tracking-tight">
                Create an Account
            </h1>
            <p class="text-xs text-stone-500 dark:text-stone-400 font-sans max-w-xs mx-auto leading-relaxed">
                Please enter your details to register.
            </p>
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
        <form wire:submit="register" class="space-y-4">
            <!-- Full Name -->
            <div>
                <label for="register-name" class="block text-xs font-semibold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-sans">
                    Full Name
                </label>
                <input id="register-name" 
                       name="name" 
                       type="text" 
                       wire:model="name" 
                       required 
                       autofocus 
                       autocomplete="name" 
                       placeholder="e.g. Ahmad Tariq" 
                       class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                @error('name') 
                    <span class="text-rose-500 text-xs mt-1 block font-sans">{{ $message }}</span> 
                @enderror
            </div>

            <!-- Email Address -->
            <div>
                <label for="register-email" class="block text-xs font-semibold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-sans">
                    Email Address
                </label>
                <input id="register-email" 
                       name="email" 
                       type="email" 
                       wire:model="email" 
                       required 
                       autocomplete="email" 
                       placeholder="name@example.com" 
                       class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                @error('email') 
                    <span class="text-rose-500 text-xs mt-1 block font-sans">{{ $message }}</span> 
                @enderror
            </div>

            <!-- Phone / WhatsApp -->
            <div>
                <label for="register-phone" class="block text-xs font-semibold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-sans">
                    Phone / WhatsApp
                </label>
                <input id="register-phone" 
                       name="phone" 
                       type="tel" 
                       wire:model="phone" 
                       required 
                       autocomplete="tel" 
                       placeholder="+92 300 1234567" 
                       class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                @error('phone') 
                    <span class="text-rose-500 text-xs mt-1 block font-sans">{{ $message }}</span> 
                @enderror
            </div>

            <!-- Password & Confirmation -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="register-password" class="block text-xs font-semibold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-sans">
                        Password
                    </label>
                    <input id="register-password" 
                           name="password" 
                           type="password" 
                           wire:model="password" 
                           required 
                           autocomplete="new-password" 
                           placeholder="••••••••" 
                           class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                    @error('password') 
                        <span class="text-rose-500 text-xs mt-1 block font-sans">{{ $message }}</span> 
                    @enderror
                </div>
                <div>
                    <label for="register-password-confirm" class="block text-xs font-semibold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-sans">
                        Confirm
                    </label>
                    <input id="register-password-confirm" 
                           name="password_confirmation" 
                           type="password" 
                           wire:model="password_confirmation" 
                           required 
                           autocomplete="new-password" 
                           placeholder="••••••••" 
                           class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button type="submit" 
                        wire:loading.attr="disabled"
                        class="btn-gold !w-full !h-11 text-xs font-bold uppercase tracking-wider font-sans rounded-lg shadow-sm transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer">
                    <span wire:loading.remove>Create Account &rarr;</span>
                    <span wire:loading class="inline-flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-[#171513]" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span>Creating Account...</span>
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
                <span>Own a banquet or marquee?</span>
                <a href="{{ route('register') }}" class="text-stone-500 dark:text-stone-400 hover:text-[#8F7239] dark:hover:text-[#C6A15B] underline ml-1">
                    Register your venue &rarr;
                </a>
            </div>
        </div>

    </div>
</div>
