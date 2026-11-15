<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="font-serif-luxury text-3xl text-[#171513] font-normal mb-2">
                    Photography Studio Dashboard
                </h1>
                <p class="text-stone-600 text-sm">
                    Manage your photography business — shoots, portfolios, deliverables & earnings
                </p>
            </div>
            <a href="{{ route('vendor.services') }}" class="btn-gold !h-10 !px-5 text-xs font-bold uppercase tracking-wider">
                Manage Services &rarr;
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-[#1C1917] border border-[#E7E5E4] dark:border-white/10 rounded-2xl p-6 shadow-sm">
                <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 block mb-2">
                    Total Bookings
                </span>
                <span class="font-cinzel text-2xl text-[#171513] dark:text-white font-bold">
                    {{ $stats['total_bookings'] }}
                </span>
            </div>

            <div class="bg-white dark:bg-[#1C1917] border border-[#E7E5E4] dark:border-white/10 rounded-2xl p-6 shadow-sm">
                <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 block mb-2">
                    Upcoming Shoots
                </span>
                <span class="font-cinzel text-2xl text-[#C6A15B] dark:text-[#F2DE9A] font-bold">
                    {{ $stats['upcoming'] }}
                </span>
            </div>

            <div class="bg-white dark:bg-[#1C1917] border border-[#E7E5E4] dark:border-white/10 rounded-2xl p-6 shadow-sm">
                <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 block mb-2">
                    Completed
                </span>
                <span class="font-cinzel text-2xl text-[#22c55e] dark:text-[#4ade80] font-bold">
                    {{ $stats['completed'] }}
                </span>
            </div>

            <div class="bg-white dark:bg-[#1C1917] border border-[#E7E5E4] dark:border-white/10 rounded-2xl p-6 shadow-sm">
                <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 block mb-2">
                    Revenue (PKR)
                </span>
                <span class="font-cinzel text-2xl text-[#171513] dark:text-white font-bold">
                    {{ number_format($stats['revenue'], 0) }}
                </span>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="bg-white dark:bg-[#1C1917] border border-[#E7E5E4] dark:border-white/10 rounded-2xl shadow-sm overflow-hidden">
            <div class="p-6 border-b border-[#E7E5E4] dark:border-white/10">
                <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-normal">
                    Recent Photography Bookings
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#171513]/50">
                            <th class="p-4 font-semibold text-stone-600 dark:text-stone-300 uppercase text-[10px] tracking-wider">Reference</th>
                            <th class="p-4 font-semibold text-stone-600 dark:text-stone-300 uppercase text-[10px] tracking-wider">Client</th>
                            <th class="p-4 font-semibold text-stone-600 dark:text-stone-300 uppercase text-[10px] tracking-wider">Event Date</th>
                            <th class="p-4 font-semibold text-stone-600 dark:text-stone-300 uppercase text-[10px] tracking-wider">Status</th>
                            <th class="p-4 font-semibold text-stone-600 dark:text-stone-300 uppercase text-[10px] tracking-wider">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4] dark:divide-white/10">
                        @forelse($recentBookings as $booking)
                            <tr class="hover:bg-[#F3EFE6] dark:hover:bg-[#24211E] transition-colors">
                                <td class="p-4 text-stone-600 dark:text-stone-300 font-mono text-xs">{{ $booking->booking_reference }}</td>
                                <td class="p-4 text-stone-600 dark:text-stone-300">{{ $booking->customer?->name }}</td>
                                <td class="p-4 text-stone-600 dark:text-stone-300">{{ optional($booking->event_date)->format('M d, Y') }}</td>
                                <td class="p-4">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-[10px] font-semibold
                                        @switch($booking->status)
                                            @case('ACCEPTED') bg-green-50 text-green-700 @break
                                            @case('CONFIRMED') bg-blue-50 text-blue-700 @break
                                            @case('IN_PROGRESS') bg-amber-50 text-amber-700 @break
                                            @case('COMPLETED') bg-green-100 text-green-800 @break
                                            @default bg-stone-100 text-stone-600
                                        @endswitch
                                    ">
                                        {{ $booking->status }}
                                    </span>
                                </td>
                                <td class="p-4 font-cinzel text-[#171513] dark:text-white">
                                    PKR {{ number_format($booking->quoted_amount, 0) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-stone-500">
                                    No recent bookings found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>