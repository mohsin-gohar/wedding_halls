<div class="min-h-[75vh] flex items-center justify-center py-16 px-4 sm:px-6 lg:px-8 bg-[#F8F5EF]">
    <div class="w-full max-w-md bg-white border border-[#E7E5E4] p-8 sm:p-10 shadow-sm space-y-6">
        
        <!-- Header -->
        <div class="text-center space-y-2">
            <span class="text-[10px] tracking-[0.2em] text-[#8F7239] uppercase font-bold block">
                Customer Registration
            </span>
            <h2 class="font-serif-luxury text-3xl text-[#171513] font-normal">
                Create Customer Account
            </h2>
            <p class="text-xs text-stone-500 font-normal">
                Save quotations, book banquet shifts, track advance payments, and download A4 contracts.
            </p>
        </div>

        <!-- Form -->
        <form wire:submit="register" class="space-y-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1">
                    Your Full Name *
                </label>
                <input type="text" wire:model="name" required placeholder="e.g. Chaudhry Ahmad Tariq" class="input-luxury text-sm">
                @error('name') <span class="text-[#171513] text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1">
                    Email Address *
                </label>
                <input type="email" wire:model="email" required placeholder="ahmad@example.com" class="input-luxury text-sm">
                @error('email') <span class="text-[#171513] text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1">
                    Phone / WhatsApp *
                </label>
                <input type="text" wire:model="phone" required placeholder="+92 300 1234567" class="input-luxury text-sm">
                @error('phone') <span class="text-[#171513] text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1">
                        Password *
                    </label>
                    <input type="password" wire:model="password" required placeholder="••••••••" class="input-luxury text-sm">
                    @error('password') <span class="text-[#171513] text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1">
                        Confirm *
                    </label>
                    <input type="password" wire:model="password_confirmation" required placeholder="••••••••" class="input-luxury text-sm">
                </div>
            </div>

            <button type="submit" class="btn-gold !w-full !h-11 text-xs font-bold uppercase tracking-wider">
                Complete Registration &rarr;
            </button>
        </form>

        <div class="pt-4 border-t border-[#E7E5E4] text-center text-xs text-stone-500">
            <span>Already have an account?</span>
            <a href="{{ route('customer.login') }}" class="text-[#8F7239] hover:underline font-bold ml-1">
                Sign In &rarr;
            </a>
        </div>

    </div>
</div>
