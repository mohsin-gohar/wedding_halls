<div class="py-12 bg-[#F8F5EF] dark:bg-[#171513] text-[#171513] dark:text-[#F8F5EF] min-h-screen transition-colors">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">

        <!-- Page Header -->
        <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-8 space-y-3">
            <div class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.2em] text-[#8F7239] dark:text-[#C6A15B]">
                <span>Shift Concurrency Engine</span>
                <span>•</span>
                <span>Zero Overbooking Protocol</span>
            </div>
            <h1 class="font-serif-luxury text-4xl sm:text-5xl text-[#171513] dark:text-white font-normal">
                Check Live Shift Availability
            </h1>
            <p class="text-stone-600 dark:text-stone-300 text-sm max-w-2xl font-normal leading-relaxed">
                Choose your wedding date to inspect real-time availability across Afternoon Lunch, Evening Dinner, and Late Night Mehndi shifts.
            </p>
        </div>

        <!-- Interactive Date Picker Controls (Zero-Pill Card) -->
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-6 sm:p-8 shadow-sm space-y-4 max-w-3xl mx-auto">
            <div class="flex flex-col sm:flex-row items-center gap-4 justify-center">
                <label class="text-xs uppercase font-bold tracking-wider text-stone-700 dark:text-stone-300 font-mono">Select Event Date:</label>
                <input type="date" wire:model.live="selectedDate" min="{{ $minDate }}" max="{{ $maxDate }}" aria-label="Select event date" class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm font-semibold max-w-xs">
            </div>

            <!-- Date Shortcuts -->
            <div class="flex flex-wrap items-center justify-center gap-2 pt-3 border-t border-[#E7E5E4] dark:border-white/10">
                <span class="text-xs font-semibold text-stone-400 dark:text-stone-400 font-mono">Quick Shortcuts:</span>
                @foreach($shortcuts as $sc)
                    <button type="button" wire:click="setDate('{{ $sc['date'] }}')" 
                            class="px-3 py-1 text-xs font-semibold transition cursor-pointer border {{ $selectedDate === $sc['date'] ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513] border-[#171513] dark:border-[#C6A15B]' : 'bg-[#F8F5EF] dark:bg-white/5 text-stone-700 dark:text-stone-300 border-[#E7E5E4] dark:border-white/10 hover:bg-[#F8F5EF] dark:hover:bg-white/10' }}">
                        {{ $sc['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Current Date Header -->
        <div class="text-center pt-4">
            <span class="text-xs text-stone-500 dark:text-stone-400 uppercase font-bold tracking-widest block font-mono">Displaying All Venues For</span>
            <h2 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal mt-1">
                {{ $date->format('l, d F Y') }}
            </h2>
        </div>

        <!-- Halls Shift Availability Cards Grid -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            @foreach($halls as $hall)
                @php
                    $bAfternoon = $bookings->get($hall->id . '_AFTERNOON');
                    $bEvening = $bookings->get($hall->id . '_EVENING');
                    $bNight = $bookings->get($hall->id . '_NIGHT');
                @endphp
                <div class="editorial-card p-6 sm:p-8 space-y-6 flex flex-col justify-between bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15">
                    <div class="space-y-4">
                        <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-3 flex justify-between items-start">
                            <div>
                                <span class="text-[10px] uppercase font-bold tracking-wider text-[#8F7239] dark:text-[#C6A15B] font-mono">VENUE</span>
                                <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-medium">{{ $hall->name }}</h3>
                                <p class="text-xs text-stone-500 dark:text-stone-400 mt-0.5">Capacity: {{ $hall->min_capacity }} - {{ $hall->max_capacity }} Heads</p>
                            </div>
                            <span class="text-xs font-bold text-stone-700 dark:text-[#C6A15B] bg-[#F8F5EF] dark:bg-white/5 border border-[#E7E5E4] dark:border-white/10 px-2.5 py-1 font-mono">
                                PKR {{ number_format($hall->base_rent) }}
                            </span>
                        </div>

                        <!-- 3 Shift Rows -->
                        <div class="space-y-3 pt-2">
                            <!-- Afternoon -->
                            <div class="p-3 border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-white/5 flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <span class="text-xs font-bold text-[#171513] dark:text-white block font-sans">Afternoon Lunch</span>
                                    <span class="text-[10px] text-stone-500 dark:text-stone-400 font-mono">{{ \App\Support\ShiftWindows::labelForSlot($hall->tenant?->shift_windows, 'AFTERNOON') }}</span>
                                </div>
                                @if($bAfternoon)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider bg-stone-100 dark:bg-white/5 text-stone-700 dark:text-stone-300 border border-stone-200 dark:border-white/10 font-mono rounded-md">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $bAfternoon->status === 'CONFIRMED' ? 'bg-stone-400' : 'bg-[#C6A15B]' }}"></span>
                                        {{ $bAfternoon->status === 'CONFIRMED' ? 'Booked' : 'Reserved Hold' }}
                                    </span>
                                @else
                                    <button onclick="Livewire.dispatch('openWalkInModal', { hall_id: @js($hall->id), event_date: @js($selectedDate), slot_type: 'AFTERNOON' })" 
                                            class="btn-gold !h-7 !px-3 rounded-md text-[10px] font-bold font-mono tracking-wider shadow-sm">
                                        <span>Available</span>
                                        <span class="opacity-50">&bull;</span>
                                        <span>Reserve</span>
                                    </button>
                                @endif
                            </div>

                            <!-- Evening -->
                            <div class="p-3 border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-white/5 flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <span class="text-xs font-bold text-[#171513] dark:text-white block font-sans">Evening Dinner</span>
                                    <span class="text-[10px] text-stone-500 dark:text-stone-400 font-mono">{{ \App\Support\ShiftWindows::labelForSlot($hall->tenant?->shift_windows, 'EVENING') }}</span>
                                </div>
                                @if($bEvening)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider bg-stone-100 dark:bg-white/5 text-stone-700 dark:text-stone-300 border border-stone-200 dark:border-white/10 font-mono rounded-md">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $bEvening->status === 'CONFIRMED' ? 'bg-stone-400' : 'bg-[#C6A15B]' }}"></span>
                                        {{ $bEvening->status === 'CONFIRMED' ? 'Booked' : 'Reserved Hold' }}
                                    </span>
                                @else
                                    <button onclick="Livewire.dispatch('openWalkInModal', { hall_id: @js($hall->id), event_date: @js($selectedDate), slot_type: 'EVENING' })" 
                                            class="btn-gold !h-7 !px-3 rounded-md text-[10px] font-bold font-mono tracking-wider shadow-sm">
                                        <span>Available</span>
                                        <span class="opacity-50">&bull;</span>
                                        <span>Reserve</span>
                                    </button>
                                @endif
                            </div>

                            <!-- Night -->
                            <div class="p-3 border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-white/5 flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <span class="text-xs font-bold text-[#171513] dark:text-white block font-sans">Late Night Mehndi</span>
                                    <span class="text-[10px] text-stone-500 dark:text-stone-400 font-mono">{{ \App\Support\ShiftWindows::labelForSlot($hall->tenant?->shift_windows, 'NIGHT') }}</span>
                                </div>
                                @if($bNight)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider bg-stone-100 dark:bg-white/5 text-stone-700 dark:text-stone-300 border border-stone-200 dark:border-white/10 font-mono rounded-md">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $bNight->status === 'CONFIRMED' ? 'bg-stone-400' : 'bg-[#C6A15B]' }}"></span>
                                        {{ $bNight->status === 'CONFIRMED' ? 'Booked' : 'Reserved Hold' }}
                                    </span>
                                @else
                                    <button onclick="Livewire.dispatch('openWalkInModal', { hall_id: @js($hall->id), event_date: @js($selectedDate), slot_type: 'NIGHT' })" 
                                            class="btn-gold !h-7 !px-3 rounded-md text-[10px] font-bold font-mono tracking-wider shadow-sm">
                                        <span>Available</span>
                                        <span class="opacity-50">&bull;</span>
                                        <span>Reserve</span>
                                    </button>
                                @endif
                            </div>

                        </div>
                    </div>

                    <div class="pt-3 border-t border-[#E7E5E4] dark:border-white/10 flex items-center justify-between">
                        <a href="{{ route('public.venue.details', $hall->slug) }}" class="text-xs text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:hover:text-white font-semibold underline">
                            View Venue Specs &rarr;
                        </a>
                        <span class="text-[10px] text-stone-400 dark:text-stone-500 font-mono">Real DB Concurrency</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="pt-2">{{ $halls->links() }}</div>

    </div>
</div>