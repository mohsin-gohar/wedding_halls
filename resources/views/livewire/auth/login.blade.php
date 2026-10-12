<div class="min-h-[75vh] flex items-center justify-center py-16 px-4 sm:px-6 lg:px-8 bg-[#F8F5EF]">
    <div class="w-full max-w-md bg-white border border-[#E7E5E4] p-8 sm:p-10 shadow-sm space-y-6">
        
        <!-- Header -->
        <div class="text-center space-y-2">
            <span class="text-[10px] tracking-[0.2em] text-[#8F7239] uppercase font-bold block">
                Venue Operator Portal
            </span>
            <h2 class="font-serif-luxury text-3xl text-[#171513] font-normal">
                Sign In to Your Hall
            </h2>
            <p class="text-xs text-stone-500 font-normal">
                Manage your photos, 4K videos, booking shifts, kitchen KOTs, and private analytics.
            </p>
        </div>

        @if(session('message'))
            <div class="p-3 bg-[#C6A15B]/15 border border-[#C6A15B]/60 text-[#8F7239] text-xs text-center font-medium">
                {{ session('message') }}
            </div>
        @endif

        <!-- Login Form -->
        <form wire:submit="login" class="space-y-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1">
                    Official Email
                </label>
                <input type="email" wire:model="email" required placeholder="owner@yourhall.pk"
                       class="input-luxury text-sm">
                @error('email') <span class="text-[#171513] text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1">
                    Password
                </label>
                <input type="password" wire:model="password" required placeholder="••••••••"
                       class="input-luxury text-sm">
                @error('password') <span class="text-[#171513] text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center justify-between text-xs pt-1">
                <label class="flex items-center gap-2 cursor-pointer text-stone-600">
                    <input type="checkbox" wire:model="remember" class="accent-[#C6A15B]">
                    <span>Remember me</span>
                </label>
                <span class="text-stone-400 text-[11px] font-mono">Demo: password</span>
            </div>

            <div class="pt-2">
                <button type="submit" class="btn-gold !w-full !h-11 text-xs font-bold uppercase tracking-wider">
                    Sign In to Dashboard &rarr;
                </button>
            </div>
        </form>

        <!-- Customer Login Alternative -->
        <div class="pt-4 border-t border-[#E7E5E4] flex flex-col gap-2 text-center text-xs text-stone-500">
            <div>
                <span>Don't have your hall registered?</span>
                <a href="{{ route('register') }}" class="text-[#8F7239] hover:underline font-bold ml-1">
                    Register Hall &rarr;
                </a>
            </div>
            <div>
                <a href="{{ route('customer.login') }}" class="text-stone-500 hover:text-stone-900 underline">
                    Looking for Customer Account Login?
                </a>
            </div>
        </div>

    </div>
</div>
