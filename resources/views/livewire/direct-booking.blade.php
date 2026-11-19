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
                <span class="text-[#C6A15B]">Book Now</span>
            </nav>
            <h1 class="font-serif-luxury text-3xl sm:text-4xl">Book Instantly</h1>
            <p class="text-sm text-stone-300 font-light">
                Confirm your date with {{ $vendor->name }} — no waiting for quotations.
            </p>
        </div>
    </section>

    <section class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        @if($submitted && $booking)
            <!-- ================= SUCCESS STATE ================= -->
            <div class="bg-white dark:bg-[#1C1917] rounded-3xl border border-[#C6A15B]/40 p-8 sm:p-12 text-center shadow-[0_20px_50px_-16px_rgba(23,21,19,0.25)]">
                <div class="w-16 h-16 mx-auto rounded-full bg-[#C6A15B]/15 flex items-center justify-center mb-5">
                    <svg class="w-8 h-8 text-[#8F7239]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                </div>
                <h2 class="font-serif-luxury text-2xl sm:text-3xl mb-3">Booking Requested!</h2>
                <p class="text-sm text-stone-600 dark:text-stone-300 font-sans leading-relaxed max-w-md mx-auto">
                    Your booking <strong class="text-[#8F7239] dark:text-[#C6A15B] font-mono">{{ $booking->booking_reference }}</strong>
                    with <strong>{{ $vendor->name }}</strong> for
                    <strong>{{ $booking->event_date?->format('d M Y') }}</strong> has been received.
                    Aap dashboard ke <em>My Quotes &amp; Services</em> section mein iski progress dekh sakte hain.
                </p>

                <div class="mt-6 inline-flex flex-col items-center gap-1 px-6 py-4 rounded-2xl bg-[#C6A15B]/10 border border-[#C6A15B]/30">
                    <span class="text-[10px] uppercase tracking-widest font-bold text-[#8F7239] dark:text-[#C6A15B]">Agreed amount</span>
                    <span class="font-serif-luxury text-2xl text-[#8F7239] dark:text-[#C6A15B]">PKR {{ number_format((float) $booking->quoted_amount, 0) }}</span>
                    <span class="text-[11px] text-stone-500 dark:text-stone-400 font-sans">
                        Advance due: PKR {{ number_format((float) $booking->advance_amount, 0) }}
                    </span>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 mt-8">
                    <a href="{{ route('public.vendor-profile', ['slug' => $vendor->slug]) }}"
                       class="btn-outline-gold px-6 py-2.5 text-xs font-bold uppercase tracking-wider rounded-xl">Back to profile</a>
                    <a href="{{ route(auth()->user()->portalHomeRoute()) }}"
                       class="btn-gold px-6 py-2.5 text-xs font-bold uppercase tracking-wider rounded-xl">My Dashboard</a>
                </div>
            </div>
        @elseif(! $this->bookable)
            <!-- ================= NO ITEM SELECTED ================= -->
            <div class="bg-white dark:bg-[#1C1917] rounded-3xl border border-[#C6A15B]/30 p-8 sm:p-12 text-center space-y-4">
                <h2 class="font-serif-luxury text-2xl">Choose a service or package first</h2>
                <p class="text-sm text-stone-600 dark:text-stone-300 font-sans leading-relaxed max-w-md mx-auto">
                    Direct bookings start from a vendor's service or package card. Browse
                    {{ $vendor->name }}'s catalogue and hit <strong>Book Now</strong> on the item you want.
                </p>
                <a href="{{ route('public.vendor-profile', ['slug' => $vendor->slug]) }}"
                   class="btn-gold inline-flex px-6 py-2.5 text-xs font-bold uppercase tracking-wider rounded-xl">View catalogue</a>
            </div>
        @elseif(! $this->canDirectBook)
            <!-- ================= QUOTE-ONLY ITEM ================= -->
            <div class="bg-white dark:bg-[#1C1917] rounded-3xl border border-[#C6A15B]/30 p-8 sm:p-12 text-center space-y-4">
                <h2 class="font-serif-luxury text-2xl">Price on request</h2>
                <p class="text-sm text-stone-600 dark:text-stone-300 font-sans leading-relaxed max-w-md mx-auto">
                    <strong>{{ $this->bookable->name }}</strong> doesn't have a fixed price yet, so it can't be
                    booked instantly. Send a quote request instead — {{ $vendor->name }} usually responds within 24 hours.
                </p>
                <a href="{{ route('public.vendor-quote', ['slug' => $vendor->slug, 'service' => $this->selectedService?->id]) }}"
                   class="btn-gold inline-flex px-6 py-2.5 text-xs font-bold uppercase tracking-wider rounded-xl">Request a Quote</a>
            </div>
        @else
            <!-- ================= BOOKING FORM ================= -->
            @if($errorMessage)
                <div class="mb-5 p-4 rounded-xl bg-red-500/10 border border-red-500/40 text-red-600 dark:text-red-400 text-xs font-semibold shadow-md flex items-center justify-between">
                    <span>⚠ {{ $errorMessage }}</span>
                    <button type="button" wire:click="$set('errorMessage', null)" class="text-red-500 text-lg font-bold leading-none">&times;</button>
                </div>
            @endif

            <form wire:submit="save" class="bg-white dark:bg-[#1C1917] rounded-3xl border border-[#C6A15B]/30 p-6 sm:p-9 space-y-6 shadow-[0_16px_44px_-14px_rgba(23,21,19,0.22)]">
                {{-- Vendor + item summary --}}
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
                    <div class="ml-auto text-right shrink-0">
                        <p class="text-[10px] uppercase tracking-wider text-stone-400 font-semibold">Booking</p>
                        <p class="text-xs font-semibold text-stone-600 dark:text-stone-300 truncate max-w-[180px]">{{ $this->bookable->name }}</p>
                        <p class="text-[11px] font-bold text-[#8F7239] dark:text-[#C6A15B]">{{ $this->bookable->priceLabel() }}</p>
                    </div>
                </div>

                {{-- Event basics --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label for="book-event-name" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Event name <span class="text-[#8F7239]">*</span></label>
                        <input id="book-event-name" type="text" wire:model="eventName" maxlength="150"
                               placeholder="e.g. Baraat — Ali &amp; Fatima"
                               class="input-luxury w-full text-sm dark:bg-[#171513] dark:text-white dark:border-white/15 rounded-lg">
                        @error('eventName') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="book-event-date" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Event date <span class="text-[#8F7239]">*</span></label>
                        <input id="book-event-date" type="date" wire:model="eventDate" min="{{ now()->toDateString() }}"
                               class="input-luxury w-full text-sm dark:bg-[#171513] dark:text-white dark:border-white/15 rounded-lg">
                        @error('eventDate') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    @if(in_array($this->bookingMode, ['APPOINTMENT', 'HOURLY'], true))
                        <div>
                            <label for="book-start-time" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Start time <span class="text-[#8F7239]">*</span></label>
                            <input id="book-start-time" type="time" wire:model="startTime"
                                   class="input-luxury w-full text-sm dark:bg-[#171513] dark:text-white dark:border-white/15 rounded-lg">
                            <p class="mt-1 text-[11px] text-stone-400 font-sans">
                                @if($this->bookingMode === 'APPOINTMENT')
                                    Ek waqt ek hi booking ke liye mojood hota hai.
                                @else
                                    Coverage is waqt se shuru hogi — overlap slots book nahi honge.
                                @endif
                            </p>
                            @error('startTime') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    @else
                        <div class="hidden sm:block"></div>
                    @endif
                </div>

                {{-- Category-specific fields (driven by vendor_categories.booking_mode) --}}
                @if($this->bookingMode === 'HOURLY')
                    <div>
                        <label for="book-hours" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Coverage hours <span class="text-[#8F7239]">*</span></label>
                        <input id="book-hours" type="number" wire:model="hours" min="1" max="24" step="1" placeholder="e.g. 6"
                               class="input-luxury w-full text-sm dark:bg-[#171513] dark:text-white dark:border-white/15 rounded-lg">
                        @error('hours') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                @elseif($this->bookingMode === 'PER_HEAD')
                    <div>
                        <label for="book-guests" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Guest count <span class="text-[#8F7239]">*</span></label>
                        <input id="book-guests" type="number" wire:model="guestCount" min="1" max="20000" placeholder="e.g. 400"
                               class="input-luxury w-full text-sm dark:bg-[#171513] dark:text-white dark:border-white/15 rounded-lg">
                        <p class="mt-1 text-[11px] text-stone-400 font-sans">Per-head price guests ki tadaad ke hisaab se calculate hota hai.</p>
                        @error('guestCount') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="book-guests-opt" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Expected guests</label>
                            <input id="book-guests-opt" type="number" wire:model="guestCount" min="1" max="20000" placeholder="e.g. 300"
                                   class="input-luxury w-full text-sm dark:bg-[#171513] dark:text-white dark:border-white/15 rounded-lg">
                            @error('guestCount') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="book-hours-opt" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Hours (optional)</label>
                            <input id="book-hours-opt" type="number" wire:model="hours" min="1" max="24" step="1" placeholder="e.g. 4"
                                   class="input-luxury w-full text-sm dark:bg-[#171513] dark:text-white dark:border-white/15 rounded-lg">
                            @error('hours') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endif

                {{-- Requirements --}}
                <div>
                    <label for="book-requirements" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Notes for the vendor</label>
                    <textarea id="book-requirements" wire:model="requirements" rows="4" maxlength="5000"
                              placeholder="Theme, style, timings, location, special requests…"
                              class="input-luxury w-full text-sm dark:bg-[#171513] dark:text-white dark:border-white/15 rounded-lg resize-y"></textarea>
                    @error('requirements') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- Server-computed price --}}
                <div class="rounded-2xl border border-[#C6A15B]/30 bg-[#C6A15B]/5 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="min-w-0">
                        <p class="text-[10px] uppercase tracking-widest font-bold text-[#8F7239] dark:text-[#C6A15B]">Total price</p>
                        <p class="text-[11px] text-stone-500 dark:text-stone-400 font-sans mt-0.5">
                            Server se calculate hota hai — aap ki party ki taraf se koi price enter nahi hota.
                        </p>
                    </div>
                    <div class="sm:ml-auto text-right shrink-0">
                        @if($this->pricePreview !== null)
                            <p class="font-serif-luxury text-2xl text-[#8F7239] dark:text-[#C6A15B]">PKR {{ number_format($this->pricePreview, 0) }}</p>
                            @if($this->bookingMode === 'PER_HEAD')
                                <p class="text-[11px] text-stone-500 dark:text-stone-400 font-sans">{{ number_format((int) $guestCount) }} guests × per-head rate</p>
                            @elseif($this->bookingMode === 'HOURLY')
                                <p class="text-[11px] text-stone-500 dark:text-stone-400 font-sans">{{ rtrim(rtrim(number_format((float) $hours, 2), '0'), '.') }} hours × hourly rate</p>
                            @endif
                        @else
                            <p class="text-xs font-semibold text-stone-500 dark:text-stone-400 font-sans">
                                @if($this->bookingMode === 'PER_HEAD') Guest count daalein taake price calculate ho.
                                @elseif($this->bookingMode === 'HOURLY') Hours daalein taake price calculate ho.
                                @else Price calculated at confirmation.
                                @endif
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Submit --}}
                <div class="pt-2 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-[11px] text-stone-400 font-sans order-2 sm:order-1">
                        <svg class="w-3.5 h-3.5 inline-block -mt-0.5 text-[#C6A15B]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-11.5a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z" clip-rule="evenodd"/></svg>
                        Vendor confirm karega — advance payment uske baad.
                    </p>
                    <button type="submit" wire:loading.attr="disabled"
                            class="btn-gold px-8 py-3 text-sm font-bold uppercase tracking-wider rounded-xl shadow-[0_10px_30px_-8px_rgba(198,161,91,0.6)] order-1 sm:order-2 w-full sm:w-auto disabled:opacity-60">
                        <span wire:loading.remove delay.300ms>Book Now</span>
                        <span wire:loading delay.300ms>Booking…</span>
                    </button>
                </div>
            </form>
        @endif
    </section>
</div>
