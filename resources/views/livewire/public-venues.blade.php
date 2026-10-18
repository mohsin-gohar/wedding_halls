<div class="bg-[#F8F5EF] dark:bg-[#171513] text-[#171513] dark:text-[#F8F5EF] min-h-screen transition-colors">
    <!-- ===================================================================
         SECTION 1: PAGE HERO & ARCHITECTURAL HEADLINE
         =================================================================== -->
    <section class="parallax-window relative bg-[#171513] text-white py-20 border-b border-[#C6A15B]/30 overflow-hidden">
        <div class="parallax-bg opacity-75" data-parallax="0.25"
             style="background-image: url('https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=2000&auto=format&fit=crop');">
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#171513] via-[#171513]/40 to-[#171513]/65"></div>
        <div class="absolute inset-0 bg-black/20"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 text-center">
            <div class="inline-flex items-center gap-2.5 px-4 py-1.5 border border-[#C6A15B]/50 bg-[#171513]/90 text-[#C6A15B] text-[11px] font-semibold tracking-[0.22em] uppercase font-mono">
                <span class="w-1.5 h-1.5 bg-[#C6A15B]"></span>
                <span>Verified Pakistani Wedding Halls &amp; Royal Marquees</span>
            </div>

            <h1 class="font-serif-luxury text-4xl sm:text-6xl text-white font-normal max-w-4xl mx-auto leading-tight">
                Explore Prestigious Banquet Venues
            </h1>

            <p class="text-sm sm:text-base text-stone-300 max-w-2xl mx-auto font-light leading-relaxed">
                Browse verified wedding halls by city or search directly by hall name.
            </p>
        </div>
    </section>


    <!-- Focused search: city and hall name -->
    <section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 -mt-8 relative z-20">
        <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,0.8fr)_minmax(0,1.4fr)_auto] gap-4 items-end bg-white dark:bg-[#211e1a] border border-[#C6A15B]/35 p-4 sm:p-6 shadow-xl">
            <div>
                <label for="venue-city" class="block text-[10px] font-bold uppercase tracking-[0.16em] text-stone-600 dark:text-stone-300 mb-2">City</label>
                <select id="venue-city" wire:model.live="city" class="input-luxury w-full text-sm dark:bg-[#171513] dark:text-white dark:border-white/15">
                    <option value="All">All cities</option>
                    @foreach($cities as $availableCity)
                        <option value="{{ $availableCity }}">{{ $availableCity }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="venue-search" class="block text-[10px] font-bold uppercase tracking-[0.16em] text-stone-600 dark:text-stone-300 mb-2">Hall name</label>
                <input id="venue-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search by hall name" class="input-luxury w-full text-sm dark:bg-[#171513] dark:text-white dark:border-white/15 dark:placeholder:text-stone-500">
            </div>
            <button type="button" wire:click="resetFilters" class="h-11 px-5 border border-[#C6A15B]/60 text-xs font-bold uppercase tracking-wider text-[#6f572b] hover:bg-[#171513] hover:text-white dark:text-[#E8DCBE] dark:hover:bg-[#C6A15B] dark:hover:text-[#171513] transition">Clear filters</button>
        </div>
    </section>


    <!-- ===================================================================
         SECTION 3: VERIFIED HALLS DIRECTORY & REAL-TIME AVAILABILITY
         =================================================================== -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 space-y-6">
        <div class="flex items-center justify-between text-xs text-stone-500 border-b border-[#E7E5E4] pb-3">
            <span>Showing <strong>{{ $halls->total() }}</strong> verified banquet halls</span>

        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse($halls as $hall)
                @php
                    $coverImg = !empty($hall->cover_image)
                        ? $hall->cover_image
                        : ($hall->images[0] ?? 'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=1000&auto=format&fit=crop');
                    $hasVideo = !empty($hall->videos) && count($hall->videos) > 0;
                    $amenities = [];
                    if ($hall->has_air_conditioning) { $amenities[] = 'Central AC'; }
                    if ($hall->has_generator_backup) { $amenities[] = '100% Generator'; }
                    if ($hall->has_bridal_room) { $amenities[] = 'Bridal Lounge'; }
                @endphp
                <a href="{{ route('public.venue.details', $hall->slug) }}"
                   class="editorial-card group flex flex-col overflow-hidden bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 hover:border-[#C6A15B] dark:hover:border-[#C6A15B]/70 transition-all duration-300">
                    <!-- Image Banner (16:10) -->
                    <div class="relative aspect-[16/10] overflow-hidden bg-stone-900">
                        <img src="{{ $coverImg }}"
                             alt="{{ $hall->name }}"
                             loading="lazy"
                             class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-black/10"></div>

                        <!-- Location Badge (top-left) -->
                        <span class="absolute top-3.5 left-3.5 inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#171513]/85 backdrop-blur-sm text-white text-[10px] font-bold uppercase tracking-wider font-mono">
                            <svg class="w-3 h-3 text-[#C6A15B]" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                            {{ $hall->venue->city ?? 'Lahore' }}
                        </span>

                        @if($hasVideo)
                            <!-- 4K Tour Badge (top-right) -->
                            <span class="absolute top-3.5 right-3.5 inline-flex items-center gap-1 px-2 py-1 bg-[#C6A15B] text-[#171513] text-[10px] font-bold uppercase tracking-wider font-mono">
                                <svg class="w-3 h-3 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                                4K Tour
                            </span>
                        @endif

                        <!-- Capacity Badge (bottom-left) -->
                        <span class="absolute bottom-3.5 left-3.5 inline-flex items-center gap-1.5 px-2.5 py-1 bg-black/70 backdrop-blur-sm text-white text-[11px] font-medium font-mono">
                            <svg class="w-3.5 h-3.5 text-[#C6A15B]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            {{ $hall->min_capacity }}–{{ $hall->max_capacity }} Guests
                        </span>
                    </div>

                        <!-- Content Block -->
                        <div class="flex flex-col flex-1 p-5 sm:p-6 space-y-3">
                            <div class="space-y-1.5">
                                <h3 class="font-serif-luxury text-xl sm:text-2xl text-[#171513] dark:text-white font-medium leading-snug group-hover:text-[#8F7239] dark:group-hover:text-[#C6A15B] transition-colors">
                                    {{ $hall->name }}
                                </h3>
                                @if(!empty($hall->tagline))
                                    <p class="text-xs text-[#8F7239] dark:text-[#C6A15B] font-medium">{{ $hall->tagline }}</p>
                                @endif
                            </div>

                            <p class="text-xs sm:text-[13px] text-stone-600 dark:text-stone-300 leading-relaxed line-clamp-2">
                                {{ $hall->description ?? 'Spectacular wedding banquet ballroom with crystal chandeliers, climate control, and power backup.' }}
                            </p>

                            <!-- Amenities -->
                            @if(count($amenities) > 0)
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 pt-1 text-[11px] text-stone-600 dark:text-stone-300 font-medium">
                                    @foreach($amenities as $amenity)
                                        <span class="inline-flex items-center gap-1">
                                            <span class="w-1 h-1 bg-[#C6A15B]"></span>
                                            {{ $amenity }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            <!-- Footer: Price + CTA -->
                            <div class="mt-auto pt-4 border-t border-[#E7E5E4] dark:border-white/10 flex items-end justify-between gap-3">
                                <div class="space-y-0.5">
                                    <span class="text-[9px] uppercase font-bold tracking-[0.15em] text-stone-500 dark:text-stone-400 block font-mono">Base Shift Rent</span>
                                    <strong class="font-cinzel text-lg sm:text-xl text-[#171513] dark:text-[#C6A15B] font-bold">PKR {{ number_format($hall->base_rent) }}</strong>
                                </div>
                                <span class="btn-obsidian !h-9 !px-4 text-[11px] font-semibold font-mono shrink-0">
                                    Details
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </span>
                            </div>
                        </div>
                    </a>
            @empty
                <div class="col-span-1 md:col-span-2 lg:col-span-3 mx-auto w-full max-w-2xl text-center py-14 px-6 bg-white dark:bg-[#211e1a] border border-[#C6A15B]/30 space-y-3">
                    @if($hasPublicHalls)
                        <p class="font-serif-luxury text-2xl text-stone-800 dark:text-white">No venues match these filters</p>
                        <p class="text-sm text-stone-600 dark:text-stone-300 max-w-md mx-auto">Try another city or a shorter hall name to see more verified venues.</p>
                    @else
                        <p class="font-serif-luxury text-2xl text-stone-800 dark:text-white">New venues are on their way</p>
                        <p class="text-sm text-stone-600 dark:text-stone-300 max-w-md mx-auto">Our team is reviewing venue listings. Verified halls will appear here as soon as they are approved.</p>
                    @endif
                    <div class="pt-3">
                        @if($hasPublicHalls)
                            <button wire:click="resetFilters" class="btn-gold !h-10 !px-5 text-xs">Show all venues</button>
                        @else
                            <a href="{{ route('public.contact') }}" class="btn-gold !h-10 !px-5 text-xs">Contact our team</a>
                        @endif
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="pt-6">
            {{ $halls->links() }}
        </div>
    </section>


    <!-- ===================================================================
         SECTION 4: PRIME PAKISTANI WEDDING HUBS (City Spotlight)
         =================================================================== -->
    <section class="py-16 bg-[#F8F5EF]/60 border-t border-b border-[#E7E5E4]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            <div class="text-center space-y-2 max-w-2xl mx-auto">
                <span class="text-[10px] font-mono uppercase tracking-[0.2em] text-[#8F7239] font-bold block">
                    Nationwide Banqueting
                </span>
                <h2 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] font-normal">
                    Popular Wedding Destinations
                </h2>
                <p class="text-xs text-stone-600 font-sans">
                    Explore marquee complexes and luxury hotel ballrooms in Pakistan's premier cultural hubs.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Lahore -->
                <button type="button" wire:click="$set('city', 'Lahore')"
                        class="group text-left relative aspect-[4/5] overflow-hidden bg-black border border-[#E7E5E4] cursor-pointer">
                    <img src="https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=800&auto=format&fit=crop"
                         alt="Lahore Wedding Halls"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 opacity-60">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>
                    <div class="absolute bottom-6 left-6 right-6 text-white space-y-1">
                        <span class="text-[10px] uppercase font-bold tracking-widest text-[#C6A15B] font-mono">Mughal Heritage</span>
                        <h3 class="font-serif-luxury text-2xl font-medium">Lahore</h3>
                        <p class="text-[11px] text-stone-300 font-mono">Gulberg, Cantt, DHA &amp; Canal Road</p>
                    </div>
                </button>

                <!-- Karachi -->
                <button type="button" wire:click="$set('city', 'Karachi')"
                        class="group text-left relative aspect-[4/5] overflow-hidden bg-black border border-[#E7E5E4] cursor-pointer">
                    <img src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=800&auto=format&fit=crop"
                         alt="Karachi Wedding Halls"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 opacity-60">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>
                    <div class="absolute bottom-6 left-6 right-6 text-white space-y-1">
                        <span class="text-[10px] uppercase font-bold tracking-widest text-[#C6A15B] font-mono">Coastal Elegance</span>
                        <h3 class="font-serif-luxury text-2xl font-medium">Karachi</h3>
                        <p class="text-[11px] text-stone-300 font-mono">Clifton, DHA, Karsaz &amp; Shahrah-e-Faisal</p>
                    </div>
                </button>

                <!-- Islamabad -->
                <button type="button" wire:click="$set('city', 'Islamabad')"
                        class="group text-left relative aspect-[4/5] overflow-hidden bg-black border border-[#E7E5E4] cursor-pointer">
                    <img src="https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?q=80&w=800&auto=format&fit=crop"
                         alt="Islamabad Wedding Venues"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 opacity-60">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>
                    <div class="absolute bottom-6 left-6 right-6 text-white space-y-1">
                        <span class="text-[10px] uppercase font-bold tracking-widest text-[#C6A15B] font-mono">Margalla Serenity</span>
                        <h3 class="font-serif-luxury text-2xl font-medium">Islamabad</h3>
                        <p class="text-[11px] text-stone-300 font-mono">Club Road, E-11, Express Highway</p>
                    </div>
                </button>

                <!-- Faisalabad -->
                <button type="button" wire:click="$set('city', 'Faisalabad')"
                        class="group text-left relative aspect-[4/5] overflow-hidden bg-black border border-[#E7E5E4] cursor-pointer">
                    <img src="https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=800&auto=format&fit=crop"
                         alt="Faisalabad Wedding Halls"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 opacity-60">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>
                    <div class="absolute bottom-6 left-6 right-6 text-white space-y-1">
                        <span class="text-[10px] uppercase font-bold tracking-widest text-[#C6A15B] font-mono">Industrial Grandeur</span>
                        <h3 class="font-serif-luxury text-2xl font-medium">Faisalabad</h3>
                        <p class="text-[11px] text-stone-300 font-mono">Canal Road, Kohinoor &amp; Jaranwala</p>
                    </div>
                </button>
            </div>
        </div>
    </section>


    <!-- ===================================================================
         SECTION 5: VENUE TIER & ARCHITECTURAL COMPARISON MATRIX
         =================================================================== -->
    <section class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
        <div class="border-b border-[#E7E5E4] pb-4 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <span class="text-[10px] font-mono uppercase tracking-[0.2em] text-[#8F7239] font-bold block">
                    Venue Classification
                </span>
                <h2 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] font-normal">
                    Banquet Tiers &amp; Facility Guide
                </h2>
            </div>
            <p class="text-xs text-stone-500 font-mono">
                Compare typical specifications across our certified banquet formats
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Tier 1 -->
            <div class="p-8 bg-white border border-[#E7E5E4] space-y-4">
                <span class="text-[10px] uppercase font-bold font-mono tracking-widest text-[#8F7239] block">TIER 1</span>
                <h3 class="font-serif-luxury text-2xl text-[#171513]">Royal Ballrooms</h3>
                <p class="text-xs text-stone-600 leading-relaxed font-normal">
                    Indoor grand halls featuring high ceilings, imported Austrian chandeliers, heavy HVAC cooling, and VIP bridal dressing chambers.
                </p>
                <div class="pt-4 border-t border-[#E7E5E4] space-y-2 text-xs font-mono text-stone-700">
                    <div>• Capacity: 400 - 1,200 Guests</div>
                    <div>• Ideal for: Barat &amp; Grand Valima</div>
                    <div>• 100% Cat Diesel Generator</div>
                </div>
            </div>

            <!-- Tier 2 -->
            <div class="p-8 bg-white border border-[#E7E5E4] space-y-4">
                <span class="text-[10px] uppercase font-bold font-mono tracking-widest text-[#8F7239] block">TIER 2</span>
                <h3 class="font-serif-luxury text-2xl text-[#171513]">Crystal Marquees</h3>
                <p class="text-xs text-stone-600 leading-relaxed font-normal">
                    Expansive modular structures with pillarless sightlines, royal carpeted staging, and dedicated paved parking for 300+ vehicles.
                </p>
                <div class="pt-4 border-t border-[#E7E5E4] space-y-2 text-xs font-mono text-stone-700">
                    <div>• Capacity: 500 - 2,500 Guests</div>
                    <div>• Ideal for: Large Gatherings &amp; Expos</div>
                    <div>• Uniformed Valet Logistics</div>
                </div>
            </div>

            <!-- Tier 3 -->
            <div class="p-8 bg-white border border-[#E7E5E4] space-y-4">
                <span class="text-[10px] uppercase font-bold font-mono tracking-widest text-[#8F7239] block">TIER 3</span>
                <h3 class="font-serif-luxury text-2xl text-[#171513]">Botanical Lawns</h3>
                <p class="text-xs text-stone-600 leading-relaxed font-normal">
                    Open-sky manicured gardens with fairy light tree wraps, illuminated water fountains, and custom live BBQ live station counters.
                </p>
                <div class="pt-4 border-t border-[#E7E5E4] space-y-2 text-xs font-mono text-stone-700">
                    <div>• Capacity: 250 - 1,500 Guests</div>
                    <div>• Ideal for: Festive Mehndi &amp; Qawwali</div>
                    <div>• Natural Ambience &amp; Fairy Lights</div>
                </div>
            </div>
        </div>
    </section>


    <!-- ===================================================================
         SECTION 6: FREQUENTLY ASKED QUESTIONS (Zero-Pill Luxury Accordion)
         =================================================================== -->
    <section class="py-16 bg-white border-t border-b border-[#E7E5E4]">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="text-center space-y-2">
                <span class="text-[10px] font-mono uppercase tracking-[0.2em] text-[#8F7239] font-bold block">
                    Transparent Booking
                </span>
                <h2 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] font-normal">
                    Frequently Asked Questions
                </h2>
            </div>

            <div class="space-y-4">
                <div class="p-5 border border-[#E7E5E4] bg-[#F8F5EF] space-y-2">
                    <h4 class="font-serif-luxury text-lg text-[#171513] font-medium">How does atomic shift reservation work?</h4>
                    <p class="text-xs text-stone-600 leading-relaxed font-normal">
                        EventHub uses database atomic transactions (`SELECT FOR UPDATE`). When you confirm a wedding shift (Afternoon, Evening, or Night), our software locks that exact hall date immediately so no other client or operator can book the same slot.
                    </p>
                </div>

                <div class="p-5 border border-[#E7E5E4] bg-[#F8F5EF] space-y-2">
                    <h4 class="font-serif-luxury text-lg text-[#171513] font-medium">Can I bring outside catering or only use hall food?</h4>
                    <p class="text-xs text-stone-600 leading-relaxed font-normal">
                        Every hall sets their policy on their dedicated showcase page. Most venues offer premier in-house Mughlai Bawarchi feasts, while some permit outside caterers with a kitchen utility surcharge.
                    </p>
                </div>

                <div class="p-5 border border-[#E7E5E4] bg-[#F8F5EF] space-y-2">
                    <h4 class="font-serif-luxury text-lg text-[#171513] font-medium">What is the payment deposit schedule?</h4>
                    <p class="text-xs text-stone-600 leading-relaxed font-normal">
                        Typically, a 50% token advance is paid upon date confirmation, followed by the remaining balance clearance 3 days prior to the event date. Official A4 printable receipts and WhatsApp notifications are issued automatically.
                    </p>
                </div>
            </div>
        </div>
    </section>


    <!-- ===================================================================
         SECTION 7: WHITE-GLOVE CONCIERGE & RENT ADMIN ONBOARDING CTA
         =================================================================== -->
    <section class="py-16 bg-[#171513] text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="space-y-2">
                <span class="text-[10px] font-mono uppercase tracking-widest text-[#C6A15B] block font-bold">
                    EventHub Concierge
                </span>
                <h3 class="font-serif-luxury text-2xl sm:text-3xl font-normal text-white">
                    Need Personalized Venue Recommendation?
                </h3>
                <p class="text-xs text-stone-300 font-light max-w-xl">
                    Our luxury wedding consultants can match your guest count and budget with certified venues in Lahore, Karachi, or Islamabad.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-4 shrink-0">
                <a href="{{ route('public.contact') }}" class="btn-gold !h-11 !px-6 text-xs font-bold uppercase font-mono">
                    Speak with Concierge &rarr;
                </a>
                @guest
                    <a href="{{ route('register') }}" class="btn-outline-gold !h-11 !px-5 text-xs font-semibold text-white border-white/20 font-mono">
                        List Your Wedding Hall &rarr;
                    </a>
                @endguest
            </div>
        </div>
    </section>

    <!-- ===================================================================
         SECTION 8: 14-DAY SHIFT AVAILABILITY CALENDAR
         =================================================================== -->
    <section class="py-16 bg-[#F8F5EF] border-t border-b border-[#E7E5E4]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="text-center space-y-2 max-w-3xl mx-auto">
                <div class="inline-flex items-center gap-2 text-[10px] font-mono uppercase tracking-[0.24em] text-[#8F7239] dark:text-[#C6A15B] font-bold">
                    <span>Interactive Booking</span>
                    <span>â€¢</span>
                    <span>14-Day Shift Availability</span>
                </div>
                <h2 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                    Check Hall Availability
                </h2>
                <p class="text-xs sm:text-sm text-stone-600 dark:text-stone-300 font-sans">
                    Browse upcoming dates across the next 14 days. Select a date to view all available shifts.
                </p>
            </div>

            <div class="max-w-4xl mx-auto bg-white dark:bg-[#171513] rounded-2xl border border-[#E7E5E4] dark:border-white/10 shadow-xl overflow-hidden">
                <!-- Calendar Header -->
                <div class="flex flex-wrap items-center justify-between gap-4 p-4 sm:p-6 border-b border-[#E7E5E4] dark:border-white/10">
                    <div class="flex items-center gap-2">
                        <button class="btn-calendar sm:btn-calendar w-8 h-8 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <span class="font-serif-luxury text-lg font-medium text-[#171513] dark:text-white">Wedding Hall Availability</span>
                        <button class="btn-calendar sm:btn-calendar w-8 h-8 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                    <div class="flex items-center gap-3 text-xs font-mono text-stone-600 dark:text-stone-400">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Available
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span> Partial
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-red-600"></span> Booked
                        </span>
                    </div>
                </div>

                <!-- Calendar Grid - Scrollable -->
                <div id="shift-calendar" class="overflow-x-auto p-4 sm:p-6 space-y-2">
                    <div class="grid grid-cols-7 gap-px bg-black/5 rounded-lg overflow-hidden">

                        <!-- Day Cells (30 days) -->
                        @for($d = 1; $d <= 30; $d++)
                            @php
                                $day = \Carbon\Carbon::now()->addDays($d);
                                $dateKey = $day->format('Y-m-d');
                                $dayAvail = $availability[$dateKey] ?? ['afternoon' => 0, 'evening' => 0, 'night' => 0];
                                $totalBooked = $dayAvail['afternoon'] + $dayAvail['evening'] + $dayAvail['night'];
                                $totalCapacity = max(1, $totalHalls) * 3;
                                $booked_ratio = $totalCapacity > 0 ? $totalBooked / $totalCapacity : 0;
                                $cell_status = $booked_ratio >= 0.9 ? 'bg-red-500/10' : ($booked_ratio > 0.3 ? 'bg-amber-500/10' : 'bg-emerald-500/5');
                            @endphp

                            <div class="bg-white dark:bg-[#171513] p-2 sm:p-3 min-h-[80px] sm:min-h-[100px] border-r border-l border-[#E7E5E4] dark:border-white/5 transition-all duration-200 hover:ring-2 hover:ring-[#C6A15B]/50 {{ $cell_status }}">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] font-mono font-bold text-[#8F7239] dark:text-[#C6A15B] uppercase">{{ $day->format('D') }}</span>
                                    <span class="text-[9px] font-mono text-stone-500">{{ $day->format('d M') }}</span>
                                </div>

                                <div class="space-y-1.5 mt-2">
                                    <div class="flex items-center gap-2 text-xs">
                                        <span class="w-2 h-2 rounded-full bg-[#C6A15B]/60 flex-shrink-0"></span>
                                        <span class="text-xs text-stone-600 dark:text-stone-400 truncate">Afternoon</span>
                                        @php $aLeft = max(0, $totalHalls - $dayAvail['afternoon']); @endphp
                                        @if($aLeft === 0)
                                            <span class="text-[9px] font-mono bg-red-500/10 text-red-600 dark:text-red-400 px-1.5 py-0.5 rounded-full font-bold">0 left</span>
                                        @else
                                            <span class="text-[9px] font-mono bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 px-1.5 py-0.5 rounded-full font-bold">{{ $aLeft }} left</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 text-xs">
                                        <span class="w-2 h-2 rounded-full bg-[#C6A15B] flex-shrink-0"></span>
                                        <span class="text-xs text-stone-600 dark:text-stone-400 truncate">Evening</span>
                                        @php $eLeft = max(0, $totalHalls - $dayAvail['evening']); @endphp
                                        @if($eLeft === 0)
                                            <span class="text-[9px] font-mono bg-red-500/10 text-red-600 dark:text-red-400 px-1.5 py-0.5 rounded-full font-bold">0 left</span>
                                        @else
                                            <span class="text-[9px] font-mono bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 px-1.5 py-0.5 rounded-full font-bold">{{ $eLeft }} left</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 text-xs">
                                        <span class="w-2 h-2 rounded-full bg-[#C6A15B]/30 flex-shrink-0"></span>
                                        <span class="text-xs text-stone-600 dark:text-stone-400 truncate">Night</span>
                                        @php $nLeft = max(0, $totalHalls - $dayAvail['night']); @endphp
                                        @if($nLeft === 0)
                                            <span class="text-[9px] font-mono bg-red-500/10 text-red-600 dark:text-red-400 px-1.5 py-0.5 rounded-full font-bold">0 left</span>
                                        @else
                                            <span class="text-[9px] font-mono bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 px-1.5 py-0.5 rounded-full font-bold">{{ $nLeft }} left</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-2 pt-2 border-t border-[#E7E5E4] dark:border-white/5 flex justify-between items-center">
                                    <span class="text-[8px] font-mono uppercase text-stone-500">{{ $totalBooked }}/{{ $totalCapacity }} booked</span>
                                    <span class="text-[9px] font-mono uppercase px-2 py-0.5 rounded-full">
                                        @if($booked_ratio >= 0.9)
                                            <span class="text-red-600 dark:text-red-400 font-bold">Busy</span>
                                        @elseif($booked_ratio > 0.3)
                                            <span class="text-amber-600 dark:text-amber-400 font-bold">Filling</span>
                                        @else
                                            <span class="text-emerald-600 dark:text-emerald-400 font-bold">Available</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
