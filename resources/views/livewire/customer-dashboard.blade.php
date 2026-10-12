<div class="py-12 bg-[#F8F5EF] dark:bg-[#171513] transition-colors duration-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">

        <!-- Header -->
        <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-8 flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div class="space-y-2">
                <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#8F7239] dark:text-[#C6A15B] block font-mono">Customer Portal</span>
                <h1 class="font-serif-luxury text-4xl sm:text-5xl text-[#171513] dark:text-white font-normal">
                    My Wedding Reservations
                </h1>
                <p class="text-xs text-stone-600 dark:text-stone-300 font-normal">
                    Welcome, <strong class="text-[#171513] dark:text-white">{{ auth()->user()->name }}</strong> ({{ auth()->user()->email }}). View itemized quotations, payments, and legal agreements.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('public.venues') }}" class="btn-gold !h-9 text-xs shadow">
                    + Explore More Venues
                </a>
            </div>
        </div>

        @if(session('message'))
            <div class="p-4 bg-[#C6A15B]/15 dark:bg-[#171513]/85 border border-[#C6A15B]/60 dark:border-[#C6A15B]/50 text-[#8F7239] dark:text-[#C6A15B] text-xs font-semibold">
                {{ session('message') }}
            </div>
        @endif

        @if($feedbackMessage)
            <div class="p-4 bg-[#C6A15B]/15 dark:bg-[#171513]/85 border border-[#C6A15B]/60 dark:border-[#C6A15B]/50 text-[#8F7239] dark:text-[#C6A15B] text-xs font-semibold">
                {{ $feedbackMessage }}
            </div>
        @endif

        <!-- Customer Summary Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div class="editorial-card p-6 space-y-2 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 shadow-sm dark:shadow-xl relative overflow-hidden group hover:border-[#C6A15B] transition duration-300">
                <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
                <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 block font-mono">Total Bookings</span>
                <strong class="font-cinzel text-3xl text-[#171513] dark:text-white font-bold">{{ $bookings->count() }}</strong>
                <span class="text-xs text-stone-500 dark:text-stone-400 block font-sans">Reservations across Pakistan</span>
            </div>

            <div class="editorial-card p-6 space-y-2 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 shadow-sm dark:shadow-xl relative overflow-hidden group hover:border-[#C6A15B] transition duration-300">
                <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
                <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 block font-mono">Total Paid Deposits</span>
                <strong class="font-cinzel text-3xl text-[#8F7239] dark:text-[#C6A15B] font-bold">PKR {{ number_format($totalSpent) }}</strong>
                <span class="text-xs text-stone-500 dark:text-stone-400 block font-sans">Verified advance tokens</span>
            </div>

            <div class="editorial-card p-6 space-y-2 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 shadow-sm dark:shadow-xl relative overflow-hidden group hover:border-[#C6A15B] transition duration-300">
                <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
                <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 block font-mono">Outstanding Balance</span>
                <strong class="font-cinzel text-3xl text-[#171513] dark:text-[#C6A15B] font-bold">PKR {{ number_format($totalDue) }}</strong>
                <span class="text-xs text-stone-500 dark:text-stone-400 block font-sans">Payable before event day</span>
            </div>
        </div>

        <!-- Bookings List Table -->
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-6 space-y-6 shadow-sm dark:shadow-xl transition-colors">
            <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-medium border-b border-[#E7E5E4] dark:border-white/10 pb-4">
                Booking Records &amp; Agreements
            </h3>

            @if($bookings->isEmpty())
                <div class="text-center py-16 space-y-4">
                    <p class="font-serif-luxury text-xl text-stone-700 dark:text-stone-300">You do not have any active reservations yet.</p>
                    <p class="text-xs text-stone-500 dark:text-stone-400 max-w-sm mx-auto">
                        Explore our luxury wedding hall marketplace to reserve an Afternoon, Evening, or Night shift.
                    </p>
                    <a href="{{ route('public.venues') }}" class="btn-gold !h-10 text-xs">Explore Venues Now &rarr;</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left border-collapse border border-[#E7E5E4] dark:border-white/10">
                        <thead>
                            <tr class="bg-[#F8F5EF] dark:bg-[#171513] border-b border-[#E7E5E4] dark:border-white/10 text-[10px] uppercase font-bold tracking-wider text-stone-600 dark:text-[#C6A15B] font-mono">
                                <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Venue &amp; Hall</th>
                                <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Date &amp; Shift</th>
                                <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Event Type</th>
                                <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Guests</th>
                                <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Grand Total</th>
                                <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Paid Deposit</th>
                                <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Balance Due</th>
                                <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Status</th>
                                <th class="p-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E7E5E4] dark:divide-white/10 text-stone-800 dark:text-stone-200">
                            @foreach($bookings as $b)
                                <tr class="hover:bg-[#F8F5EF] dark:hover:bg-[#171513]/70 transition">
                                    <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10 font-semibold text-[#171513] dark:text-white">
                                        {{ $b->hall->name ?? 'Hall' }}
                                        <span class="block text-[10px] text-stone-500 dark:text-stone-400 font-normal font-mono">{{ $b->hall->venue->city ?? 'Lahore' }}</span>
                                    </td>
                                    <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10 whitespace-nowrap font-mono">
                                        {{ $b->event_date->format('d M Y') }}
                                        <span class="block text-[10px] text-stone-500 dark:text-stone-400 font-bold uppercase">{{ $b->slot_type }}</span>
                                    </td>
                                    <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10">
                                        <div class="font-semibold text-[#171513] dark:text-white">{{ $b->event_type }}</div>
                                        <span class="font-mono text-[10px] text-stone-500 dark:text-stone-400">{{ $b->booking_reference }}</span>
                                    </td>
                                    <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10 font-mono">{{ $b->guest_count }} Heads</td>
                                    <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10 font-mono font-bold text-[#171513] dark:text-white">PKR {{ number_format($b->grand_total) }}</td>
                                    <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10 text-[#8F7239] dark:text-[#C6A15B] font-mono font-bold">PKR {{ number_format($b->advance_paid) }}</td>
                                    <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10 font-mono font-bold text-[#171513] dark:text-[#F8F5EF]">PKR {{ number_format($b->balance_due) }}</td>
                                    <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10">
                                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider font-mono border {{ $b->status === 'CONFIRMED' ? 'bg-[#C6A15B]/20 dark:bg-[#171513]/85 border-[#C6A15B]/60 dark:border-[#C6A15B]/50 text-[#8F7239] dark:text-[#C6A15B]' : 'bg-[#C6A15B]/20 dark:bg-[#171513]/85 border-[#C6A15B]/60 dark:border-[#C6A15B]/50 text-[#8F7239] dark:text-[#C6A15B]' }}">
                                            {{ $b->status }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-right space-x-2 whitespace-nowrap font-mono">
                                        <button wire:click="viewBooking('{{ $b->id }}')" class="btn-outline-gold !h-7 !px-2.5 text-[11px] font-bold">
                                            Details
                                        </button>
                                        <a href="{{ route('documents.agreement', $b->id) }}" target="_blank" class="btn-gold !h-7 !px-2.5 text-[11px] font-bold">
                                            Contract ↗
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Booking Details Modal -->
        @if($viewingBooking)
            <div class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/60 gold-foil-border max-w-2xl w-full p-8 shadow-2xl space-y-6 max-h-[90vh] overflow-y-auto text-stone-800 dark:text-stone-200 transition-colors">
                    <div class="flex items-center justify-between border-b border-[#E7E5E4] dark:border-white/15 pb-4">
                        <div>
                            <span class="text-[10px] uppercase font-bold tracking-wider text-[#8F7239] dark:text-[#C6A15B] font-mono">Reservation Details</span>
                            <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-normal">
                                {{ $viewingBooking->hall->name ?? 'Ballroom' }}
                            </h3>
                        </div>
                        <button wire:click="closeBookingModal" class="text-stone-400 hover:text-stone-700 dark:hover:text-white text-2xl font-bold leading-none">
                            &times;
                        </button>
                    </div>

                    <!-- Itemized Breakdown -->
                    <div class="space-y-3 text-xs font-mono">
                        <div class="flex justify-between py-1 border-b border-[#E7E5E4] dark:border-white/10">
                            <span class="text-stone-600 dark:text-stone-400">Event Date &amp; Shift:</span>
                            <span class="font-semibold text-stone-900 dark:text-white">{{ $viewingBooking->event_date->format('d M Y') }} ({{ $viewingBooking->slot_type }})</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-[#E7E5E4] dark:border-white/10">
                            <span class="text-stone-600 dark:text-stone-400">Catering Per-Head ({{ $viewingBooking->guest_count }} guests @ PKR {{ number_format($viewingBooking->per_head_rate) }}):</span>
                            <span class="font-semibold text-stone-900 dark:text-white">PKR {{ number_format($viewingBooking->guest_count * $viewingBooking->per_head_rate) }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-[#E7E5E4] dark:border-white/10">
                            <span class="text-stone-600 dark:text-stone-400">Base Hall Rental:</span>
                            <span class="font-semibold text-stone-900 dark:text-white">PKR {{ number_format($viewingBooking->hall_rent) }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-[#E7E5E4] dark:border-white/10">
                            <span class="text-stone-600 dark:text-stone-400">Add-ons Total:</span>
                            <span class="font-semibold text-stone-900 dark:text-white">PKR {{ number_format($viewingBooking->addons_total) }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-[#E7E5E4] dark:border-white/10">
                            <span class="text-stone-600 dark:text-stone-400">Discount Applied:</span>
                            <span class="font-semibold text-stone-900 dark:text-white">- PKR {{ number_format($viewingBooking->discount_amount) }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-[#E7E5E4] dark:border-white/10">
                            <span class="text-stone-600 dark:text-stone-400">Provincial Services Tax:</span>
                            <span class="font-semibold text-stone-900 dark:text-white">PKR {{ number_format($viewingBooking->tax_amount) }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-t-2 border-[#C6A15B] text-sm">
                            <span class="font-bold text-[#171513] dark:text-white">Grand Total:</span>
                            <strong class="font-cinzel text-lg text-[#171513] dark:text-[#C6A15B] font-bold">PKR {{ number_format($viewingBooking->grand_total) }}</strong>
                        </div>
                        <div class="flex justify-between py-1 text-xs">
                            <span class="text-[#8F7239] dark:text-[#C6A15B] font-semibold">Advance Token Paid:</span>
                            <strong class="text-[#8F7239] dark:text-[#C6A15B]">PKR {{ number_format($viewingBooking->advance_paid) }}</strong>
                        </div>
                        <div class="flex justify-between py-1 text-xs">
                            <span class="text-[#171513] dark:text-[#F8F5EF] font-semibold">Remaining Balance Due:</span>
                            <strong class="text-[#171513] dark:text-[#F8F5EF]">PKR {{ number_format($viewingBooking->balance_due) }}</strong>
                        </div>
                    </div>

                    <!-- Payment Receipts Downloads -->
                    @if($viewingBooking->payments->isNotEmpty())
                        <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10 space-y-2 font-mono">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400 block">Payment Receipts</span>
                            <div class="space-y-1">
                                @foreach($viewingBooking->payments as $p)
                                    <div class="flex items-center justify-between text-xs bg-[#F8F5EF] dark:bg-[#171513] p-2 border border-[#E7E5E4] dark:border-white/10">
                                        <span>{{ $p->receipt_no }} ({{ $p->payment_type }}) — PKR {{ number_format($p->amount) }}</span>
                                        <a href="{{ route('documents.receipt', $p->id) }}" target="_blank" class="text-[#8F7239] dark:text-[#C6A15B] font-semibold underline">
                                            Download Receipt ↗
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Cancellation & Close Actions -->
                    <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="space-y-2 w-full sm:w-auto">
                            <input type="text" wire:model="cancellationReason" placeholder="Reason for cancellation/reschedule..." class="input-luxury text-xs w-full sm:w-72 dark:bg-[#171513] dark:text-white dark:border-white/15">
                            <button type="button" wire:click="requestCancellation('{{ $viewingBooking->id }}')" class="text-xs text-[#171513] dark:text-[#F8F5EF] hover:underline block font-semibold font-mono">
                                Request Reschedule or Cancellation
                            </button>
                        </div>
                        <button type="button" wire:click="closeBookingModal" class="btn-outline-gold !h-9 !px-4 text-xs font-semibold self-end sm:self-auto font-mono">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>
