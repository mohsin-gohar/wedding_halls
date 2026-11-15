<div class="space-y-8 max-w-7xl mx-auto pb-16">
    <!-- Header Banner -->
    <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-6 sm:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6 shadow-xl transition-colors duration-200">
        <div class="space-y-2">
            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 border border-[#C6A15B]/50 bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] text-[10px] font-mono uppercase tracking-widest font-bold">
                💼 Vendor Marketplace ERP • Quotations &amp; Bids
            </div>
            <h1 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                Quotation Inquiries
            </h1>
            <p class="text-xs text-stone-600 dark:text-stone-300 font-sans max-w-2xl leading-relaxed">
                Customer RFQs addressed to your business land here. Submit competitive quotations; the customer reviews offers before converting an accepted offer into a confirmed service booking.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <span class="px-4 py-2 border border-[#C6A15B]/50 bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] text-[11px] font-mono uppercase tracking-widest font-bold">
                Open RFQs: {{ $this->openQuoteCount }}
            </span>
            <a href="{{ route('public.vendors') }}" target="_blank" class="btn-outline-gold !h-10 !px-4 text-xs font-mono uppercase transition flex items-center gap-2 font-bold">
                <span>Marketplace ↗</span>
            </a>
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

    @if($this->rfqs->isEmpty())
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-10 text-center shadow-sm dark:shadow-xl">
            <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white mb-2">No open quotations</h3>
            <p class="text-sm text-stone-600 dark:text-stone-300">When customers request quotes from your business you will see them here.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-5">
            @foreach($this->rfqs as $rfq)
                <article class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 shadow-sm dark:shadow-xl p-5 sm:p-6 hover:border-[#C6A15B] transition">
                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                        <div class="space-y-2 min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2.5 py-0.5 border border-[#C6A15B]/50 bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] text-[10px] font-mono uppercase tracking-widest font-bold">
                                    {{ $rfq->vendorCategory?->name ?? 'General' }}
                                </span>
                                <span class="px-2.5 py-0.5 border border-stone-200 dark:border-white/15 text-stone-600 dark:text-stone-300 text-[10px] font-mono uppercase tracking-widest font-bold">
                                    RFQ #{{ $rfq->id }}
                                </span>
                                <span class="px-2.5 py-0.5 bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513] text-[10px] font-mono uppercase tracking-widest font-bold">
                                    {{ strtoupper($rfq->status) }}
                                </span>
                            </div>

                            <h2 class="font-serif-luxury text-xl text-[#171513] dark:text-white truncate">{{ $rfq->title }}</h2>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs text-stone-600 dark:text-stone-300 font-sans">
                                <div><span class="block text-[10px] uppercase tracking-wider font-semibold text-stone-400">Event date</span><span>{{ $rfq->event_date?->format('M d, Y') ?? '—' }}</span></div>
                                <div><span class="block text-[10px] uppercase tracking-wider font-semibold text-stone-400">City</span><span>{{ $rfq->city ?? '—' }}</span></div>
                                <div><span class="block text-[10px] uppercase tracking-wider font-semibold text-stone-400">Guests</span><span>{{ $rfq->guest_count ?? '—' }}</span></div>
                                <div><span class="block text-[10px] uppercase tracking-wider font-semibold text-stone-400">Budget</span><span>{{ $rfq->budget_range }}</span></div>
                            </div>

                            @if($rfq->requirements)
                                <p class="text-xs text-stone-600 dark:text-stone-300 font-sans leading-relaxed border-l-2 border-[#C6A15B]/40 pl-3">{{ Str::limit($rfq->requirements, 220) }}</p>
                            @endif

                            <div class="flex flex-wrap items-center gap-2 pt-2">
                                <button wire:click="markRfqViewed({{ $rfq->id }})" class="btn-outline-gold !h-9 !px-4 text-[10px] font-mono uppercase">
                                    Mark viewed
                                </button>
                                <button wire:click="openBidModal({{ $rfq->id }})" class="btn-gold !h-9 !px-4 text-[10px] font-mono uppercase">
                                    Submit quotation
                                </button>
                                <button wire:click="declineRfq({{ $rfq->id }})" class="btn-outline-gold !h-9 !px-4 text-[10px] font-mono uppercase"
                            </button>
                            </div>
                        </div>

                        <div class="lg:min-w-56 lg:text-right space-y-2">
                            <p class="text-[10px] uppercase tracking-wider font-semibold text-stone-400 font-mono">Quotation offers</p>
                            @if($rfq->bids->isEmpty())
                                <p class="text-xs text-stone-500 dark:text-stone-400 font-sans">No offers yet — you may quote first.</p>
                            @else
                                @foreach($rfq->bids as $bid)
                                    <div class="border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#1C1917] p-3">
                                        <p class="text-xs font-bold text-[#171513] dark:text-white">{{ $bid->vendor?->name }}</p>
                                        <p class="text-sm font-serif-luxury text-[#8F7239] dark:text-[#C6A15B] font-bold">{{ number_format((float) $bid->bid_amount) }} PKR</p>
                                        <p class="text-[11px] text-stone-500 dark:text-stone-400 font-sans">Bid status: <span class="font-bold uppercase">{{ $bid->status }}</span></p>
                                        @if($bid->status === 'PENDING')
                                            <button type="button" wire:click="awardQuote({{ $bid->id }})" class="btn-gold !h-8 !px-3 text-[10px] font-mono uppercase mt-2">
                                                Award booking → creates service booking
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $this->rfqs->links() }}
        </div>
    @endif

    <!-- Bid Modal -->
    @if($showBidModal && $selectedRfq)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true" role="dialog" aria-labelledby="bid-title">
            <div class="absolute inset-0 bg-[#171513]/70 backdrop-blur-sm" wire:click="closeBidModal"></div>
            <div class="relative mx-auto mt-16 w-full max-w-lg rounded-2xl bg-white dark:bg-[#171513] border border-[#C6A15B]/50 shadow-2xl p-6">
                <h2 id="bid-title" class="font-serif-luxury text-2xl text-[#171513] dark:text-white mb-4">Submit Quotation</h2>

                <div class="space-y-4">
                    <div>
                        <label for="bid-rfq" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">For RFQ #{{ $selectedRfq->id }}</label>
                        <p class="text-sm font-medium text-stone-700 dark:text-stone-200">{{ $selectedRfq->title }}</p>
                    </div>

                    <div>
                        <label for="bid-amount" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Bid amount (PKR)</label>
                        <input id="bid-amount" type="number" step="0.01" min="0" wire:model="bidAmount" class="input-luxury w-full text-sm">
                        @error('bidAmount') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="bid-proposal" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Proposal details</label>
                        <textarea id="bid-proposal" wire:model="bidProposal" rows="5" class="input-luxury w-full text-sm resize-y"></textarea>
                        @error('bidProposal') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="closeBidModal" class="btn-outline-gold !h-10 !px-5 text-[11px] font-mono uppercase">Close</button>
                        <button type="button" wire:click="submitBid({ id: selectedRfqId }, bidAmount, bidProposal)" class="btn-gold !h-10 !px-6 text-[11px] font-mono uppercase">Submit Quotation</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
