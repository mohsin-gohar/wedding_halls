<div class="w-full max-w-md mx-auto">
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 rounded-2xl p-8 sm:p-10 shadow-xl space-y-6 relative overflow-hidden">

        <!-- Brand Logo -->
        <div class="flex justify-center -mb-1">
            <img src="{{ asset('images/eventhub-logo.png') }}" alt="EventHub Logo" class="h-14 w-auto object-contain rounded-xl border border-[#C6A15B]/40 bg-[#171513] shadow-sm p-1">
        </div>

        <!-- Header -->
        <div class="text-center space-y-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border border-[#C6A15B]/30 bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] text-[10px] font-bold uppercase tracking-widest font-sans">
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
            <div class="p-3 rounded-lg bg-[#C6A15B]/15 border border-[#C6A15B]/60 text-[#8F7239] dark:text-[#C6A15B] text-xs text-center font-medium">
                {{ session('message') }}
            </div>
        @endif

        @if(session('error'))
            <div class="p-3 rounded-lg bg-red-950/40 border border-red-700/60 text-red-300 text-xs text-center font-medium">
                {{ session('error') }}
            </div>
        @endif

        <!-- Form -->
        <form method="POST" action="{{ route('customer.login.perform') }}" class="space-y-4">
            @csrf
            <div>
                <label for="customer-email" class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-sans">
                    Email Address
                </label>
                <input id="customer-email" name="email" type="email" value="{{ old('email', $email) }}" wire:model="email" required autofocus placeholder="your.name@example.com" 
                       class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                @error('email') <span class="text-rose-500 text-xs mt-1 block font-sans">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="customer-password" class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-sans">
                    Password
                </label>
                <input id="customer-password" name="password" type="password" wire:model="password" required placeholder="••••••••" 
                       class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                @error('password') <span class="text-rose-500 text-xs mt-1 block font-sans">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center justify-between text-xs pt-1">
                <label class="flex items-center gap-2 cursor-pointer text-stone-600 dark:text-stone-400">
                    <input type="checkbox" name="remember" value="1" wire:model="remember" class="accent-[#C6A15B] rounded">
                    <span>Remember me</span>
                </label>
                <a href="{{ route('login') }}" class="text-[#8F7239] dark:text-[#C6A15B] hover:underline font-sans text-[11px] font-semibold">
                    Unified Sign In &rarr;
                </a>
            </div>

            <div class="pt-2">
                <button type="submit" 
                        class="btn-gold !w-full !h-12 text-xs font-bold uppercase tracking-wider font-sans rounded-lg shadow-sm transition-all duration-200">
                    Sign In &rarr;
                </button>
            </div>
        </form>

        <!-- 1-Click Customer Demo Login -->
        <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10">
            <form method="POST" action="{{ route('customer.login.perform') }}">
                @csrf
                <input type="hidden" name="email" value="customer@example.com">
                <input type="hidden" name="password" value="password">
                <input type="hidden" name="remember" value="1">
                <button type="submit" class="w-full flex items-center justify-between px-4 py-2.5 rounded-xl border border-[#C6A15B]/35 hover:border-[#C6A15B] bg-[#C6A15B]/10 hover:bg-[#C6A15B]/20 transition-all text-left">
                    <div>
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-[#8F7239] dark:text-[#F2DE9A]">1-Click Customer Demo Sign In</span>
                        <span class="block text-[10px] text-stone-500 dark:text-stone-400">customer@example.com · password</span>
                    </div>
                    <span class="text-xs font-bold text-[#8F7239] dark:text-[#C6A15B]">&rarr;</span>
                </button>
            </form>
        </div>

        <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10 text-center text-xs text-stone-500 dark:text-stone-400">
            <span>Don't have an account yet?</span>
            <a href="{{ route('customer.register') }}" class="text-[#8F7239] dark:text-[#C6A15B] hover:underline font-bold ml-1 font-sans">
                Create Account &rarr;
            </a>
        </div>

    </div>
</div>
