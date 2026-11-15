<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="font-serif-luxury text-3xl text-[#171513] font-normal mb-2">
                    Beauty & Bridal Studio Dashboard
                </h1>
                <p class="text-stone-600 text-sm">
                    Manage appointments, packages, client bookings & studio earnings
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
                    Upcoming Appointments
                </span>
                <span class="font-cinzel text-2xl text-[#C6A15B] dark:text-[#F2DE9A] font-bold">
                    {{ $stats['upcoming_appointments'] }}
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

        <!-- Active Services -->
        <div class="bg-white dark:bg-[#1C1917] border border-[#E7E5E4] dark:border-white/10 rounded-2xl shadow-sm overflow-hidden">
            <div class="p-6 border-b border-[#E7E5E4] dark:border-white/10">
                <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-normal">
                    Active Beauty & Bridal Services
                </h3>
            </div>

            <div class="p-6">
                @if($services->count())
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($services as $service)
                            <div class="border border-[#E7E5E4] dark:border-white/10 rounded-xl p-4 bg-[#F8F5EF] dark:bg-[#171513]">
                                <h4 class="font-serif-luxury text-lg text-[#171513] dark:text-white font-normal mb-2">
                                    {{ $service->name }}
                                </h4>
                                <p class="text-stone-600 dark:text-stone-300 text-xs mb-3">
                                    {{ $service->priceLabel() }}
                                </p>
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] uppercase font-bold tracking-wider text-[#C6A15B]">
                                        {{ $service->pricing_type }}
                                    </span>
                                    <a href="{{ route('vendor.services') }}" class="text-[#C6A15B] text-xs font-semibold hover:underline">
                                        Edit
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center text-stone-500">
                        No active services. <a href="{{ route('vendor.services') }}" class="text-[#C6A15B] font-semibold hover:underline">Add your first service</a>.
                    </div>
                @endif
            </div>
        </div>
    </div>