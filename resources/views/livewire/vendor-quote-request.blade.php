<div class="bg-[#F8F5EF] dark:bg-[#171513] text-[#171513] dark:text-[#F8F5EF] min-h-screen transition-colors">
    <!-- Hero strip -->
    <section class="relative bg-[#171513] text-white py-12 border-b border-[#C6A15B]/30 overflow-hidden">
        <div class="parallax-bg opacity-40" data-parallax="0.2"
             style="background-image: url('https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=2000&auto=format&fit=crop');"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#171513] via-[#171513]/60 to-[#171513]/75"></div>
        <div class="relative z-10 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-3">
            <nav class="text-[11px] font-sans uppercase tracking-wider text-stone-400 flex items-center justify-center gap-2" aria-label="Breadcrumb">
                <a href="{{ route('public.vendors') }}" class="hover:text-[#C6A15B] transition-colors">Marketplace</a>
                <span>/</span>
                <a href="{{ route('public.vendor-profile', ['slug' => $vendor->slug]) }}" class="hover:text-[#C6A15B] transition-colors">{{ $vendor->name }}</a>
                <span>/</span>
                <span class="text-[#C6A15B]">Request Quote</span>
            </nav>
            <h1 class="font-serif-luxury text-3xl sm:text-4xl">Request a Free Quote</h1>
            <p class="text-sm text-stone-300 font-light">
                Tell {{ $vendor->name }} about your event — they usually respond within 24 hours.
            </p>
        </div>
    </section>

    <section class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        @if($submitted)
            <!-- ================= SUCCESS STATE ================= -->
            <div class="bg-white dark:bg-[#1C1917] rounded-3xl border border-[#C6A15B]/40 p-8 sm:p-12 text-center shadow-[0_20px_50px_-16px_rgba(23,21,19,0.25)]">
                <div class="w-16 h-16 mx-auto rounded-full bg-[#C6A15B]/15 flex items-center justify-center mb-5">
                    <svg class="w-8 h-8 text-[#8F7239]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                </div>
                <h2 class="font-serif-luxury text-2xl sm:text-3xl mb-3">Request Sent!</h2>
                <p class="text-sm text-stone-600 dark:text-stone-300 font-sans leading-relaxed max-w-md mx-auto">
                    Your quote request <strong class="text-[#8F7239] dark:text-[#C6A15B]">RFQ #{{ $rfq->id }}</strong>
                    for <strong>{{ $vendor->name }}</strong> has been delivered. Aap dashboard ke quotations section mein
                    vendor ke offers receive karte dekh sakte hain.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 mt-8">
                    <a href="{{ route('public.vendor-profile', ['slug' => $vendor->slug]) }}"
                       class="btn-outline-gold px-6 py-2.5 text-xs font-bold uppercase tracking-wider rounded-xl">Back to profile</a>
                    <a href="{{ route(auth()->user()->portalHomeRoute()) }}"
                       class="btn-gold px-6 py-2.5 text-xs font-bold uppercase tracking-wider rounded-xl">My Dashboard</a>
                </div>
            </div>
        @else
            <!-- ================= REQUEST FORM ================= -->
            <form wire:submit="save" class="bg-white dark:bg-[#1C1917] rounded-3xl border border-[#C6A15B]/30 p-6 sm:p-9 space-y-6 shadow-[0_16px_44px_-14px_rgba(23,21,19,0.22)]">
                {{-- Vendor summary --}}
                <div class="flex items-center gap-4 pb-5 border-b border-[#C6A15B]/20">
                    <div class="w-12 h-12 rounded-full bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513] flex items-center justify-center font-serif-luxury text-lg shrink-0">
                        {{ mb_substr($vendor->name, 0, 1) }}
                    </div>
                    <div class="min-w-0">
                        <p class="font-serif-luxury text-lg truncate">{{ $vendor->name }}</p>
                        <p class="text-[11px] uppercase tracking-wider font-semibold text-[#8F7239] dark:text-[#C6A15B]">
                            {{ $category?->name }} @if($vendor->city) · {{ $vendor->city }} @endif
                        </p>
                    </div>
                    @if($this->selectedService)
                        <div class="ml-auto text-right shrink-0">
                            <p class="text-[10px] uppercase tracking-wider text-stone-400 font-semibold">Selected</p>
                            <p class="text-xs font-semibold text-stone-600 dark:text-stone-300 truncate max-w-[180px]">{{ $this->selectedService->name }}</p>
                            <p class="text-[11px] font-bold text-[#8F7239] dark:text-[#C6A15B]">{{ $this->selectedService->priceLabel() }}</p>
                        </div>
                    @endif
                </div>

                {{-- Event basics --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label for="quote-event-name" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Event name <span class="text-[#8F7239]">*</span></label>
                        <input id="quote-event-name" type="text" wire:model="eventName" maxlength="150"
                               placeholder="e.g. Baraat — Ali &amp; Fatima"
                               class="input-luxury w-full text-sm dark:bg-[#171513] dark:text-white dark:border-white/15 rounded-lg">
                        @error('eventName') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="quote-event-date" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Event date <span class="text-[#8F7239]">*</span></label>
                        <input id="quote-event-date" type="date" wire:model="eventDate" min="{{ now()->toDateString() }}"
                               class="input-luxury w-full text-sm dark:bg-[#171513] dark:text-white dark:border-white/15 rounded-lg">
                        @error('eventDate') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="quote-city" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Event city</label>
                        <input id="quote-city" type="text" wire:model="city" maxlength="80"
                               class="input-luxury w-full text-sm dark:bg-[#171513] dark:text-white dark:border-white/15 rounded-lg">
                        @error('city') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Category-specific fields (driven by vendor_categories.booking_mode) --}}
                @if($category?->booking_mode === 'HOURLY')
                    <div>
                        <label for="quote-hours" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Coverage hours <span class="text-[#8F7239]">*</span></label>
                        <input id="quote-hours" type="number" wire:model="hours" min="1" max="24" step="1" placeholder="e.g. 6"
                               class="input-luxury w-full text-sm dark:bg-[#171513] dark:text-white dark:border-white/15 rounded-lg">
                        <p class="mt-1 text-[11px] text-stone-400 font-sans">Kitne ghante ki coverage chahiye? (photography/video sessions)</p>
                        @error('hours') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                @elseif($category?->booking_mode === 'PER_HEAD')
                    <div>
                        <label for="quote-guests" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Guest count <span class="text-[#8F7239]">*</span></label>
                        <input id="quote-guests" type="number" wire:model="guestCount" min="1" max="20000" placeholder="e.g. 400"
                               class="input-luxury w-full text-sm dark:bg-[#171513] dark:text-white dark:border-white/15 rounded-lg">
                        <p class="mt-1 text-[11px] text-stone-400 font-sans">Per-head menus ka andaza lagane ke liye guests ki tadaad zaroori hai.</p>
                        @error('guestCount') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                @elseif($category?->booking_mode === 'APPOINTMENT')
                    <div>
                        <label for="quote-start-time" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Preferred time <span class="text-[#8F7239]">*</span></label>
                        <input id="quote-start-time" type="time" wire:model="startTime"
                               class="input-luxury w-full text-sm dark:bg-[#171513] dark:text-white dark:border-white/15 rounded-lg">
                        <p class="mt-1 text-[11px] text-stone-400 font-sans">Appointment ke liye preferred waqt chunein.</p>
                        @error('startTime') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                @else
                    <div>
                        <label for="quote-guests-opt" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Expected guests</label>
                        <input id="quote-guests-opt" type="number" wire:model="guestCount" min="1" max="20000" placeholder="e.g. 300"
                               class="input-luxury w-full text-sm dark:bg-[#171513] dark:text-white dark:border-white/15 rounded-lg">
                        @error('guestCount') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                @endif

                {{-- Budget --}}
                <div>
                    <label for="quote-budget" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Budget range (PKR)</label>
                    <select id="quote-budget" wire:model="budgetRange"
                            class="input-luxury w-full text-sm dark:bg-[#171513] dark:text-white dark:border-white/15 rounded-lg">
                        <option value="not-sure">Not sure yet</option>
                        <option value="under-50k">Under PKR 50,000</option>
                        <option value="50k-150k">PKR 50,000 – 150,000</option>
                        <option value="150k-300k">PKR 150,000 – 300,000</option>
                        <option value="300k-plus">PKR 300,000+</option>
                    </select>
                    @error('budgetRange') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- Requirements --}}
                <div>
                    <label for="quote-requirements" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Requirements</label>
                    <textarea id="quote-requirements" wire:model="requirements" rows="5" maxlength="5000"
                              placeholder="Apni requirements describe karein — theme, style, timings, location, any special requests…"
                              class="input-luxury w-full text-sm dark:bg-[#171513] dark:text-white dark:border-white/15 rounded-lg resize-y"></textarea>
                    @error('requirements') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- Submit --}}
                <div class="pt-2 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-[11px] text-stone-400 font-sans order-2 sm:order-1">
                        <svg class="w-3.5 h-3.5 inline-block -mt-0.5 text-[#C6A15B]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-11.5a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z" clip-rule="evenodd"/></svg>
                        Free &amp; no obligation — only {{ $vendor->name }} sees this request.
                    </p>
                    <button type="submit" wire:loading.attr="disabled"
                            class="btn-gold px-8 py-3 text-sm font-bold uppercase tracking-wider rounded-xl shadow-[0_10px_30px_-8px_rgba(198,161,91,0.6)] order-1 sm:order-2 w-full sm:w-auto disabled:opacity-60">
                        <span wire:loading.remove delay.300ms>Send Quote Request</span>
                        <span wire:loading delay.300ms>Sending…</span>
                    </button>
                </div>
            </form>
        @endif
    </section>
</div>
