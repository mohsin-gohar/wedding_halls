<div class="bg-[#F8F5EF] dark:bg-[#171513] text-[#171513] dark:text-[#F8F5EF] min-h-screen">

    <!-- ===================================================================
         SECTION 1: HERO & ARCHITECTURAL VISUALS HEADLINE
         =================================================================== -->
    <section class="parallax-window relative bg-[#171513] text-white py-20 border-b border-[#C6A15B]/30 overflow-hidden">
        <div class="parallax-bg opacity-30" data-parallax="0.25"
             style="background-image: url('https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=2000&auto=format&fit=crop');">
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#171513] via-[#171513]/75 to-[#171513]/90"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 text-center">
            <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full border border-[#C6A15B]/40 bg-[#171513]/90 text-[#D4AF67] text-[11px] font-medium tracking-[0.2em] uppercase font-sans">
                <span class="w-1.5 h-1.5 rounded-full bg-[#D4AF67]"></span>
                <span>Editorial Photography &amp; 4K Virtual Tours</span>
            </div>

            <h1 class="font-serif-luxury text-4xl sm:text-6xl text-white font-normal max-w-4xl mx-auto leading-tight" style="text-wrap: balance;">
                Photo &amp; Video Showcase
            </h1>

            <p class="text-sm sm:text-base text-stone-300 max-w-2xl mx-auto font-light leading-relaxed" style="text-wrap: pretty;">
                Explore real photographs and 4K virtual tours of grand crystal ballrooms, royal Mehndi stages, Austrian chandeliers, and VIP dining setups across Pakistan.
            </p>
        </div>
    </section>


    <!-- ===================================================================
         SECTION 2: MINIMALIST FILTER TABS
         =================================================================== -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6 relative z-20">
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-3 sm:p-4 rounded-2xl shadow-sm flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" wire:click="setCategory('ALL')" 
                        class="px-4 py-2 text-xs font-medium uppercase tracking-wider font-sans rounded-xl transition-all cursor-pointer {{ $activeCategory === 'ALL' ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513] shadow-sm' : 'bg-[#F8F5EF] dark:bg-white/5 text-stone-700 dark:text-stone-300 border border-[#E7E5E4] dark:border-white/10 hover:bg-[#F0EDE5] dark:hover:bg-white/10' }}">
                    All Visuals
                </button>
                <button type="button" wire:click="setCategory('STAGES')" 
                        class="px-4 py-2 text-xs font-medium uppercase tracking-wider font-sans rounded-xl transition-all cursor-pointer {{ $activeCategory === 'STAGES' ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513] shadow-sm' : 'bg-[#F8F5EF] dark:bg-white/5 text-stone-700 dark:text-stone-300 border border-[#E7E5E4] dark:border-white/10 hover:bg-[#F0EDE5] dark:hover:bg-white/10' }}">
                    Wedding Stages
                </button>
                <button type="button" wire:click="setCategory('LIGHTING')" 
                        class="px-4 py-2 text-xs font-medium uppercase tracking-wider font-sans rounded-xl transition-all cursor-pointer {{ $activeCategory === 'LIGHTING' ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513] shadow-sm' : 'bg-[#F8F5EF] dark:bg-white/5 text-stone-700 dark:text-stone-300 border border-[#E7E5E4] dark:border-white/10 hover:bg-[#F0EDE5] dark:hover:bg-white/10' }}">
                    Chandeliers &amp; Lighting
                </button>
                <button type="button" wire:click="setCategory('BANQUET')" 
                        class="px-4 py-2 text-xs font-medium uppercase tracking-wider font-sans rounded-xl transition-all cursor-pointer {{ $activeCategory === 'BANQUET' ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513] shadow-sm' : 'bg-[#F8F5EF] dark:bg-white/5 text-stone-700 dark:text-stone-300 border border-[#E7E5E4] dark:border-white/10 hover:bg-[#F0EDE5] dark:hover:bg-white/10' }}">
                    Banquet Dining
                </button>
                <button type="button" wire:click="setCategory('VIDEOS')" 
                        class="px-4 py-2 text-xs font-medium uppercase tracking-wider font-sans rounded-xl transition-all cursor-pointer {{ $activeCategory === 'VIDEOS' ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513] shadow-sm' : 'bg-[#F8F5EF] dark:bg-white/5 text-stone-700 dark:text-stone-300 border border-[#E7E5E4] dark:border-white/10 hover:bg-[#F0EDE5] dark:hover:bg-white/10' }}">
                    4K Virtual Tours
                </button>
            </div>

            <span class="text-xs font-sans text-stone-400">High-Resolution Visuals</span>
        </div>
    </section>


    <!-- ===================================================================
         SECTION 3: 4K VIDEO WALKTHROUGHS
         =================================================================== -->
    @if($activeCategory === 'ALL' || $activeCategory === 'VIDEOS')
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 space-y-8">
            <div class="flex items-center gap-2 border-b border-[#E7E5E4] dark:border-white/10 pb-3">
                <span class="w-2.5 h-2.5 bg-[#171513] rounded-full animate-pulse"></span>
                <h3 class="font-cinzel text-xl font-bold uppercase text-[#171513] dark:text-white">4K Virtual Video Tours</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                @foreach($halls as $h)
                    @if(!empty($h->videos) && count($h->videos) > 0)
                        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-5 space-y-4 rounded-2xl shadow-sm hover:shadow-md transition">
                            <div class="aspect-video bg-black overflow-hidden rounded-xl border border-[#E7E5E4] dark:border-white/10">
                                <iframe src="{{ $h->videos[0] }}" class="w-full h-full" allowfullscreen></iframe>
                            </div>
                            <div class="flex items-center justify-between pt-1">
                                <div>
                                    <h4 class="font-serif-luxury text-xl font-medium text-[#171513] dark:text-white">{{ $h->name }}</h4>
                                    <span class="text-xs text-stone-500 dark:text-stone-400 font-sans">{{ $h->venue->city ?? 'Lahore' }} • Capacity: {{ $h->min_capacity }}-{{ $h->max_capacity }}</span>
                                </div>
                                <a href="{{ route('public.venue.details', $h->slug) }}" class="btn-obsidian dark:bg-white/10 dark:text-white dark:border-white/20 dark:hover:bg-[#C6A15B] dark:hover:text-[#171513] !h-8 !px-4 text-xs font-sans rounded-lg">
                                    Explore Hall &rarr;
                                </a>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </section>
    @endif


    <!-- ===================================================================
         SECTION 4: CURATED ARCHITECTURAL PHOTOGRAPHY
         =================================================================== -->
    @if($activeCategory !== 'VIDEOS')
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 space-y-8">
            <div class="flex items-center gap-2 border-b border-[#E7E5E4] dark:border-white/10 pb-3">
                <span class="w-2.5 h-2.5 rounded-full bg-[#C6A15B]"></span>
                <h3 class="font-cinzel text-xl font-bold uppercase text-[#171513] dark:text-white">Curated Architectural Photography</h3>
            </div>

            @php
                $editorialPhotos = [
                    ['url' => 'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=1200&auto=format&fit=crop', 'title' => 'Austrian Crystal Ballroom Chandelier', 'cat' => 'LIGHTING', 'location' => 'Gulberg III, Lahore'],
                    ['url' => 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=1200&auto=format&fit=crop', 'title' => 'Royal Barat Grand Floral Stage', 'cat' => 'STAGES', 'location' => 'Cantt, Lahore'],
                    ['url' => 'https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?q=80&w=1200&auto=format&fit=crop', 'title' => 'Open-Sky Botanical Lawn Walkway', 'cat' => 'BANQUET', 'location' => 'DHA Phase V, Lahore'],
                    ['url' => 'https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=1200&auto=format&fit=crop', 'title' => 'Imperial VIP Banquet Round Tables', 'cat' => 'BANQUET', 'location' => 'Clifton, Karachi'],
                    ['url' => 'https://images.unsplash.com/photo-1465495976277-4387d4b0b4c6?q=80&w=1200&auto=format&fit=crop', 'title' => 'Festive Yellow Marigold Mehndi Canopy', 'cat' => 'STAGES', 'location' => 'Club Road, Islamabad'],
                    ['url' => 'https://images.unsplash.com/photo-1520854221256-17451cc331bf?q=80&w=1200&auto=format&fit=crop', 'title' => 'Fairy Light Ceiling & Chandelier Array', 'cat' => 'LIGHTING', 'location' => 'Gulberg, Lahore'],
                ];
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($editorialPhotos as $photo)
                    @if($activeCategory === 'ALL' || $activeCategory === $photo['cat'])
                        <div class="editorial-card group overflow-hidden bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 rounded-2xl shadow-sm hover:shadow-md transition">
                            <div class="aspect-[4/3] overflow-hidden relative bg-stone-900 rounded-xl m-2">
                                <img src="{{ $photo['url'] }}" alt="{{ $photo['title'] }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-90"></div>
                                <div class="absolute bottom-4 left-4 right-4 text-white">
                                    <span class="text-[10px] uppercase font-semibold tracking-wider text-[#D4AF67] font-sans px-2.5 py-0.5 rounded-full bg-black/40 backdrop-blur-sm border border-[#C6A15B]/30 inline-block mb-1">{{ $photo['cat'] }}</span>
                                    <h4 class="font-serif-luxury text-lg font-medium text-white">{{ $photo['title'] }}</h4>
                                    <span class="text-xs text-stone-300 font-light font-sans">{{ $photo['location'] }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </section>
    @endif


    <!-- ===================================================================
         SECTION 5: ATMOSPHERE & CELEBRATION MOMENTS
         =================================================================== -->
    <section class="py-16 bg-[#F8F5EF]/60 dark:bg-[#171513]/60 border-t border-b border-[#E7E5E4] dark:border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            <div class="text-center space-y-2 max-w-2xl mx-auto">
                <span class="text-[10px] font-sans uppercase tracking-[0.2em] text-[#8F7239] dark:text-[#D4AF67] font-semibold block">
                    Signature Moments
                </span>
                <h2 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal" style="text-wrap: balance;">
                    Atmosphere &amp; Ambience
                </h2>
                <p class="text-xs text-stone-600 dark:text-stone-300 font-sans" style="text-wrap: pretty;">
                    Every function format brings its unique acoustic, floral, and culinary atmosphere.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="p-6 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 rounded-2xl shadow-sm space-y-3">
                    <span class="text-xs font-sans font-semibold text-[#8F7239] dark:text-[#D4AF67] uppercase tracking-wider">Red Rose Opulence</span>
                    <h4 class="font-serif-luxury text-2xl text-[#171513] dark:text-white">Baraat Grandeur</h4>
                    <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed font-sans">
                        Red velvet carpet walkways, royal crystal candelabras, and spotlighted bride-groom throned staging.
                    </p>
                </div>

                <div class="p-6 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 rounded-2xl shadow-sm space-y-3">
                    <span class="text-xs font-sans font-semibold text-[#8F7239] dark:text-[#D4AF67] uppercase tracking-wider">Marigold &amp; Fairy Lights</span>
                    <h4 class="font-serif-luxury text-2xl text-[#171513] dark:text-white">Mehndi Festive Nights</h4>
                    <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed font-sans">
                        Yellow and orange marigold flower drapes, cushioned low dholak seating, and energetic acoustic line-arrays.
                    </p>
                </div>

                <div class="p-6 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 rounded-2xl shadow-sm space-y-3">
                    <span class="text-xs font-sans font-semibold text-[#8F7239] dark:text-[#D4AF67] uppercase tracking-wider">Silverware &amp; Pastels</span>
                    <h4 class="font-serif-luxury text-2xl text-[#171513] dark:text-white">Valima Elegance</h4>
                    <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed font-sans">
                        Soft ivory and champagne pastels, silver cutlery dining arrangements, and live acoustic violin background music.
                    </p>
                </div>
            </div>
        </div>
    </section>


    <!-- ===================================================================
         SECTION 6: BOOK VENUE CTA
         =================================================================== -->
    <section class="py-16 bg-[#171513] text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="space-y-2">
                <span class="text-[10px] font-sans uppercase tracking-widest text-[#D4AF67] block font-semibold">
                    Inspired by Our Architecture?
                </span>
                <h3 class="font-serif-luxury text-2xl sm:text-3xl font-normal text-white" style="text-wrap: balance;">
                    Book an On-Site Private Venue Tour
                </h3>
                <p class="text-xs text-stone-300 font-light max-w-xl font-sans" style="text-wrap: pretty;">
                    Experience the majestic chandeliers, bridal suite staging, and heavy AC cooling in person before reserving your date.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-4 shrink-0">
                <button type="button" onclick="Livewire.dispatch('openWalkInModal')" class="btn-gold !h-11 !px-6 text-xs font-semibold uppercase tracking-wider font-sans rounded-xl transition-all active:scale-98">
                    Reserve Your Date
                </button>
                <a href="{{ route('public.venues') }}" class="btn-outline-gold !h-11 !px-6 text-xs font-semibold uppercase tracking-wider font-sans rounded-xl transition-all active:scale-98">
                    Browse All Venues &rarr;
                </a>
            </div>
        </div>
    </section>

</div>
