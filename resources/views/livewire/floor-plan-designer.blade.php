<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    <!-- ===================================================================
         EXECUTIVE HEADER BANNER (Dual-Mode Luxury)
         =================================================================== -->
    <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-6 sm:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6 shadow-xl transition-colors duration-200">
        <div class="space-y-1.5">
            <div class="flex items-center gap-2 text-xs font-mono text-[#8F7239] dark:text-[#C6A15B] uppercase tracking-widest font-bold">
                <span>Vendor Portal</span>
                <span class="text-stone-400 dark:text-stone-600">/</span>
                <span>Banquet Architecture</span>
                <span class="text-stone-400 dark:text-stone-600">•</span>
                <span class="text-stone-600 dark:text-stone-300 font-mono">2D Interactive Blueprint</span>
            </div>
            <h1 class="font-serif-luxury text-2xl sm:text-4xl text-[#171513] dark:text-white font-bold tracking-tight">
                Banquet Seating &amp; Floor Planner
            </h1>
            <p class="text-xs text-stone-600 dark:text-stone-300 font-sans max-w-2xl leading-relaxed">
                Arrange 10-seater round tables, VIP royal bridal stages, family sofa lounges, and buffet stations with dynamic guest capacity tallying.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <button wire:click="saveFloorPlan" class="btn-gold !h-[40px] !px-6 text-xs font-mono font-bold uppercase tracking-wider shadow">
                💾 Save Floor Plan
            </button>
        </div>
    </div>

    @if($successMessage)
        <div class="p-4 bg-[#171513]/90 border border-[#C6A15B]/60 text-[#C6A15B] text-xs font-semibold shadow-md flex justify-between items-center">
            <span>✓ {{ $successMessage }}</span>
            <button wire:click="$set('successMessage', null)" class="text-[#8F7239] font-bold text-sm leading-none">&times;</button>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Control Palette Sidebar (Dual Mode Surface) -->
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-6 space-y-6 shadow-sm dark:shadow-xl transition-colors">
            <div>
                <label class="block text-[11px] uppercase font-mono tracking-wider text-[#171513] dark:text-[#C6A15B] mb-1.5 font-bold">Banquet Hall Space</label>
                <select wire:model.live="selectedHallId" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 text-[#171513] dark:text-white text-xs py-2.5 px-3 font-mono focus:border-[#C6A15B] outline-none">
                    @foreach($halls as $hall)
                        <option value="{{ $hall->id }}">{{ $hall->name }} (Max: {{ $hall->max_capacity }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Seat Tally KPI Card (Dual-Mode Gold Foil) -->
            <div class="p-5 bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border text-center shadow-md">
                <span class="text-[10px] uppercase font-mono tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold block">Allocated Seating Capacity</span>
                <span class="font-cinzel text-4xl text-[#171513] dark:text-white font-bold mt-1 block">{{ $totalSeats }}</span>
                <span class="text-[10px] text-stone-500 dark:text-stone-400 font-mono block mt-1">Total Dynamic Headcount</span>
            </div>

            <!-- Elements Palette -->
            <div class="space-y-2 font-mono">
                <span class="text-[11px] uppercase tracking-wider text-stone-700 dark:text-stone-300 font-bold block font-sans">Add Architectural Elements</span>
                
                <button wire:click="addElement('TABLE_10')" 
                        class="w-full p-2.5 bg-[#F8F5EF] dark:bg-[#171513] hover:bg-stone-100 dark:hover:bg-white/5 border border-[#E7E5E4] dark:border-white/10 hover:border-[#C6A15B] text-left text-xs text-stone-800 dark:text-stone-200 flex items-center justify-between transition font-medium">
                    <span>10-Seater Round Table</span>
                    <strong class="text-[#8F7239] dark:text-[#C6A15B] font-mono text-xs font-bold">+10</strong>
                </button>
                
                <button wire:click="addElement('TABLE_8')" 
                        class="w-full p-2.5 bg-[#F8F5EF] dark:bg-[#171513] hover:bg-stone-100 dark:hover:bg-white/5 border border-[#E7E5E4] dark:border-white/10 hover:border-[#C6A15B] text-left text-xs text-stone-800 dark:text-stone-200 flex items-center justify-between transition font-medium">
                    <span>8-Seater Round Table</span>
                    <strong class="text-[#8F7239] dark:text-[#C6A15B] font-mono text-xs font-bold">+8</strong>
                </button>
                
                <button wire:click="addElement('VIP_SOFA')" 
                        class="w-full p-2.5 bg-[#F8F5EF] dark:bg-[#171513] hover:bg-stone-100 dark:hover:bg-white/5 border border-[#E7E5E4] dark:border-white/10 hover:border-[#C6A15B] text-left text-xs text-stone-800 dark:text-stone-200 flex items-center justify-between transition font-medium">
                    <span>VIP Family Sofa Lounge</span>
                    <strong class="text-[#171513] dark:text-[#F8F5EF] font-mono text-xs font-bold">+8</strong>
                </button>
                
                <button wire:click="addElement('STAGE')" 
                        class="w-full p-2.5 bg-[#F8F5EF] dark:bg-[#171513] hover:bg-stone-100 dark:hover:bg-white/5 border border-[#E7E5E4] dark:border-white/10 hover:border-[#C6A15B] text-left text-xs text-stone-800 dark:text-stone-200 flex items-center justify-between transition font-medium">
                    <span>Royal Bridal Stage</span>
                    <strong class="text-[#8F7239] dark:text-[#C6A15B] font-mono text-xs font-bold">+4</strong>
                </button>
                
                <button wire:click="addElement('BUFFET')" 
                        class="w-full p-2.5 bg-[#F8F5EF] dark:bg-[#171513] hover:bg-stone-100 dark:hover:bg-white/5 border border-[#E7E5E4] dark:border-white/10 hover:border-[#C6A15B] text-left text-xs text-stone-800 dark:text-stone-200 flex items-center justify-between transition font-medium">
                    <span>Main Buffet Catering Line</span>
                    <strong class="text-[#8F7239] dark:text-[#C6A15B] font-mono text-xs font-bold">0</strong>
                </button>
            </div>
        </div>

        <!-- 2D Canvas Area (Architectural Blueprint Grid) -->
        <div class="lg:col-span-3 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-6 shadow-sm dark:shadow-xl min-h-[580px] flex flex-col justify-between relative transition-colors">
            <div class="text-center py-2 bg-[#F8F5EF] dark:bg-[#171513] border-b border-stone-200 dark:border-white/10 font-mono text-xs tracking-widest text-[#171513] dark:text-[#C6A15B] uppercase font-bold">
                ▲ MAIN VIP BRIDAL ELEVATION &amp; STAGE AREA ▲
            </div>

            <!-- Floor Elements Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 p-4 flex-1 my-4 bg-[radial-gradient(#E7E5E4_1px,transparent_1px)] dark:bg-[radial-gradient(#292524_1px,transparent_1px)] [background-size:16px_16px]">
                @foreach($elements as $el)
                    <div class="p-3.5 border transition relative group shadow-xs
                        {{ $el['type'] === 'STAGE' ? 'border-[#C6A15B] bg-[#C6A15B]/20 dark:bg-[#171513]/85 text-stone-900 dark:text-white font-bold' : 
                          ($el['type'] === 'BUFFET' ? 'border-[#C6A15B]/60 dark:border-[#C6A15B]/50 bg-[#C6A15B]/20 dark:bg-[#171513]/85 text-stone-900 dark:text-white' : 
                          ($el['type'] === 'VIP_SOFA' ? 'border-[#C6A15B]/50 dark:border-[#C6A15B]/50 bg-[#171513]/10 dark:bg-[#171513]/85 text-stone-900 dark:text-white' : 'border-[#E7E5E4] dark:border-white/15 bg-white dark:bg-[#171513] text-stone-900 dark:text-white')) }}">
                        
                            <div class="flex items-center justify-between border-b border-stone-200/60 dark:border-white/10 pb-1.5 mb-2">
                                <span class="text-[9px] font-mono uppercase tracking-wider font-bold text-stone-500 dark:text-stone-400">
                                    {{ $el['type'] === 'STAGE' ? 'Stage' : ($el['type'] === 'BUFFET' ? 'Buffet' : ($el['type'] === 'VIP_SOFA' ? 'Sofa' : 'Table')) }}
                                </span>
                                <button wire:click="removeElement('{{ $el['id'] }}')" class="text-stone-900 dark:text-white hover:text-[#F8F5EF] font-mono text-sm px-2 py-1">
                                    &times; Remove
                                </button>
                            </div>

                        <div>
                            <div class="font-sans text-xs font-bold text-[#171513] dark:text-white truncate">{{ $el['name'] }}</div>
                            <div class="text-[10px] text-stone-500 dark:text-stone-400 mt-0.5 font-mono">
                                {{ $el['seats'] > 0 ? "Cap: {$el['seats']} Heads" : 'Buffet Line' }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="text-center py-2 bg-[#F8F5EF] dark:bg-[#171513] border-t border-stone-200 dark:border-white/10 text-[11px] font-mono tracking-wider text-stone-600 dark:text-stone-300 uppercase font-bold">
                ▼ GUEST MAIN RED CARPET ENTRANCE &amp; FOYER ▼
            </div>
        </div>
    </div>
</div>
