<div class="min-h-[85vh] flex items-center justify-center py-16 px-4 sm:px-6 lg:px-8 bg-[#F8F5EF]">
    <div class="w-full max-w-2xl bg-white border border-[#E7E5E4] p-8 sm:p-12 shadow-sm space-y-8">
        
        <!-- Header -->
        <div class="text-center space-y-2">
            <span class="text-[10px] tracking-[0.2em] text-[#8F7239] uppercase font-bold block">
                Venue Operator Onboarding
            </span>
            <h1 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] font-normal">
                Register Your Wedding Hall
            </h1>
            <p class="text-xs text-stone-500 font-normal max-w-md mx-auto">
                Create your verified venue account on VenueCloud to showcase your ballrooms, receive inquiries, and operate shifts with automated SaaS precision.
            </p>
        </div>

        @if(session('message'))
            <div class="p-4 bg-[#C6A15B]/15 border border-[#C6A15B]/60 text-[#8F7239] text-xs text-center font-medium">
                {{ session('message') }}
            </div>
        @endif

        <!-- Registration Form -->
        <form wire:submit="register" class="space-y-6">
            
            <!-- Hall & Venue Details -->
            <div class="border-b border-[#E7E5E4] pb-5 space-y-4">
                <div class="flex items-center gap-2">
                    <span class="w-1.5 h-1.5 bg-[#C6A15B]"></span>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-stone-800">
                        Venue &amp; Hall Information
                    </h3>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1">
                            Wedding Hall / Marquee Name *
                        </label>
                        <input type="text" wire:model="hallName" required placeholder="e.g. The Royal Crystal Ballroom"
                               class="input-luxury text-sm">
                        @error('hallName') <span class="text-[#171513] text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1">
                            City / Location *
                        </label>
                        <input type="text" wire:model="city" required placeholder="Lahore, Islamabad, Karachi..."
                               class="input-luxury text-sm">
                        @error('city') <span class="text-[#171513] text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1">
                            Max Guest Capacity *
                        </label>
                        <input type="number" wire:model="capacity" required placeholder="500" min="50" max="5000"
                               class="input-luxury text-sm">
                        @error('capacity') <span class="text-[#171513] text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1">
                            Standard Base Shift Rental (PKR) *
                        </label>
                        <input type="number" wire:model="baseRent" required placeholder="150000" min="10000" step="5000"
                               class="input-luxury text-sm font-semibold">
                        <span class="text-[11px] text-stone-400 mt-0.5 block">Indicative starting rental fee per shift (Afternoon/Evening)</span>
                        @error('baseRent') <span class="text-[#171513] text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Business Owner Account -->
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <span class="w-1.5 h-1.5 bg-[#C6A15B]"></span>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-stone-800">
                        Owner Credentials &amp; Contact
                    </h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1">
                            Director / Owner Name *
                        </label>
                        <input type="text" wire:model="ownerName" required placeholder="e.g. Sheikh Ahsan Tariq"
                               class="input-luxury text-sm">
                        @error('ownerName') <span class="text-[#171513] text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1">
                            Official Mobile / WhatsApp *
                        </label>
                        <input type="text" wire:model="phone" required placeholder="+92 300 8472910"
                               class="input-luxury text-sm">
                        @error('phone') <span class="text-[#171513] text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1">
                            Official Business Email *
                        </label>
                        <input type="email" wire:model="email" required placeholder="management@royalballroom.pk"
                               class="input-luxury text-sm">
                        @error('email') <span class="text-[#171513] text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1">
                            Portal Password *
                        </label>
                        <input type="password" wire:model="password" required placeholder="••••••••"
                               class="input-luxury text-sm">
                        @error('password') <span class="text-[#171513] text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1">
                            Confirm Password *
                        </label>
                        <input type="password" wire:model="password_confirmation" required placeholder="••••••••"
                               class="input-luxury text-sm">
                    </div>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="btn-gold !w-full !h-12 text-xs font-bold uppercase tracking-wider">
                    Register Hall &amp; Continue &rarr;
                </button>
            </div>
        </form>

        <div class="pt-4 border-t border-[#E7E5E4] text-center text-xs text-stone-500">
            <span>Already registered your hall?</span>
            <a href="{{ route('login') }}" class="text-[#8F7239] hover:underline font-bold ml-1">
                Sign In to Owner Portal &rarr;
            </a>
        </div>

    </div>
</div>
