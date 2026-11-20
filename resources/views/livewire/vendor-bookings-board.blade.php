<div class="space-y-8 max-w-7xl mx-auto pb-16">
    <!-- Header Banner -->
    <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-6 sm:p-8 shadow-xl transition-colors duration-200">
        <div class="space-y-2">
            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 border border-[#C6A15B]/50 bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] text-[10px] font-mono uppercase tracking-widest font-bold">
                🚚 Vendor Marketplace ERP • Service Bookings
            </div>
            <h1 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                Service Bookings
            </h1>
            <p class="text-xs text-stone-600 dark:text-stone-300 font-sans max-w-2xl leading-relaxed">
                Category-specific service bookings accepted from the marketplace RFQ flow. Advance payments, deliverables and completion tracking happen here.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="px-4 py-2 border border-[#C6A15B]/50 bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] text-[11px] font-mono uppercase tracking-widest font-bold">
                Active: {{ $this->stats['confirmed'] }}
            </div>
            <div class="px-4 py-2 border border-[#C6A15B]/50 bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] text-[11px] font-mono uppercase tracking-widest font-bold">
                Review: {{ $this->stats['pending'] }}
            </div>
        </div>
    </div>

    @if($feedbackMessage)
        <div class="p-4 bg-[#171513]/90 border border-[#C6A15B]/60 text-[#C6A15B] text-xs font-semibold shadow-md">
            ✓ {{ $feedbackMessage }}
        </div>
    @endif

    @if($errorMessage)
        <div class="p-4 bg-[#171513]/90 border border-[#C6A15B]/60 text-[#F8F5EF] text-xs font-semibold shadow-md">
            ⚠️ {{ $errorMessage }}
        </div>
    @endif

    @if($this->bookings->isEmpty())
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-10 text-center shadow-sm dark:shadow-xl">
            <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white mb-2">No service bookings yet</h3>
            <p class="text-sm text-stone-600 dark:text-stone-300">Accepted marketplace quotations will appear here as service bookings.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-5">
            @foreach($this->bookings as $booking)
                <article class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 shadow-sm dark:shadow-xl p-5 sm:p-6 hover:border-[#C6A15B] transition">
                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                        <div class="space-y-2 min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2.5 py-0.5 border border-[#C6A15B]/50 bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] text-[10px] font-mono uppercase tracking-widest font-bold">
                                    {{ $booking->vendorCategory?->name ?? 'Service' }}
                                </span>
                                <span class="px-2.5 py-0.5 border border-stone-200 dark:border-white/15 text-stone-600 dark:text-stone-300 text-[10px] font-mono uppercase tracking-widest font-bold">
                                    {{ $booking->booking_reference }}
                                </span>
                                <span class="px-2.5 py-0.5 bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513] text-[10px] font-mono uppercase tracking-widest font-bold">
                                    {{ $booking->status }}
                                </span>
                            </div>

                            <h2 class="font-serif-luxury text-xl text-[#171513] dark:text-white truncate">{{ $booking->event_name }}</h2>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs text-stone-600 dark:text-stone-300 font-sans">
                                <div>
                                    <span class="block text-[10px] uppercase tracking-wider font-semibold text-stone-400">Event date</span>
                                    <span>{{ $booking->event_date?->format('M d, Y') ?? '—' }}</span>
                                </div>
                                <div>
                                    <span class="block text-[10px] uppercase tracking-wider font-semibold text-stone-400">Customer</span>
                                    <span>{{ $booking->customer?->name ?? '—' }}</span>
                                </div>
                                <div>
                                    <span class="block text-[10px] uppercase tracking-wider font-semibold text-stone-400">Quoted</span>
                                    <span>{{ number_format((float) $booking->quoted_amount) }} PKR</span>
                                </div>
                                <div>
                                    <span class="block text-[10px] uppercase tracking-wider font-semibold text-stone-400">Paid</span>
                                    <span>{{ number_format((float) $booking->paid_amount) }} PKR</span>
                                </div>
                            </div>

                            @if(in_array($booking->status, ['ACCEPTED', 'CONFIRMED', 'IN_PROGRESS'], true))
                                <div class="flex flex-wrap gap-2 pt-2">
                                    @if($booking->status === 'ACCEPTED' && $this->hasPortalPermission('service-bookings.confirm'))
                                        <button wire:click="confirmBooking({{ $booking->id }})" class="btn-gold !h-9 !px-4 text-[10px] font-mono uppercase">Confirm booking</button>
                                    @endif
                                    @if(in_array($booking->status, ['CONFIRMED', 'ACCEPTED'], true) && $this->hasPortalPermission('service-bookings.update'))
                                        <button wire:click="startBooking({{ $booking->id }})" class="btn-outline-gold !h-9 !px-4 text-[10px] font-mono uppercase">Start event</button>
                                    @endif
                                    @if($booking->status === 'IN_PROGRESS' && $this->hasPortalPermission('service-bookings.complete'))
                                        <button wire:click="completeBooking({{ $booking->id }})" class="btn-outline-gold !h-9 !px-4 text-[10px] font-mono uppercase">Mark completed</button>
                                    @endif
                                    @if($booking->isCancellable() && $this->hasPortalPermission('service-bookings.cancel'))
                                        <button wire:click="cancelBooking({{ $booking->id }}, 'Vendor-initiated cancellation.')" class="btn-outline-gold !h-9 !px-4 text-[10px] font-mono uppercase">Cancel</button>
                                    @endif
                                </div>
                            @endif

                            @if($this->canVerifyPayments && $this->pendingPaymentsFor($booking->id)->isNotEmpty())
                                <div class="mt-3 border-t border-[#E7E5E4] dark:border-white/10 pt-3">
                                    <p class="text-[10px] uppercase tracking-wider font-semibold text-stone-400 font-mono mb-2">Cash payments awaiting verification</p>
                                    <div class="space-y-2">
                                        @foreach($this->pendingPaymentsFor($booking->id) as $pending)
                                            <div class="flex flex-wrap items-center justify-between gap-3 bg-[#F8F5EF] dark:bg-[#1C1917] p-2.5 text-xs font-sans">
                                                <span class="text-stone-600 dark:text-stone-300">
                                                    {{ number_format((float) $pending->amount, 2) }} PKR
                                                    <span class="block text-[10px] text-stone-400 font-mono">{{ $pending->reference ?? 'Cash submission' }}</span>
                                                </span>
                                                @if($this->canVerifyPayments)
                                                    <button type="button" wire:click="verifyPayment('{{ $pending->id }}')"
                                                            class="btn-gold !h-8 !px-3 text-[10px] font-mono uppercase"
                                                            onclick="return confirm('Verify that cash was physically received and settle this payment?');">
                                                        Verify cash
                                                    </button>
                                                @else
                                                    <span class="text-[10px] text-stone-400">Verification restricted</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="lg:min-w-64 lg:text-right space-y-2">
                            <p class="text-[10px] uppercase tracking-wider font-semibold text-stone-400 font-mono">Event</p>
                            <p class="text-xs text-stone-600 dark:text-stone-300 font-sans">{{ $booking->event_date?->format('M d, Y') ?? '—' }}</p>
                            <p class="text-[11px] text-stone-500 dark:text-stone-400 font-sans">Customer: {{ $booking->customer?->name ?? '—' }}</p>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $this->bookings->links() }}
        </div>
    @endif
</div>
