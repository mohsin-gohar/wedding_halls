<div class="space-y-8 max-w-7xl mx-auto pb-16">
    <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-6 sm:p-8 shadow-xl">
        <div class="inline-flex items-center gap-2 px-2.5 py-0.5 border border-[#C6A15B]/50 bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] text-[10px] font-mono uppercase tracking-widest font-bold">
            🔐 Platform Governance • Vendor Payout Approvals
        </div>
        <h1 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal mt-2">
            Vendor Payout Approvals
        </h1>
        <p class="text-xs text-stone-600 dark:text-stone-300 font-sans max-w-2xl leading-relaxed mt-2">
            Super-admin-only settlement queue. Processing a requested payout debits the vendor's primary wallet and writes an immutable audit entry.
        </p>
    </div>

    @if(session('message'))
        <div class="p-4 bg-[#171513]/90 border border-[#C6A15B]/60 text-[#C6A15B] text-xs font-semibold shadow-md">
            ✓ {{ session('message') }}
        </div>
    @endif

    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 shadow-sm dark:shadow-xl">
        @if($payouts->isEmpty())
            <p class="text-sm text-stone-500 dark:text-stone-400 font-sans">No payout requests found.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs font-sans">
                    <thead class="text-[10px] uppercase tracking-wider text-stone-400 font-mono">
                        <tr>
                            <th class="py-2 pr-4">Reference</th>
                            <th class="py-2 pr-4">Vendor</th>
                            <th class="py-2 pr-4">Amount</th>
                            <th class="py-2 pr-4">Status</th>
                            <th class="py-2 pr-4">Requested</th>
                            <th class="py-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4] dark:divide-white/10">
                        @foreach($payouts as $payout)
                            <tr>
                                <td class="py-3 pr-4 font-mono">{{ $payout->reference ?? $payout->id }}</td>
                                <td class="py-3 pr-4">{{ $payout->tenant?->name ?? '—' }}</td>
                                <td class="py-3 pr-4 font-semibold">{{ number_format((float) $payout->amount, 2) }} PKR</td>
                                <td class="py-3 pr-4 font-mono uppercase">{{ $payout->status }}</td>
                                <td class="py-3 pr-4 text-stone-500 dark:text-stone-400">{{ $payout->requested_at?->format('M d, Y H:i') }}</td>
                                <td class="py-3 text-right">
                                    @if($payout->status === 'REQUESTED')
                                        <form method="POST" action="{{ route('admin.vendor-payouts.process', $payout) }}">
                                            @csrf
                                            <button type="submit" class="btn-gold !h-9 !px-4 text-[10px] font-mono uppercase"
                                                    onclick="return confirm('Debit the vendor wallet and mark this payout as paid?');">
                                                Process payout
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-[10px] font-mono uppercase text-stone-400">
                                            {{ $payout->processor?->name ? 'by '.$payout->processor->name : 'final' }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
