<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    <!-- ===================================================================
         1. EXECUTIVE HEADER BANNER (Obsidian & Champagne Gold)
         =================================================================== -->
    <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-6 sm:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6 shadow-xl transition-colors duration-200">
        <div class="space-y-1.5">
            <div class="flex items-center gap-2 text-xs font-mono text-[#8F7239] dark:text-[#C6A15B] uppercase tracking-widest font-bold">
                <span>Vendor Portal</span>
                <span class="text-stone-400 dark:text-stone-600">/</span>
                <span>Operational Calendar</span>
                <span class="text-stone-400 dark:text-stone-600">•</span>
                <span class="text-[#8F7239] dark:text-[#C6A15B] font-mono">ACID Distributed Mutex Active</span>
            </div>
            <h1 class="font-serif-luxury text-2xl sm:text-4xl text-[#171513] dark:text-white font-bold tracking-tight">
                Shift Concurrency Matrix
            </h1>
            <p class="text-xs text-stone-600 dark:text-stone-300 font-sans max-w-2xl leading-relaxed">
                Real-time booking shifts across Afternoon, Evening, and Night slots. Guaranteed zero double-bookings with instant status lookup.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div>
                <label class="block text-[10px] uppercase font-mono tracking-wider text-[#8F7239] dark:text-[#C6A15B] mb-1 font-bold">Banquet Hall</label>
                <select wire:model.live="selectedHallId" class="bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/50 text-[#171513] dark:text-stone-200 text-xs px-3.5 py-2 font-mono focus:border-[#C6A15B] outline-none">
                    @foreach($halls as $hall)
                        <option value="{{ $hall->id }}">{{ $hall->name }} (Cap: {{ $hall->max_capacity }})</option>
                    @endforeach
                </select>
            </div>

            <div class="self-end">
                <button wire:click="openWalkInModal" class="lux-btn lux-btn--gold lux-btn--sm">
                    + Walk-In Reservation
                </button>
            </div>
        </div>
    </div>

    <!-- ===================================================================
         2. MONTH NAVIGATION & RESTRAINED LEGEND TOOLBAR
         =================================================================== -->
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-4 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm">
        <!-- Month Controller -->
        <div class="flex items-center space-x-2">
            <button wire:click="prevMonth" class="w-8 h-8 border border-[#E7E5E4] dark:border-white/10 hover:border-[#171513] dark:hover:border-white bg-[#F8F5EF] dark:bg-white/5 hover:bg-stone-100 dark:hover:bg-white/10 text-stone-700 dark:text-stone-300 flex items-center justify-center transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>

            <h2 class="font-cinzel text-xl sm:text-2xl text-[#171513] dark:text-white font-bold px-3 min-w-[200px] text-center">
                {{ $monthTitle }}
            </h2>

            <button wire:click="nextMonth" class="w-8 h-8 border border-[#E7E5E4] dark:border-white/10 hover:border-[#171513] dark:hover:border-white bg-[#F8F5EF] dark:bg-white/5 hover:bg-stone-100 dark:hover:bg-white/10 text-stone-700 dark:text-stone-300 flex items-center justify-center transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>

            <button wire:click="goToToday" class="ml-2 px-3 py-1.5 border border-[#E7E5E4] dark:border-white/10 bg-white dark:bg-white/10 hover:bg-[#F8F5EF] dark:hover:bg-white/15 text-stone-800 dark:text-stone-200 text-xs font-mono uppercase tracking-wider font-semibold transition">
                Today
            </button>
        </div>

        <!-- Restrained Status Legend (Zero-Pill) -->
        <div class="flex items-center flex-wrap gap-2 text-xs font-mono">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#C6A15B]/15 dark:bg-[#171513]/85 border border-[#C6A15B]/60 dark:border-[#C6A15B]/70 text-[#8F7239] dark:text-[#C6A15B] text-[11px] font-bold">
                <span class="w-2 h-2 bg-[#C6A15B]"></span> Confirmed Shift
            </span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#C6A15B]/15 dark:bg-[#171513]/85 border border-[#C6A15B]/60 dark:border-[#C6A15B]/70 text-[#8F7239] dark:text-[#C6A15B] text-[11px] font-bold">
                <span class="w-2 h-2 bg-[#C6A15B]"></span> On Hold (24H)
            </span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#F8F5EF] dark:bg-white/5 border border-[#E7E5E4] dark:border-white/10 text-stone-600 dark:text-stone-300 text-[11px] font-medium">
                <span class="w-2 h-2 bg-stone-300"></span> Available
            </span>
        </div>
    </div>

    <!-- ===================================================================
         3. CALENDAR MONTH SHIFT GRID (Refined Editorial Design)
         =================================================================== -->
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 shadow-sm overflow-hidden">
        <!-- Day Names Header -->
        <div class="grid grid-cols-7 bg-[#F8F5EF] dark:bg-[#171513] border-b border-[#E7E5E4] dark:border-white/10 text-center text-xs tracking-wider uppercase text-stone-600 dark:text-stone-400 py-3 font-mono font-bold">
            <div>Mon</div>
            <div>Tue</div>
            <div>Wed</div>
            <div>Thu</div>
            <div>Fri</div>
            <div>Sat</div>
            <div>Sun</div>
        </div>

        <!-- Days Grid -->
        <div class="grid grid-cols-7 auto-rows-fr divide-x divide-y divide-[#E7E5E4] dark:divide-white/10 bg-[#F8F5EF] dark:bg-[#171513]">
            @php
                $firstDayOfWeek = $days[0]->dayOfWeekIso; // 1 (Mon) to 7 (Sun)
            @endphp

            {{-- Empty cells before first day --}}
            @for ($i = 1; $i < $firstDayOfWeek; $i++)
                <div class="min-h-[155px] bg-stone-100/60 dark:bg-white/5 p-2 opacity-40"></div>
            @endfor

            @foreach ($days as $day)
                @php
                    $dateStr = $day->format('Y-m-d');
                    $isToday = $day->isToday();
                @endphp
                <div class="min-h-[155px] p-2.5 flex flex-col justify-between hover:bg-stone-50 dark:hover:bg-white/5 transition relative {{ $isToday ? 'bg-[#C6A15B]/15 dark:bg-[#C6A15B]/10 border-t-2 border-t-[#C6A15B]' : 'bg-white dark:bg-[#171513]' }}">
                    <!-- Day Header -->
                    <div class="flex items-center justify-between mb-2 pb-1.5 border-b border-stone-100 dark:border-white/5">
                        <span class="font-cinzel text-sm {{ $isToday ? 'font-bold text-[#C6A15B] bg-[#171513] px-2 py-0.5 text-xs' : 'text-[#171513] dark:text-stone-200 font-bold' }}">
                            {{ $day->format('j') }}
                        </span>
                        <span class="text-[10px] text-stone-400 font-mono uppercase">{{ $day->format('D') }}</span>
                    </div>

                    <!-- 3 Shifts Container (Afternoon, Evening, Night) -->
                    <div class="space-y-1.5 flex-1 flex flex-col justify-center">
                        @foreach(['AFTERNOON', 'EVENING', 'NIGHT'] as $slot)
                            @php
                                $key = $dateStr . '_' . $slot;
                                $booking = $bookings[$key] ?? null;
                                $slotLabel = $slot === 'AFTERNOON' ? 'Lunch' : ($slot === 'EVENING' ? 'Dinner' : 'Night');
                                $slotFull = $slot === 'AFTERNOON' ? 'Afternoon' : ($slot === 'EVENING' ? 'Evening' : 'Late Night');
                            @endphp

                            @if($booking)
                                <!-- Booked Slot Card (Zero-Pill) -->
                                <button wire:click="viewBookingDetails('{{ $booking->id }}')" 
                                        class="w-full text-left p-1.5 border border-[#C6A15B]/60 dark:border-[#C6A15B]/70 bg-[#C6A15B]/15 dark:bg-[#171513]/85 hover:bg-[#C6A15B]/20 dark:hover:bg-[#171513]/85 transition flex items-center justify-between text-xs group">
                                    <div class="truncate">
                                        <div class="flex items-center gap-1">
                                            <span class="font-mono text-[10px] uppercase font-bold text-[#8F7239] dark:text-[#C6A15B]">{{ $slotLabel }}:</span>
                                            <span class="text-[11px] font-semibold text-[#8F7239] dark:text-[#E8DCBE] truncate">{{ $booking->event_type }}</span>
                                        </div>
                                        <span class="text-[10px] text-[#8F7239] dark:text-[#C6A15B] block truncate font-sans">{{ $booking->customer_name }}</span>
                                    </div>
                                    <span class="text-[9px] font-mono px-1 py-0.2 bg-[#C6A15B]/35 dark:bg-[#C6A15B] text-[#8F7239] dark:text-[#E8DCBE] font-bold shrink-0 ml-1">
                                        {{ $booking->guests_count ?? $booking->guest_count }}p
                                    </span>
                                </button>
                            @else
                                <!-- Available Slot Button -->
                                <button wire:click="openWalkInModal('{{ $dateStr }}', '{{ $slot }}')" 
                                        class="w-full text-left px-2 py-1 border border-dashed border-stone-200 dark:border-white/10 hover:border-[#C6A15B] hover:bg-[#C6A15B]/18 dark:hover:bg-white/5 text-stone-400 dark:text-stone-500 hover:text-[#171513] dark:hover:text-[#C6A15B] transition text-[10px] font-mono flex items-center justify-between group">
                                    <span class="text-stone-500 dark:text-stone-400 group-hover:text-[#171513] dark:group-hover:text-white">{{ $slotFull }}</span>
                                    <span class="opacity-0 group-hover:opacity-100 font-bold text-[#C6A15B] transition">+ Book</span>
                                </button>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- ===================================================================
         4. BOOKING DETAILS MODAL (Executive Clean Business Card)
         =================================================================== -->
    @if($showDetailsModal && $viewingBooking)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="bg-white dark:bg-[#171513] border-2 border-[#C6A15B] max-w-lg w-full shadow-2xl overflow-hidden p-6 sm:p-8 space-y-6 relative text-[#171513] dark:text-stone-200">
                <!-- Modal Header -->
                <div class="flex items-start justify-between border-b border-[#E7E5E4] dark:border-white/10 pb-4">
                    <div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 bg-[#C6A15B]/15 dark:bg-[#171513]/85 border border-[#C6A15B]/60 dark:border-[#C6A15B]/70 text-[#8F7239] dark:text-[#C6A15B] text-[10px] font-mono font-bold uppercase mb-1">
                            <span class="w-1.5 h-1.5 bg-[#C6A15B]"></span> Confirmed Event Reservation
                        </div>
                        <h3 class="font-cinzel text-2xl text-[#171513] dark:text-white font-bold">
                            {{ $viewingBooking->event_type }} Ceremony
                        </h3>
                        <p class="text-xs text-stone-500 dark:text-stone-400 font-mono mt-0.5">
                            Ref: #{{ substr($viewingBooking->id, 0, 8) }} • {{ $viewingBooking->hall->name ?? 'Venue Space' }}
                        </p>
                    </div>
                    <button wire:click="closeDetailsModal" class="w-8 h-8 border border-stone-300 dark:border-white/20 hover:border-[#171513] dark:hover:border-white text-stone-600 dark:text-stone-300 hover:text-[#171513] dark:hover:text-white flex items-center justify-center transition text-lg font-bold leading-none">&times;</button>
                </div>

                <!-- Host & Date Details -->
                <div class="grid grid-cols-2 gap-3 text-xs font-mono">
                    <div class="p-3 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10">
                        <span class="text-stone-500 dark:text-stone-400 block text-[10px] uppercase font-bold">Host / Client</span>
                        <strong class="text-[#171513] dark:text-white font-sans font-bold text-sm">{{ $viewingBooking->customer_name }}</strong>
                    </div>
                    <div class="p-3 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10">
                        <span class="text-stone-500 dark:text-stone-400 block text-[10px] uppercase font-bold">Contact Phone</span>
                        <strong class="text-[#171513] dark:text-white">{{ $viewingBooking->customer_phone }}</strong>
                    </div>
                    <div class="p-3 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10">
                        <span class="text-stone-500 dark:text-stone-400 block text-[10px] uppercase font-bold">Event Date &amp; Shift</span>
                        <strong class="text-[#171513] dark:text-white">{{ \Carbon\Carbon::parse($viewingBooking->event_date)->format('d M Y') }} ({{ $viewingBooking->slot_type }})</strong>
                    </div>
                    <div class="p-3 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10">
                        <span class="text-stone-500 dark:text-stone-400 block text-[10px] uppercase font-bold">Confirmed Guests</span>
                        <strong class="text-[#8F7239] dark:text-[#C6A15B] font-bold text-sm">{{ $viewingBooking->guests_count ?? $viewingBooking->guest_count }} Heads</strong>
                    </div>
                </div>

                <!-- Financial Statement Summary -->
                <div class="p-4 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-2 text-xs font-mono">
                    <div class="flex justify-between text-stone-600 dark:text-stone-400">
                        <span>Total Grand Valuation:</span>
                        <strong class="text-[#171513] dark:text-white font-bold">PKR {{ number_format($viewingBooking->grand_total ?? $viewingBooking->total_amount, 0) }}</strong>
                    </div>
                    <div class="flex justify-between text-[#8F7239] dark:text-[#C6A15B]">
                        <span>Advance Cleared:</span>
                        <strong class="font-bold">PKR {{ number_format($viewingBooking->advance_paid, 0) }}</strong>
                    </div>
                    <div class="flex justify-between text-[#171513] dark:text-[#F8F5EF] font-bold border-t border-[#E7E5E4] dark:border-white/10 pt-2">
                        <span>Remaining Balance:</span>
                        <span>PKR {{ number_format($viewingBooking->balance_due ?? (($viewingBooking->grand_total ?? $viewingBooking->total_amount) - $viewingBooking->advance_paid), 0) }}</span>
                    </div>
                </div>

                <!-- Printable Business Documents Actions -->
                <div class="grid grid-cols-3 gap-3 pt-2 font-mono text-xs">
                    <a href="{{ route('documents.agreement', $viewingBooking->id) }}" target="_blank" 
                       class="px-3 py-2 bg-[#F8F5EF] dark:bg-white/10 border border-[#E7E5E4] dark:border-white/10 hover:border-[#171513] dark:hover:border-white text-stone-800 dark:text-stone-200 text-center font-bold transition">
                        📜 Agreement
                    </a>
                    @if($viewingBooking->payments && $viewingBooking->payments->isNotEmpty())
                        <a href="{{ route('documents.receipt', $viewingBooking->payments->first()->id) }}" target="_blank" 
                           class="px-3 py-2 bg-[#C6A15B] hover:bg-[#D9C088] text-[#171513] text-center font-bold transition">
                            🧾 Receipt
                        </a>
                    @else
                        <span class="px-3 py-2 bg-stone-100 dark:bg-white/5 text-stone-400 text-center">No Receipt</span>
                    @endif
                    @if($viewingBooking->kitchenKot)
                        <a href="{{ route('documents.kot', $viewingBooking->kitchenKot->id) }}" target="_blank" 
                           class="px-3 py-2 bg-[#F8F5EF] dark:bg-white/10 border border-[#C6A15B]/60 dark:border-[#C6A15B]/70 text-[#8F7239] dark:text-[#C6A15B] text-center font-bold hover:bg-[#C6A15B]/15 dark:hover:bg-[#171513]/85 transition">
                            🍳 Chef KOT
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
