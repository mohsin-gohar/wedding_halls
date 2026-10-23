<div class="bg-[#F8F5EF] dark:bg-[#171513] text-[#171513] dark:text-[#F8F5EF] min-h-screen transition-colors">

    <!-- ===================================================================
         SECTION 1: HERO & CULINARY HERITAGE HEADLINE
         =================================================================== -->
    <section class="parallax-window relative bg-[#171513] text-white py-20 border-b border-[#C6A15B]/30 overflow-hidden">
        <div class="parallax-bg opacity-30" data-parallax="0.25"
             style="background-image: url('https://images.unsplash.com/photo-1555244162-803834f70033?q=80&w=2000&auto=format&fit=crop');">
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#171513] via-[#171513]/75 to-[#171513]/90"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 text-center">
            <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full border border-[#C6A15B]/40 bg-[#171513]/90 text-[#E8DCBE] text-[11px] font-semibold tracking-[0.18em] uppercase font-sans">
                <span class="w-1.5 h-1.5 rounded-full bg-[#C6A15B]"></span>
                <span>Authentic Pakistani Mughlai Banqueting</span>
            </div>

            <h1 class="font-serif-luxury text-4xl sm:text-6xl text-white font-normal max-w-4xl mx-auto leading-tight">
                Wedding Packages &amp; Catering Menus
            </h1>

            <p class="text-sm sm:text-base text-stone-300 max-w-2xl mx-auto font-light leading-relaxed">
                Authored by veteran Pakistani executive chefs. From tender Mutton Qorma and slow-steamed Dum Pukht Pulao to live charcoal Seekh Kababs and traditional sweet rabri jalebi.
            </p>
        </div>
    </section>


    <!-- ===================================================================
         SECTION 2: SIGNATURE BANQUET PACKAGES (3 Grand Offerings)
         =================================================================== -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 space-y-10">
        <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-4 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <span class="text-[11px] font-sans uppercase tracking-[0.2em] text-[#8F7239] dark:text-[#C6A15B] font-semibold block">
                    Curated Feasts
                </span>
                <h2 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                    Verified Catering Packages
                </h2>
            </div>
            <p class="text-xs text-stone-500 dark:text-stone-400 font-sans">
                Standardized per-guest pricing inclusive of service staff and cutlery
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($packages as $pkg)
                <div class="editorial-card rounded-2xl p-8 bg-white dark:bg-[#1C1917] border border-[#E7E5E4] dark:border-white/15 hover:border-[#C6A15B]/60 dark:hover:border-[#C6A15B]/60 flex flex-col justify-between space-y-6 transition {{ $selectedPackageId === $pkg->id ? 'ring-2 ring-[#C6A15B]' : '' }}">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-[#E7E5E4] dark:border-white/10">
                            <span class="text-[11px] uppercase font-semibold tracking-[0.12em] text-[#8F7239] dark:text-[#C6A15B] font-sans">
                                Verified Menu
                            </span>
                            <span class="text-xs font-sans font-medium text-stone-500 dark:text-stone-400">Min {{ $pkg->min_guests }} Heads</span>
                        </div>

                        <h3 class="font-serif-luxury text-3xl text-[#171513] dark:text-white font-medium leading-snug">{{ $pkg->name }}</h3>
                        
                        <div class="flex items-baseline gap-2">
                            <span class="font-cinzel text-3xl font-bold text-[#171513] dark:text-[#C6A15B]">PKR {{ number_format($pkg->per_head_price) }}</span>
                            <span class="text-xs text-stone-500 dark:text-stone-400 font-sans font-semibold uppercase tracking-wider">/ Guest</span>
                        </div>

                        <p class="text-xs text-stone-600 dark:text-stone-300 font-normal leading-relaxed">
                            {{ $pkg->description }}
                        </p>

                        <!-- Dishes List -->
                        @if(is_array($pkg->menu_items_json))
                            <div class="mt-4 pt-4 border-t border-[#E7E5E4] dark:border-white/10 space-y-3 text-xs">
                                @foreach($pkg->menu_items_json as $cat => $items)
                                    <div>
                                        <span class="text-[11px] uppercase tracking-wider text-[#8F7239] dark:text-[#C6A15B] font-semibold block font-sans">{{ $cat }}:</span>
                                        <p class="text-stone-700 dark:text-stone-200 font-medium mt-0.5">
                                            {{ is_array($items) ? implode(' • ', $items) : $items }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="pt-6 border-t border-[#E7E5E4] dark:border-white/10 flex items-center justify-between gap-3">
                        <button type="button" wire:click="$set('selectedPackageId', '{{ $pkg->id }}')" 
                                class="btn-secondary !h-9 !px-3.5 text-xs font-sans font-semibold">
                            Use in Calculator
                        </button>
                        <button onclick="Livewire.dispatch('openWalkInModal', { package_id: '{{ $pkg->id }}' })" 
                                class="btn-obsidian !h-9 !px-4 text-xs font-sans font-semibold">
                            Reserve Menu &rarr;
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </section>


    <!-- ===================================================================
         SECTION 3: BAWARCHI KITCHEN RAW INGREDIENT & LIVE CALCULATOR
         =================================================================== -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="bg-white dark:bg-[#1C1917] rounded-2xl border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-8 sm:p-10 shadow-xs space-y-8">
            <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-4 flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <span class="text-[11px] uppercase font-semibold tracking-[0.18em] text-[#8F7239] dark:text-[#C6A15B] block font-sans">Raw Procurement Formulas</span>
                    <h3 class="font-serif-luxury text-2xl sm:text-3xl text-[#171513] dark:text-white font-normal">
                        Bawarchi Kitchen Ingredient Estimator
                    </h3>
                </div>
                <div class="text-xs text-stone-500 dark:text-stone-400 font-sans">
                    Calculated for: <strong class="text-[#171513] dark:text-[#C6A15B]">{{ $selectedPackage ? $selectedPackage->name : 'Standard Feast' }}</strong>
                </div>
            </div>

            <!-- Slider Controls -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
                <div class="md:col-span-5 space-y-4">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-stone-700 dark:text-stone-300 font-sans">
                        Total Expected Guests: <span class="font-cinzel text-xl text-[#171513] dark:text-[#C6A15B] font-bold ml-2">{{ $calculatorGuests }} Heads</span>
                    </label>
                    <input type="range" wire:model.live="calculatorGuests" min="50" max="2500" step="25" class="w-full accent-[#C6A15B]">
                    <div class="flex justify-between text-[11px] text-stone-400 font-sans">
                        <span>50 guests</span>
                        <span>1,250 guests</span>
                        <span>2,500 guests</span>
                    </div>
                </div>

                <!-- Live Multipliers Table -->
                <div class="md:col-span-7 grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="p-4 rounded-xl bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 text-center space-y-1">
                        <span class="text-[10px] uppercase font-semibold text-stone-500 dark:text-stone-400 font-sans block">Raw Meat</span>
                        <strong class="font-cinzel text-xl text-[#171513] dark:text-white block">{{ round($calculatorGuests * 0.22, 1) }} KG</strong>
                        <span class="text-[11px] text-stone-400 font-sans">Mutton &amp; Chicken</span>
                    </div>
                    <div class="p-4 rounded-xl bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 text-center space-y-1">
                        <span class="text-[10px] uppercase font-semibold text-stone-500 dark:text-stone-400 font-sans block">Kainat Rice</span>
                        <strong class="font-cinzel text-xl text-[#171513] dark:text-white block">{{ round($calculatorGuests * 0.16, 1) }} KG</strong>
                        <span class="text-[11px] text-stone-400 font-sans">Basmati Aged</span>
                    </div>
                    <div class="p-4 rounded-xl bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 text-center space-y-1">
                        <span class="text-[10px] uppercase font-semibold text-stone-500 dark:text-stone-400 font-sans block">Pure Cooking Oil</span>
                        <strong class="font-cinzel text-xl text-[#171513] dark:text-white block">{{ round($calculatorGuests * 0.05, 1) }} Ltr</strong>
                        <span class="text-[11px] text-stone-400 font-sans">Desi Ghee / Oil</span>
                    </div>
                    <div class="p-4 rounded-xl bg-[#171513] text-white border border-[#C6A15B]/45 text-center space-y-1">
                        <span class="text-[10px] uppercase font-semibold text-[#C6A15B] font-sans block">Est. Catering</span>
                        <strong class="font-cinzel text-base text-white block">
                            PKR {{ number_format($calculatorGuests * ($selectedPackage ? $selectedPackage->per_head_price : 2450)) }}
                        </strong>
                        <span class="text-[11px] text-stone-400 font-sans">Grand Subtotal</span>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- ===================================================================
         SECTION 4: LIVE COOKING STATIONS & LIVE TANDOOR COUNTERS
         =================================================================== -->
    <section class="py-16 bg-[#F8F5EF]/50 dark:bg-[#171513]/50 border-t border-b border-[#E7E5E4] dark:border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            <div class="text-center space-y-2 max-w-2xl mx-auto">
                <span class="text-[11px] font-sans uppercase tracking-[0.2em] text-[#8F7239] dark:text-[#C6A15B] font-semibold block">
                    Sensory Dining
                </span>
                <h2 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                    Live Cooking Stations &amp; Street Counters
                </h2>
                <p class="text-xs text-stone-600 dark:text-stone-300 font-sans">
                    Treat your guests to the unforgettable aroma of live charcoal barbecue and sizzling sweet counters.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Station 1 -->
                <div class="editorial-card rounded-2xl p-6 bg-white dark:bg-[#1C1917] border border-[#E7E5E4] dark:border-white/10 space-y-3.5">
                    <div class="w-10 h-10 rounded-xl bg-[#C6A15B]/10 border border-[#C6A15B]/30 flex items-center justify-center text-[#8F7239] dark:text-[#C6A15B]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"/></svg>
                    </div>
                    <h4 class="font-serif-luxury text-xl text-[#171513] dark:text-white">Live Charcoal Seekh Bar</h4>
                    <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed">
                        Sizzling chicken and beef seekh kababs grilled directly over hardwood coals with mint chutney.
                    </p>
                </div>

                <!-- Station 2 -->
                <div class="editorial-card rounded-2xl p-6 bg-white dark:bg-[#1C1917] border border-[#E7E5E4] dark:border-white/10 space-y-3.5">
                    <div class="w-10 h-10 rounded-xl bg-[#C6A15B]/10 border border-[#C6A15B]/30 flex items-center justify-center text-[#8F7239] dark:text-[#C6A15B]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/><circle cx="12" cy="12" r="9"/></svg>
                    </div>
                    <h4 class="font-serif-luxury text-xl text-[#171513] dark:text-white">Clay Tandoor Hot Breads</h4>
                    <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed">
                        Piping hot sesame Roghni Naans, Taftan, and garlic butter kulchas straight from clay ovens.
                    </p>
                </div>

                <!-- Station 3 -->
                <div class="editorial-card rounded-2xl p-6 bg-white dark:bg-[#1C1917] border border-[#E7E5E4] dark:border-white/10 space-y-3.5">
                    <div class="w-10 h-10 rounded-xl bg-[#C6A15B]/10 border border-[#C6A15B]/30 flex items-center justify-center text-[#8F7239] dark:text-[#C6A15B]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                    </div>
                    <h4 class="font-serif-luxury text-xl text-[#171513] dark:text-white">Crisp Jalebi &amp; Rabri</h4>
                    <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed">
                        Live wok-fried crispy saffron jalebis drenched in pure sugar syrup and topped with chilled creamy rabri.
                    </p>
                </div>

                <!-- Station 4 -->
                <div class="editorial-card rounded-2xl p-6 bg-white dark:bg-[#1C1917] border border-[#E7E5E4] dark:border-white/10 space-y-3.5">
                    <div class="w-10 h-10 rounded-xl bg-[#C6A15B]/10 border border-[#C6A15B]/30 flex items-center justify-center text-[#8F7239] dark:text-[#C6A15B]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 8h1a4 4 0 010 8h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8zm4-7v3m4-3v3m4-3v3"/></svg>
                    </div>
                    <h4 class="font-serif-luxury text-xl text-[#171513] dark:text-white">Pink Kashmiri Chai Khokha</h4>
                    <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed">
                        Slow-brewed traditional pink tea garnished with crushed pistachios, green cardamom, and almonds.
                    </p>
                </div>
            </div>
        </div>
    </section>


    <!-- ===================================================================
         SECTION 5: FOOD QUALITY, HYGIENE & 100% HALAL CERTIFICATION
         =================================================================== -->
    <section class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="p-6 rounded-2xl bg-white dark:bg-[#1C1917] border border-[#E7E5E4] dark:border-white/10 space-y-2 shadow-xs">
                <span class="text-xs font-sans uppercase font-semibold tracking-wider text-[#8F7239] dark:text-[#C6A15B]">100% Certified Halal</span>
                <h4 class="font-serif-luxury text-xl text-[#171513] dark:text-white">Prime Grade Fresh Meat</h4>
                <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed">
                    Sourced exclusively from verified hygienic abattoirs on the morning of each wedding program.
                </p>
            </div>

            <div class="p-6 rounded-2xl bg-white dark:bg-[#1C1917] border border-[#E7E5E4] dark:border-white/10 space-y-2 shadow-xs">
                <span class="text-xs font-sans uppercase font-semibold tracking-wider text-[#8F7239] dark:text-[#C6A15B]">Punjab &amp; Sindh Food Authority</span>
                <h4 class="font-serif-luxury text-xl text-[#171513] dark:text-white">Compliant Kitchen Sanitation</h4>
                <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed">
                    Stainless steel preparation tables, industrial water filtration, and temperature-controlled food warming chafers.
                </p>
            </div>

            <div class="p-6 rounded-2xl bg-white dark:bg-[#1C1917] border border-[#E7E5E4] dark:border-white/10 space-y-2 shadow-xs">
                <span class="text-xs font-sans uppercase font-semibold tracking-wider text-[#8F7239] dark:text-[#C6A15B]">Zero Food Shortage Guarantee</span>
                <h4 class="font-serif-luxury text-xl text-[#171513] dark:text-white">10% Kitchen Safety Buffer</h4>
                <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed">
                    Every banquet booking includes an emergency kitchen surplus reserve to ensure all unannounced guests are served with honor.
                </p>
            </div>
        </div>
    </section>


    <!-- ===================================================================
         SECTION 6: TASTING SESSIONS & CUSTOM CONSULTATION CTA
         =================================================================== -->
    <section class="py-16 bg-[#171513] text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="space-y-2">
                <span class="text-[11px] font-sans uppercase tracking-[0.2em] text-[#C6A15B] block font-semibold">
                    Complimentary Tasting Session
                </span>
                <h3 class="font-serif-luxury text-2xl sm:text-3xl font-normal text-white">
                    Schedule a Pre-Wedding Menu Tasting
                </h3>
                <p class="text-xs text-stone-300 font-light max-w-xl">
                    Sample our signature Mutton Qorma and Dum Pukht Pulao with your family prior to final booking confirmation.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-4 shrink-0">
                <a href="{{ route('public.contact') }}" class="btn-gold !h-11 !px-6 text-xs font-semibold uppercase font-sans">
                    Book Tasting Session &rarr;
                </a>
            </div>
        </div>
    </section>

</div>
