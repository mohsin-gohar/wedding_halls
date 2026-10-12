<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    <!-- ===================================================================
         EXECUTIVE HEADER BANNER (Dual-Mode Luxury)
         =================================================================== -->
    <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-6 sm:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6 shadow-xl transition-colors duration-200">
        <div class="space-y-1.5">
            <div class="flex items-center gap-2 text-xs font-mono text-[#8F7239] dark:text-[#C6A15B] uppercase tracking-widest font-bold">
                <span>Vendor Portal</span>
                <span class="text-stone-400 dark:text-stone-600">/</span>
                <span>Power &amp; Utilities</span>
                <span class="text-stone-400 dark:text-stone-600">•</span>
                <span class="text-[#8F7239] dark:text-[#C6A15B] font-mono">Load-Shedding Cost Intelligence</span>
            </div>
            <h1 class="font-serif-luxury text-2xl sm:text-4xl text-[#171513] dark:text-white font-bold tracking-tight">
                Generator Fuel &amp; Power Log
            </h1>
            <p class="text-xs text-stone-600 dark:text-stone-300 font-sans max-w-2xl leading-relaxed">
                Log standby diesel generator meter run-hours, fuel consumption during outages, and calculate net event operating margins.
            </p>
        </div>
    </div>

    @if($successMessage)
        <div class="p-4 bg-[#171513]/90 border border-[#C6A15B]/60 text-[#C6A15B] text-xs font-semibold shadow-md flex justify-between items-center">
            <span>✓ {{ $successMessage }}</span>
            <button wire:click="$set('successMessage', null)" class="text-[#8F7239] font-bold text-sm leading-none">&times;</button>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Function Booking Selector -->
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-6 space-y-4 shadow-sm dark:shadow-xl transition-colors">
            <span class="text-[11px] uppercase tracking-wider text-stone-700 dark:text-stone-300 font-mono font-bold block">Select Event Function</span>
            <div class="space-y-2 max-h-[540px] overflow-y-auto pr-1">
                @foreach($bookings as $b)
                    <button wire:click="selectBooking('{{ $b->id }}')" 
                            class="w-full text-left p-3.5 border transition text-xs
                            {{ $selectedBookingId === $b->id 
                                ? 'border-[#C6A15B] bg-[#C6A15B]/20 dark:bg-[#C6A15B]/10 shadow-xs' 
                                : 'border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#171513] hover:bg-stone-100 dark:hover:bg-white/5' }}">
                        <div class="flex justify-between items-start">
                            <span class="font-sans font-bold text-sm text-[#171513] dark:text-white">{{ $b->customer_name }}</span>
                            <span class="text-[10px] uppercase font-mono font-bold px-2 py-0.5 border border-[#E7E5E4] dark:border-white/15 bg-white dark:bg-[#171513] text-stone-700 dark:text-stone-300">
                                {{ $b->slot_type }}
                            </span>
                        </div>
                        <div class="text-[11px] text-stone-500 dark:text-stone-400 mt-1 font-mono">
                            {{ $b->event_type }} • {{ \Carbon\Carbon::parse($b->event_date)->format('d M Y') }} • {{ $b->hall->name ?? 'Hall' }}
                        </div>
                        @if($b->utilityLog)
                            <span class="text-[10px] text-[#8F7239] dark:text-[#C6A15B] mt-1.5 block font-mono font-bold">
                                ✓ Logged: {{ $b->utilityLog->diesel_liters_consumed }}L Diesel
                            </span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Center & Right: Form & Margin Intelligence -->
        <div class="lg:col-span-2 space-y-6">
            @if($currentBooking)
                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-6 sm:p-8 space-y-6 shadow-sm dark:shadow-xl transition-colors">
                    <div class="flex justify-between items-start border-b border-[#E7E5E4] dark:border-white/10 pb-4">
                        <div>
                            <span class="text-[10px] font-mono uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold block">Active Event Selection</span>
                            <h2 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-bold mt-0.5">
                                {{ $currentBooking->event_type }} — {{ $currentBooking->customer_name }}
                            </h2>
                            <p class="text-xs text-stone-500 dark:text-stone-400 font-mono mt-0.5">
                                {{ $currentBooking->hall->name ?? 'Hall' }} | Guests: {{ $currentBooking->guests_count ?? $currentBooking->guest_count }} Heads
                            </p>
                        </div>
                        <div class="text-right font-mono">
                            <span class="text-[10px] text-stone-400 uppercase font-bold block">Contract Revenue</span>
                            <span class="font-cinzel text-xl text-[#171513] dark:text-[#C6A15B] font-bold">
                                PKR {{ number_format($currentBooking->grand_total ?? $currentBooking->total_amount, 0) }}
                            </span>
                        </div>
                    </div>

                    <!-- Input Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <!-- Generator Section -->
                        <div class="p-5 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-3.5">
                            <h3 class="font-serif-luxury text-lg text-[#171513] dark:text-white font-bold">
                                Standby Diesel Generator
                            </h3>
                            <div>
                                <label class="block text-[11px] uppercase tracking-wider font-mono text-stone-700 dark:text-stone-300 mb-1 font-bold">Meter Run Hours</label>
                                <input type="number" step="0.1" wire:model.live="generator_run_hours" class="w-full bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 text-[#171513] dark:text-white px-3 py-2 text-xs font-mono font-bold focus:border-[#C6A15B] outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] uppercase tracking-wider font-mono text-stone-700 dark:text-stone-300 mb-1 font-bold">Diesel Consumed (Liters)</label>
                                <input type="number" step="0.5" wire:model.live="diesel_liters_consumed" class="w-full bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 text-[#171513] dark:text-white px-3 py-2 text-xs font-mono font-bold focus:border-[#C6A15B] outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] uppercase tracking-wider font-mono text-stone-700 dark:text-stone-300 mb-1 font-bold">Diesel Tariff (PKR / Liter)</label>
                                <input type="number" step="1" wire:model.live="diesel_rate_per_liter" class="w-full bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 text-[#171513] dark:text-white px-3 py-2 text-xs font-mono font-bold focus:border-[#C6A15B] outline-none">
                            </div>
                            <div class="pt-2 border-t border-stone-200 dark:border-white/10 text-xs font-mono flex justify-between">
                                <span class="text-stone-500 dark:text-stone-400 font-bold">Fuel Subtotal:</span>
                                <strong class="text-[#171513] dark:text-white font-bold">PKR {{ number_format($dieselCost, 0) }}</strong>
                            </div>
                        </div>

                        <!-- Grid Electricity (WAPDA) Section -->
                        <div class="p-5 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-3.5">
                            <h3 class="font-serif-luxury text-lg text-[#171513] dark:text-white font-bold">
                                Grid Electricity (WAPDA)
                            </h3>
                            <div>
                                <label class="block text-[11px] uppercase tracking-wider font-mono text-stone-700 dark:text-stone-300 mb-1 font-bold">Units Consumed (KWh)</label>
                                <input type="number" step="1" wire:model.live="wapda_units_consumed" class="w-full bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 text-[#171513] dark:text-white px-3 py-2 text-xs font-mono font-bold focus:border-[#C6A15B] outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] uppercase tracking-wider font-mono text-stone-700 dark:text-stone-300 mb-1 font-bold">Unit Rate (PKR / KWh)</label>
                                <input type="number" step="1" wire:model.live="wapda_unit_rate" class="w-full bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 text-[#171513] dark:text-white px-3 py-2 text-xs font-mono font-bold focus:border-[#C6A15B] outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] uppercase tracking-wider font-mono text-stone-700 dark:text-stone-300 mb-1 font-bold">Operational Shift Notes</label>
                                <input type="text" wire:model="notes" placeholder="Generator active 8:30 PM to 10:15 PM" class="w-full bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 text-[#171513] dark:text-white px-3 py-2 text-xs font-sans focus:border-[#C6A15B] outline-none">
                            </div>
                            <div class="pt-2 border-t border-stone-200 dark:border-white/10 text-xs font-mono flex justify-between">
                                <span class="text-stone-500 dark:text-stone-400 font-bold">Grid Power Subtotal:</span>
                                <strong class="text-[#171513] dark:text-white font-bold">PKR {{ number_format($wapdaCost, 0) }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Margin Intelligence Frame (Dual Mode High Contrast) -->
                    <div class="p-6 bg-[#F8F5EF] dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 shadow-md transition-colors">
                        <div>
                            <span class="text-[10px] uppercase font-mono tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold block">Net Event Profit Margin</span>
                            <span class="text-xs text-stone-600 dark:text-stone-300 font-sans mt-0.5 block">Revenue realization after Bawarchi raw materials &amp; combined utility tariffs.</span>
                        </div>
                        <div class="text-right font-mono">
                            <span class="font-cinzel text-3xl font-bold text-[#8F7239] dark:text-[#C6A15B] block">PKR {{ number_format($estimatedNetProfit, 0) }}</span>
                            <span class="text-[10px] text-stone-500 dark:text-stone-400">Total Utility Cost: PKR {{ number_format($totalUtilityCost, 0) }}</span>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button wire:click="saveUtilityLog" class="btn-gold !h-[40px] !px-6 text-xs font-mono font-bold uppercase tracking-wider shadow">
                            💾 Save Fuel &amp; Utility Log
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
