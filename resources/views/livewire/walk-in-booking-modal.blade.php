<div>
    @if($isOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-md overflow-y-auto">
        <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/60 gold-foil-border max-w-2xl w-full shadow-[0_25px_70px_rgba(0,0,0,0.6)] overflow-hidden my-8 p-6 sm:p-8 space-y-6 relative transform transition-all text-[#171513] dark:text-[#F8F5EF]">
            
            <!-- Modal Header (Editorial Luxury Style Matching Home Hero) -->
            <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-5 flex items-start justify-between">
                <div>

                    <h2 class="font-serif-luxury text-2xl sm:text-3xl text-[#171513] dark:text-white font-normal tracking-wide mt-1">
                        Walk-in Reservation Wizard
                    </h2>
                    <p class="text-xs text-stone-600 dark:text-stone-300 font-sans mt-1">
                        Select wedding space, event date, operational shift slot, and catering package.
                    </p>
                </div>
                <button wire:click="closeModal" class="w-9 h-9 border border-[#E7E5E4] dark:border-white/20 hover:border-[#C6A15B] text-stone-600 dark:text-white/70 hover:text-[#171513] dark:hover:text-white hover:bg-[#F8F5EF] dark:hover:bg-[#C6A15B]/10 flex items-center justify-center transition text-xl font-bold leading-none">&times;</button>
            </div>

            <!-- Wizard Progress Steps (Minimalist Editorial Tabs) -->
            @if($step < 5)
            <div class="grid grid-cols-4 gap-2 border-b border-[#E7E5E4] dark:border-white/10 pb-4">
                <div class="text-center">
                    <div class="h-1 transition {{ $step >= 1 ? 'bg-[#C6A15B]' : 'bg-stone-200 dark:bg-stone-800' }}"></div>
                    <span class="text-[11px] uppercase tracking-wider block mt-2 font-mono {{ $step >= 1 ? 'text-[#8F7239] dark:text-[#C6A15B] font-bold' : 'text-stone-400 dark:text-stone-500' }}">1. Shift</span>
                </div>
                <div class="text-center">
                    <div class="h-1 transition {{ $step >= 2 ? 'bg-[#C6A15B]' : 'bg-stone-200 dark:bg-stone-800' }}"></div>
                    <span class="text-[11px] uppercase tracking-wider block mt-2 font-mono {{ $step >= 2 ? 'text-[#8F7239] dark:text-[#C6A15B] font-bold' : 'text-stone-400 dark:text-stone-500' }}">2. Host</span>
                </div>
                <div class="text-center">
                    <div class="h-1 transition {{ $step >= 3 ? 'bg-[#C6A15B]' : 'bg-stone-200 dark:bg-stone-800' }}"></div>
                    <span class="text-[11px] uppercase tracking-wider block mt-2 font-mono {{ $step >= 3 ? 'text-[#8F7239] dark:text-[#C6A15B] font-bold' : 'text-stone-400 dark:text-stone-500' }}">3. Menu</span>
                </div>
                <div class="text-center">
                    <div class="h-1 transition {{ $step >= 4 ? 'bg-[#C6A15B]' : 'bg-stone-200 dark:bg-stone-800' }}"></div>
                    <span class="text-[11px] uppercase tracking-wider block mt-2 font-mono {{ $step >= 4 ? 'text-[#8F7239] dark:text-[#C6A15B] font-bold' : 'text-stone-400 dark:text-stone-500' }}">{{ $isStaff ? '4. Advance' : '4. Review' }}</span>
                </div>
            </div>
            @endif

            <!-- Error Banner -->
            @if($errorMessage)
            <div class="p-3.5 bg-[#171513]/8 dark:bg-[#171513]/90 border border-[#C6A15B]/50 dark:border-[#C6A15B]/60 text-xs text-[#171513] dark:text-[#F8F5EF] font-medium flex items-center justify-between shadow-sm">
                <span>{{ $errorMessage }}</span>
                <button wire:click="$set('errorMessage', null)" class="text-[#171513] dark:text-[#F8F5EF] font-bold text-base">&times;</button>
            </div>
            @endif

            <!-- STEP 1: Date & Slot Picker -->
            @if($step === 1)
            <div class="space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] uppercase tracking-[0.1em] text-[#8F7239] dark:text-[#C6A15B] mb-1.5 font-mono font-bold">Select Banquet Hall</label>
                        <select wire:model.live="hall_id" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] focus:ring-1 focus:ring-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 text-sm font-medium">
                            @foreach($halls as $hall)
                                <option value="{{ $hall->id }}">{{ $hall->name }} (Rent: PKR {{ number_format($hall->base_rent) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] uppercase tracking-[0.1em] text-[#8F7239] dark:text-[#C6A15B] mb-1.5 font-mono font-bold">Event Date</label>
                        <input type="date" wire:model.live="event_date" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] focus:ring-1 focus:ring-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 text-sm font-medium">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] uppercase tracking-[0.1em] text-[#8F7239] dark:text-[#C6A15B] mb-1.5 font-mono font-bold">Event Function Type</label>
                    <select wire:model.live="event_type" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] focus:ring-1 focus:ring-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 text-sm font-medium">
                        <option value="Barat">Barat Ceremony</option>
                        <option value="Valima">Valima Reception</option>
                        <option value="Mehndi">Mehndi Celebration</option>
                        <option value="Qawwali Night">Qawwali / Musical Gala</option>
                        <option value="Engagement">Engagement Party</option>
                        <option value="Corporate Banquet">Corporate Banquet</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] uppercase tracking-[0.1em] text-[#8F7239] dark:text-[#C6A15B] mb-2 font-mono font-bold">Operational Shift Slot</label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="cursor-pointer border p-3.5 text-center transition {{ $slot_type === 'AFTERNOON' ? 'border-[#C6A15B] bg-[#C6A15B]/15 text-[#171513] dark:text-[#C6A15B] font-bold shadow-sm ring-1 ring-[#C6A15B]' : 'border-[#E7E5E4] dark:border-white/15 bg-[#F8F5EF] dark:bg-[#171513] hover:border-[#C6A15B] text-stone-700 dark:text-stone-300' }}">
                            <input type="radio" wire:model.live="slot_type" value="AFTERNOON" class="sr-only">
                            <span class="text-xs uppercase font-bold block font-mono">Afternoon</span>
                            <span class="text-[11px] text-stone-500 dark:text-stone-400 block font-mono mt-1">12:00 – 16:00</span>
                        </label>

                        <label class="cursor-pointer border p-3.5 text-center transition {{ $slot_type === 'EVENING' ? 'border-[#C6A15B] bg-[#C6A15B]/15 text-[#171513] dark:text-[#C6A15B] font-bold shadow-sm ring-1 ring-[#C6A15B]' : 'border-[#E7E5E4] dark:border-white/15 bg-[#F8F5EF] dark:bg-[#171513] hover:border-[#C6A15B] text-stone-700 dark:text-stone-300' }}">
                            <input type="radio" wire:model.live="slot_type" value="EVENING" class="sr-only">
                            <span class="text-xs uppercase font-bold block font-mono">Evening</span>
                            <span class="text-[11px] text-stone-500 dark:text-stone-400 block font-mono mt-1">19:00 – 23:00</span>
                        </label>

                        <label class="cursor-pointer border p-3.5 text-center transition {{ $slot_type === 'NIGHT' ? 'border-[#C6A15B] bg-[#C6A15B]/15 text-[#171513] dark:text-[#C6A15B] font-bold shadow-sm ring-1 ring-[#C6A15B]' : 'border-[#E7E5E4] dark:border-white/15 bg-[#F8F5EF] dark:bg-[#171513] hover:border-[#C6A15B] text-stone-700 dark:text-stone-300' }}">
                            <input type="radio" wire:model.live="slot_type" value="NIGHT" class="sr-only">
                            <span class="text-xs uppercase font-bold block font-mono">Late Night</span>
                            <span class="text-[11px] text-stone-500 dark:text-stone-400 block font-mono mt-1">23:30 – 03:30</span>
                        </label>
                    </div>
                </div>

                @if($slotMessage)
                <div class="p-3.5 text-xs text-center border font-semibold font-mono {{ $isSlotAvailable ? 'border-[#C6A15B]/60 dark:border-[#C6A15B]/70 bg-[#C6A15B]/15 dark:bg-[#171513]/85 text-[#8F7239] dark:text-[#C6A15B]' : 'border-[#C6A15B]/50 dark:border-[#C6A15B]/70 bg-[#171513]/8 dark:bg-[#171513]/85 text-[#171513] dark:text-[#F8F5EF]' }}">
                    {{ $slotMessage }}
                </div>
                @endif

                <div class="flex justify-end pt-4 border-t border-[#E7E5E4] dark:border-white/10">
                    <button wire:click="goToStep(2)" {{ !$isSlotAvailable ? 'disabled' : '' }} class="btn-gold !h-11 !px-8 text-xs font-bold uppercase tracking-wider font-mono shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
                        Next: Host Details &rarr;
                    </button>
                </div>
            </div>
            @endif

            <!-- STEP 2: Customer Profile & Lookup -->
            @if($step === 2)
            <div class="space-y-4">
                @if($isStaff)
                <div class="p-4 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-2">
                    <label class="block text-[11px] uppercase tracking-[0.1em] text-[#8F7239] dark:text-[#C6A15B] font-mono font-bold">Returning Host Phone Lookup (Tenant Isolated)</label>
                    <div class="flex gap-2">
                        <input type="text" wire:model="customer_phone" placeholder="Enter Host Mobile (e.g. 03001234567)" class="flex-1 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2 px-3 text-sm placeholder-stone-400">
                        <button type="button" wire:click="lookupClient" class="border border-[#C6A15B] bg-[#C6A15B]/15 hover:bg-[#C6A15B] text-[#171513] dark:text-[#C6A15B] hover:text-[#171513] px-4 py-2 text-xs font-bold uppercase font-mono transition">
                            Search
                        </button>
                    </div>
                    @if($clientFound)
                        <span class="text-xs text-[#8F7239] dark:text-[#C6A15B] block font-semibold font-mono">✓ Returning client profile found &amp; auto-filled</span>
                    @endif
                </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] uppercase tracking-[0.1em] text-[#8F7239] dark:text-[#C6A15B] mb-1.5 font-mono font-bold">Host Full Name *</label>
                        <input type="text" wire:model="customer_name" placeholder="Chaudhry Muhammad Tariq" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 text-sm placeholder-stone-400">
                        @error('customer_name') <span class="text-xs text-rose-600 dark:text-rose-400 font-mono mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[11px] uppercase tracking-[0.1em] text-[#8F7239] dark:text-[#C6A15B] mb-1.5 font-mono font-bold">Mobile Phone *</label>
                        <input type="text" wire:model="customer_phone" placeholder="03001234567" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 text-sm placeholder-stone-400">
                        @error('customer_phone') <span class="text-xs text-rose-600 dark:text-rose-400 font-mono mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] uppercase tracking-[0.1em] text-[#8F7239] dark:text-[#C6A15B] mb-1.5 font-mono font-bold">Email Address (Optional)</label>
                        <input type="email" wire:model="customer_email" placeholder="client@example.com" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 text-sm placeholder-stone-400">
                        @error('customer_email') <span class="text-xs text-rose-600 dark:text-rose-400 font-mono mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    @if($isStaff)
                    <div>
                        <label class="block text-[11px] uppercase tracking-[0.1em] text-[#8F7239] dark:text-[#C6A15B] mb-1.5 font-mono font-bold">CNIC / Passport (13 Digits)</label>
                        <input type="text" wire:model="customer_cnic" placeholder="35201-1234567-1" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 text-sm placeholder-stone-400">
                    </div>
                    @endif
                </div>

                <div class="flex justify-between pt-4 border-t border-[#E7E5E4] dark:border-white/10">
                    <button wire:click="goToStep(1)" class="btn-obsidian dark:bg-white/10 dark:text-white dark:border-white/20 !h-10 !px-5 text-xs font-mono">
                        &larr; Back
                    </button>
                    <button wire:click="goToStep(3)" class="btn-gold !h-10 !px-7 text-xs font-bold uppercase font-mono tracking-wider shadow-md">
                        Next: Catering &amp; Menu &rarr;
                    </button>
                </div>
            </div>
            @endif

            <!-- STEP 3: Dynamic Menu & Grand Total Calculator -->
            @if($step === 3)
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] uppercase tracking-[0.1em] text-[#8F7239] dark:text-[#C6A15B] mb-1.5 font-mono font-bold">Catering Package</label>
                        <select wire:model.live="package_id" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 text-sm font-medium">
                            <option value="">Custom Per-Head Menu</option>
                            @foreach($packages as $pkg)
                                <option value="{{ $pkg->id }}">{{ $pkg->name }} (PKR {{ number_format($pkg->per_head_price) }}/head)</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] uppercase tracking-[0.1em] text-[#8F7239] dark:text-[#C6A15B] mb-1.5 font-mono font-bold">Guest Headcount</label>
                        <input type="number" wire:model.live="guest_count" min="20" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 text-sm font-mono font-bold">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] uppercase tracking-[0.1em] text-[#8F7239] dark:text-[#C6A15B] mb-1.5 font-mono font-bold">Per-Head Food Rate (PKR)</label>
                        <input type="number" wire:model.live="per_head_rate" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 text-sm font-mono font-bold">
                    </div>

                    <div>
                        <label class="block text-[11px] uppercase tracking-[0.1em] text-[#8F7239] dark:text-[#C6A15B] mb-1.5 font-mono font-bold">Hall Base Rent (PKR)</label>
                        <input type="number" wire:model.live="hall_rent" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-[#C6A15B] py-2.5 px-3.5 text-sm font-mono font-bold">
                    </div>
                </div>

                <!-- Addons Checkboxes (Clean Minimalist Cards) -->
                <div>
                    <label class="block text-[11px] uppercase tracking-[0.1em] text-[#8F7239] dark:text-[#C6A15B] mb-2 font-mono font-bold">Hospitality Add-ons &amp; Decor</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                        <label class="flex items-center justify-between gap-2 p-2.5 border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#171513] cursor-pointer hover:border-[#C6A15B] text-[#171513] dark:text-stone-200 transition">
                            <span class="inline-flex items-center gap-2 min-w-0">
                                <input type="checkbox" wire:click="toggleAddon('sparklers')" {{ in_array('sparklers', $selected_addons) ? 'checked' : '' }} class="accent-[#C6A15B] w-4 h-4 shrink-0">
                                <span class="font-medium font-sans truncate">Sparklers Entry</span>
                            </span>
                            <span class="inline-flex items-center gap-1 font-mono text-[11px] text-stone-500 shrink-0">
                                <input type="number" wire:model.live="addonPrices.sparklers" min="0" step="500" class="w-20 border border-[#E7E5E4] dark:border-white/15 bg-white dark:bg-white/5 px-2 py-0.5 text-xs font-mono text-[#171513] dark:text-white text-right"> PKR
                            </span>
                        </label>
                        <label class="flex items-center justify-between gap-2 p-2.5 border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#171513] cursor-pointer hover:border-[#C6A15B] text-[#171513] dark:text-stone-200 transition">
                            <span class="inline-flex items-center gap-2 min-w-0">
                                <input type="checkbox" wire:click="toggleAddon('dj_lighting')" {{ in_array('dj_lighting', $selected_addons) ? 'checked' : '' }} class="accent-[#C6A15B] w-4 h-4 shrink-0">
                                <span class="font-medium font-sans truncate">DJ &amp; Moving Lights</span>
                            </span>
                            <span class="inline-flex items-center gap-1 font-mono text-[11px] text-stone-500 shrink-0">
                                <input type="number" wire:model.live="addonPrices.dj_lighting" min="0" step="500" class="w-20 border border-[#E7E5E4] dark:border-white/15 bg-white dark:bg-white/5 px-2 py-0.5 text-xs font-mono text-[#171513] dark:text-white text-right"> PKR
                            </span>
                        </label>
                        <label class="flex items-center justify-between gap-2 p-2.5 border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#171513] cursor-pointer hover:border-[#C6A15B] text-[#171513] dark:text-stone-200 transition">
                            <span class="inline-flex items-center gap-2 min-w-0">
                                <input type="checkbox" wire:click="toggleAddon('stage_flowers')" {{ in_array('stage_flowers', $selected_addons) ? 'checked' : '' }} class="accent-[#C6A15B] w-4 h-4 shrink-0">
                                <span class="font-medium font-sans truncate">Fresh Floral Stage</span>
                            </span>
                            <span class="inline-flex items-center gap-1 font-mono text-[11px] text-stone-500 shrink-0">
                                <input type="number" wire:model.live="addonPrices.stage_flowers" min="0" step="500" class="w-20 border border-[#E7E5E4] dark:border-white/15 bg-white dark:bg-white/5 px-2 py-0.5 text-xs font-mono text-[#171513] dark:text-white text-right"> PKR
                            </span>
                        </label>
                        <label class="flex items-center justify-between gap-2 p-2.5 border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#171513] cursor-pointer hover:border-[#C6A15B] text-[#171513] dark:text-stone-200 transition">
                            <span class="inline-flex items-center gap-2 min-w-0">
                                <input type="checkbox" wire:click="toggleAddon('valet')" {{ in_array('valet', $selected_addons) ? 'checked' : '' }} class="accent-[#C6A15B] w-4 h-4 shrink-0">
                                <span class="font-medium font-sans truncate">Valet Parking Service</span>
                            </span>
                            <span class="inline-flex items-center gap-1 font-mono text-[11px] text-stone-500 shrink-0">
                                <input type="number" wire:model.live="addonPrices.valet" min="0" step="500" class="w-20 border border-[#E7E5E4] dark:border-white/15 bg-white dark:bg-white/5 px-2 py-0.5 text-xs font-mono text-[#171513] dark:text-white text-right"> PKR
                            </span>
                        </label>
                        <label class="flex items-center justify-between gap-2 p-2.5 border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#171513] cursor-pointer hover:border-[#C6A15B] text-[#171513] dark:text-stone-200 transition sm:col-span-2">
                            <span class="inline-flex items-center gap-2 min-w-0">
                                <input type="checkbox" wire:click="toggleAddon('ac_extra')" {{ in_array('ac_extra', $selected_addons) ? 'checked' : '' }} class="accent-[#C6A15B] w-4 h-4 shrink-0">
                                <span class="font-medium font-sans truncate">AC Extra Capacity</span>
                            </span>
                            <span class="inline-flex items-center gap-1 font-mono text-[11px] text-stone-500 shrink-0">
                                <input type="number" wire:model.live="addonPrices.ac_extra" min="0" step="500" class="w-20 border border-[#E7E5E4] dark:border-white/15 bg-white dark:bg-white/5 px-2 py-0.5 text-xs font-mono text-[#171513] dark:text-white text-right"> PKR
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Live Grand Total Formulation Bar -->
                <div class="p-4 bg-[#F8F5EF] dark:bg-[#171513] border border-[#C6A15B]/50 flex items-center justify-between">
                    <div>
                        <span class="text-xs text-[#8F7239] dark:text-[#C6A15B] font-bold uppercase tracking-wider block font-mono">Total Estimated Cost</span>
                        <span class="text-xs text-stone-500 dark:text-stone-400 font-light font-mono">({{ $per_head_rate }} × {{ $guest_count }}) + Rent + Addons</span>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-stone-500 uppercase block font-semibold font-mono">Grand Total</span>
                        <span class="font-cinzel text-2xl sm:text-3xl font-bold text-[#171513] dark:text-[#C6A15B]">PKR {{ number_format($grand_total) }}</span>
                    </div>
                </div>

                <div class="flex justify-between pt-4 border-t border-[#E7E5E4] dark:border-white/10">
                    <button wire:click="goToStep(2)" class="btn-obsidian dark:bg-white/10 dark:text-white dark:border-white/20 !h-10 !px-5 text-xs font-mono">
                        &larr; Back
                    </button>
                    <button wire:click="goToStep(4)" class="btn-gold !h-10 !px-7 text-xs font-bold uppercase font-mono tracking-wider shadow-md">
                        {{ $isStaff ? 'Next: Advance Deposit &rarr;' : 'Next: Review &amp; Submit &rarr;' }}
                    </button>
                </div>
            </div>
            @endif

            <!-- STEP 4: Advance Payment Collection (Staff) OR Review Summary (Public) -->
            @if($step === 4)
            <div class="space-y-4">
                <div class="p-4 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-2 text-xs font-mono">
                    <div class="flex justify-between items-center font-semibold text-[#171513] dark:text-white">
                        <span class="uppercase tracking-wider text-stone-600 dark:text-stone-300">Event Grand Total:</span>
                        <span class="text-[#171513] dark:text-[#C6A15B] font-cinzel text-xl font-bold">PKR {{ number_format($grand_total) }}</span>
                    </div>
                    @if($isStaff)
                    <div class="flex justify-between items-center text-[#171513] dark:text-[#F8F5EF] font-semibold border-t border-[#E7E5E4] dark:border-white/10 pt-2">
                        <span class="uppercase tracking-wider">Remaining Balance Due:</span>
                        <span class="font-mono text-base font-bold">PKR {{ number_format($balance_due) }}</span>
                    </div>
                    @else
                    <div class="border-t border-[#E7E5E4] dark:border-white/10 pt-2 text-stone-600 dark:text-stone-400 text-xs leading-relaxed font-sans">
                        ℹ️ Submitting this reservation sends a verified booking request directly to the venue reservations manager. No payment is charged online; our manager will contact you to verify details and accept official token deposit.
                    </div>
                    @endif
                </div>

                @if($isStaff)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] uppercase tracking-[0.1em] text-[#8F7239] dark:text-[#C6A15B] mb-1.5 font-mono font-bold">Token Advance Deposit (PKR) *</label>
                        <input type="number" wire:model.live="advance_paid" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border-2 border-[#C6A15B]/60 focus:border-[#C6A15B]/60 text-[#8F7239] dark:text-[#C6A15B] py-2.5 px-3.5 text-lg font-bold font-mono">
                    </div>

                    <div>
                        <label class="block text-[11px] uppercase tracking-[0.1em] text-[#8F7239] dark:text-[#C6A15B] mb-1.5 font-mono font-bold">Payment Method</label>
                        <select wire:model="payment_method" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 text-sm font-medium">
                            <option value="CASH">Cash at Front Desk</option>
                            <option value="BANK_TRANSFER">Online Bank Transfer</option>
                            <option value="JAZZCASH">JazzCash Direct</option>
                            <option value="EASYPAISA">EasyPaisa</option>
                            <option value="CREDIT_CARD">POS Card Terminal</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] uppercase tracking-[0.1em] text-[#8F7239] dark:text-[#C6A15B] mb-1.5 font-mono font-bold">Transaction Reference / Slip No (If Digital)</label>
                    <input type="text" wire:model="transaction_reference" placeholder="e.g. HBL-FT-992104" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 text-sm placeholder-stone-400">
                </div>
                @endif

                <div>
                    <label class="block text-[11px] uppercase tracking-[0.1em] text-[#8F7239] dark:text-[#C6A15B] mb-1.5 font-mono font-bold">{{ $isStaff ? 'Internal Reception Notes' : 'Special Instructions / Requirements' }}</label>
                    <textarea wire:model="notes" rows="2" placeholder="Stage colors champagne & burgundy. Bridal suite required 2 hours prior." class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 text-sm placeholder-stone-400 font-sans"></textarea>
                </div>

                <div class="flex justify-between pt-4 border-t border-[#E7E5E4] dark:border-white/10">
                    <button wire:click="goToStep(3)" class="btn-obsidian dark:bg-white/10 dark:text-white dark:border-white/20 !h-10 !px-5 text-xs font-mono">
                        &larr; Back
                    </button>
                    <button wire:click="confirmBooking" wire:loading.attr="disabled" class="btn-gold !h-10 !px-7 text-xs font-bold uppercase font-mono tracking-wider shadow-md">
                        <span wire:loading.remove>{{ $isStaff ? 'Confirm & Lock Reservation' : 'Submit Booking Request' }}</span>
                        <span wire:loading>Securing Mutex Lock...</span>
                    </button>
                </div>
            </div>
            @endif

            <!-- STEP 5: Success & Instant Confirmation -->
            @if($step === 5 && $createdBooking)
            <div class="p-6 text-center space-y-6">
                <div class="w-16 h-16 border-2 border-[#C6A15B]/60 bg-[#C6A15B]/15 dark:bg-[#171513]/85 flex items-center justify-center mx-auto text-3xl font-cinzel text-[#8F7239] dark:text-[#C6A15B]">
                    ✓
                </div>

                <div>
                    @if($createdBooking->status === 'CONFIRMED')
                        <span class="text-[10px] uppercase tracking-[0.25em] text-[#8F7239] dark:text-[#C6A15B] font-bold block font-mono">
                            Zero-Collision Atomic Lock Acquired
                        </span>
                        <h3 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white mt-2 font-normal">
                            Reservation Confirmed
                        </h3>
                        <p class="text-xs text-stone-600 dark:text-stone-300 font-sans mt-1 max-w-md mx-auto">
                            Booking #{{ substr($createdBooking->id, 0, 8) }} recorded with automated kitchen KOT ingredient calculations.
                        </p>
                    @else
                        <span class="text-[10px] uppercase tracking-[0.25em] text-[#8F7239] dark:text-[#C6A15B] font-bold block font-mono">
                            Booking Request Received
                        </span>
                        <h3 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white mt-2 font-normal">
                            Inquiry Submitted
                        </h3>
                        <p class="text-xs text-stone-600 dark:text-stone-300 font-sans mt-1 max-w-md mx-auto">
                            Inquiry #{{ substr($createdBooking->id, 0, 8) }} has been forwarded to the venue banquet management team. You will be contacted shortly with official quotation.
                        </p>
                    @endif
                </div>

                <!-- Action Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 max-w-lg mx-auto pt-2 font-mono">
                    <a href="{{ route('documents.agreement', $createdBooking->id) }}" target="_blank" class="border border-[#E7E5E4] dark:border-white/20 bg-[#F8F5EF] dark:bg-white/10 hover:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3 text-xs font-bold uppercase transition block text-center">
                        View Contract &rarr;
                    </a>
                    @if($createdBooking->payments->isNotEmpty())
                        <a href="{{ route('documents.receipt', $createdBooking->payments->first()->id) }}" target="_blank" class="btn-gold !h-auto py-2.5 px-3 text-xs font-bold uppercase block text-center">
                            Print Receipt &rarr;
                        </a>
                    @endif
                    @if($createdBooking->kitchenKot && $isStaff)
                        <a href="{{ route('documents.kot', $createdBooking->kitchenKot->id) }}" target="_blank" class="border border-[#C6A15B]/60 bg-[#C6A15B]/15 dark:bg-[#171513]/85 text-[#8F7239] dark:text-[#C6A15B] hover:bg-[#C6A15B]/20 py-2.5 px-3 text-xs font-bold uppercase transition block text-center">
                            Chef KOT Slip &rarr;
                        </a>
                    @endif
                </div>

                <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10">
                    <button wire:click="closeModal" class="btn-obsidian dark:bg-white/10 dark:text-white dark:border-white/20 px-6 py-2.5 text-xs font-bold uppercase tracking-wider font-mono transition">
                        Close Terminal
                    </button>
                </div>
            </div>
            @endif

        </div>
    </div>
    @endif
</div>
