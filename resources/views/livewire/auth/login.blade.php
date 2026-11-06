<div class="w-full max-w-md">
    <!-- Card Container -->
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 rounded-2xl p-7 sm:p-9 shadow-xl space-y-6 relative overflow-hidden">

        <!-- Brand Logo -->
        <div class="flex justify-center -mb-1">
            <img src="{{ asset('images/eventhub-logo.png') }}" width="56" height="56" fetchpriority="high" decoding="async" alt="EventHub Logo" class="h-14 w-14 object-contain rounded-xl border border-[#C6A15B]/40 bg-[#171513] shadow-sm p-1">
        </div>

        <!-- Heading Section (Clean, Universal Luxury Login) -->
        <div class="text-center space-y-1.5">
            <h1 class="font-serif-luxury text-2xl sm:text-3xl text-[#171513] dark:text-white font-normal tracking-tight">
                Welcome Back
            </h1>
            <p class="text-xs text-stone-500 dark:text-stone-400 font-sans max-w-xs mx-auto leading-relaxed">
                Please enter your credentials to sign in.
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

        @if($loginRetryAfter > 0)
            <div class="p-3 rounded-lg bg-amber-500/10 border border-amber-500/50 text-amber-600 dark:text-amber-400 text-xs text-center font-medium"
                 x-data="{ remaining: {{ $loginRetryAfter }}, timer: null }"
                 x-init="() => {
                    let n = {{ $loginRetryAfter }};
                    const startTimer = () => {
                        timer = setInterval(() => {
                            n -= 1;
                            if (n <= 0) {
                                clearInterval(timer);
                                timer = null;
                                $wire.call('clearLoginRetryAfter');
                                return;
                            }
                            remaining = n;
                        }, 1000);
                    };
                    startTimer();
                    return () => { if (timer) clearInterval(timer); };
                 }">
                <div class="flex items-center justify-center gap-2">
                    <svg class="w-3.5 h-3.5 text-amber-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Too many login attempts. Retry in</span>
                    <span class="font-bold text-amber-700 dark:text-amber-300 tabular-nums" x-text="remaining">0</span>
                    <span>seconds.</span>
                </div>
            </div>
        @endif

        @auth
            <div class="p-3 rounded-xl bg-[#C6A15B]/10 border border-[#C6A15B]/35 text-xs flex items-center justify-between gap-3">
                <div class="text-stone-700 dark:text-stone-200">
                    Signed in as <strong class="text-[#8F7239] dark:text-[#C6A15B]">{{ auth()->user()->name }}</strong>
                </div>
                <a href="{{ auth()->user()->isSuperAdmin() ? route('admin.dashboard') : (auth()->user()->isCustomer() ? route('customer.dashboard') : route('dashboard')) }}"
                   class="px-3 py-1.5 rounded-lg bg-[#C6A15B] text-[#171513] font-bold uppercase tracking-wider text-[10px] shrink-0">
                    Open Dashboard &rarr;
                </a>
            </div>
        @endauth

        <!-- Sign In Form (Supports both Livewire and Native POST) -->
        <form wire:submit="login" method="POST" action="{{ route('login.perform') }}" class="space-y-4">
            @csrf
            <!-- Email -->
            <div>
                <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-sans">
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
                       placeholder="name@example.com"
                       class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                @error('email') 
                    <span class="text-rose-500 text-xs mt-1 block font-sans">{{ $message }}</span> 
                @enderror
            </div>

            <!-- Password -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-stone-700 dark:text-stone-300 font-sans">
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
            <div class="flex items-center justify-between text-xs pt-0.5">
                <label class="flex items-center gap-2 cursor-pointer text-stone-600 dark:text-stone-400 select-none">
                    <input type="checkbox" name="remember" value="1" wire:model="remember" class="accent-[#C6A15B] rounded">
                    <span>Remember me</span>
                </label>
                <span class="inline-flex items-center gap-1 text-stone-400 dark:text-stone-500 text-[11px] font-sans">
                    <svg class="w-3.5 h-3.5 text-[#C6A15B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    256-bit Encrypted
                </span>
            </div>

            <!-- Submit Button -->
            <div class="pt-1.5">
                <button type="submit" 
                        class="btn-gold !w-full !h-11 text-xs font-bold uppercase tracking-wider font-sans rounded-lg shadow-sm transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer">
                    <span>Sign In</span>
                </button>
            </div>
        </form>

        <!-- Clean Footer Link -->
        <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10 text-center text-xs text-stone-500 dark:text-stone-400">
            <span>Don't have an account?</span>
            <a href="{{ route('customer.register') }}" class="text-[#8F7239] dark:text-[#C6A15B] hover:underline font-semibold ml-1">
                Sign up &rarr;
            </a>
        </div>

        <!-- Discreet Demo Quick-Fill for Testing -->
        <div class="pt-1 text-center">
            <details class="text-[11px] text-stone-400 dark:text-stone-500 cursor-pointer group">
                <summary class="hover:text-[#8F7239] dark:hover:text-[#C6A15B] transition-colors py-0.5 select-none inline-flex items-center gap-1 font-sans text-[10px]">
                    <span>Demo Accounts</span>
                    <svg class="w-3 h-3 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="mt-2 flex flex-wrap justify-center gap-1.5 pt-1">
                    <button type="button" @click="$wire.set('email', 'customer@example.com'); $wire.set('password', 'password'); document.getElementById('email').value = 'customer@example.com'; document.getElementById('password').value = 'password';" class="px-2.5 py-1 rounded-md bg-stone-100 dark:bg-white/5 hover:bg-[#C6A15B]/15 text-stone-600 dark:text-stone-300 hover:text-[#C6A15B] text-[10px] font-sans border border-stone-200 dark:border-white/10 transition cursor-pointer">
                        Customer
                    </button>
                    <button type="button" @click="$wire.set('email', 'owner@venuecloud.pk'); $wire.set('password', 'password'); document.getElementById('email').value = 'owner@venuecloud.pk'; document.getElementById('password').value = 'password';" class="px-2.5 py-1 rounded-md bg-stone-100 dark:bg-white/5 hover:bg-[#C6A15B]/15 text-stone-600 dark:text-stone-300 hover:text-[#C6A15B] text-[10px] font-sans border border-stone-200 dark:border-white/10 transition cursor-pointer">
                        Hall Owner
                    </button>
                    <button type="button" @click="$wire.set('email', 'admin@eventhub.com'); $wire.set('password', 'admin1235'); document.getElementById('email').value = 'admin@eventhub.com'; document.getElementById('password').value = 'admin1235';" class="px-2.5 py-1 rounded-md bg-stone-100 dark:bg-white/5 hover:bg-[#C6A15B]/15 text-stone-600 dark:text-stone-300 hover:text-[#C6A15B] text-[10px] font-sans border border-stone-200 dark:border-white/10 transition cursor-pointer">
                        Super Admin
                    </button>
                </div>
            </details>
        </div>

    </div>
</div>
