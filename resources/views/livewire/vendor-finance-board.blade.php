<div class="space-y-8 max-w-7xl mx-auto pb-16">
    <!-- Header -->
    <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-6 sm:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6 shadow-xl transition-colors duration-200">
        <div class="space-y-2">
            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 border border-[#C6A15B]/50 bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] text-[10px] font-mono uppercase tracking-widest font-bold">
                💰 Vendor Marketplace ERP • Finance &amp; Commission
            </div>
            <h1 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                Finance &amp; Earnings
            </h1>
            <p class="text-xs text-stone-600 dark:text-stone-300 font-sans max-w-2xl leading-relaxed">
                Full transparency on what you earned, the Riwaj Grand commission, outstanding payments and every payout — backed by your wallet ledger.
            </p>
        </div>

        <button wire:click="openPayoutModal" class="btn-gold !h-10 !px-5 text-xs font-bold uppercase font-mono shadow-md">
            Request Payout
        </button>
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

    @php($earnings = $this->earnings)

    <!-- Earnings summary -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
        <div class="bg-[#171513] text-white border border-[#C6A15B]/40 p-6 shadow-xl">
            <p class="text-[10px] font-mono uppercase tracking-widest text-[#C6A15B]">Gross booking value</p>
            <p class="font-serif-luxury text-3xl mt-2">{{ number_format($earnings['gross']) }}</p>
            <p class="text-[11px] text-stone-400 font-sans mt-1">{{ $earnings['booking_count'] }} service bookings</p>
        </div>

        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 shadow-sm dark:shadow-xl">
            <p class="text-[10px] font-mono uppercase tracking-widest text-stone-400">Your net earnings</p>
            <p class="font-serif-luxury text-3xl mt-2 text-[#171513] dark:text-white">{{ number_format($earnings['net']) }}</p>
            <p class="text-[11px] text-stone-500 dark:text-stone-400 font-sans mt-1">After platform commission</p>
        </div>

        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 shadow-sm dark:shadow-xl">
            <p class="text-[10px] font-mono uppercase tracking-widest text-stone-400">Platform commission</p>
            <p class="font-serif-luxury text-3xl mt-2 text-[#8F7239] dark:text-[#C6A15B]">{{ number_format($earnings['commission']) }}</p>
            <p class="text-[11px] text-stone-500 dark:text-stone-400 font-sans mt-1">{{ $earnings['effective_rate'] }}% effective rate</p>
        </div>

        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 shadow-sm dark:shadow-xl">
            <p class="text-[10px] font-mono uppercase tracking-widest text-stone-400">Available to withdraw</p>
            <p class="font-serif-luxury text-3xl mt-2 text-emerald-600 dark:text-emerald-400">{{ number_format($earnings['available']) }}</p>
            <p class="text-[11px] text-stone-500 dark:text-stone-400 font-sans mt-1">
                {{ number_format($earnings['pending_payout']) }} pending approval
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Wallet ledger -->
        <div class="lg:col-span-7 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 shadow-sm dark:shadow-xl">
            <h2 class="font-serif-luxury text-xl text-[#171513] dark:text-white mb-4">Wallet ledger</h2>

            @if($this->transactions->isEmpty())
                <p class="text-sm text-stone-500 dark:text-stone-400 font-sans">No wallet movements yet. Settled bookings appear here automatically.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs font-sans">
                        <thead class="text-[10px] uppercase tracking-wider text-stone-400 font-mono">
                            <tr>
                                <th class="py-2 pr-4">Date</th>
                                <th class="py-2 pr-4">Source</th>
                                <th class="py-2 pr-4">Reference</th>
                                <th class="py-2 pr-4 text-right">Amount</th>
                                <th class="py-2 text-right">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E7E5E4] dark:divide-white/10">
                            @foreach($this->transactions as $tx)
                                <tr>
                                    <td class="py-2 pr-4 text-stone-500 dark:text-stone-400 whitespace-nowrap">{{ $tx->created_at?->format('M d, Y H:i') }}</td>
                                    <td class="py-2 pr-4 font-mono text-[11px]">{{ str_replace('_', ' ', $tx->source) }}</td>
                                    <td class="py-2 pr-4 text-stone-500 dark:text-stone-400">{{ $tx->serviceBooking?->booking_reference ?? $tx->payout?->reference ?? '—' }}</td>
                                    <td class="py-2 pr-4 text-right font-semibold {{ $tx->type === 'CREDIT' ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                                        {{ $tx->type === 'CREDIT' ? '+' : '−' }}{{ number_format((float) $tx->amount) }}
                                    </td>
                                    <td class="py-2 text-right text-stone-600 dark:text-stone-300">{{ number_format((float) $tx->balance_after) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Payouts -->
        <div class="lg:col-span-5 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 shadow-sm dark:shadow-xl">
            <h2 class="font-serif-luxury text-xl text-[#171513] dark:text-white mb-4">Payouts</h2>

            <dl class="grid grid-cols-2 gap-4 mb-6 pb-6 border-b border-[#E7E5E4] dark:border-white/10">
                <div>
                    <dt class="text-[10px] uppercase tracking-wider font-semibold text-stone-400 font-mono">Paid out</dt>
                    <dd class="font-serif-luxury text-xl text-[#171513] dark:text-white">{{ number_format($earnings['paid_out']) }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] uppercase tracking-wider font-semibold text-stone-400 font-mono">Outstanding payments</dt>
                    <dd class="font-serif-luxury text-xl text-amber-600 dark:text-amber-400">{{ number_format($earnings['outstanding']) }}</dd>
                </div>
            </dl>

            @if($this->payouts->isEmpty())
                <p class="text-sm text-stone-500 dark:text-stone-400 font-sans">No payout requests yet.</p>
            @else
                <ul class="space-y-2">
                    @foreach($this->payouts as $payout)
                        <li class="flex items-center justify-between gap-3 border-b border-[#E7E5E4] dark:border-white/10 pb-2">
                            <div>
                                <p class="text-sm font-bold text-[#171513] dark:text-white">{{ number_format((float) $payout->amount) }} PKR</p>
                                <p class="text-[10px] font-mono uppercase tracking-wider text-stone-400">{{ $payout->reference ?? '—' }} · {{ str_replace('_', ' ', $payout->status) }}</p>
                            </div>
                            <span class="text-[10px] font-mono uppercase tracking-widest px-2 py-0.5 border border-[#C6A15B]/50 bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] font-bold">
                                {{ str_replace('_', ' ', $payout->status) }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <!-- Payout modal -->
    @if($showPayoutModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true" role="dialog" aria-labelledby="payout-modal-title">
            <div class="absolute inset-0 bg-[#171513]/70 backdrop-blur-sm" wire:click="closePayoutModal"></div>
            <div class="relative mx-auto mt-16 w-full max-w-xl rounded-2xl bg-white dark:bg-[#171513] border border-[#C6A15B]/50 shadow-2xl p-6">
                <h2 id="payout-modal-title" class="font-serif-luxury text-2xl text-[#171513] dark:text-white mb-2">Request a payout</h2>
                <p class="text-xs text-stone-600 dark:text-stone-300 font-sans mb-4">
                    Available balance: <span class="font-bold text-[#171513] dark:text-white">PKR {{ number_format($earnings['available']) }}</span>
                </p>

                <form wire:submit="requestPayout" class="space-y-4">
                    <div>
                        <label for="po-amount" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Amount (PKR) <span class="text-[#8F7239]">*</span></label>
                        <input id="po-amount" type="number" step="0.01" min="1" wire:model="payoutAmount" class="input-luxury w-full text-sm">
                        @error('payoutAmount') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="po-method" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Payout method</label>
                        <select id="po-method" wire:model="payoutMethod" class="input-luxury w-full text-sm">
                            <option value="CASH">Cash handover (verified)</option>
                            <option value="BANK_TRANSFER">Bank transfer (offline, verified)</option>
                        </select>
                    </div>

                    <div>
                        <label for="po-name" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Account holder name <span class="text-[#8F7239]">*</span></label>
                        <input id="po-name" type="text" wire:model="payoutAccountName" maxlength="150">
                        @error('payoutAccountName') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="po-details" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Account details</label>
                        <input id="po-details" type="text" wire:model="payoutAccountDetails" maxlength="255" placeholder="IBAN / mobile number">
                        @error('payoutAccountDetails') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" wire:click="closePayoutModal" class="btn-outline-gold !h-10 !px-5 text-[11px] font-mono uppercase">Cancel</button>
                        <button type="submit" class="btn-gold !h-10 !px-6 text-[11px] font-mono uppercase">Submit Request →</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
