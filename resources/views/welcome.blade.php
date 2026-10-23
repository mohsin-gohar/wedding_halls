<x-layouts.public title="EventHub — Transform Your Wedding Dreams Into Timeless Luxury">

    <!-- ===================================================================
         1. CINEMATIC LUXURY HERO SECTION (With Smooth Visual Depth)
         =================================================================== -->
    <section class="relative text-white min-h-[92vh] flex items-center justify-center overflow-hidden border-b border-[#C6A15B]/30 bg-[#171513]">
        <!-- Cinematic Wedding Hall Background Image Slideshow -->
        <div class="hero-slideshow absolute inset-0 z-0 overflow-hidden" id="heroSlideshow">
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
            <div class="absolute inset-0 bg-black/20 z-1"></div>
        </div>

        <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-24 text-center space-y-7 flex flex-col items-center">
            
            <!-- Prestige Eyebrow Badge -->
            <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full border border-[#C6A15B]/30 bg-[#171513]/85 text-[#F2DE9A] text-[11px] font-medium tracking-[0.18em] uppercase backdrop-blur-md">
                <span class="w-1.5 h-1.5 rounded-full bg-[#C6A15B]"></span>
                <span>Pakistan's Premier Wedding Venue Network</span>
            </div>

            <!-- Headline: Playfair Editorial Luxury -->
            <h1 class="font-serif-luxury text-4xl sm:text-6xl md:text-7xl lg:text-8xl font-normal leading-[1.08] tracking-tight max-w-4xl text-[#F8F5EF]">
                Transform Your Wedding Dreams <br class="hidden sm:block">
                Into <span class="italic font-light text-[#C6A15B]">Timeless Luxury.</span>
            </h1>

            <!-- Subtitle -->
            <p class="text-sm sm:text-base md:text-lg text-stone-300/90 max-w-2xl mx-auto font-light leading-relaxed">
                Discover, tour, and reserve verified 5-star wedding ballrooms, open-air marquees, and bespoke catering services across Lahore, Karachi, and Islamabad with guaranteed single-host shift exclusivity.
            </p>

            <!-- Action CTAs (Handcrafted Tactile Luxury) -->
            <div class="flex flex-wrap items-center justify-center gap-4 pt-2">
                <a href="#search-section" class="btn-gold !h-12 !px-8 text-xs font-semibold uppercase tracking-wider">
                    <span>Explore Wedding Halls &rarr;</span>
                </a>
                <a href="#featured-venues" class="btn-outline-gold !h-12 !px-7 text-xs font-semibold uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#C6A15B]" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    <span>View Featured Venues</span>
                </a>
                @guest
                    <a href="{{ route('register') }}" class="btn-obsidian !h-12 !px-7 text-xs font-semibold uppercase tracking-wider flex items-center gap-2 border-[#C6A15B]/40 hover:border-[#C6A15B]">
                        <svg class="w-4 h-4 text-[#C6A15B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        <span>List Your Venue &rarr;</span>
                    </a>
                @endguest
            </div>

            <!-- Trust Metrics Bar -->
            <div class="pt-10 mt-6 border-t border-white/10 grid grid-cols-2 sm:grid-cols-4 gap-6 sm:gap-8 text-left w-full max-w-3xl">
                <div class="border-l-2 border-[#C6A15B] pl-4">
                    <span class="font-cinzel text-2xl font-bold text-white block">100%</span>
                    <span class="text-[11px] uppercase tracking-wider text-stone-300 mt-1 block font-medium font-sans">Single-Host Exclusivity</span>
                </div>
                <div class="border-l-2 border-[#C6A15B] pl-4">
                    <span class="font-cinzel text-2xl font-bold text-white block">PKR Standard</span>
                    <span class="text-[11px] uppercase tracking-wider text-stone-300 mt-1 block font-medium font-sans">Direct Transparent Rates</span>
                </div>
                <div class="border-l-2 border-[#C6A15B] pl-4">
                    <span class="font-cinzel text-2xl font-bold text-white block">3 Shifts</span>
                    <span class="text-[11px] uppercase tracking-wider text-stone-300 mt-1 block font-medium font-sans">Lunch, Dinner &amp; Night</span>
                </div>
                <div class="border-l-2 border-[#C6A15B] pl-4">
                    <span class="font-cinzel text-2xl font-bold text-white block">Direct Portals</span>
                    <span class="text-[11px] uppercase tracking-wider text-stone-300 mt-1 block font-medium font-sans">Verified Hall Showcases</span>
                </div>
            </div>

        </div>
    </section>

    <!-- ===================================================================
         2. FUNCTIONAL SEARCH BAR (Bespoke Concierge Search Console)
         =================================================================== -->
    <section class="relative -mt-10 z-20 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8" id="search-section">
        <div class="bg-white dark:bg-[#1C1917] border border-[#C6A15B]/30 rounded-2xl p-6 sm:p-8 shadow-[0_12px_36px_-8px_rgba(23,21,19,0.18)] dark:shadow-[0_20px_50px_rgba(0,0,0,0.7)]">
            <div class="text-center mb-6">
                <span class="text-[11px] uppercase tracking-[0.2em] text-[#8F7239] dark:text-[#C6A15B] font-semibold block mb-1.5 font-sans">Find Your Perfect Venue</span>
                <h3 class="font-serif-luxury text-2xl sm:text-3xl text-[#171513] dark:text-white font-normal">Check Real-Time Availability</h3>
                <p class="text-xs sm:text-sm text-stone-600 dark:text-stone-400 mt-1">Select date, shift, and guest count to view live availability across all verified halls.</p>
            </div>
            <form action="{{ route('public.venues') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                <!-- City -->
                <div>
                    <label for="city" class="block text-[11px] font-semibold uppercase tracking-[0.06em] text-stone-700 dark:text-stone-300 mb-1.5 font-sans">City</label>
                    <select name="city" id="city" class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/15 text-sm focus:border-[#C6A15B] focus:ring-2 focus:ring-[#C6A15B]/25 rounded-lg">
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
                    <label for="date" class="block text-[11px] font-semibold uppercase tracking-[0.06em] text-stone-700 dark:text-stone-300 mb-1.5 font-sans">Event Date</label>
                    <input type="date" name="date" id="date" value="{{ \Carbon\Carbon::tomorrow()->format('Y-m-d') }}" class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/15 text-sm focus:border-[#C6A15B] focus:ring-2 focus:ring-[#C6A15B]/25 rounded-lg">
                </div>

                <!-- Shift -->
                <div>
                    <label for="shift" class="block text-[11px] font-semibold uppercase tracking-[0.06em] text-stone-700 dark:text-stone-300 mb-1.5 font-sans">Shift</label>
                    <select name="shift" id="shift" class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/15 text-sm focus:border-[#C6A15B] focus:ring-2 focus:ring-[#C6A15B]/25 rounded-lg">
                        <option value="ALL">Any Shift</option>
                        <option value="EVENING" selected>Evening Dinner (7–11 PM)</option>
                        <option value="AFTERNOON">Afternoon Lunch (12–4 PM)</option>
                        <option value="NIGHT">Late Night Mehndi (11:30 PM+)</option>
                    </select>
                </div>

                <!-- Guest Count -->
                <div>
                    <label for="guests" class="block text-[11px] font-semibold uppercase tracking-[0.06em] text-stone-700 dark:text-stone-300 mb-1.5 font-sans">Guest Count</label>
                    <select name="guests" id="guests" class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/15 text-sm focus:border-[#C6A15B] focus:ring-2 focus:ring-[#C6A15B]/25 rounded-lg">
                        <option value="100">100+ Guests</option>
                        <option value="250" selected>250+ Guests</option>
                        <option value="500">500+ Guests</option>
                        <option value="800">800+ Guests</option>
                        <option value="1200">1,200+ Guests</option>
                    </select>
                </div>

                <!-- Submit Button -->
                <div>
                    <button type="submit" class="btn-gold !w-full !h-[44px] text-xs font-semibold uppercase tracking-wider flex items-center justify-center gap-2">
                        <span>Search Halls</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- ===================================================================
         2b. OUR SERVICES — 4-column hospitality grid
         =================================================================== -->
    <section class="relative py-20 bg-[#171513] border-b border-[#C6A15B]/20 overflow-hidden">
        <div class="absolute inset-0 bg-palace-pattern opacity-50 pointer-events-none"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="text-center max-w-2xl mx-auto space-y-3 reveal-on-scroll">
                <span class="text-[11px] uppercase tracking-[0.2em] text-[#C6A15B] font-semibold block font-sans">
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
                <div class="glass-card-lux rounded-2xl p-7 space-y-4 reveal-on-scroll reveal-delay-1 group">
                    <div class="w-11 h-11 rounded-xl border border-[#C6A15B]/35 flex items-center justify-center text-[#C6A15B] bg-[#C6A15B]/10 group-hover:bg-[#C6A15B] group-hover:text-[#171513] transition-colors duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
                    </div>
                    <h3 class="font-serif-luxury text-xl text-[#F8F5EF] font-normal group-hover:text-[#E8DCBE] transition-colors">Grand Ballrooms</h3>
                    <p class="text-xs text-stone-400 leading-relaxed font-light">
                        Crystal-chandelier banquet halls with climate control, power backup, and bridal suites for up to 1,500 guests.
                    </p>
                    <a href="{{ route('public.venues') }}" class="text-[11px] font-semibold uppercase tracking-wider text-[#C6A15B] hover:text-[#E8DCBE] transition inline-flex items-center gap-1.5">
                        Explore Halls <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>

                {{-- Marquees & Lawns --}}
                <div class="glass-card-lux rounded-2xl p-7 space-y-4 reveal-on-scroll reveal-delay-2 group">
                    <div class="w-11 h-11 rounded-xl border border-[#C6A15B]/35 flex items-center justify-center text-[#C6A15B] bg-[#C6A15B]/10 group-hover:bg-[#C6A15B] group-hover:text-[#171513] transition-colors duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l9 6H3l9-6zM5 9v11m14-11v11M3 20h18M9 20v-5h6v5"/></svg>
                    </div>
                    <h3 class="font-serif-luxury text-xl text-[#F8F5EF] font-normal group-hover:text-[#E8DCBE] transition-colors">Marquees &amp; Lawns</h3>
                    <p class="text-xs text-stone-400 leading-relaxed font-light">
                        Expansive open-air marquees and manicured lawns, ideal for Mehndi pavilions and grand Barat receptions.
                    </p>
                    <a href="{{ route('public.venues') }}" class="text-[11px] font-semibold uppercase tracking-wider text-[#C6A15B] hover:text-[#E8DCBE] transition inline-flex items-center gap-1.5">
                        View Lawns <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>

                {{-- Catering Menus --}}
                <div class="glass-card-lux rounded-2xl p-7 space-y-4 reveal-on-scroll reveal-delay-3 group">
                    <div class="w-11 h-11 rounded-xl border border-[#C6A15B]/35 flex items-center justify-center text-[#C6A15B] bg-[#C6A15B]/10 group-hover:bg-[#C6A15B] group-hover:text-[#171513] transition-colors duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3v8a2 2 0 002 2v8M9 3v8m8-8c0 3-2 3-2 6v4h2v5M15 9c0 2 2 2 2 4"/></svg>
                    </div>
                    <h3 class="font-serif-luxury text-xl text-[#F8F5EF] font-normal group-hover:text-[#E8DCBE] transition-colors">Catering Menus</h3>
                    <p class="text-xs text-stone-400 leading-relaxed font-light">
                        Authentic Mughlai, BBQ, and continental feasts curated by master bawarchis with live counters and silver service.
                    </p>
                    <a href="{{ route('public.packages') }}" class="text-[11px] font-semibold uppercase tracking-wider text-[#C6A15B] hover:text-[#E8DCBE] transition inline-flex items-center gap-1.5">
                        View Menus <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>

                {{-- Event Planning --}}
                <div class="glass-card-lux rounded-2xl p-7 space-y-4 reveal-on-scroll reveal-delay-4 group">
                    <div class="w-11 h-11 rounded-xl border border-[#C6A15B]/35 flex items-center justify-center text-[#C6A15B] bg-[#C6A15B]/10 group-hover:bg-[#C6A15B] group-hover:text-[#171513] transition-colors duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    </div>
                    <h3 class="font-serif-luxury text-xl text-[#F8F5EF] font-normal group-hover:text-[#E8DCBE] transition-colors">Event Planning</h3>
                    <p class="text-xs text-stone-400 leading-relaxed font-light">
                        Full-service décor, stage design, lighting, and dedicated coordinators to orchestrate your perfect celebration.
                    </p>
                    <a href="{{ route('public.services') }}" class="text-[11px] font-semibold uppercase tracking-wider text-[#C6A15B] hover:text-[#E8DCBE] transition inline-flex items-center gap-1.5">
                        Plan Event <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
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
                <span class="text-[11px] uppercase tracking-[0.2em] text-[#C6A15B] font-semibold block font-sans">
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
                    <div class="group bg-[#1C1917] border border-[#C6A15B]/25 hover:border-[#C6A15B]/55 rounded-2xl transition-all duration-300 overflow-hidden hover:shadow-[0_12px_32px_-8px_rgba(0,0,0,0.6)] hover:-translate-y-0.5 flex flex-col justify-between">
                        <div>
                            <!-- Opulent 4:3 Image Banner with subtle frame -->
                            <div class="relative aspect-[4/3] overflow-hidden bg-stone-900">
                                <img src="{{ $coverImg }}" 
                                     alt="{{ $hall->name }}" 
                                     class="w-full h-full object-cover group-hover:scale-103 transition-transform duration-700 opacity-90">
                                <div class="absolute inset-0 bg-gradient-to-t from-[#1C1917] via-transparent to-black/30"></div>

                                <!-- Location Badge -->
                                <span class="absolute top-4 left-4 px-3 py-1 rounded-full bg-black/75 text-stone-200 text-[11px] font-medium border border-white/15 backdrop-blur-md font-sans">
                                    {{ $hall->venue->city ?? 'Lahore' }} • {{ $hall->venue->area ?? 'Gulberg' }}
                                </span>

                                <!-- Capacity Badge -->
                                <span class="absolute bottom-4 left-4 px-3 py-1 rounded-full bg-black/75 text-stone-200 text-[11px] font-medium border border-white/15 backdrop-blur-md font-sans">
                                    {{ $hall->min_capacity }}–{{ $hall->max_capacity }} Guests
                                </span>

                                @if($hasVideo)
                                    <span class="absolute top-4 right-4 px-2.5 py-1 rounded-full bg-[#C6A15B] text-[#171513] text-[10px] font-bold uppercase tracking-wider flex items-center gap-1 shadow-md font-sans">
                                        <svg class="w-3 h-3 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                        4K Tour
                                    </span>
                                @endif
                                
                                <!-- View Details Overlay -->
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                    <a href="{{ route('public.venue.details', $hall->slug) }}" class="btn-gold !h-10 !px-6 text-xs font-semibold uppercase tracking-wider shadow-lg">
                                        View Details &rarr;
                                    </a>
                                </div>
                            </div>

                            <!-- Content Block -->
                            <div class="p-6 space-y-3">
                                <h3 class="font-serif-luxury text-2xl text-[#F8F5EF] font-medium leading-snug group-hover:text-[#E8DCBE] transition-colors duration-200">
                                    <a href="{{ route('public.venue.details', $hall->slug) }}">
                                        {{ $hall->name }}
                                    </a>
                                </h3>
                                <p class="text-xs text-stone-400 font-normal leading-relaxed line-clamp-2">
                                    {{ $hall->description ?? 'Spectacular 5-star wedding banquet ballroom with crystal chandeliers, climate control, and power backup.' }}
                                </p>
                                
                                <!-- Amenities Strip -->
                                <div class="pt-3 border-t border-white/10 flex flex-wrap gap-2 text-[11px] text-stone-300 font-medium font-sans">
                                    @if($hall->has_air_conditioning)
                                        <span class="px-2.5 py-0.5 rounded-md bg-white/5 border border-white/10">Central AC</span>
                                    @endif
                                    @if($hall->has_generator_backup)
                                        <span class="px-2.5 py-0.5 rounded-md bg-white/5 border border-white/10">100% Gen Backup</span>
                                    @endif
                                    @if($hall->has_bridal_room)
                                        <span class="px-2.5 py-0.5 rounded-md bg-white/5 border border-white/10">Bridal Lounge</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Card Action Footer -->
                        <div class="px-6 pb-6 pt-2 flex items-center justify-between border-t border-white/5">
                            <div>
                                <span class="text-[10px] uppercase font-semibold tracking-wider text-stone-400 block font-sans">Starting Shift Rate</span>
                                <strong class="font-cinzel text-lg text-[#C6A15B]">PKR {{ number_format($hall->base_rent) }}</strong>
                            </div>
                            <a href="{{ route('public.venue.details', $hall->slug) }}" class="btn-gold !h-9 !px-4 text-[11px] font-semibold uppercase tracking-wider rounded-lg">
                                Reserve &rarr;
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 text-center py-12 bg-[#171513] border border-white/10 rounded-xl">
                        <p class="text-stone-400 text-sm">No venues currently listed in this category.</p>
                    </div>
                @endforelse
            </div>

            @if($halls->count() > 0)
                <div class="text-center pt-2">
                    <a href="{{ route('public.venues') }}" class="btn-gold !h-11 !px-8 text-xs font-bold uppercase tracking-wider">
                        <span>View All Certified Venues &rarr;</span>
                    </a>
                </div>
            @endif

        </div>
    </section>

    <!-- ===================================================================
         3b. STATISTICS — Large gold numbers with ivory labels
         =================================================================== -->
    <section class="py-16 sm:py-20 bg-[#171513] border-b border-[#C6A15B]/20">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 sm:gap-10 text-center">
                <div class="space-y-1.5">
                    <span class="font-cinzel text-4xl sm:text-6xl font-bold text-[#C6A15B] block">100+</span>
                    <span class="text-xs sm:text-sm uppercase tracking-[0.16em] text-stone-300 font-medium font-sans">Verified Luxury Venues</span>
                </div>
                <div class="space-y-1.5 sm:border-x sm:border-white/10 sm:px-8">
                    <span class="font-cinzel text-4xl sm:text-6xl font-bold text-[#C6A15B] block">5,000+</span>
                    <span class="text-xs sm:text-sm uppercase tracking-[0.16em] text-stone-300 font-medium font-sans">Celebrations Hosted</span>
                </div>
                <div class="space-y-1.5">
                    <span class="font-cinzel text-4xl sm:text-6xl font-bold text-[#C6A15B] block">99.4%</span>
                    <span class="text-xs sm:text-sm uppercase tracking-[0.16em] text-stone-300 font-medium font-sans">Client Satisfaction</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================================================================
         4. CINEMATIC PARALLAX BREAK 1: ROYAL HERITAGE & SCROLLING VISUAL
         =================================================================== -->
    <section class="relative py-28 sm:py-32 bg-fixed bg-cover bg-center overflow-hidden border-y border-[#C6A15B]/30"
             style="background-image: url('https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=2000&auto=format&fit=crop');">
        <!-- Deep Obsidian Translucent Filter -->
        <div class="absolute inset-0 bg-[#171513]/85 backdrop-blur-[1px]"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-[#171513] via-transparent to-[#171513]"></div>

        <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-6">
            <span class="text-[11px] uppercase tracking-[0.2em] text-[#C6A15B] font-semibold block font-sans">
                A Legacy of Majestic Hospitality
            </span>
            <h2 class="font-serif-luxury text-3xl sm:text-5xl md:text-6xl text-white font-normal leading-tight">
                "Where timeless traditions meet impeccable hospitality — every celebration crafted with grace."
            </h2>
            <p class="text-sm sm:text-base text-stone-300/90 max-w-2xl mx-auto font-light leading-relaxed">
                From candlelit Mehndi pavilions in Gulberg to palatial Barat ballrooms hosting 1,500 VIP guests along Islamabad Club Road.
            </p>
            <div class="pt-2 flex items-center justify-center gap-4">
                <a href="{{ route('public.venues') }}" class="btn-gold !h-11 !px-8 text-xs font-semibold uppercase tracking-wider">
                    <span>Explore All Wedding Halls &rarr;</span>
                </a>
            </div>
        </div>
    </section>

    <!-- ===================================================================
         5. POPULAR DESTINATIONS & CATEGORIES
         =================================================================== -->
    <section class="py-20 sm:py-24 bg-[#F8F5EF] dark:bg-[#171513] border-b border-[#E7E5E4] dark:border-white/10 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            
            <div class="text-center max-w-2xl mx-auto space-y-2">
                <span class="text-[11px] uppercase tracking-[0.2em] text-[#8F7239] dark:text-[#C6A15B] font-semibold block font-sans">
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
                <a href="{{ route('public.venues', ['city' => 'Lahore']) }}" class="group relative aspect-[4/5] rounded-2xl overflow-hidden bg-stone-900 border border-stone-200/70 dark:border-white/10 shadow-sm hover:shadow-lg transition-all duration-300">
                    <img src="https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=800&auto=format&fit=crop" 
                         alt="Lahore Wedding Venues" 
                         class="w-full h-full object-cover group-hover:scale-103 transition-transform duration-700 opacity-75">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#171513] via-black/20 to-transparent"></div>
                    <div class="absolute bottom-6 left-6 right-6 text-white space-y-1">
                        <span class="text-[10px] uppercase font-semibold tracking-[0.14em] text-[#C6A15B] font-sans">Punjab Heritage</span>
                        <h3 class="font-serif-luxury text-2xl font-normal">Lahore</h3>
                        <p class="text-xs text-stone-300 font-sans">Gulberg III • Cantt • DHA • Raiwind</p>
                    </div>
                </a>

                <!-- Islamabad -->
                <a href="{{ route('public.venues', ['city' => 'Islamabad']) }}" class="group relative aspect-[4/5] rounded-2xl overflow-hidden bg-stone-900 border border-stone-200/70 dark:border-white/10 shadow-sm hover:shadow-lg transition-all duration-300">
                    <img src="https://images.unsplash.com/photo-1465495976277-4387d4b0b4c6?q=80&w=800&auto=format&fit=crop" 
                         alt="Islamabad Wedding Venues" 
                         class="w-full h-full object-cover group-hover:scale-103 transition-transform duration-700 opacity-75">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#171513] via-black/20 to-transparent"></div>
                    <div class="absolute bottom-6 left-6 right-6 text-white space-y-1">
                        <span class="text-[10px] uppercase font-semibold tracking-[0.14em] text-[#C6A15B] font-sans">Capital Elegance</span>
                        <h3 class="font-serif-luxury text-2xl font-normal">Islamabad</h3>
                        <p class="text-xs text-stone-300 font-sans">Club Road • E-11 • Chak Shahzad</p>
                    </div>
                </a>

                <!-- Karachi -->
                <a href="{{ route('public.venues', ['city' => 'Karachi']) }}" class="group relative aspect-[4/5] rounded-2xl overflow-hidden bg-stone-900 border border-stone-200/70 dark:border-white/10 shadow-sm hover:shadow-lg transition-all duration-300">
                    <img src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=800&auto=format&fit=crop" 
                         alt="Karachi Wedding Venues" 
                         class="w-full h-full object-cover group-hover:scale-103 transition-transform duration-700 opacity-75">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#171513] via-black/20 to-transparent"></div>
                    <div class="absolute bottom-6 left-6 right-6 text-white space-y-1">
                        <span class="text-[10px] uppercase font-semibold tracking-[0.14em] text-[#C6A15B] font-sans">Coastal Splendor</span>
                        <h3 class="font-serif-luxury text-2xl font-normal">Karachi</h3>
                        <p class="text-xs text-stone-300 font-sans">Clifton • Karsaz • DHA Phase VIII</p>
                    </div>
                </a>

                <!-- Faisalabad / Multan -->
                <a href="{{ route('public.venues', ['city' => 'Faisalabad']) }}" class="group relative aspect-[4/5] rounded-2xl overflow-hidden bg-stone-900 border border-stone-200/70 dark:border-white/10 shadow-sm hover:shadow-lg transition-all duration-300">
                    <img src="https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?q=80&w=800&auto=format&fit=crop" 
                         alt="Faisalabad Wedding Venues" 
                         class="w-full h-full object-cover group-hover:scale-103 transition-transform duration-700 opacity-75">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#171513] via-black/20 to-transparent"></div>
                    <div class="absolute bottom-6 left-6 right-6 text-white space-y-1">
                        <span class="text-[10px] uppercase font-semibold tracking-[0.14em] text-[#C6A15B] font-sans">Grand Marquees</span>
                        <h3 class="font-serif-luxury text-2xl font-normal">Faisalabad</h3>
                        <p class="text-xs text-stone-300 font-sans">Canal Road • Jaranwala Road</p>
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
                    <span class="text-[11px] uppercase tracking-[0.2em] text-[#8F7239] dark:text-[#C6A15B] font-semibold block font-sans">
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
                    <div class="group bg-white dark:bg-[#1C1917] border border-[#E7E5E4] dark:border-white/15 hover:border-[#C6A15B]/60 dark:hover:border-[#C6A15B]/60 rounded-2xl transition-all duration-300 overflow-hidden reveal-on-scroll reveal-delay-{{ $loop->iteration }} shadow-xs hover:shadow-[0_12px_28px_-8px_rgba(23,21,19,0.08)] dark:hover:shadow-[0_16px_32px_-8px_rgba(0,0,0,0.6)] p-8 flex flex-col justify-between space-y-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between border-b border-[#E7E5E4] dark:border-white/10 pb-4">
                                <span class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#8F7239] dark:text-[#C6A15B] font-sans">Pakistani Banquet</span>
                                <span class="text-xs font-medium text-stone-500 dark:text-stone-400 font-sans">Min {{ $pkg->min_guests }} Guests</span>
                            </div>
                            
                            <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-medium group-hover:text-[#8F7239] dark:group-hover:text-[#E8DCBE] transition-colors">{{ $pkg->name }}</h3>
                            <p class="text-xs text-stone-600 dark:text-stone-300 font-normal leading-relaxed">
                                {{ $pkg->description }}
                            </p>

                            <!-- Menu Items -->
                            @if(is_array($pkg->menu_items_json))
                                <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10 space-y-2">
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($pkg->menu_items_json as $item)
                                            <span class="px-2.5 py-1 rounded-md bg-[#F8F5EF] dark:bg-[#171513] text-[#171513] dark:text-stone-200 border border-[#E7E5E4] dark:border-white/10 text-[11px] font-sans">
                                                {{ is_array($item) ? implode(', ', $item) : $item }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="pt-6 border-t border-[#E7E5E4] dark:border-white/10 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] uppercase font-semibold tracking-wider text-stone-500 dark:text-stone-400 block font-sans">Per Head Rate</span>
                                <strong class="font-cinzel text-xl text-[#171513] dark:text-[#C6A15B]">PKR {{ number_format($pkg->per_head_price) }}</strong>
                            </div>
                            <button onclick="Livewire.dispatch('openWalkInModal', { package_id: '{{ $pkg->id }}' })" class="btn-obsidian !h-9 !px-4 text-xs font-semibold hover:border-[#C6A15B] hover:text-[#E8DCBE] transition-all flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                Reserve
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 text-center py-12 bg-white dark:bg-[#1C1917] rounded-2xl border border-[#E7E5E4] dark:border-white/10">
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
                <span class="text-[11px] font-semibold uppercase tracking-[0.2em] text-[#C6A15B] block font-sans">
                    Interactive Planning Assistant
                </span>
                <h2 class="font-serif-luxury text-3xl sm:text-5xl text-white font-normal">
                    Estimate Your Wedding Feast &amp; Hall Budget
                </h2>
                <p class="text-xs sm:text-sm text-stone-400 font-light">
                    Adjust your expected guest count and catering package to see estimated PKR costs and raw Bawarchi kitchen provisions.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center bg-[#1C1917] border border-white/10 rounded-2xl p-6 sm:p-10 reveal-on-scroll">
                <!-- Left: Interactive Controls (7 cols) -->
                <div class="lg:col-span-7 space-y-6">
                    <div>
                        <div class="flex justify-between items-center mb-2 text-xs">
                            <span class="text-stone-300 uppercase tracking-wider font-medium">Expected Guest Count:</span>
                            <span class="text-[#C6A15B] font-semibold text-base" x-text="guests + ' Guests'"></span>
                        </div>
                        <input type="range" min="100" max="1200" step="25" x-model.number="guests" 
                               class="w-full accent-[#C6A15B] cursor-pointer">
                        <div class="flex justify-between text-[11px] text-stone-400 mt-1">
                            <span>100 Guests</span>
                            <span>500 Guests</span>
                            <span>1,200+ Guests</span>
                        </div>
                    </div>

                    <!-- Package Tier Selection -->
                    <div>
                        <label class="block text-xs uppercase tracking-wider text-stone-300 font-medium mb-2.5">
                            Select Feast Menu Standard:
                        </label>
                        <div class="grid grid-cols-3 gap-3">
                            <button type="button" @click="tier = 1850; hallRent = 120000"
                                    :class="tier === 1850 ? 'border-[#C6A15B] bg-[#C6A15B]/15 text-[#E8DCBE] font-semibold' : 'border-white/10 text-stone-400 hover:border-white/25'"
                                    class="p-3.5 rounded-xl border text-center transition text-xs">
                                <div>Silver Feast</div>
                                <div class="text-[11px] opacity-75 mt-0.5">PKR 1,850/Head</div>
                            </button>

                            <button type="button" @click="tier = 2200; hallRent = 150000"
                                    :class="tier === 2200 ? 'border-[#C6A15B] bg-[#C6A15B]/15 text-[#E8DCBE] font-semibold' : 'border-white/10 text-stone-400 hover:border-white/25'"
                                    class="p-3.5 rounded-xl border text-center transition text-xs">
                                <div>Gold Mughlai</div>
                                <div class="text-[11px] opacity-75 mt-0.5">PKR 2,200/Head</div>
                            </button>

                            <button type="button" @click="tier = 2850; hallRent = 200000"
                                    :class="tier === 2850 ? 'border-[#C6A15B] bg-[#C6A15B]/15 text-[#E8DCBE] font-semibold' : 'border-white/10 text-stone-400 hover:border-white/25'"
                                    class="p-3.5 rounded-xl border text-center transition text-xs">
                                <div>Royal Imperial</div>
                                <div class="text-[11px] opacity-75 mt-0.5">PKR 2,850/Head</div>
                            </button>
                        </div>
                    </div>

                    <!-- Raw Kitchen Requirements Output -->
                    <div class="pt-4 border-t border-white/10 grid grid-cols-3 gap-4 text-center font-sans">
                        <div class="bg-[#171513] p-3.5 rounded-xl border border-white/10">
                            <span class="text-[10px] uppercase tracking-wider text-stone-400 block">Raw Meat</span>
                            <strong class="text-sm text-white mt-0.5 block" x-text="meatKg + ' kg'"></strong>
                        </div>
                        <div class="bg-[#171513] p-3.5 rounded-xl border border-white/10">
                            <span class="text-[10px] uppercase tracking-wider text-stone-400 block">Basmati Rice</span>
                            <strong class="text-sm text-white mt-0.5 block" x-text="riceKg + ' kg'"></strong>
                        </div>
                        <div class="bg-[#171513] p-3.5 rounded-xl border border-white/10">
                            <span class="text-[10px] uppercase tracking-wider text-stone-400 block">Pure Ghee / Oil</span>
                            <strong class="text-sm text-white mt-0.5 block" x-text="oilLiters + ' L'"></strong>
                        </div>
                    </div>
                </div>

                <!-- Right: Calculated Estimate Card (5 cols) -->
                <div class="lg:col-span-5 bg-[#171513] border border-[#C6A15B]/35 rounded-2xl p-6 sm:p-8 space-y-6">
                    <span class="text-[11px] font-semibold uppercase tracking-[0.16em] text-[#C6A15B] block font-sans">Indicative Banquet Estimate</span>
                    
                    <div class="space-y-3 text-xs border-b border-white/10 pb-4">
                        <div class="flex justify-between text-stone-300">
                            <span>Hall Shift Base Rent:</span>
                            <span class="text-white font-semibold" x-text="'PKR ' + Number(hallRent).toLocaleString()"></span>
                        </div>
                        <div class="flex justify-between text-stone-300">
                            <span x-text="'Catering (' + guests + ' × PKR ' + tier + '):'"></span>
                            <span class="text-white font-semibold" x-text="'PKR ' + Number(cateringTotal).toLocaleString()"></span>
                        </div>
                    </div>

                    <div>
                        <span class="text-[11px] uppercase tracking-wider text-stone-400 block font-sans">Estimated Grand Total</span>
                        <div class="font-serif-luxury text-3xl sm:text-4xl text-[#C6A15B] font-semibold mt-1"
                             x-text="'PKR ' + Number(grandTotal).toLocaleString()">
                        </div>
                        <span class="text-[11px] text-stone-400 block mt-1">Indicative estimate • Subject to applicable provincial sales taxes.</span>
                    </div>

                    <a :href="'{{ route('public.venues') }}?guests=' + guests" class="btn-gold !w-full !h-11 text-xs font-semibold uppercase tracking-wider block text-center">
                        View Matching Wedding Halls &rarr;
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================================================================
         8. EDITORIAL GALLERY HIGHLIGHTS (Stages, Chandeliers & Floristry)
         =================================================================== -->
    <section class="py-20 sm:py-24 bg-[#171513] text-white border-t border-[#C6A15B]/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-white/10 pb-6">
                <div class="space-y-2">
                    <span class="text-[11px] uppercase tracking-[0.2em] text-[#C6A15B] font-semibold block font-sans">
                        Visual Excellence
                    </span>
                    <h2 class="font-serif-luxury text-3xl sm:text-5xl text-white font-normal">
                        Bespoke Banqueting Aesthetics
                    </h2>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('public.gallery') }}" class="btn-outline-gold !h-9 text-xs text-white">
                        <span>View Full Gallery &rarr;</span>
                    </a>
                </div>
            </div>

            <!-- Gallery Mosaic -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="relative aspect-[4/3] rounded-2xl overflow-hidden border border-white/10 group bg-stone-900 shadow-sm">
                    <img src="https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=1000&auto=format&fit=crop" 
                         alt="Crystal Chandeliers" 
                         class="w-full h-full object-cover group-hover:scale-103 transition-transform duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/20 to-transparent"></div>
                    <div class="absolute bottom-4 left-4 text-white">
                        <span class="text-[10px] uppercase font-semibold tracking-wider text-[#C6A15B] font-sans">Lighting Architecture</span>
                        <h4 class="font-serif-luxury text-lg font-normal">Austrian Crystal Chandeliers</h4>
                    </div>
                </div>

                <div class="relative aspect-[4/3] rounded-2xl overflow-hidden border border-white/10 group bg-stone-900 shadow-sm">
                    <img src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=1000&auto=format&fit=crop" 
                         alt="Royal Stage Decor" 
                         class="w-full h-full object-cover group-hover:scale-103 transition-transform duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/20 to-transparent"></div>
                    <div class="absolute bottom-4 left-4 text-white">
                        <span class="text-[10px] uppercase font-semibold tracking-wider text-[#C6A15B] font-sans">Stage Styling</span>
                        <h4 class="font-serif-luxury text-lg font-normal">Royal Floral Stage Thrones</h4>
                    </div>
                </div>

                <div class="relative aspect-[4/3] rounded-2xl overflow-hidden border border-white/10 group bg-stone-900 shadow-sm">
                    <img src="https://images.unsplash.com/photo-1465495976277-4387d4b0b4c6?q=80&w=1000&auto=format&fit=crop" 
                         alt="Imperial Dining Setup" 
                         class="w-full h-full object-cover group-hover:scale-103 transition-transform duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/20 to-transparent"></div>
                    <div class="absolute bottom-4 left-4 text-white">
                        <span class="text-[10px] uppercase font-semibold tracking-wider text-[#C6A15B] font-sans">Imperial Banqueting</span>
                        <h4 class="font-serif-luxury text-lg font-normal">VIP Banquet Seating &amp; Silverware</h4>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- ===================================================================
         9. CINEMATIC PARTNER SECTION FOR VENUE OWNERS (Rent Admin)
         =================================================================== -->
    <section class="relative py-24 sm:py-28 bg-fixed bg-cover bg-center overflow-hidden border-t border-[#C6A15B]/30"
             style="background-image: url('https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=2000&auto=format&fit=crop');">
        <!-- Deep Obsidian Backdrop -->
        <div class="absolute inset-0 bg-[#171513]/90 backdrop-blur-[2px]"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
                <div class="lg:col-span-8 space-y-6">
                    <span class="text-[11px] uppercase tracking-[0.2em] text-[#C6A15B] block font-semibold font-sans">
                        EventHub Partner Network
                    </span>
                    <h2 class="font-serif-luxury text-3xl sm:text-5xl font-normal leading-tight text-[#F8F5EF]">
                        Are You a Wedding Hall or Marquee Owner? <br>
                        <span class="italic text-[#C6A15B]">Power Your Banquet with EventHub.</span>
                    </h2>
                    <p class="text-sm sm:text-base text-stone-300 font-light leading-relaxed max-w-2xl">
                        Manage your entire banquet operation seamlessly on EventHub. Every hall receives a dedicated public showcase link to share with families, a private Rent Admin workspace, guaranteed single-shift exclusivity, kitchen order slips, and complete expense tracking.
                    </p>

                    <!-- Features Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1 text-xs text-stone-200 font-sans">
                        <div class="flex items-center gap-2.5">
                            <span class="text-[#C6A15B] font-bold">✓</span>
                            <span>Dedicated Public Showcase for Each Hall</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <span class="text-[#C6A15B] font-bold">✓</span>
                            <span>Private Rent Admin Operations Workspace</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <span class="text-[#C6A15B] font-bold">✓</span>
                            <span>Guaranteed Shift Exclusivity (Zero Double-Bookings)</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <span class="text-[#C6A15B] font-bold">✓</span>
                            <span>Printable Contracts &amp; WhatsApp Guest RSVP</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-4 pt-3">
                        <a href="{{ route('register') }}" class="btn-gold !h-12 !px-8 text-xs font-semibold uppercase tracking-wider">
                            <span>Register Your Hall on EventHub &rarr;</span>
                        </a>
                        <a href="{{ route('public.about') }}" class="btn-outline-gold !h-12 !px-7 text-xs font-semibold text-white border-white/20">
                            Learn About Partner Benefits
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-4 bg-[#1C1917]/95 border border-[#C6A15B]/35 rounded-2xl p-6 sm:p-8 space-y-4 text-center shadow-xl backdrop-blur-md">
                    <span class="text-[11px] uppercase tracking-[0.16em] text-[#C6A15B] font-semibold block font-sans">Hall Owner Portal</span>
                    <h3 class="font-serif-luxury text-2xl text-white font-normal">Already Listed?</h3>
                    <p class="text-xs text-stone-300 leading-relaxed">
                        Sign in to your Rent Admin dashboard to manage your hall website, calendar shifts, and payments.
                    </p>
                    <a href="{{ route('login') }}" class="btn-obsidian !w-full !h-11 text-xs font-semibold uppercase block hover:border-[#C6A15B] hover:text-[#E8DCBE] transition-all">
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
