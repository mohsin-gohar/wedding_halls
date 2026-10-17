<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-[#F8F5EF] dark:bg-[#171513] transition-colors">
    <!-- Left Brand Panel -->
    <div class="hidden lg:flex lg:w-1/2 h-screen flex-col items-center justify-center p-8 border-r border-[#E7E5E4] dark:border-white/10 bg-[#171513]/5 text-white">
        <div class="text-center space-y-6 max-w-lg">
            <div class="flex items-center justify-center gap-3 mb-2">
                <div class="w-12 h-12 border border-[#C6A15B] flex items-center justify-center bg-[#C6A15B] text-[#171513]">
                    <span class="font-cinzel text-lg font-bold">V</span>
                </div>
                <span class="font-cinzel text-2xl font-bold tracking-[0.16em]">EventHub</span>
            </div>
            <h1 class="font-serif-luxury text-3xl lg:text-5xl font-normal leading-tight text-white">
                Hall Management<br>
                <span class="text-[#C6A15B] italic">Admin &amp; Owner Access</span>
            </h1>
            <p class="text-sm lg:text-base text-stone-300 font-light leading-relaxed">
                This is the <strong>only login portal</strong> for venue hall administrators and website owner administrators. Sign in to access your dashboard, manage your hall, update photos, services, and availability.
            </p>
        </div>
    </div>

    <!-- Right Login Form -->
    <div class="w-full max-w-md lg:w-1/2 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-8 sm:p-12 shadow-xl space-y-6 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-[#C6A15B] via-[#E8DCBE] to-[#C6A15B]"></div>

        <!-- Header -->
        <div class="text-center space-y-3">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-[#C6A15B]/50 bg-[#171513]/5 text-[#8F7239] dark:text-[#C6A15B] text-[10px] font-bold uppercase tracking-widest font-mono">
                <span class="w-1.5 h-1.5 bg-[#C6A15B] rounded-full"></span>
                Hall Admin &amp; Website Owner Login
            </span>
            <h2 class="font-serif-luxury text-2xl sm:text-3xl text-[#171513] dark:text-white font-normal">
                Sign In to Dashboard
            </h2>
            <p class="text-xs text-stone-500 dark:text-stone-400 font-normal max-w-xs mx-auto leading-relaxed">
                Enter your credentials below. After login, you will be redirected to your respective dashboard — Hall Admin Dashboard or Website Owner Admin Dashboard.
            </p>
        </div>

        @if(session('message'))
            <div class="p-3 bg-[#171513]/5 border border-[#C6A15B]/40 text-[#8F7239] dark:text-[#C6A15B] text-xs text-center font-medium rounded-lg">
                {{ session('message') }}
            </div>
        @endif

        @if(session('error'))
            <div class="p-3 bg-[#7F1D1D]/10 border border-[#7F1D1D]/40 text-[#FEE2E2] dark:text-[#FEE2E2] text-xs text-center font-medium rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <!-- Login Form -->
        <form wire:submit="login" class="space-y-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-mono">
                    Email Address
                </label>
                <input type="email" wire:model="email" required placeholder="admin@yourhall.com.pk"
                       class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                @error('email') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1.5 font-mono">
                    Password
                </label>
                <input type="password" wire:model="password" required placeholder="••••••••"
                       class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm w-full">
                @error('password') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center justify-between text-xs pt-1">
                <label class="flex items-center gap-2 cursor-pointer text-stone-600 dark:text-stone-400">
                    <input type="checkbox" wire:model="remember" class="accent-[#C6A15B]">
                    <span>Keep me signed in</span>
                </label>
                <span class="text-stone-400 dark:text-stone-500 text-[10px] font-mono">Encrypted Access</span>
            </div>

            <div class="pt-2">
                 <button type="submit" class="btn-gold !w-full !h-12 text-xs font-bold uppercase tracking-wider font-mono shadow-lg hover:shadow-xl transition-all duration-200">
                     Sign In to Dashboard &rarr;
                 </button>
            </div>
        </form>

        <!-- Role Information -->
        <div class="border-t border-[#E7E5E4] dark:border-white/10 pt-6 space-y-3">
            <p class="text-[10px] font-mono uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold">
                Who can use this portal
            </p>
            <div class="space-y-2">
                <div class="flex items-start gap-3">
                    <span class="text-[10px] bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] px-2 py-1 rounded font-mono font-bold uppercase">Hall Admin</span>
                    <span class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed">Manages a specific wedding hall — photos, services, availability, bookings.</span>
                </div>
                <div class="flex items-start gap-3">
                    <span class="text-[10px] bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] px-2 py-1 rounded font-mono font-bold uppercase">Website Owner</span>
                    <span class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed">Platform super admin — manages all halls, tenants, subscriptions, and system settings.</span>
                </div>
            </div>
        </div>

        <!-- Not a hall admin? -->
        <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10 flex flex-col gap-2.5 text-center">
            <p class="text-xs text-stone-500 dark:text-stone-400">
                Looking to book a hall as a customer?
            </p>
            <a href="{{ route('home') }}" class="text-xs text-[#C6A15B] dark:text-[#C6A15B] hover:underline font-bold inline-block">
                Visit Public Website &rarr;
            </a>
        </div>
    </div>
</div>
