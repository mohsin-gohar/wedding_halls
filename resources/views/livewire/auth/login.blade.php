<div class="w-full max-w-md mx-auto">
    <!-- Card Container -->
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 rounded-2xl p-8 sm:p-10 shadow-xl space-y-6 relative overflow-hidden">

        <!-- Brand Logo -->
        <div class="flex justify-center -mb-1">
            <img src="{{ asset('images/eventhub-logo.png') }}" alt="EventHub Logo" class="h-14 w-auto object-contain rounded-xl border border-[#C6A15B]/40 bg-[#171513] shadow-sm p-1">
        </div>

        <!-- Heading Section -->
        <div class="text-center space-y-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border border-[#C6A15B]/30 bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] text-[10px] font-bold uppercase tracking-widest font-sans">
                <span class="w-1.5 h-1.5 bg-[#C6A15B] rounded-full"></span>
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
            <div class="p-3 rounded-lg bg-[#C6A15B]/15 border border-[#C6A15B]/50 text-[#8F7239] dark:text-[#C6A15B] text-xs text-center font-medium">
                {{ session('message') }}
            </div>
        @endif

        @if(session('error'))
            <div class="p-3 rounded-lg bg-red-950/40 border border-red-700/60 text-red-300 text-xs text-center font-medium">
                {{ session('error') }}
            </div>
        @endif

        @auth
            <div class="p-3.5 rounded-xl bg-[#C6A15B]/10 border border-[#C6A15B]/35 text-xs flex items-center justify-between gap-3">
                <div class="text-stone-700 dark:text-stone-200">
                    Signed in as <strong class="text-[#8F7239] dark:text-[#C6A15B]">{{ auth()->user()->name }}</strong>
                </div>
                <a href="{{ auth()->user()->isSuperAdmin() ? route('admin.dashboard') : (auth()->user()->isCustomer() ? route('customer.dashboard') : route('dashboard')) }}"
                   class="px-3 py-1.5 rounded-lg bg-[#C6A15B] text-[#171513] font-bold uppercase tracking-wider text-[10px] shrink-0">
                    Open Portal &rarr;
                </a>
            </div>
        @endauth

        <!-- Login Form -->
        <form method="POST" action="{{ route('login.perform') }}" class="space-y-4">
            @csrf
            <!-- Email -->
            <div>
                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-sans">
                    Email Address
                </label>
                <input id="email" 
                       name="email"
                       type="email" 
                       value="{{ old('email', $email) }}"
                       wire:model="email" 
                       required 
                       autofocus
                       autocomplete="email"
                       placeholder="your-email@example.com"
                       class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                @error('email') 
                    <span class="text-rose-500 text-xs mt-1 block font-sans">{{ $message }}</span> 
                @enderror
            </div>

            <!-- Password -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 font-sans">
                        Password
                    </label>
                </div>
                <input id="password" 
                       name="password"
                       type="password" 
                       wire:model="password" 
                       required 
                       autocomplete="current-password"
                       placeholder="••••••••"
                       class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                @error('password') 
                    <span class="text-rose-500 text-xs mt-1 block font-sans">{{ $message }}</span> 
                @enderror
            </div>

            <!-- Remember Me & Security -->
            <div class="flex items-center justify-between text-xs pt-1">
                <label class="flex items-center gap-2 cursor-pointer text-stone-600 dark:text-stone-400 select-none">
                    <input type="checkbox" name="remember" value="1" wire:model="remember" class="accent-[#C6A15B] rounded">
                    <span>Keep me signed in</span>
                </label>
                <span class="inline-flex items-center gap-1 text-stone-400 dark:text-stone-500 text-[11px] font-sans">
                    <svg class="w-3.5 h-3.5 text-[#C6A15B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    256-bit Encrypted
                </span>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button type="submit" 
                        class="btn-gold !w-full !h-12 text-xs font-bold uppercase tracking-wider font-sans rounded-lg shadow-sm transition-all duration-200 flex items-center justify-center gap-2">
                    <span>Sign In &rarr;</span>
                </button>
            </div>
        </form>

        <!-- 1-Click Instant Portal Sign-In (Executive Demo Access) -->
        <div class="border-t border-[#E7E5E4] dark:border-white/10 pt-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-sans">
                    Instant 1-Click Portal Access
                </span>
                <span class="text-[10px] text-stone-400 dark:text-stone-500 font-sans">
                    Password: <code class="text-stone-600 dark:text-stone-300">password</code>
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <!-- Super Admin -->
                <form method="POST" action="{{ route('login.perform') }}">
                    @csrf
                    <input type="hidden" name="email" value="admin@venuecloud.pk">
                    <input type="hidden" name="password" value="password">
                    <input type="hidden" name="remember" value="1">
                    <button type="submit" class="w-full text-left px-3 py-2.5 rounded-xl border border-[#C6A15B]/35 hover:border-[#C6A15B] bg-[#C6A15B]/10 hover:bg-[#C6A15B]/20 transition-all group">
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-[#8F7239] dark:text-[#F2DE9A] group-hover:text-[#C6A15B]">Super Admin &rarr;</span>
                        <span class="block text-[10px] text-stone-500 dark:text-stone-400 truncate">admin@venuecloud.pk</span>
                    </button>
                </form>

                <!-- Hall Owner -->
                <form method="POST" action="{{ route('login.perform') }}">
                    @csrf
                    <input type="hidden" name="email" value="owner@venuecloud.pk">
                    <input type="hidden" name="password" value="password">
                    <input type="hidden" name="remember" value="1">
                    <button type="submit" class="w-full text-left px-3 py-2.5 rounded-xl border border-[#E7E5E4] dark:border-white/10 hover:border-[#C6A15B]/60 bg-stone-50 dark:bg-white/[0.03] hover:bg-[#C6A15B]/10 transition-all group">
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-[#171513] dark:text-white group-hover:text-[#C6A15B]">Hall Owner ERP &rarr;</span>
                        <span class="block text-[10px] text-stone-500 dark:text-stone-400 truncate">owner@venuecloud.pk</span>
                    </button>
                </form>

                <!-- Customer Portal -->
                <form method="POST" action="{{ route('login.perform') }}">
                    @csrf
                    <input type="hidden" name="email" value="customer@example.com">
                    <input type="hidden" name="password" value="password">
                    <input type="hidden" name="remember" value="1">
                    <button type="submit" class="w-full text-left px-3 py-2.5 rounded-xl border border-[#E7E5E4] dark:border-white/10 hover:border-[#C6A15B]/60 bg-stone-50 dark:bg-white/[0.03] hover:bg-[#C6A15B]/10 transition-all group">
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-[#171513] dark:text-white group-hover:text-[#C6A15B]">Customer Portal &rarr;</span>
                        <span class="block text-[10px] text-stone-500 dark:text-stone-400 truncate">customer@example.com</span>
                    </button>
                </form>

                <!-- Front Desk / Kitchen -->
                <form method="POST" action="{{ route('login.perform') }}">
                    @csrf
                    <input type="hidden" name="email" value="reception@venuecloud.pk">
                    <input type="hidden" name="password" value="password">
                    <input type="hidden" name="remember" value="1">
                    <button type="submit" class="w-full text-left px-3 py-2.5 rounded-xl border border-[#E7E5E4] dark:border-white/10 hover:border-[#C6A15B]/60 bg-stone-50 dark:bg-white/[0.03] hover:bg-[#C6A15B]/10 transition-all group">
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-[#171513] dark:text-white group-hover:text-[#C6A15B]">Front Desk / Shift &rarr;</span>
                        <span class="block text-[10px] text-stone-500 dark:text-stone-400 truncate">reception@venuecloud.pk</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Alternative Options / Registration Links -->
        <div class="border-t border-[#E7E5E4] dark:border-white/10 pt-5 space-y-3">
            <p class="text-[11px] text-center text-stone-500 dark:text-stone-400 font-sans">
                Don't have an account yet?
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-center text-xs">
                <a href="{{ route('register') }}" 
                   class="p-2.5 rounded-lg border border-[#C6A15B]/30 hover:border-[#C6A15B] bg-[#C6A15B]/5 hover:bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] font-sans text-[11px] font-bold uppercase tracking-wider transition-colors">
                    Register Venue
                </a>
                <a href="{{ route('customer.register') }}" 
                   class="p-2.5 rounded-lg border border-stone-200 dark:border-white/10 hover:border-stone-400 dark:hover:border-white/30 bg-stone-50 dark:bg-white/5 text-stone-700 dark:text-stone-300 font-sans text-[11px] font-bold uppercase tracking-wider transition-colors">
                    Customer Sign Up
                </a>
            </div>
        </div>

    </div>
</div>
