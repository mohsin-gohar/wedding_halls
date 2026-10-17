<x-layouts.public title="EventHub — Transform Your Wedding Dreams Into Timeless Luxury">

    <!-- ===================================================================
         1. CINEMATIC LUXURY HERO SECTION (With Smooth Visual Depth)
         =================================================================== -->
    <section class="relative text-white min-h-[92vh] flex items-center justify-center overflow-hidden border-b border-[#C6A15B]/30">
        <!-- Cinematic Wedding Hall Background Image Slideshow -->
        <div class="hero-slideshow absolute inset-0 z-[-1] overflow-hidden" id="heroSlideshow">
            <!-- Slide 1: Elegant Wedding Venue -->
            <div class="slideshow-slide absolute inset-0 w-full h-full">
                <img src="https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=2000&auto=format&fit=crop" alt="Luxury Wedding Venue" class="w-full h-full object-cover slideshow-image transition-opacity duration-750">
            </div>
            <!-- Slide 2: Grand Wedding Ballroom -->
            <div class="slideshow-slide absolute inset-0 w-full h-full opacity-0">
                <img src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=2000&auto=format&fit=crop" alt="Grand Wedding Ballroom" class="w-full h-full object-cover slideshow-image transition-opacity duration-750">
            </div>
            <!-- Slide 3: Wedding Ceremony Decor -->
            <div class="slideshow-slide absolute inset-0 w-full h-full opacity-0">
                <img src="https://i.pinimg.com/1200x/b6/1d/f9/b61df9ab70e16a13f6a00b8341f0057c.jpg" alt="Wedding Ceremony Decor" class="w-full h-full object-cover slideshow-image transition-opacity duration-750">
            </div>
            <!-- Slide 4: Elegant Banquet Hall -->
            <div class="slideshow-slide absolute inset-0 w-full h-full opacity-0">
                <img src="https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=2000&auto=format&fit=crop" alt="Elegant Banquet Hall" class="w-full h-full object-cover slideshow-image transition-opacity duration-750">
            </div>
            <!-- Slide 5: Grand Wedding Reception -->
            <div class="slideshow-slide absolute inset-0 w-full h-full opacity-0">
                <img src="https://i.pinimg.com/1200x/d6/b6/bd/d6b6bdb32f8029dfc2bbe091a6a260df.jpg" alt="Grand Wedding Reception" class="w-full h-full object-cover slideshow-image transition-opacity duration-750">
            </div>

            <!-- Slideshow Navigation Dots -->
            <div class="absolute bottom-8 left-1/2 -translate-x-1/2 z-20 flex gap-3">
                <button class="slideshow-dot w-2 h-2 rounded-full bg-[#C6A15B] transition-all duration-300" onclick="setSlide(0)" aria-label="Slide 1"></button>
                <button class="slideshow-dot w-2 h-2 rounded-full bg-white/30 hover:bg-[#C6A15B] transition-all duration-300" onclick="setSlide(1)" aria-label="Slide 2"></button>
                <button class="slideshow-dot w-2 h-2 rounded-full bg-white/30 hover:bg-[#C6A15B] transition-all duration-300" onclick="setSlide(2)" aria-label="Slide 3"></button>
                <button class="slideshow-dot w-2 h-2 rounded-full bg-white/30 hover:bg-[#C6A15B] transition-all duration-300" onclick="setSlide(3)" aria-label="Slide 4"></button>
                <button class="slideshow-dot w-2 h-2 rounded-full bg-white/30 hover:bg-[#C6A15B] transition-all duration-300" onclick="setSlide(4)" aria-label="Slide 5"></button>
            </div>

            <!-- Subtle overlay for text readability -->
            <div class="absolute inset-0 bg-black/20"></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">

        <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center space-y-8 flex flex-col items-center">
            
            <!-- Category Tag -->
            <!-- <div class="inline-flex items-center gap-2.5 px-4 py-1.5 border border-[#C6A15B]/50 bg-[#171513]/90 text-[#C6A15B] text-[11px] font-semibold tracking-[0.22em] uppercase reveal-on-scroll">
                <span class="w-1.5 h-1.5 bg-[#C6A15B]"></span>
                <span>Pakistan's Exclusive Wedding Hall Marketplace</span>
            </div> -->

            <!-- Headline: Cinzel / Playfair Editorial -->
            <h1 class="font-serif-luxury text-4xl sm:text-6xl md:text-7xl lg:text-8xl font-normal leading-[1.08] tracking-tight max-w-4xl text-[#F8F5EF] reveal-on-scroll reveal-delay-1">
                Transform Your Wedding Dreams <br class="hidden sm:block">
                Into <span class="italic font-light text-[#C6A15B]">Timeless Luxury.</span>
            </h1>

            <!-- Subtitle -->
            <!-- <p class="text-base sm:text-lg md:text-xl text-stone-300 max-w-2xl mx-auto font-light leading-relaxed reveal-on-scroll reveal-delay-2">
                Discover Pakistan's Most Exclusive Wedding Halls, Marquees, and Event Spaces.
            </p> -->

<!-- Zero-Pill CTAs -->
            <div class="flex flex-wrap items-center justify-center gap-4 pt-2 reveal-on-scroll reveal-delay-3">
                <a href="#search-section" class="btn-gold !h-12 !px-8 text-xs font-bold uppercase tracking-wider shadow-[0_0_24px_rgba(212,175,55,0.35)] hover:shadow-[0_0_30px_rgba(212,175,55,0.5)] transition-all duration-300 relative overflow-hidden group">
                    <span class="relative z-10">Explore Wedding Halls &rarr;</span>
                    <span class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-500"></span>
                </a>
                <a href="#featured-venues" class="btn-outline-gold !h-12 !px-7 text-xs font-bold uppercase tracking-wider !text-[#F8F5EF] !border-[#C6A15B]/60 hover:!text-[#171513] hover:!border-[#C6A15B] hover:!bg-[#C6A15B] transition-all duration-300">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    Watch Venue Tour
                </a>
                @guest
                    <a href="{{ route('register') }}" class="btn-gold !h-12 !px-8 text-xs font-bold uppercase tracking-wider shadow-[0_0_30px_rgba(198,161,91,0.45)] hover:shadow-[0_0_40px_rgba(198,161,91,0.6)] transition-all duration-300 flex items-center gap-2 group">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        <span class="relative z-10">List & Rent Your Wedding Hall &rarr;</span>
                        <span class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-500"></span>
                    </a>
                @endguest
            </div>

            <!-- Trust Metrics Bar -->
            <div class="pt-12 mt-6 border-t border-white/10 grid grid-cols-2 sm:grid-cols-4 gap-8 text-left w-full max-w-3xl reveal-on-scroll reveal-delay-4">
                <div class="border-l-2 border-[#C6A15B] pl-4">
                    <span class="font-cinzel text-2xl font-bold text-white block">100%</span>
                    <span class="text-[10px] uppercase tracking-wider text-stone-400 mt-0.5 block font-medium font-mono">Atomic Shift Lock</span>
                </div>
                <div class="border-l-2 border-[#C6A15B] pl-4">
                    <span class="font-cinzel text-2xl font-bold text-white block">PKR Standard</span>
                    <span class="text-[10px] uppercase tracking-wider text-stone-400 mt-0.5 block font-medium font-mono">Transparent Pricing</span>
                </div>
                <div class="border-l-2 border-[#C6A15B] pl-4">
                    <span class="font-cinzel text-2xl font-bold text-white block">3 Shifts</span>
                    <span class="text-[10px] uppercase tracking-wider text-stone-400 mt-0.5 block font-medium font-mono">Lunch, Dinner, Night</span>
                </div>
                <div class="border-l-2 border-[#C6A15B] pl-4">
                    <span class="font-cinzel text-2xl font-bold text-white block">Multi-Hall</span>
                    <span class="text-[10px] uppercase tracking-wider text-stone-400 mt-0.5 block font-medium font-mono">Dedicated Websites</span>
                </div>
            </div>

        </div>
    </section>

    <!-- ===================================================================
         2. FUNCTIONAL SEARCH BAR (Zero-Pill Luxury Surface)
         =================================================================== -->
    <section class="relative -mt-10 z-20 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8" id="search-section">
        <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-6 sm:p-8 shadow-2xl reveal-on-scroll">
            <div class="text-center mb-6">
                <span class="text-[10px] uppercase tracking-[0.2em] text-[#C6A15B] font-bold block mb-2">Find Your Perfect Venue</span>
                <h3 class="font-serif-luxury text-2xl sm:text-3xl text-[#171513] dark:text-white font-normal">Check Real-Time Availability</h3>
                <p class="text-xs sm:text-sm text-stone-600 dark:text-stone-400 mt-1">Select date, shift, and guest count to see live shift availability across all verified halls.</p>
            </div>
            <form action="{{ route('public.venues') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                <!-- City -->
                <div>
                    <label for="city" class="block text-[11px] font-bold uppercase tracking-[0.1em] text-[#171513] dark:text-[#C6A15B] mb-1.5 font-mono">City</label>
                    <select name="city" id="city" class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm focus:border-[#C6A15B] focus:ring-1 focus:ring-[#C6A15B]/20">
                        <option value="All">All Cities</option>
                        <option value="Lahore" selected>Lahore</option>
                        <option value="Karachi">Karachi</option>
                        <option value="Islamabad">Islamabad</option>
                        <option value="Rawalpindi">Rawalpindi</option>
                        <option value="Faisalabad">Faisalabad</option>
                        <option value="Multan">Multan</option>
                    </select>
                </div>

                <!-- Event Date -->
                <div>
                    <label for="date" class="block text-[11px] font-bold uppercase tracking-[0.1em] text-[#171513] dark:text-[#C6A15B] mb-1.5 font-mono">Event Date</label>
                    <input type="date" name="date" id="date" value="{{ \Carbon\Carbon::tomorrow()->format('Y-m-d') }}" class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm focus:border-[#C6A15B] focus:ring-1 focus:ring-[#C6A15B]/20">
                </div>

                <!-- Shift -->
                <div>
                    <label for="shift" class="block text-[11px] font-bold uppercase tracking-[0.1em] text-[#171513] dark:text-[#C6A15B] mb-1.5 font-mono">Shift</label>
                    <select name="shift" id="shift" class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm focus:border-[#C6A15B] focus:ring-1 focus:ring-[#C6A15B]/20">
                        <option value="ALL">Any Shift</option>
                        <option value="EVENING" selected>Evening Dinner (7-11 PM)</option>
                        <option value="AFTERNOON">Afternoon Lunch (12-4 PM)</option>
                        <option value="NIGHT">Late Night Mehndi (11:30 PM+)</option>
                    </select>
                </div>

                <!-- Guest Count -->
                <div>
                    <label for="guests" class="block text-[11px] font-bold uppercase tracking-[0.1em] text-[#171513] dark:text-[#C6A15B] mb-1.5 font-mono">Guest Count</label>
                    <select name="guests" id="guests" class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm focus:border-[#C6A15B] focus:ring-1 focus:ring-[#C6A15B]/20">
                        <option value="100">100+ Guests</option>
                        <option value="250" selected>250+ Guests</option>
                        <option value="500">500+ Guests</option>
                        <option value="800">800+ Guests</option>
                        <option value="1200">1,200+ Guests</option>
                    </select>
                </div>

                <!-- Submit Button -->
                <div>
                    <button type="submit" class="btn-gold !w-full !h-[42px] text-xs font-bold uppercase tracking-wider shadow-[0_0_25px_rgba(198,161,91,0.4)] hover:shadow-[0_0_35px_rgba(198,161,91,0.6)] transition-all duration-300 flex items-center justify-center gap-2 group relative overflow-hidden">
                        <span class="relative z-10">Search Halls</span>
                        <svg class="w-4 h-4 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        <span class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></span>
                    </button>
                </div>
            </form>
        </div>
    </section>

<!-- ===================================================================
         2b. OUR SERVICES — 4-column dark glassmorphism grid
         =================================================================== -->
    <section class="relative py-20 bg-[#171513] border-b border-[#C6A15B]/20 overflow-hidden">
        <div class="absolute inset-0 bg-palace-pattern opacity-50 pointer-events-none"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="text-center max-w-2xl mx-auto space-y-3 reveal-on-scroll">
                <span class="text-[11px] uppercase tracking-[0.25em] text-[#C6A15B] font-bold block font-mono">
                    Curated Hospitality
                </span>
                <h2 class="font-serif-luxury text-3xl sm:text-5xl text-[#F8F5EF] font-normal">
                    Our Services
                </h2>
                <p class="text-sm text-stone-400 font-light leading-relaxed">
                    End-to-end luxury event solutions — from palatial ballrooms to authentic Mughlai catering and flawless on-day planning.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                {{-- Grand Ballrooms --}}
                <div class="glass-card-lux p-7 space-y-4 reveal-on-scroll reveal-delay-1 group hover:shadow-[0_25px_60px_-15px_rgba(198,161,91,0.45)]">
                    <div class="w-12 h-12 border border-[#C6A15B]/60 flex items-center justify-center text-[#C6A15B] bg-[#171513]/60 group-hover:bg-gradient-to-br group-hover:from-[#C6A15B] group-hover:to-[#8F7239] group-hover:text-[#171513] transition-all duration-300 rounded-sm shadow-[0_0_20px_-8px_rgba(198,161,91,0.4)] group-hover:shadow-[0_0_30px_rgba(198,161,91,0.6)]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
                    </div>
                    <h3 class="font-serif-luxury text-xl text-[#F8F5EF] font-normal group-hover:text-[#C6A15B] transition-colors duration-300">Grand Ballrooms</h3>
                    <p class="text-xs text-stone-400 leading-relaxed font-light">
                        Crystal-chandelier banquet halls with climate control, power backup, and bridal suites for up to 1,500 guests.
                    </p>
                    <a href="{{ route('public.venues') }}" class="text-[11px] uppercase tracking-wider text-[#C6A15B] hover:text-[#E8DCBE] transition flex items-center gap-1 group-hover:gap-2 duration-300">
                        Explore Halls <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>

                {{-- Marquees & Lawns --}}
                <div class="glass-card-lux p-7 space-y-4 reveal-on-scroll reveal-delay-2 group">
                    <div class="w-12 h-12 border border-[#C6A15B]/60 flex items-center justify-center text-[#C6A15B] bg-[#171513]/60 group-hover:bg-[#C6A15B] group-hover:text-[#171513] transition-all duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l9 6H3l9-6zM5 9v11m14-11v11M3 20h18M9 20v-5h6v5"/></svg>
                    </div>
                    <h3 class="font-serif-luxury text-xl text-[#F8F5EF] font-normal">Marquees & Lawns</h3>
                    <p class="text-xs text-stone-400 leading-relaxed font-light">
                        Expansive open-air marquees and manicured lawns, ideal for Mehndi pavilions and grand Barat receptions.
                    </p>
                    <a href="{{ route('public.venues') }}" class="text-[11px] uppercase tracking-wider text-[#C6A15B] hover:text-[#E8DCBE] transition flex items-center gap-1">
                        View Lawns <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>

                {{-- Catering Menus --}}
                <div class="glass-card-lux p-7 space-y-4 reveal-on-scroll reveal-delay-3 group">
                    <div class="w-12 h-12 border border-[#C6A15B]/60 flex items-center justify-center text-[#C6A15B] bg-[#171513]/60 group-hover:bg-[#C6A15B] group-hover:text-[#171513] transition-all duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3v8a2 2 0 002 2v8M9 3v8m8-8c0 3-2 3-2 6v4h2v5M15 9c0 2 2 2 2 4"/></svg>
                    </div>
                    <h3 class="font-serif-luxury text-xl text-[#F8F5EF] font-normal">Catering Menus</h3>
                    <p class="text-xs text-stone-400 leading-relaxed font-light">
                        Authentic Mughlai, BBQ, and continental feasts curated by master bawarchis with live counters and silver service.
                    </p>
                    <a href="{{ route('public.packages') }}" class="text-[11px] uppercase tracking-wider text-[#C6A15B] hover:text-[#E8DCBE] transition flex items-center gap-1">
                        View Menus <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>

                {{-- Event Planning --}}
                <div class="glass-card-lux p-7 space-y-4 reveal-on-scroll reveal-delay-4 group">
                    <div class="w-12 h-12 border border-[#C6A15B]/60 flex items-center justify-center text-[#C6A15B] bg-[#171513]/60 group-hover:bg-[#C6A15B] group-hover:text-[#171513] transition-all duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    </div>
                    <h3 class="font-serif-luxury text-xl text-[#F8F5EF] font-normal">Event Planning</h3>
                    <p class="text-xs text-stone-400 leading-relaxed font-light">
                        Full-service décor, stage design, lighting, and dedicated coordinators to orchestrate your perfect celebration.
                    </p>
                    <a href="{{ route('public.services') }}" class="text-[11px] uppercase tracking-wider text-[#C6A15B] hover:text-[#E8DCBE] transition flex items-center gap-1">
                        Plan Event <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================================================================
         3. FEATURED VENUES SHOWCASE — 3-column opulent image grid (Real DB)
         =================================================================== -->
    <section class="py-24 bg-[#171513] border-b border-[#C6A15B]/20" id="featured-venues">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            
            <div class="text-center max-w-2xl mx-auto space-y-3 reveal-on-scroll">
                <span class="text-[11px] uppercase tracking-[0.25em] text-[#C6A15B] font-bold block font-mono">
                    Verified Luxury Inventory
                </span>
                <h2 class="font-serif-luxury text-3xl sm:text-5xl text-[#F8F5EF] font-normal">
                    Featured Venues
                </h2>
                <p class="text-sm text-stone-400 font-light leading-relaxed">
                    Each banquet operates its own live booking calendar and itemized quotation engine — zero double-bookings, guaranteed.
                </p>
            </div>

            <!-- Venues Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @forelse($halls as $hall)
                    @php
                        $coverImg = !empty($hall->cover_image) 
                            ? $hall->cover_image 
                            : ($hall->images[0] ?? 'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=1000&auto=format&fit=crop');
                        $hasVideo = !empty($hall->videos) && count($hall->videos) > 0;
                    @endphp
                    <div class="group bg-[#171513] border border-[#C6A15B]/40 hover:border-[#C6A15B] transition-all duration-500 overflow-hidden reveal-on-scroll reveal-delay-{{ $loop->iteration }} hover:shadow-[0_20px_40px_-10px_rgba(0,0,0,0.5)] hover:shadow-[0_0_30px_rgba(198,161,91,0.15)]">
                        <div>
                            <!-- Opulent 4:3 Image Banner with gold frame -->
                            <div class="relative aspect-[4/3] overflow-hidden bg-stone-900">
                                <img src="{{ $coverImg }}" 
                                     alt="{{ $hall->name }}" 
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 opacity-90">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-black/30"></div>

                                <!-- Location Badge -->
                                <span class="absolute top-4 left-4 px-3 py-1 bg-[#171513]/95 text-white text-[10px] font-bold uppercase tracking-wider border border-[#C6A15B]/30 backdrop-blur-sm">
                                    {{ $hall->venue->city ?? 'Lahore' }} • {{ $hall->venue->area ?? 'Gulberg' }}
                                </span>

                                <!-- Capacity Badge -->
                                <span class="absolute bottom-4 left-4 px-2.5 py-1 bg-black/85 text-stone-200 text-xs font-medium border border-white/10 backdrop-blur-sm">
                                    {{ $hall->min_capacity }} - {{ $hall->max_capacity }} Guests
                                </span>

                                @if($hasVideo)
                                    <span class="absolute top-4 right-4 px-2.5 py-1 bg-[#C6A15B] text-[#171513] text-[10px] font-bold uppercase tracking-wider flex items-center gap-1 shadow-lg font-mono">
                                        <svg class="w-3 h-3 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                        4K Tour
                                    </span>
                                @endif
                                
                                <!-- View Details Overlay -->
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                    <a href="{{ route('public.venue.details', $hall->slug) }}" class="btn-gold !h-11 !px-6 text-xs font-bold uppercase tracking-wider shadow-xl transform -translate-y-4 group-hover:translate-y-0 transition-transform duration-300">
                                        View Details &rarr;
                                    </a>
                                </div>
                            </div>

                            <!-- Content Block -->
                            <div class="p-6 space-y-3">
                                <h3 class="font-serif-luxury text-2xl text-[#F8F5EF] font-medium leading-snug group-hover:text-[#C6A15B] transition-colors duration-300">
                                    <a href="{{ route('public.venue.details', $hall->slug) }}">
                                        {{ $hall->name }}
                                    </a>
                                </h3>
                                <p class="text-xs text-stone-400 font-normal leading-relaxed line-clamp-2">
                                    {{ $hall->description ?? 'Spectacular 5-star wedding banquet ballroom with crystal chandeliers, climate control, and power backup.' }}
                                </p>
                                
                                <!-- Amenities Strip -->
                                <div class="pt-3 border-t border-white/10 flex flex-wrap gap-2 text-[10px] text-stone-300 font-semibold uppercase tracking-wider font-mono">
                                    @if($hall->has_air_conditioning)
                                        <span class="px-2 py-0.5 bg-[#171513] border border-white/10">Central AC</span>
                                    @endif
                                    @if($hall->has_generator_backup)
                                        <span class="px-2 py-0.5 bg-[#171513] border border-white/10">100% Gen Backup</span>
                                    @endif
                                    @if($hall->has_bridal_room)
                                        <span class="px-2 py-0.5 bg-[#171513] border border-white/10">Bridal Lounge</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Card Action Footer -->
                        <div class="px-6 pb-6 flex items-center justify-between mt-4">
                            <div>
                                <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 block font-mono">STARTING FROM</span>
                                <strong class="font-cinzel text-lg text-[#C6A15B]">PKR {{ number_format($hall->base_rent) }}</strong>
                            </div>
                            <a href="{{ route('public.venue.details', $hall->slug) }}" class="btn-gold !h-9 !px-5 text-[11px] font-bold uppercase tracking-wider shadow-md hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300">
                                View Details &rarr;
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 text-center py-12 bg-[#171513] border border-white/10">
                        <p class="text-stone-500 text-sm">No venues currently listed in this category.</p>
                    </div>
                @endforelse
            </div>

            @if($halls->count() > 0)
                <div class="text-center pt-2 reveal-on-scroll">
                    <a href="{{ route('public.venues') }}" class="btn-gold !h-11 !px-8 text-xs font-bold uppercase tracking-wider shadow-[0_0_25px_rgba(198,161,91,0.4)] hover:shadow-[0_0_35px_rgba(198,161,91,0.55)] transition-all duration-300 group">
                        <span class="relative z-10">View All Venues &rarr;</span>
                        <span class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-500"></span>
                    </a>
                </div>
            @endif

        </div>
    </section>

    <!-- ===================================================================
         3b. STATISTICS — Large gold numbers with ivory labels
         =================================================================== -->
    <section class="py-20 bg-[#171513] border-b border-[#C6A15B]/20">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-10 text-center">
                <div class="space-y-2 reveal-on-scroll reveal-delay-1">
                    <span class="font-cinzel text-5xl sm:text-6xl font-bold text-[#C6A15B] block">100+</span>
                    <span class="text-xs sm:text-sm uppercase tracking-[0.2em] text-[#F8F5EF] font-medium font-mono">Verified Venues</span>
                </div>
                <div class="space-y-2 reveal-on-scroll reveal-delay-2 sm:border-x sm:border-white/10 sm:px-10">
                    <span class="font-cinzel text-5xl sm:text-6xl font-bold text-[#C6A15B] block">5000+</span>
                    <span class="text-xs sm:text-sm uppercase tracking-[0.2em] text-[#F8F5EF] font-medium font-mono">Successful Events</span>
                </div>
                <div class="space-y-2 reveal-on-scroll reveal-delay-3">
                    <span class="font-cinzel text-5xl sm:text-6xl font-bold text-[#C6A15B] block">99%</span>
                    <span class="text-xs sm:text-sm uppercase tracking-[0.2em] text-[#F8F5EF] font-medium font-mono">Customer Satisfaction</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================================================================
         4. CINEMATIC PARALLAX BREAK 1: ROYAL HERITAGE & SCROLLING VISUAL
         =================================================================== -->
    <section class="relative py-32 bg-fixed bg-cover bg-center overflow-hidden border-y border-[#C6A15B]/30"
             style="background-image: url('https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=2000&auto=format&fit=crop');">
        <!-- Deep Obsidian Translucent Filter -->
        <div class="absolute inset-0 bg-[#171513]/80 backdrop-blur-[1px]"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-[#171513] via-transparent to-[#171513]"></div>

        <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-6">
            <span class="text-xs uppercase tracking-[0.25em] text-[#C6A15B] block reveal-on-scroll">
                A Legacy of Majestic Hospitality
            </span>
            <h2 class="font-serif-luxury text-3xl sm:text-5xl md:text-6xl text-white font-normal leading-tight reveal-on-scroll reveal-delay-1">
                "Every wedding marks the beginning of an empire. We craft the stage worthy of your crown."
            </h2>
            <p class="text-sm sm:text-base text-stone-300 max-w-2xl mx-auto font-light leading-relaxed reveal-on-scroll reveal-delay-2">
                From candlelit Mehndi pavilions in Gulberg to palatial Barat ballrooms hosting 1,500 VIP guests along Islamabad Club Road.
            </p>
            <div class="pt-4 flex items-center justify-center gap-4 reveal-on-scroll reveal-delay-3">
                <a href="{{ route('public.venues') }}" class="btn-gold !h-11 !px-8 text-xs font-bold uppercase tracking-wider shadow-[0_0_25px_rgba(198,161,91,0.4)] hover:shadow-[0_0_35px_rgba(198,161,91,0.55)] transition-all duration-300 group">
                    <span class="relative z-10">Explore All Wedding Halls &rarr;</span>
                    <span class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-500"></span>
                </a>
            </div>
        </div>
    </section>

    <!-- ===================================================================
         5. POPULAR DESTINATIONS & CATEGORIES
         =================================================================== -->
    <section class="py-24 bg-[#F8F5EF] dark:bg-[#171513] border-b border-[#E7E5E4] dark:border-white/10 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            
            <div class="text-center max-w-2xl mx-auto space-y-2 reveal-on-scroll">
                <span class="text-[11px] uppercase tracking-[0.2em] text-[#8F7239] dark:text-[#C6A15B] font-bold block font-mono">
                    Curated Across Pakistan
                </span>
                <h2 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                    Premier Wedding Destinations
                </h2>
                <p class="text-xs sm:text-sm text-stone-600 dark:text-stone-300 leading-relaxed font-normal">
                    From Gulberg and DHA Lahore to Clifton Karachi and the scenic foothills of Islamabad.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Lahore -->
                <a href="{{ route('public.venues', ['city' => 'Lahore']) }}" class="group relative aspect-[4/5] overflow-hidden bg-stone-900 border border-[#E7E5E4] dark:border-white/10 reveal-on-scroll reveal-delay-1">
                    <img src="https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=800&auto=format&fit=crop" 
                         alt="Lahore Wedding Venues" 
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 opacity-75">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#171513] via-transparent to-transparent"></div>
                    <div class="absolute bottom-6 left-6 right-6 text-white space-y-1">
                        <span class="text-[10px] uppercase font-bold tracking-[0.15em] text-[#C6A15B] font-mono">Punjab Heritage</span>
                        <h3 class="font-serif-luxury text-2xl font-normal">Lahore</h3>
                        <p class="text-xs text-stone-300 font-mono">Gulberg III • Cantt • DHA • Raiwind</p>
                    </div>
                </a>

                <!-- Islamabad -->
                <a href="{{ route('public.venues', ['city' => 'Islamabad']) }}" class="group relative aspect-[4/5] overflow-hidden bg-stone-900 border border-[#E7E5E4] dark:border-white/10 reveal-on-scroll reveal-delay-2">
                    <img src="https://images.unsplash.com/photo-1465495976277-4387d4b0b4c6?q=80&w=800&auto=format&fit=crop" 
                         alt="Islamabad Wedding Venues" 
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 opacity-75">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#171513] via-transparent to-transparent"></div>
                    <div class="absolute bottom-6 left-6 right-6 text-white space-y-1">
                        <span class="text-[10px] uppercase font-bold tracking-[0.15em] text-[#C6A15B] font-mono">Capital Elegance</span>
                        <h3 class="font-serif-luxury text-2xl font-normal">Islamabad</h3>
                        <p class="text-xs text-stone-300 font-mono">Club Road • E-11 • Chak Shahzad</p>
                    </div>
                </a>

                <!-- Karachi -->
                <a href="{{ route('public.venues', ['city' => 'Karachi']) }}" class="group relative aspect-[4/5] overflow-hidden bg-stone-900 border border-[#E7E5E4] dark:border-white/10 reveal-on-scroll reveal-delay-3">
                    <img src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=800&auto=format&fit=crop" 
                         alt="Karachi Wedding Venues" 
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 opacity-75">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#171513] via-transparent to-transparent"></div>
                    <div class="absolute bottom-6 left-6 right-6 text-white space-y-1">
                        <span class="text-[10px] uppercase font-bold tracking-[0.15em] text-[#C6A15B] font-mono">Coastal Splendor</span>
                        <h3 class="font-serif-luxury text-2xl font-normal">Karachi</h3>
                        <p class="text-xs text-stone-300 font-mono">Clifton • Karsaz • DHA Phase VIII</p>
                    </div>
                </a>

                <!-- Faisalabad / Multan -->
                <a href="{{ route('public.venues', ['city' => 'Faisalabad']) }}" class="group relative aspect-[4/5] overflow-hidden bg-stone-900 border border-[#E7E5E4] dark:border-white/10 reveal-on-scroll reveal-delay-4">
                    <img src="https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?q=80&w=800&auto=format&fit=crop" 
                         alt="Faisalabad Wedding Venues" 
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 opacity-75">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#171513] via-transparent to-transparent"></div>
                    <div class="absolute bottom-6 left-6 right-6 text-white space-y-1">
                        <span class="text-[10px] uppercase font-bold tracking-[0.15em] text-[#C6A15B] font-mono">Grand Marquees</span>
                        <h3 class="font-serif-luxury text-2xl font-normal">Faisalabad</h3>
                        <p class="text-xs text-stone-300 font-mono">Canal Road • Jaranwala Road</p>
                    </div>
                </a>
            </div>

        </div>
    </section>

    <!-- ===================================================================
         6. WEDDING PACKAGES & AUTHENTIC PAKISTANI CATERING HIGHLIGHTS
         =================================================================== -->
    <section class="py-24 bg-[#F8F5EF] dark:bg-[#171513] transition-colors" id="packages">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-14">
            
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-[#E7E5E4] dark:border-white/10 pb-6 reveal-on-scroll">
                <div class="space-y-2">
                    <span class="text-[11px] uppercase tracking-[0.2em] text-[#8F7239] dark:text-[#C6A15B] font-bold block font-mono">
                        Five-Star Mughlai Culinary Arts
                    </span>
                    <h2 class="font-serif-luxury text-3xl sm:text-5xl text-[#171513] dark:text-white font-normal">
                        Wedding Feast Packages
                    </h2>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('public.packages') }}" class="btn-gold !h-9 text-xs">
                        View Complete Menus &rarr;
                    </a>
                </div>
            </div>

            <!-- Packages Display -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @forelse($packages as $pkg)
                    <div class="group bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 hover:border-[#C6A15B] dark:hover:border-[#C6A15B] transition-all duration-500 overflow-hidden reveal-on-scroll reveal-delay-{{ $loop->iteration }} hover:shadow-[0_20px_40px_-10px_rgba(0,0,0,0.1)] dark:hover:shadow-[0_20px_40px_-10px_rgba(198,161,91,0.2)] p-8 flex flex-col justify-between space-y-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between border-b border-[#E7E5E4] dark:border-white/10 pb-4">
                                <span class="text-[10px] font-bold uppercase tracking-[0.15em] text-[#8F7239] dark:text-[#C6A15B] font-mono">PAKISTANI BANQUET</span>
                                <span class="text-xs font-semibold text-stone-500 dark:text-stone-400 font-mono">Min {{ $pkg->min_guests }} Guests</span>
                            </div>
                            
                            <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-medium group-hover:text-[#C6A15B] transition-colors duration-300">{{ $pkg->name }}</h3>
                            <p class="text-xs text-stone-600 dark:text-stone-300 font-normal leading-relaxed">
                                {{ $pkg->description }}
                            </p>

                            <!-- Menu Items -->
                            @if(is_array($pkg->menu_items_json))
                                <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10 space-y-2">
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($pkg->menu_items_json as $item)
                                            <span class="px-2 py-0.5 bg-[#F8F5EF] dark:bg-[#171513] text-[#171513] dark:text-stone-200 border border-[#E7E5E4] dark:border-white/10 text-[11px] font-sans group-hover:border-[#C6A15B] dark:group-hover:border-[#C6A15B] transition-colors duration-300">
                                                {{ is_array($item) ? implode(', ', $item) : $item }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="pt-6 border-t border-[#E7E5E4] dark:border-white/10 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 block font-mono">PER HEAD RATE</span>
                                <strong class="font-cinzel text-xl text-[#171513] dark:text-[#C6A15B]">PKR {{ number_format($pkg->per_head_price) }}</strong>
                            </div>
                            <button onclick="Livewire.dispatch('openWalkInModal', { package_id: '{{ $pkg->id }}' })" class="btn-obsidian !h-9 !px-4 text-xs font-semibold hover:border-[#C6A15B] hover:text-[#C6A15B] transition-all duration-300 flex items-center gap-1.5">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                Reserve
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 text-center py-12 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10">
                        <p class="text-stone-500 text-sm">Packages will appear shortly.</p>
                    </div>
                @endforelse
            </div>

        </div>
    </section>

    <!-- ===================================================================
         7. INTERACTIVE WEDDING BUDGET & BAWARCHI ESTIMATOR (Client Feature)
         =================================================================== -->
    <section class="py-20 bg-[#171513] border-y border-[#C6A15B]/30 text-white" 
             x-data="{
                 guests: 350,
                 tier: 2200,
                 hallRent: 150000,
                 get cateringTotal() { return this.guests * this.tier; },
                 get grandTotal() { return this.cateringTotal + this.hallRent; },
                 get meatKg() { return Math.round(this.guests * 0.22); },
                 get riceKg() { return Math.round(this.guests * 0.16); },
                 get oilLiters() { return Math.round(this.guests * 0.05); }
             }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="text-center max-w-3xl mx-auto space-y-3 reveal-on-scroll">
                <span class="text-xs uppercase tracking-[0.2em] text-[#C6A15B] block">
                    Interactive Planning Assistant
                </span>
                <h2 class="font-serif-luxury text-3xl sm:text-5xl text-white font-normal">
                    Estimate Your Wedding Feast &amp; Hall Budget
                </h2>
                <p class="text-xs sm:text-sm text-stone-400 font-light">
                    Adjust your expected guest count and catering package to see estimated PKR costs and raw Bawarchi kitchen provisions.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center bg-[#171513] border border-white/10 p-6 sm:p-10 reveal-on-scroll">
                <!-- Left: Interactive Controls (7 cols) -->
                <div class="lg:col-span-7 space-y-6">
                    <div>
                        <div class="flex justify-between items-center mb-2 text-xs">
                            <span class="text-stone-300 uppercase tracking-wider">Expected Guest Count:</span>
                            <span class="text-[#C6A15B] font-bold text-base" x-text="guests + ' Guests'"></span>
                        </div>
                        <input type="range" min="100" max="1200" step="25" x-model.number="guests" 
                               class="w-full accent-[#C6A15B] cursor-pointer">
                        <div class="flex justify-between text-[10px] text-stone-500 mt-1">
                            <span>100 Guests</span>
                            <span>500 Guests</span>
                            <span>1,200+ Guests</span>
                        </div>
                    </div>

                    <!-- Package Tier Selection -->
                    <div>
                        <label class="block text-xs uppercase tracking-wider text-stone-300 mb-2">
                            Select Feast Menu Standard:
                        </label>
                        <div class="grid grid-cols-3 gap-3">
                            <button type="button" @click="tier = 1850; hallRent = 120000"
                                    :class="tier === 1850 ? 'border-[#C6A15B] bg-[#C6A15B]/15 text-[#C6A15B] font-bold' : 'border-white/10 text-stone-400 hover:border-white/30'"
                                    class="p-3 border text-center transition text-xs">
                                <div>Silver Feast</div>
                                <div class="text-[10px] opacity-75 mt-0.5">PKR 1,850/Head</div>
                            </button>

                            <button type="button" @click="tier = 2200; hallRent = 150000"
                                    :class="tier === 2200 ? 'border-[#C6A15B] bg-[#C6A15B]/15 text-[#C6A15B] font-bold' : 'border-white/10 text-stone-400 hover:border-white/30'"
                                    class="p-3 border text-center transition text-xs">
                                <div>Gold Mughlai</div>
                                <div class="text-[10px] opacity-75 mt-0.5">PKR 2,200/Head</div>
                            </button>

                            <button type="button" @click="tier = 2850; hallRent = 200000"
                                    :class="tier === 2850 ? 'border-[#C6A15B] bg-[#C6A15B]/15 text-[#C6A15B] font-bold' : 'border-white/10 text-stone-400 hover:border-white/30'"
                                    class="p-3 border text-center transition text-xs">
                                <div>Royal Imperial</div>
                                <div class="text-[10px] opacity-75 mt-0.5">PKR 2,850/Head</div>
                            </button>
                        </div>
                    </div>

                    <!-- Raw Kitchen Requirements Output -->
                    <div class="pt-4 border-t border-white/10 grid grid-cols-3 gap-4 text-center font-mono">
                        <div class="bg-[#171513] p-3 border border-white/10">
                            <span class="text-[10px] uppercase text-stone-400 block">Raw Meat</span>
                            <strong class="text-sm text-white" x-text="meatKg + ' kg'"></strong>
                        </div>
                        <div class="bg-[#171513] p-3 border border-white/10">
                            <span class="text-[10px] uppercase text-stone-400 block">Basmati Rice</span>
                            <strong class="text-sm text-white" x-text="riceKg + ' kg'"></strong>
                        </div>
                        <div class="bg-[#171513] p-3 border border-white/10">
                            <span class="text-[10px] uppercase text-stone-400 block">Pure Ghee / Oil</span>
                            <strong class="text-sm text-white" x-text="oilLiters + ' L'"></strong>
                        </div>
                    </div>
                </div>

                <!-- Right: Calculated Estimate Card (5 cols) -->
                <div class="lg:col-span-5 bg-[#171513] border border-[#C6A15B]/40 p-6 sm:p-8 space-y-6">
                    <span class="text-[10px] uppercase tracking-widest text-[#C6A15B] block">Authoritative Estimate</span>
                    
                    <div class="space-y-3 text-xs border-b border-white/10 pb-4">
                        <div class="flex justify-between text-stone-300">
                            <span>Hall Shift Base Rent:</span>
                            <span class="text-white font-bold" x-text="'PKR ' + Number(hallRent).toLocaleString()"></span>
                        </div>
                        <div class="flex justify-between text-stone-300">
                            <span x-text="'Catering (' + guests + ' × PKR ' + tier + '):'"></span>
                            <span class="text-white font-bold" x-text="'PKR ' + Number(cateringTotal).toLocaleString()"></span>
                        </div>
                    </div>

                    <div>
                        <span class="text-[10px] uppercase tracking-wider text-stone-400 block font-mono">Estimated Grand Total</span>
                        <div class="font-serif-luxury text-3xl sm:text-4xl text-[#C6A15B] font-semibold mt-1"
                             x-text="'PKR ' + Number(grandTotal).toLocaleString()">
                        </div>
                        <span class="text-[10px] text-stone-500 block mt-1">Indicative estimate • Subject to applicable provincial sales taxes.</span>
                    </div>

                    <a :href="'{{ route('public.venues') }}?guests=' + guests" class="btn-gold !w-full !h-11 text-xs font-bold uppercase tracking-wider block text-center">
                        View Matching Wedding Halls &rarr;
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================================================================
         8. EDITORIAL GALLERY HIGHLIGHTS (Stages, Chandeliers & Floristry)
         =================================================================== -->
    <section class="py-24 bg-[#171513] text-white border-t border-[#C6A15B]/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-white/10 pb-6 reveal-on-scroll">
                <div class="space-y-2">
                    <span class="text-[11px] uppercase tracking-[0.2em] text-[#C6A15B] font-bold block font-mono">
                        Visual Excellence
                    </span>
                    <h2 class="font-serif-luxury text-3xl sm:text-5xl text-white font-normal">
                        Bespoke Banqueting Aesthetics
                    </h2>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('public.gallery') }}" class="btn-outline-gold !h-9 text-xs text-white border-\[#C6A15B\]/60">
                        View Full Gallery &rarr;
                    </a>
                </div>
            </div>

            <!-- Gallery Mosaic -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="relative aspect-[4/3] overflow-hidden border border-white/10 group bg-stone-900">
                    <img src="https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=1000&auto=format&fit=crop" 
                         alt="Crystal Chandeliers" 
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                    <div class="absolute bottom-4 left-4 text-white">
                        <span class="text-[10px] uppercase font-bold tracking-wider text-[#C6A15B] font-mono">Lighting Architecture</span>
                        <h4 class="font-serif-luxury text-lg font-normal">Austrian Crystal Chandeliers</h4>
                    </div>
                </div>

                <div class="relative aspect-[4/3] overflow-hidden border border-white/10 group bg-stone-900">
                    <img src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=1000&auto=format&fit=crop" 
                         alt="Royal Stage Decor" 
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                    <div class="absolute bottom-4 left-4 text-white">
                        <span class="text-[10px] uppercase font-bold tracking-wider text-[#C6A15B] font-mono">Stage Styling</span>
                        <h4 class="font-serif-luxury text-lg font-normal">Royal Floral Stage Thrones</h4>
                    </div>
                </div>

                <div class="relative aspect-[4/3] overflow-hidden border border-white/10 group bg-stone-900">
                    <img src="https://images.unsplash.com/photo-1465495976277-4387d4b0b4c6?q=80&w=1000&auto=format&fit=crop" 
                         alt="Imperial Dining Setup" 
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                    <div class="absolute bottom-4 left-4 text-white">
                        <span class="text-[10px] uppercase font-bold tracking-wider text-[#C6A15B] font-mono">Imperial Banqueting</span>
                        <h4 class="font-serif-luxury text-lg font-normal">VIP Banquet Seating &amp; Silverware</h4>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- ===================================================================
         9. CINEMATIC SCROLLING SAAS SECTION FOR VENUE OWNERS (Rent Admin)
         =================================================================== -->
    <section class="relative py-28 bg-fixed bg-cover bg-center overflow-hidden border-t border-[#C6A15B]/30"
             style="background-image: url('https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=2000&auto=format&fit=crop');">
        <!-- Deep Obsidian Backdrop -->
        <div class="absolute inset-0 bg-[#171513]/88 backdrop-blur-[2px]"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
                <div class="lg:col-span-8 space-y-6 reveal-on-scroll">
                    <span class="text-xs uppercase tracking-[0.25em] text-[#C6A15B] block font-bold">
                        EventHub Multi-Tenant SaaS
                    </span>
                    <h2 class="font-serif-luxury text-3xl sm:text-5xl font-normal leading-tight text-[#F8F5EF]">
                        Are You a Wedding Hall or Marquee Owner? <br>
                        <span class="italic text-[#C6A15B]">Rent EventHub for Your Banquet.</span>
                    </h2>
                    <p class="text-sm sm:text-base text-stone-300 font-light leading-relaxed max-w-2xl">
                        Power your entire banquet operations on EventHub. Every hall gets its own dedicated public website link to share with clients, a private Rent Admin dashboard, zero double-booking shift locking, chef kitchen KOT order slips, and generator fuel expense tracking.
                    </p>

                    <!-- Features Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 text-xs text-stone-200">
                        <div class="flex items-center gap-2">
                            <span class="text-[#C6A15B] font-bold">✓</span>
                            <span>Dedicated Public Website for Each Hall</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[#C6A15B] font-bold">✓</span>
                            <span>Isolated Private Rent Admin Dashboard</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[#C6A15B] font-bold">✓</span>
                            <span>Atomic Concurrency (Zero Overlapping Dates)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[#C6A15B] font-bold">✓</span>
                            <span>A4 Legal Contracts &amp; WhatsApp Bot</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-4 pt-4">
                          <a href="{{ route('register') }}" class="btn-gold !h-12 !px-8 text-xs font-bold uppercase tracking-wider shadow-[0_0_25px_rgba(198,161,91,0.4)] hover:shadow-[0_0_35px_rgba(198,161,91,0.55)] transition-all duration-300 group">
                              <span class="relative z-10">Register Your Hall on EventHub &rarr;</span>
                              <span class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-500"></span>
                          </a>
                        <a href="{{ route('public.about') }}" class="btn-outline-gold !h-12 !px-7 text-xs font-semibold text-white border-white/20">
                            Explore SaaS Architecture
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-4 bg-[#171513] border border-[#C6A15B]/50 p-6 sm:p-8 space-y-4 text-center">
                    <span class="text-[10px] uppercase tracking-widest text-[#C6A15B] block">Hall Owner Portal</span>
                    <h3 class="font-serif-luxury text-2xl text-white font-normal">Already Listed?</h3>
                    <p class="text-xs text-stone-400">
                        Sign in to your Rent Admin dashboard to manage your hall website, calendar shifts, and payments.
                    </p>
                      <a href="{{ route('login') }}" class="btn-obsidian !w-full !h-10 text-xs font-bold uppercase block hover:border-[#C6A15B] hover:text-[#C6A15B] transition-all duration-300">
                          Sign In as Rent Admin &rarr;
                      </a>
                </div>
            </div>
        </div>
    </section>

    <script>
        let currentSlide = 0;
        const totalSlides = 5;
        let slideInterval;

        function showSlide(index) {
            const slides = document.querySelectorAll('.slideshow-slide');
            const dots = document.querySelectorAll('.slideshow-dot');
            slides.forEach((slide, i) => {
                slide.classList.toggle('opacity-0', i !== index);
                slide.classList.toggle('opacity-100', i === index);
            });
            dots.forEach((dot, i) => {
                dot.classList.toggle('bg-[#C6A15B]', i === index);
                dot.classList.toggle('bg-white/30', i !== index);
            });
            currentSlide = index;
        }

        function nextSlide() { showSlide((currentSlide + 1) % totalSlides); resetInterval(); }
        function setSlide(index) { showSlide(index); resetInterval(); }
        function startInterval() { slideInterval = setInterval(() => { nextSlide(); }, 3500); }
        function resetInterval() { clearInterval(slideInterval); startInterval(); }

        document.addEventListener('DOMContentLoaded', () => {
            startInterval();
            const slideshow = document.querySelector('.hero-slideshow');
            if (slideshow) {
                slideshow.addEventListener('mouseenter', () => clearInterval(slideInterval));
                slideshow.addEventListener('mouseleave', () => startInterval());
            }
        });
    </script>
</x-layouts.public>
