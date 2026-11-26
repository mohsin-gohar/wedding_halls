<div class="min-h-screen bg-[#F8F5EF] dark:bg-[#171513] px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-8">
        <header class="flex flex-col gap-3 border-b border-[#E7E5E4] pb-6 dark:border-white/10 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.25em] text-[#8F7239] dark:text-[#C6A15B]">Riwaj Grand · Venue Operations</p>
                <h1 class="mt-2 font-serif-luxury text-3xl text-[#171513] dark:text-white sm:text-4xl">Operations Dashboard</h1>
                <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">Booking activity and upcoming event coordination for your venue.</p>
            </div>
            <span class="text-xs font-semibold text-stone-500 dark:text-stone-400">{{ now()->format('l, d F Y') }}</span>
        </header>

        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <article class="border border-[#E7E5E4] bg-white p-5 dark:border-white/10 dark:bg-[#201d19]">
                <p class="text-[10px] font-bold uppercase tracking-wider text-stone-500">Active Halls</p>
                <p class="mt-3 font-serif-luxury text-3xl text-[#171513] dark:text-white">{{ number_format($totalHallsCount) }}</p>
            </article>
            <article class="border border-[#E7E5E4] bg-white p-5 dark:border-white/10 dark:bg-[#201d19]">
                <p class="text-[10px] font-bold uppercase tracking-wider text-stone-500">Events Today</p>
                <p class="mt-3 font-serif-luxury text-3xl text-[#171513] dark:text-white">{{ number_format($todayEventsCount) }}</p>
            </article>
            <article class="border border-[#E7E5E4] bg-white p-5 dark:border-white/10 dark:bg-[#201d19]">
                <p class="text-[10px] font-bold uppercase tracking-wider text-stone-500">Upcoming Events</p>
                <p class="mt-3 font-serif-luxury text-3xl text-[#171513] dark:text-white">{{ number_format($upcomingEventsCount) }}</p>
            </article>
            <article class="border border-[#E7E5E4] bg-white p-5 dark:border-white/10 dark:bg-[#201d19]">
                <p class="text-[10px] font-bold uppercase tracking-wider text-stone-500">Pending Inquiries</p>
                <p class="mt-3 font-serif-luxury text-3xl text-[#171513] dark:text-white">{{ number_format($pendingInquiriesCount) }}</p>
            </article>
        </section>

        <section class="grid gap-4 sm:grid-cols-2">
            <article class="border border-[#E7E5E4] bg-white p-5 dark:border-white/10 dark:bg-[#201d19]">
                <p class="text-[10px] font-bold uppercase tracking-wider text-stone-500">This Month</p>
                <p class="mt-2 text-2xl font-semibold text-[#171513] dark:text-white">{{ number_format($monthProgramsCount) }} <span class="text-sm font-normal text-stone-500">confirmed / held events</span></p>
            </article>
            <article class="border border-[#E7E5E4] bg-white p-5 dark:border-white/10 dark:bg-[#201d19]">
                <p class="text-[10px] font-bold uppercase tracking-wider text-stone-500">Guest Capacity Booked This Month</p>
                <p class="mt-2 text-2xl font-semibold text-[#171513] dark:text-white">{{ number_format($monthGuestsCount) }} <span class="text-sm font-normal text-stone-500">guests</span></p>
            </article>
        </section>

        <section class="overflow-hidden border border-[#E7E5E4] bg-white dark:border-white/10 dark:bg-[#201d19]">
            <div class="flex items-center justify-between border-b border-[#E7E5E4] px-5 py-4 dark:border-white/10">
                <div>
                    <h2 class="font-serif-luxury text-xl text-[#171513] dark:text-white">Upcoming Events</h2>
                    <p class="mt-1 text-xs text-stone-500">Next scheduled venue functions</p>
                </div>
                <a href="{{ route('vendor.bookings') }}" class="text-xs font-bold text-[#8F7239] hover:underline dark:text-[#C6A15B]">View bookings</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-left text-sm">
                    <thead class="bg-[#F8F5EF] text-[10px] uppercase tracking-wider text-stone-500 dark:bg-white/5">
                        <tr>
                            <th class="px-5 py-3">Date</th>
                            <th class="px-5 py-3">Event / Customer</th>
                            <th class="px-5 py-3">Hall</th>
                            <th class="px-5 py-3">Shift</th>
                            <th class="px-5 py-3 text-right">Guests</th>
                            <th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4] dark:divide-white/10">
                        @forelse($upcomingBookings as $booking)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-4 text-stone-600 dark:text-stone-300">{{ $booking->event_date?->format('D, d M Y') ?? '—' }}</td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-[#171513] dark:text-white">{{ $booking->event_type }}</p>
                                    <p class="mt-1 text-xs text-stone-500">{{ $booking->customer_name }}</p>
                                </td>
                                <td class="px-5 py-4 text-stone-600 dark:text-stone-300">{{ $booking->hall?->name ?? '—' }}</td>
                                <td class="px-5 py-4 text-stone-600 dark:text-stone-300">{{ str($booking->slot_type)->replace('_', ' ')->title() }}</td>
                                <td class="px-5 py-4 text-right font-semibold text-[#171513] dark:text-white">{{ number_format((int) $booking->guest_count) }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex border border-[#C6A15B]/40 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-[#8F7239] dark:text-[#C6A15B]">{{ $booking->status }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-stone-500">No upcoming events are scheduled.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
