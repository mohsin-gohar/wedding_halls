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
            <div class="inline-flex items-center gap-2.5 px-4 py-1.5 border border-[#C6A15B]/50 bg-[#171513]/90 text-[#C6A15B] text-[11px] font-semibold tracking-[0.22em] uppercase font-mono">
                <span class="w-1.5 h-1.5 bg-[#C6A15B]"></span>
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
                <span class="text-[10px] font-mono uppercase tracking-[0.2em] text-[#8F7239] dark:text-[#C6A15B] font-bold block">
                    Curated Feasts
                </span>
                <h2 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                    Verified Catering Packages
                </h2>
            </div>
            <p class="text-xs text-stone-500 dark:text-stone-400 font-mono">
                Standardized per-guest pricing inclusive of service staff and cutlery
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($packages as $pkg)
                <div class="editorial-card p-8 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 hover:border-[#C6A15B] dark:hover:border-[#C6A15B] flex flex-col justify-between space-y-6 shadow-sm hover:shadow-md transition {{ $selectedPackageId === $pkg->id ? 'ring-2 ring-[#C6A15B]' : '' }}">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-[#E7E5E4] dark:border-white/10">
                            <span class="text-[10px] uppercase font-bold tracking-[0.15em] text-[#8F7239] dark:text-[#C6A15B] font-mono">
                                Verified Menu
                            </span>
                            <span class="text-xs font-mono font-semibold text-stone-500 dark:text-stone-400">Min {{ $pkg->min_guests }} Heads</span>
                        </div>

                        <h3 class="font-serif-luxury text-3xl text-[#171513] dark:text-white font-medium leading-snug">{{ $pkg->name }}</h3>
                        
                        <div class="flex items-baseline gap-2">
                            <span class="font-cinzel text-3xl font-bold text-[#171513] dark:text-[#C6A15B]">PKR {{ number_format($pkg->per_head_price) }}</span>
                            <span class="text-xs text-stone-500 dark:text-stone-400 font-mono font-semibold uppercase tracking-wider">/ Guest</span>
                        </div>

                        <p class="text-xs text-stone-600 dark:text-stone-300 font-normal leading-relaxed">
                            {{ $pkg->description }}
                        </p>

                        <!-- Dishes List -->
                        @if(is_array($pkg->menu_items_json))
                            <div class="mt-4 pt-4 border-t border-[#E7E5E4] dark:border-white/10 space-y-3 text-xs">
                                @foreach($pkg->menu_items_json as $cat => $items)
                                    <div>
                                        <span class="text-[10px] uppercase tracking-wider text-[#8F7239] dark:text-[#C6A15B] font-bold block font-mono">{{ $cat }}:</span>
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
                                class="btn-secondary !h-9 !px-3 text-xs font-mono font-semibold">
                            Use in Calculator
                        </button>
                        <button onclick="Livewire.dispatch('openWalkInModal', { package_id: '{{ $pkg->id }}' })" 
                                class="btn-obsidian !h-9 !px-4 text-xs font-mono font-semibold">
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
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-8 sm:p-10 shadow-sm space-y-8">
            <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-4 flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <span class="text-[10px] uppercase font-bold tracking-[0.2em] text-[#8F7239] dark:text-[#C6A15B] block font-mono">Raw Procurement Formulas</span>
                    <h3 class="font-serif-luxury text-2xl sm:text-3xl text-[#171513] dark:text-white font-normal">
                        Bawarchi Kitchen Ingredient Estimator
                    </h3>
                </div>
                <div class="text-xs text-stone-500 dark:text-stone-400 font-mono">
                    Calculated for: <strong class="text-[#171513] dark:text-[#C6A15B]">{{ $selectedPackage ? $selectedPackage->name : 'Standard Feast' }}</strong>
                </div>
            </div>

            <!-- Slider Controls -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
                <div class="md:col-span-5 space-y-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 font-mono">
                        Total Expected Guests: <span class="font-cinzel text-xl text-[#171513] dark:text-[#C6A15B] font-bold ml-2">{{ $calculatorGuests }} Heads</span>
                    </label>
                    <input type="range" wire:model.live="calculatorGuests" min="50" max="2500" step="25" class="w-full accent-[#C6A15B]">
                    <div class="flex justify-between text-[11px] text-stone-400 font-mono">
                        <span>50 guests</span>
                        <span>1,250 guests</span>
                        <span>2,500 guests</span>
                    </div>
                </div>

                <!-- Live Multipliers Table -->
                <div class="md:col-span-7 grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="p-4 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 text-center space-y-1">
                        <span class="text-[10px] uppercase font-bold text-stone-500 dark:text-stone-400 font-mono block">Raw Meat</span>
                        <strong class="font-cinzel text-xl text-[#171513] dark:text-white block">{{ round($calculatorGuests * 0.22, 1) }} KG</strong>
                        <span class="text-[10px] text-stone-400 font-mono">Mutton &amp; Chicken</span>
                    </div>
                    <div class="p-4 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 text-center space-y-1">
                        <span class="text-[10px] uppercase font-bold text-stone-500 dark:text-stone-400 font-mono block">Kainat Rice</span>
                        <strong class="font-cinzel text-xl text-[#171513] dark:text-white block">{{ round($calculatorGuests * 0.16, 1) }} KG</strong>
                        <span class="text-[10px] text-stone-400 font-mono">Basmati Aged</span>
                    </div>
                    <div class="p-4 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 text-center space-y-1">
                        <span class="text-[10px] uppercase font-bold text-stone-500 dark:text-stone-400 font-mono block">Pure Cooking Oil</span>
                        <strong class="font-cinzel text-xl text-[#171513] dark:text-white block">{{ round($calculatorGuests * 0.05, 1) }} Ltr</strong>
                        <span class="text-[10px] text-stone-400 font-mono">Desi Ghee / Oil</span>
                    </div>
                    <div class="p-4 bg-[#171513] text-white border border-[#C6A15B]/50 text-center space-y-1">
                        <span class="text-[10px] uppercase font-bold text-[#C6A15B] font-mono block">Est. Catering</span>
                        <strong class="font-cinzel text-base text-white block">
                            PKR {{ number_format($calculatorGuests * ($selectedPackage ? $selectedPackage->per_head_price : 2450)) }}
                        </strong>
                        <span class="text-[10px] text-stone-400 font-mono">Grand Subtotal</span>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- ===================================================================
         SECTION 4: LIVE COOKING STATIONS & LIVE TANDOOR COUNTERS
         =================================================================== -->
    <!-- ===================================================================
         SECTION 4: LIVE COOKING STATIONS & LIVE TANDOOR COUNTERS
         =================================================================== -->
    <section class="py-16 bg-[#F8F5EF]/50 dark:bg-[#171513]/50 border-t border-b border-[#E7E5E4] dark:border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            <div class="text-center space-y-2 max-w-2xl mx-auto">
                <span class="text-[10px] font-mono uppercase tracking-[0.2em] text-[#8F7239] dark:text-[#C6A15B] font-bold block">
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
                <div class="editorial-card p-6 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-3">
                    <span class="text-2xl">🍢</span>
                    <h4 class="font-serif-luxury text-xl text-[#171513] dark:text-white">Live Charcoal Seekh Bar</h4>
                    <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed">
                        Sizzling chicken and beef seekh kababs grilled directly over hardwood coals with mint chutney.
                    </p>
                </div>

                <!-- Station 2 -->
                <div class="editorial-card p-6 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-3">
                    <span class="text-2xl">🫓</span>
                    <h4 class="font-serif-luxury text-xl text-[#171513] dark:text-white">Clay Tandoor Hot Breads</h4>
                    <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed">
                        Piping hot sesame Roghni Naans, Taftan, and garlic butter kulchas straight from clay ovens.
                    </p>
                </div>

                <!-- Station 3 -->
                <div class="editorial-card p-6 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-3">
                    <span class="text-2xl">🍯</span>
                    <h4 class="font-serif-luxury text-xl text-[#171513] dark:text-white">Crisp Jalebi &amp; Rabri</h4>
                    <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed">
                        Live wok-fried crispy saffron jalebis drenched in pure sugar syrup and topped with chilled creamy rabri.
                    </p>
                </div>

                <!-- Station 4 -->
                <div class="editorial-card p-6 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-3">
                    <span class="text-2xl">☕</span>
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
            <div class="p-6 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-2">
                <span class="text-xs font-mono uppercase font-bold text-[#8F7239] dark:text-[#C6A15B]">100% Certified Halal</span>
                <h4 class="font-serif-luxury text-xl text-[#171513] dark:text-white">Prime Grade Fresh Meat</h4>
                <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed">
                    Sourced exclusively from verified hygienic abattoirs on the morning of each wedding program.
                </p>
            </div>

            <div class="p-6 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-2">
                <span class="text-xs font-mono uppercase font-bold text-[#8F7239] dark:text-[#C6A15B]">Punjab &amp; Sindh Food Authority</span>
                <h4 class="font-serif-luxury text-xl text-[#171513] dark:text-white">Compliant Kitchen Sanitation</h4>
                <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed">
                    Stainless steel preparation tables, industrial water filtration, and temperature-controlled food warming chafers.
                </p>
            </div>

            <div class="p-6 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-2">
                <span class="text-xs font-mono uppercase font-bold text-[#8F7239] dark:text-[#C6A15B]">Zero Food Shortage Guarantee</span>
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
                <span class="text-[10px] font-mono uppercase tracking-widest text-[#C6A15B] block font-bold">
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
                <a href="{{ route('public.contact') }}" class="btn-gold !h-11 !px-6 text-xs font-bold uppercase font-mono">
                    Book Tasting Session &rarr;
                </a>
            </div>
        </div>
    </section>

</div>
