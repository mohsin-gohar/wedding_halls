<div class="py-12 bg-[#F8F5EF]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">

        <!-- Page Header -->
        <div class="border-b border-[#E7E5E4] pb-8 space-y-3">
            <div class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.2em] text-[#8F7239]">
                <span>Shift Concurrency Engine</span>
                <span>•</span>
                <span>Zero Overbooking Protocol</span>
            </div>
            <h1 class="font-serif-luxury text-4xl sm:text-5xl text-[#171513] font-normal">
                Check Live Shift Availability
            </h1>
            <p class="text-stone-600 text-sm max-w-2xl font-normal leading-relaxed">
                Choose your wedding date to inspect real-time availability across Afternoon Lunch, Evening Dinner, and Late Night Mehndi shifts.
            </p>
        </div>

        <!-- Interactive Date Picker Controls (Zero-Pill Card) -->
        <div class="bg-white border border-[#E7E5E4] p-6 sm:p-8 shadow-sm space-y-4 max-w-3xl mx-auto">
            <div class="flex flex-col sm:flex-row items-center gap-4 justify-center">
                <label class="text-xs uppercase font-bold tracking-wider text-stone-700">Select Event Date:</label>
                <input type="date" wire:model.live="selectedDate" class="input-luxury text-sm font-semibold max-w-xs">
            </div>

            <!-- Date Shortcuts -->
            <div class="flex flex-wrap items-center justify-center gap-2 pt-3 border-t border-[#E7E5E4]">
                <span class="text-xs font-semibold text-stone-400">Quick Shortcuts:</span>
                @foreach($shortcuts as $sc)
                    <button type="button" wire:click="setDate('{{ $sc['date'] }}')" 
                            class="px-3 py-1 text-xs font-semibold transition cursor-pointer border {{ $selectedDate === $sc['date'] ? 'bg-[#171513] text-white border-[#171513]' : 'bg-[#F8F5EF] text-stone-700 border-[#E7E5E4] hover:bg-[#F8F5EF]' }}">
                        {{ $sc['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Current Date Header -->
        <div class="text-center pt-4">
            <span class="text-xs text-stone-500 uppercase font-bold tracking-widest block">Displaying All Venues For</span>
            <h2 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] font-normal mt-1">
                {{ $date->format('l, d F Y') }}
            </h2>
        </div>

        <!-- Halls Shift Availability Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($halls as $hall)
                @php
                    $bAfternoon = $bookings->get($hall->id . '_AFTERNOON');
                    $bEvening = $bookings->get($hall->id . '_EVENING');
                    $bNight = $bookings->get($hall->id . '_NIGHT');
                @endphp
                <div class="editorial-card p-6 sm:p-8 space-y-6 flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="border-b border-[#E7E5E4] pb-3 flex justify-between items-start">
                            <div>
                                <span class="text-[10px] uppercase font-bold tracking-wider text-[#8F7239]">VENUE</span>
                                <h3 class="font-serif-luxury text-2xl text-[#171513] font-medium">{{ $hall->name }}</h3>
                                <p class="text-xs text-stone-500 mt-0.5">Capacity: {{ $hall->min_capacity }} - {{ $hall->max_capacity }} Heads</p>
                            </div>
                            <span class="text-xs font-bold text-stone-700 bg-[#F8F5EF] border border-[#E7E5E4] px-2.5 py-1">
                                PKR {{ number_format($hall->base_rent) }}
                            </span>
                        </div>

                        <!-- 3 Shift Rows -->
                        <div class="space-y-3 pt-2">
                            <!-- Afternoon -->
                            <div class="p-3 border border-[#E7E5E4] bg-[#F8F5EF] flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-bold text-[#171513] block">Afternoon Lunch</span>
                                    <span class="text-[10px] text-stone-500">12:00 PM – 4:00 PM</span>
                                </div>
                                @if($bAfternoon)
                                    <span class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider bg-[#171513]/10 text-[#171513] border border-[#C6A15B]/50">
                                        {{ $bAfternoon->status === 'CONFIRMED' ? 'Booked' : 'Hold' }}
                                    </span>
                                @else
                                    <button onclick="Livewire.dispatch('openWalkInModal', { hall_id: '{{ $hall->id }}', event_date: '{{ $selectedDate }}', slot_type: 'AFTERNOON' })" 
                                            class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider bg-[#C6A15B] text-white hover:bg-[#C6A15B] transition cursor-pointer">
                                        Available &bull; Book
                                    </button>
                                @endif
                            </div>

                            <!-- Evening -->
                            <div class="p-3 border border-[#E7E5E4] bg-[#F8F5EF] flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-bold text-[#171513] block">Evening Dinner</span>
                                    <span class="text-[10px] text-stone-500">6:30 PM – 10:30 PM</span>
                                </div>
                                @if($bEvening)
                                    <span class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider bg-[#171513]/10 text-[#171513] border border-[#C6A15B]/50">
                                        {{ $bEvening->status === 'CONFIRMED' ? 'Booked' : 'Hold' }}
                                    </span>
                                @else
                                    <button onclick="Livewire.dispatch('openWalkInModal', { hall_id: '{{ $hall->id }}', event_date: '{{ $selectedDate }}', slot_type: 'EVENING' })" 
                                            class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider bg-[#C6A15B] text-white hover:bg-[#C6A15B] transition cursor-pointer">
                                        Available &bull; Book
                                    </button>
                                @endif
                            </div>

                            <!-- Night -->
                            <div class="p-3 border border-[#E7E5E4] bg-[#F8F5EF] flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-bold text-[#171513] block">Late Night Mehndi</span>
                                    <span class="text-[10px] text-stone-500">11:00 PM – 3:00 AM</span>
                                </div>
                                @if($bNight)
                                    <span class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider bg-[#171513]/10 text-[#171513] border border-[#C6A15B]/50">
                                        {{ $bNight->status === 'CONFIRMED' ? 'Booked' : 'Hold' }}
                                    </span>
                                @else
                                    <button onclick="Livewire.dispatch('openWalkInModal', { hall_id: '{{ $hall->id }}', event_date: '{{ $selectedDate }}', slot_type: 'NIGHT' })" 
                                            class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider bg-[#C6A15B] text-white hover:bg-[#C6A15B] transition cursor-pointer">
                                        Available &bull; Book
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-[#E7E5E4] flex items-center justify-between">
                        <a href="{{ route('public.venue.details', $hall->id) }}" class="text-xs text-stone-600 hover:text-stone-900 font-semibold underline">
                            View Venue Specs &rarr;
                        </a>
                        <span class="text-[10px] text-stone-400 font-mono">Real DB Concurrency</span>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
</div>
