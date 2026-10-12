<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    <!-- ===================================================================
         EXECUTIVE HEADER BANNER (Dual-Mode Luxury)
         =================================================================== -->
    <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-6 sm:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6 shadow-xl transition-colors duration-200">
        <div class="space-y-1.5">
            <div class="flex items-center gap-2 text-xs font-mono text-[#8F7239] dark:text-[#C6A15B] uppercase tracking-widest font-bold">
                <span>Vendor Portal</span>
                <span class="text-stone-400 dark:text-stone-600">/</span>
                <span>Culinary Logistics</span>
                <span class="text-stone-400 dark:text-stone-600">•</span>
                <span class="text-stone-600 dark:text-stone-300 font-mono">Bawarchi Production Engine</span>
            </div>
            <h1 class="font-serif-luxury text-2xl sm:text-4xl text-[#171513] dark:text-white font-bold tracking-tight">
                Chef Kitchen Order Tickets (KOT)
            </h1>
            <p class="text-xs text-stone-600 dark:text-stone-300 font-sans max-w-2xl leading-relaxed">
                Raw material formulations per head: 0.12kg meat, 0.16kg basmati rice, and 0.025L oil. Real-time preparation pipeline and authoritative kitchen slips.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <div>
                <label class="block text-[10px] uppercase font-mono tracking-wider text-[#8F7239] dark:text-[#C6A15B] mb-1 font-bold">Production Date</label>
                <input type="date" wire:model.live="filterDate" class="bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/50 text-[#171513] dark:text-stone-200 text-xs px-3.5 py-2 font-mono focus:border-[#C6A15B] outline-none">
            </div>
        </div>
    </div>

    <!-- ===================================================================
         RAW MATERIAL AGGREGATE SUMMARY (Dual-Mode Luxury KPI Panels)
         =================================================================== -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 font-mono">
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl space-y-1 relative overflow-hidden group hover:border-[#C6A15B] transition duration-300">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <span class="text-[10px] uppercase tracking-wider text-stone-500 dark:text-stone-400 font-bold block">Confirmed Headcount</span>
            <span class="font-cinzel text-3xl text-[#171513] dark:text-white font-bold block">{{ number_format($totalGuests) }}</span>
            <span class="text-[11px] text-stone-500 dark:text-stone-400 block font-sans">Total Expected Diners</span>
        </div>

        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl space-y-1 relative overflow-hidden group hover:border-[#C6A15B] transition duration-300">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <span class="text-[10px] uppercase tracking-wider text-[#8F7239] dark:text-[#C6A15B] font-bold block">Raw Meat Needed</span>
            <span class="font-cinzel text-3xl text-[#8F7239] dark:text-[#C6A15B] font-bold block">{{ number_format($totalMeat, 1) }} KG</span>
            <span class="text-[11px] text-stone-500 dark:text-stone-400 block font-sans">Mutton &amp; Farm Chicken</span>
        </div>

        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl space-y-1 relative overflow-hidden group hover:border-[#C6A15B] transition duration-300">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <span class="text-[10px] uppercase tracking-wider text-[#8F7239] dark:text-[#C6A15B] font-bold block">Basmati Rice Needed</span>
            <span class="font-cinzel text-3xl text-[#8F7239] dark:text-[#C6A15B] font-bold block">{{ number_format($totalRice, 1) }} KG</span>
            <span class="text-[11px] text-stone-500 dark:text-stone-400 block font-sans">Super Kernel Basmati</span>
        </div>

        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl space-y-1 relative overflow-hidden group hover:border-[#C6A15B] transition duration-300">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <span class="text-[10px] uppercase tracking-wider text-stone-600 dark:text-stone-300 font-bold block">Cooking Oil Needed</span>
            <span class="font-cinzel text-3xl text-[#171513] dark:text-white font-bold block">{{ number_format($totalOil, 1) }} L</span>
            <span class="text-[11px] text-stone-500 dark:text-stone-400 block font-sans">Refined Canola Oil</span>
        </div>
    </div>

    <!-- ===================================================================
         KOT ORDERS LIST (Refined Business Cards)
         =================================================================== -->
    @if($kots->isEmpty())
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-16 text-center text-stone-500 dark:text-stone-400 shadow-sm transition-colors">
            <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-bold">No Kitchen Orders for {{ $filterDate }}</h3>
            <p class="text-xs text-stone-500 dark:text-stone-400 mt-1 font-sans">Confirmed functions for this date will formulate here automatically.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($kots as $kot)
                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 hover:border-[#C6A15B] p-6 space-y-4 shadow-sm dark:shadow-xl transition">
                    <div class="flex justify-between items-start border-b border-[#E7E5E4] dark:border-white/10 pb-3">
                        <div>
                            <span class="text-[10px] tracking-wider px-2 py-0.5 border border-[#E7E5E4] dark:border-white/15 bg-[#F8F5EF] dark:bg-[#171513] text-stone-700 dark:text-stone-300 font-mono font-bold">
                                {{ $kot->kot_number }}
                            </span>
                            <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold mt-2">{{ $kot->booking->event_type ?? 'Wedding Function' }}</h3>
                            <p class="text-xs text-stone-500 dark:text-stone-400 font-mono mt-0.5">{{ $kot->booking->hall->name ?? 'Banquet Space' }} ({{ $kot->booking->slot_type ?? 'Evening' }} Shift)</p>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-stone-500 dark:text-stone-400 uppercase font-mono block font-bold">Headcount</span>
                            <span class="font-cinzel text-3xl text-[#171513] dark:text-white font-bold">{{ $kot->guest_count }}</span>
                        </div>
                    </div>

                    <!-- Material Allocations -->
                    <div class="grid grid-cols-3 gap-3 bg-[#F8F5EF] dark:bg-[#171513] p-3 border border-[#E7E5E4] dark:border-white/10 text-center font-mono">
                        <div class="p-2 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10">
                            <span class="text-[9px] uppercase tracking-wider text-stone-500 dark:text-stone-400 block font-bold">RAW MEAT</span>
                            <strong class="font-cinzel text-base text-[#8F7239] dark:text-[#C6A15B]">{{ $kot->raw_meat_kg }} KG</strong>
                        </div>
                        <div class="p-2 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10">
                            <span class="text-[9px] uppercase tracking-wider text-stone-500 dark:text-stone-400 block font-bold">BASMATI RICE</span>
                            <strong class="font-cinzel text-base text-[#8F7239] dark:text-[#C6A15B]">{{ $kot->raw_rice_kg }} KG</strong>
                        </div>
                        <div class="p-2 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10">
                            <span class="text-[9px] uppercase tracking-wider text-stone-500 dark:text-stone-400 block font-bold">COOKING OIL</span>
                            <strong class="font-cinzel text-base text-[#171513] dark:text-white">{{ $kot->raw_oil_liters }} L</strong>
                        </div>
                    </div>

                    <!-- Approved Dishes -->
                    @if(is_array($kot->dishes_summary))
                        <div class="text-xs space-y-1 font-mono">
                            <span class="text-[10px] tracking-wider uppercase text-stone-500 dark:text-stone-400 font-bold block font-sans">Dishes Checklist:</span>
                            <div class="bg-[#F8F5EF] dark:bg-[#171513] p-3 border border-[#E7E5E4] dark:border-white/10 space-y-1.5 text-xs">
                                @foreach($kot->dishes_summary as $cat => $dishes)
                                    <div>
                                        <strong class="text-[#171513] dark:text-white">{{ is_string($cat) ? $cat : 'Dish Item' }}:</strong>
                                        <span class="text-stone-700 dark:text-stone-300">{{ is_array($dishes) ? implode(', ', $dishes) : $dishes }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Status Segmented Controls & Print Button (Zero-Pill) -->
                    <div class="pt-3 border-t border-[#E7E5E4] dark:border-white/10 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-1 text-xs p-1 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 font-mono">
                            @foreach(['PENDING', 'PREPARING', 'READY', 'SERVED'] as $st)
                                <button wire:click="updateStatus('{{ $kot->id }}', '{{ $st }}')"
                                        class="px-2.5 py-1 text-[10px] uppercase tracking-wider transition font-bold
                                        {{ $kot->preparation_status === $st 
                                            ? ($st === 'READY' || $st === 'SERVED' ? 'bg-[#C6A15B] text-white shadow' : 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513] shadow')
                                            : 'text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-white' }}">
                                    {{ $st }}
                                </button>
                            @endforeach
                        </div>

                        <a href="{{ route('documents.kot', $kot->id) }}" target="_blank" 
                           class="btn-outline-gold !h-8 !px-3 text-xs font-mono font-bold uppercase transition flex items-center gap-1">
                            <span>Print Slip &rarr;</span>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
