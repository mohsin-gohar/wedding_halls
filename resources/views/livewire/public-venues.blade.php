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
            <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full border border-[#C6A15B]/40 bg-[#171513]/90 text-[#E8DCBE] text-[11px] font-semibold tracking-[0.18em] uppercase font-sans">
                <span class="w-1.5 h-1.5 rounded-full bg-[#C6A15B]"></span>
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
        <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,0.8fr)_minmax(0,1.4fr)_auto] gap-4 items-end bg-white dark:bg-[#1C1917] border border-[#C6A15B]/30 rounded-2xl p-5 sm:p-6 shadow-[0_12px_36px_-8px_rgba(23,21,19,0.18)] dark:shadow-[0_20px_50px_rgba(0,0,0,0.7)]">
            <div>
                <label for="venue-city" class="block text-[11px] font-semibold uppercase tracking-[0.06em] text-stone-700 dark:text-stone-300 mb-1.5 font-sans">City</label>
                <select id="venue-city" wire:model.live="city" class="input-luxury w-full text-sm dark:bg-[#171513] dark:text-white dark:border-white/15 rounded-lg focus:border-[#C6A15B] focus:ring-2 focus:ring-[#C6A15B]/25">
                    <option value="All">All cities</option>
                    @foreach($cities as $availableCity)
                        <option value="{{ $availableCity }}">{{ $availableCity }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="venue-search" class="block text-[11px] font-semibold uppercase tracking-[0.06em] text-stone-700 dark:text-stone-300 mb-1.5 font-sans">Hall name</label>
                <input id="venue-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search by hall name" class="input-luxury w-full text-sm dark:bg-[#171513] dark:text-white dark:border-white/15 dark:placeholder:text-stone-500 rounded-lg focus:border-[#C6A15B] focus:ring-2 focus:ring-[#C6A15B]/25">
            </div>
            <button type="button" wire:click="resetFilters" class="btn-outline-gold !h-[42px] !px-5 text-xs font-semibold uppercase tracking-wider rounded-lg">Clear filters</button>
        </div>
    </section>


    <!-- ===================================================================
         SECTION 3: VERIFIED HALLS DIRECTORY & REAL-TIME AVAILABILITY
         =================================================================== -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 space-y-6">
        <div class="flex items-center justify-between text-xs text-stone-500 border-b border-[#E7E5E4] dark:border-white/10 pb-3 font-sans">
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
                   class="group flex flex-col rounded-2xl overflow-hidden bg-white dark:bg-[#1C1917] border border-stone-200/80 dark:border-white/10 hover:border-[#C6A15B]/60 shadow-xs hover:shadow-[0_12px_30px_-8px_rgba(23,21,19,0.12)] hover:-translate-y-0.5 transition-all duration-300">
                    <!-- Image Banner (16:10) -->
                    <div class="relative aspect-[16/10] overflow-hidden bg-stone-900">
                        <img src="{{ $coverImg }}"
                             alt="{{ $hall->name }}"
                             loading="lazy"
                             class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-103">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-black/10"></div>

                        <!-- Location Badge (top-left) -->
                        <span class="absolute top-3.5 left-3.5 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-black/75 backdrop-blur-md text-white text-[11px] font-medium font-sans border border-white/15">
                            <svg class="w-3 h-3 text-[#C6A15B]" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                            {{ $hall->venue->city ?? 'Lahore' }}
                        </span>

                        @if($hasVideo)
                            <!-- 4K Tour Badge (top-right) -->
                            <span class="absolute top-3.5 right-3.5 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-[#C6A15B] text-[#171513] text-[10px] font-bold uppercase tracking-wider font-sans shadow-md">
                                <svg class="w-3 h-3 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                                4K Tour
                            </span>
                        @endif

                        <!-- Capacity Badge (bottom-left) -->
                        <span class="absolute bottom-3.5 left-3.5 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-black/75 backdrop-blur-md text-white text-[11px] font-medium font-sans border border-white/15">
                            <svg class="w-3.5 h-3.5 text-[#C6A15B]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            {{ $hall->min_capacity }}–{{ $hall->max_capacity }} Guests
                        </span>
                    </div>

                    <!-- Content Block -->
                    <div class="flex flex-col flex-1 p-5 sm:p-6 space-y-3">
                        <div class="space-y-1">
                            <h3 class="font-serif-luxury text-xl sm:text-2xl text-[#171513] dark:text-white font-medium leading-snug group-hover:text-[#8F7239] dark:group-hover:text-[#E8DCBE] transition-colors">
                                {{ $hall->name }}
                            </h3>
                            @if(!empty($hall->tagline))
                                <p class="text-xs text-[#8F7239] dark:text-[#C6A15B] font-medium font-sans">{{ $hall->tagline }}</p>
                            @endif
                            @php
                                $rCount = \App\Models\Review::countForHall($hall->id);
                                $rAvg = \App\Models\Review::averageForHall($hall->id);
                            @endphp
                            @if($rCount > 0)
                                <div class="flex items-center gap-1 text-xs text-[#C6A15B]">
                                    @for($i = 1; $i <= 5; $i++){{ $i <= round($rAvg) ? '★' : '☆' }}@endfor
                                    <span class="text-stone-500 dark:text-stone-400 ml-1">{{ number_format($rAvg, 1) }} ({{ $rCount }})</span>
                                </div>
                            @endif
                        </div>

                        <p class="text-xs sm:text-[13px] text-stone-600 dark:text-stone-300 leading-relaxed line-clamp-2">
                            {{ $hall->description ?? 'Spectacular wedding banquet ballroom with crystal chandeliers, climate control, and power backup.' }}
                        </p>

                        <!-- Amenities -->
                        @if(count($amenities) > 0)
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 pt-1 text-[11px] text-stone-600 dark:text-stone-300 font-medium font-sans">
                                @foreach($amenities as $amenity)
                                    <span class="inline-flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#C6A15B]"></span>
                                        {{ $amenity }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <!-- Footer: Price + CTA -->
                        <div class="mt-auto pt-4 border-t border-stone-200/80 dark:border-white/10 flex items-end justify-between gap-3">
                            <div class="space-y-0.5">
                                <span class="text-[10px] uppercase font-semibold tracking-wider text-stone-500 dark:text-stone-400 block font-sans">Base Shift Rent</span>
                                <strong class="font-cinzel text-lg sm:text-xl text-[#171513] dark:text-[#C6A15B] font-bold">PKR {{ number_format($hall->base_rent) }}</strong>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($hallWaUrl = $hall->getWhatsappUrl())
                                    <span onclick="event.preventDefault(); event.stopPropagation(); window.open('{{ $hallWaUrl }}', '_blank');"
                                          title="Chat with {{ $hall->name }} on WhatsApp"
                                          class="h-9 px-3 rounded-lg bg-emerald-600/15 hover:bg-emerald-600/25 border border-emerald-500/40 text-emerald-600 dark:text-emerald-400 text-xs font-bold font-sans flex items-center gap-1.5 transition cursor-pointer">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span>WhatsApp</span>
                                    </span>
                                @endif
                                <span class="btn-gold !h-9 !px-4 text-[11px] font-semibold uppercase rounded-lg shrink-0">
                                    Details &rarr;
                                </span>
                            </div>
                        </div>
                    </div>
                    </a>
            @empty
                <div class="col-span-1 md:col-span-2 lg:col-span-3 mx-auto w-full max-w-2xl text-center py-14 px-6 bg-white dark:bg-[#211e1a] rounded-2xl border border-[#C6A15B]/30 space-y-3">
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
    <section class="py-16 bg-[#F8F5EF]/60 dark:bg-white/5 border-t border-b border-[#E7E5E4] dark:border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            <div class="text-center space-y-2 max-w-2xl mx-auto">
                <span class="text-[11px] font-sans uppercase tracking-[0.2em] text-[#8F7239] dark:text-[#C6A15B] font-semibold block">
                    Nationwide Banqueting
                </span>
                <h2 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                    Popular Wedding Destinations
                </h2>
                <p class="text-xs text-stone-600 dark:text-stone-300 font-sans">
                    Explore marquee complexes and luxury hotel ballrooms in Pakistan's premier cultural hubs.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Lahore -->
                <button type="button" wire:click="$set('city', 'Lahore')"
                        class="group text-left relative aspect-[4/5] rounded-2xl overflow-hidden bg-black border border-[#E7E5E4] dark:border-white/10 cursor-pointer shadow-xs hover:shadow-lg transition-all">
                    <img src="https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=800&auto=format&fit=crop"
                         alt="Lahore Wedding Halls"
                         loading="lazy" decoding="async" class="w-full h-full object-cover group-hover:scale-103 transition-transform duration-700 opacity-65">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/35 to-transparent"></div>
                    <div class="absolute bottom-6 left-6 right-6 text-white space-y-1">
                        <span class="text-[10px] uppercase font-semibold tracking-widest text-[#C6A15B] font-sans">Mughal Heritage</span>
                        <h3 class="font-serif-luxury text-2xl font-medium">Lahore</h3>
                        <p class="text-[11px] text-stone-300 font-sans">Gulberg, Cantt, DHA &amp; Canal Road</p>
                    </div>
                </button>

                <!-- Karachi -->
                <button type="button" wire:click="$set('city', 'Karachi')"
                        class="group text-left relative aspect-[4/5] rounded-2xl overflow-hidden bg-black border border-[#E7E5E4] dark:border-white/10 cursor-pointer shadow-xs hover:shadow-lg transition-all">
                    <img src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=800&auto=format&fit=crop"
                         alt="Karachi Wedding Halls"
                         loading="lazy" decoding="async" class="w-full h-full object-cover group-hover:scale-103 transition-transform duration-700 opacity-65">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/35 to-transparent"></div>
                    <div class="absolute bottom-6 left-6 right-6 text-white space-y-1">
                        <span class="text-[10px] uppercase font-semibold tracking-widest text-[#C6A15B] font-sans">Coastal Elegance</span>
                        <h3 class="font-serif-luxury text-2xl font-medium">Karachi</h3>
                        <p class="text-[11px] text-stone-300 font-sans">Clifton, DHA, Karsaz &amp; Shahrah-e-Faisal</p>
                    </div>
                </button>

                <!-- Islamabad -->
                <button type="button" wire:click="$set('city', 'Islamabad')"
                        class="group text-left relative aspect-[4/5] rounded-2xl overflow-hidden bg-black border border-[#E7E5E4] dark:border-white/10 cursor-pointer shadow-xs hover:shadow-lg transition-all">
                    <img src="https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?q=80&w=800&auto=format&fit=crop"
                         alt="Islamabad Wedding Venues"
                         loading="lazy" decoding="async" class="w-full h-full object-cover group-hover:scale-103 transition-transform duration-700 opacity-65">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/35 to-transparent"></div>
                    <div class="absolute bottom-6 left-6 right-6 text-white space-y-1">
                        <span class="text-[10px] uppercase font-semibold tracking-widest text-[#C6A15B] font-sans">Margalla Serenity</span>
                        <h3 class="font-serif-luxury text-2xl font-medium">Islamabad</h3>
                        <p class="text-[11px] text-stone-300 font-sans">Club Road, E-11, Express Highway</p>
                    </div>
                </button>

                <!-- Faisalabad -->
                <button type="button" wire:click="$set('city', 'Faisalabad')"
                        class="group text-left relative aspect-[4/5] rounded-2xl overflow-hidden bg-black border border-[#E7E5E4] dark:border-white/10 cursor-pointer shadow-xs hover:shadow-lg transition-all">
                    <img src="https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=800&auto=format&fit=crop"
                         alt="Faisalabad Wedding Halls"
                         loading="lazy" decoding="async" class="w-full h-full object-cover group-hover:scale-103 transition-transform duration-700 opacity-65">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/35 to-transparent"></div>
                    <div class="absolute bottom-6 left-6 right-6 text-white space-y-1">
                        <span class="text-[10px] uppercase font-semibold tracking-widest text-[#C6A15B] font-sans">Industrial Grandeur</span>
                        <h3 class="font-serif-luxury text-2xl font-medium">Faisalabad</h3>
                        <p class="text-[11px] text-stone-300 font-sans">Canal Road, Kohinoor &amp; Jaranwala</p>
                    </div>
                </button>
            </div>
        </div>
    </section>


    <!-- ===================================================================
         SECTION 5: VENUE TIER & ARCHITECTURAL COMPARISON MATRIX
         =================================================================== -->
    <section class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
        <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-4 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <span class="text-[11px] font-sans uppercase tracking-[0.2em] text-[#8F7239] dark:text-[#C6A15B] font-semibold block">
                    Venue Classification
                </span>
                <h2 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                    Banquet Tiers &amp; Facility Guide
                </h2>
            </div>
            <p class="text-xs text-stone-500 dark:text-stone-400 font-sans">
                Compare typical specifications across our certified banquet formats
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Tier 1 -->
            <div class="p-8 rounded-2xl bg-white dark:bg-[#1C1917] border border-[#E7E5E4] dark:border-white/10 space-y-4 shadow-xs">
                <span class="text-[11px] uppercase font-semibold font-sans tracking-widest text-[#8F7239] dark:text-[#C6A15B] block">Tier 1</span>
                <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white">Royal Ballrooms</h3>
                <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed font-normal">
                    Indoor grand halls featuring high ceilings, imported Austrian chandeliers, heavy HVAC cooling, and VIP bridal dressing chambers.
                </p>
                <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10 space-y-2 text-xs font-sans text-stone-700 dark:text-stone-300">
                    <div>• Capacity: 400 - 1,200 Guests</div>
                    <div>• Ideal for: Barat &amp; Grand Valima</div>
                    <div>• 100% Cat Diesel Generator</div>
                </div>
            </div>

            <!-- Tier 2 -->
            <div class="p-8 rounded-2xl bg-white dark:bg-[#1C1917] border border-[#E7E5E4] dark:border-white/10 space-y-4 shadow-xs">
                <span class="text-[11px] uppercase font-semibold font-sans tracking-widest text-[#8F7239] dark:text-[#C6A15B] block">Tier 2</span>
                <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white">Crystal Marquees</h3>
                <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed font-normal">
                    Expansive modular structures with pillarless sightlines, royal carpeted staging, and dedicated paved parking for 300+ vehicles.
                </p>
                <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10 space-y-2 text-xs font-sans text-stone-700 dark:text-stone-300">
                    <div>• Capacity: 500 - 2,500 Guests</div>
                    <div>• Ideal for: Large Gatherings &amp; Expos</div>
                    <div>• Uniformed Valet Logistics</div>
                </div>
            </div>

            <!-- Tier 3 -->
            <div class="p-8 rounded-2xl bg-white dark:bg-[#1C1917] border border-[#E7E5E4] dark:border-white/10 space-y-4 shadow-xs">
                <span class="text-[11px] uppercase font-semibold font-sans tracking-widest text-[#8F7239] dark:text-[#C6A15B] block">Tier 3</span>
                <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white">Botanical Lawns</h3>
                <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed font-normal">
                    Open-sky manicured gardens with fairy light tree wraps, illuminated water fountains, and custom live BBQ station counters.
                </p>
                <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10 space-y-2 text-xs font-sans text-stone-700 dark:text-stone-300">
                    <div>• Capacity: 250 - 1,500 Guests</div>
                    <div>• Ideal for: Festive Mehndi &amp; Qawwali</div>
                    <div>• Natural Ambience &amp; Fairy Lights</div>
                </div>
            </div>
        </div>
    </section>


    <!-- ===================================================================
         SECTION 6: FREQUENTLY ASKED QUESTIONS
         =================================================================== -->
    <section class="py-16 bg-white dark:bg-[#171513] border-t border-b border-[#E7E5E4] dark:border-white/10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="text-center space-y-2">
                <span class="text-[11px] font-sans uppercase tracking-[0.2em] text-[#8F7239] dark:text-[#C6A15B] font-semibold block">
                    Transparent Booking
                </span>
                <h2 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                    Frequently Asked Questions
                </h2>
            </div>

            <div class="space-y-4">
                <div class="p-6 rounded-xl border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#1C1917] space-y-2">
                    <h4 class="font-serif-luxury text-lg text-[#171513] dark:text-white font-medium">How does single-shift reservation exclusivity work?</h4>
                    <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed font-normal">
                        When you confirm a wedding shift (Afternoon, Evening, or Night), our reservation system immediately locks that exact hall date and shift so no other family or operator can book the same slot.
                    </p>
                </div>

                <div class="p-6 rounded-xl border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#1C1917] space-y-2">
                    <h4 class="font-serif-luxury text-lg text-[#171513] dark:text-white font-medium">Can I bring outside catering or only use hall food?</h4>
                    <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed font-normal">
                        Every hall sets their policy on their dedicated showcase page. Most venues offer premier in-house Mughlai Bawarchi feasts, while some permit outside caterers with a kitchen utility surcharge.
                    </p>
                </div>

                <div class="p-6 rounded-xl border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#1C1917] space-y-2">
                    <h4 class="font-serif-luxury text-lg text-[#171513] dark:text-white font-medium">What is the payment deposit schedule?</h4>
                    <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed font-normal">
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
                <span class="text-[11px] font-sans uppercase tracking-[0.2em] text-[#C6A15B] block font-semibold">
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
                <a href="{{ route('public.contact') }}" class="btn-gold !h-11 !px-6 text-xs font-semibold uppercase font-sans">
                    Speak with Concierge &rarr;
                </a>
                @guest
                    <a href="{{ route('register') }}" class="btn-outline-gold !h-11 !px-5 text-xs font-semibold text-white border-white/20 font-sans">
                        List Your Wedding Hall &rarr;
                    </a>
                @endguest
            </div>
        </div>
    </section>

    <!-- ===================================================================
         SECTION 8: LUXURY SHIFT AVAILABILITY CALENDAR
         =================================================================== -->
    <section class="py-20 bg-[#12100E] text-white border-t border-b border-[#C6A15B]/30 relative overflow-hidden">
        <!-- Subtle ambient glow -->
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(198,161,91,0.06)_0%,transparent_70%)] pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10 relative z-10">
            <!-- Header -->
            <div class="text-center space-y-2 max-w-2xl mx-auto">
                <span class="text-[11px] font-sans uppercase tracking-[0.22em] text-[#C6A15B] font-semibold block">
                    Interactive Booking • Shift Availability
                </span>
                <h2 class="font-serif-luxury text-3xl sm:text-5xl text-white font-normal tracking-wide">
                    Check Hall Availability
                </h2>
                <p class="text-xs sm:text-sm text-stone-300 font-sans font-light">
                    Visual guide and live availability for upcoming wedding dates. Select a date to view all shifts.
                </p>

                @if($calendarHallsList->count() > 1)
                    <div class="pt-3 flex items-center justify-center gap-2">
                        <label for="hall-selector" class="text-[11px] font-sans text-[#C6A15B] uppercase tracking-wider font-semibold">Hall Filter:</label>
                        <select id="hall-selector" wire:model.live="calendarHallId" class="bg-[#18140f] text-stone-200 text-xs border border-[#C6A15B]/40 rounded-lg px-3 py-1.5 focus:border-[#C6A15B] focus:ring-1 focus:ring-[#C6A15B] font-sans">
                            <option value="">All Certified Halls ({{ $city === 'All' ? 'Nationwide' : $city }})</option>
                            @foreach($calendarHallsList as $h)
                                <option value="{{ $h->id }}">{{ $h->name }} ({{ $h->venue->name ?? 'Venue' }})</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            <!-- Main Golden Card Container -->
            <div class="max-w-6xl mx-auto rounded-2xl border border-[#C6A15B]/40 bg-[#171513] shadow-[0_20px_50px_rgba(0,0,0,0.75)] p-5 sm:p-8 lg:p-10">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    
                    <!-- LEFT COLUMN: The Monthly Calendar + Legend (8 cols) -->
                    <div class="lg:col-span-8 space-y-6">
                        
                        <!-- Year Jump Quick Pills (Dynamic Range) -->
                        <div class="flex items-center justify-between flex-wrap gap-2.5 pb-1">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-[11px] font-sans text-[#C6A15B] uppercase font-semibold tracking-wider">Year:</span>
                                @foreach($allowedYears as $yr)
                                    <button type="button" 
                                            wire:click="jumpToYear({{ $yr }})" 
                                            class="px-2.5 py-1 rounded-md text-xs font-sans font-semibold transition cursor-pointer {{ $selectedYear === $yr ? 'bg-[#C6A15B] text-[#16120b]' : 'bg-[#18140f] text-stone-300 border border-[#C6A15B]/30 hover:border-[#C6A15B] hover:text-white' }}">
                                        {{ $yr }}
                                    </button>
                                @endforeach
                            </div>
                            <span class="text-[11px] font-sans text-stone-400">Multi-Year Calendar ({{ min($allowedYears) }}-{{ max($allowedYears) }})</span>
                        </div>

                        <!-- Month Header Golden Banner -->
                        <div class="rounded-xl overflow-hidden border border-[#C6A15B]/40 shadow-md">
                            <div class="bg-gradient-to-r from-[#9e7633] via-[#e2c178] to-[#9e7633] text-[#16120b] py-3.5 px-4 sm:px-6 flex items-center justify-between gap-2">
                                <button type="button" 
                                        wire:click="previousMonth" 
                                        @if($selectedYear === now()->year && $selectedMonthNumber === now()->month) disabled @endif
                                        aria-label="Previous month"
                                        class="w-9 h-9 rounded-full flex items-center justify-center text-xl font-bold hover:bg-black/10 active:scale-95 transition cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed"
                                        title="Previous Month">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                
                                <div class="flex items-center justify-center gap-1 sm:gap-2 text-[#16120b]">
                                    <!-- Month Dropdown -->
                                    <select wire:model.live="selectedMonthNumber" 
                                            class="bg-black/10 hover:bg-black/20 text-[#16120b] font-serif-luxury font-bold text-base sm:text-2xl uppercase tracking-wider border-none rounded-md px-2 py-0.5 focus:ring-0 cursor-pointer">
                                        <option value="1">January</option>
                                        <option value="2">February</option>
                                        <option value="3">March</option>
                                        <option value="4">April</option>
                                        <option value="5">May</option>
                                        <option value="6">June</option>
                                        <option value="7">July</option>
                                        <option value="8">August</option>
                                        <option value="9">September</option>
                                        <option value="10">October</option>
                                        <option value="11">November</option>
                                        <option value="12">December</option>
                                    </select>

                                    <!-- Year Dropdown (Up to {{ max($allowedYears) }}) -->
                                    <select wire:model.live="selectedYear" 
                                            aria-label="Select calendar year"
                                            class="bg-black/10 hover:bg-black/20 text-[#16120b] font-serif-luxury font-bold text-base sm:text-2xl uppercase tracking-wider border-none rounded-md px-2 py-0.5 focus:ring-0 cursor-pointer">
                                        @foreach($allowedYears as $yr)
                                            <option value="{{ $yr }}">{{ $yr }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <button type="button" 
                                        wire:click="nextMonth" 
                                        @if($selectedYear === max($allowedYears) && $selectedMonthNumber === 12) disabled @endif
                                        aria-label="Next month"
                                        class="w-9 h-9 rounded-full flex items-center justify-center text-xl font-bold hover:bg-black/10 active:scale-95 transition cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed"
                                        title="Next Month">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>

                            <!-- Calendar Grid Container (Premium) -->
                            <div class="bg-gradient-to-b from-[#1a1610] to-[#120f0b] p-3 sm:p-5 rounded-2xl border border-[#C6A15B]/20 shadow-[inset_0_1px_0_rgba(198,161,91,0.08)]">
                                <!-- Day Names Row -->
                                <div class="grid grid-cols-7 gap-1 sm:gap-1.5 mb-2.5 text-center">
                                    @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dow)
                                        <span class="text-[10px] sm:text-[11px] font-sans font-semibold tracking-[0.18em] text-[#C6A15B]/70 uppercase py-1">{{ $dow }}</span>
                                    @endforeach
                                </div>

                                <!-- Days Grid -->
                                <div class="grid grid-cols-7 gap-1 sm:gap-1.5">
                                    <!-- Leading blank days -->
                                    @for($i = 0; $i < $startDayOfWeek; $i++)
                                        <div class="min-h-[60px] sm:min-h-[72px] rounded-xl border border-transparent"></div>
                                    @endfor

                                    <!-- Actual Days -->
                                    @foreach($monthGrid as $cell)
                                        @php
                                            $isSelected = ($selectedCalendarDate === $cell['date']);
                                        @endphp
                                        <button type="button"
                                                wire:click="selectCalendarDate('{{ $cell['date'] }}')"
                                                class="group min-h-[60px] sm:min-h-[72px] rounded-xl p-2 flex flex-col items-center justify-between text-center transition-all duration-200 cursor-pointer select-none
                                                    {{ $isSelected
                                                        ? 'border-2 border-[#C6A15B] bg-gradient-to-b from-[#2a2217] to-[#1a1610] shadow-[0_0_20px_rgba(198,161,91,0.35)] scale-[1.02]'
                                                        : 'border border-white/5 bg-[#17130e] hover:border-[#C6A15B]/50 hover:bg-[#221c14] hover:-translate-y-0.5 hover:shadow-[0_6px_16px_rgba(0,0,0,0.4)]'
                                                    }}">
                                            <!-- Date Number -->
                                            <span class="text-sm sm:text-base font-semibold leading-none {{ $isSelected ? 'text-[#fcedaa]' : 'text-stone-200 group-hover:text-white' }}">
                                                {{ $cell['day'] }}
                                            </span>

                                            <!-- Shift Indicators -->
                                            @if($cell['all_booked'])
                                                <div class="flex items-center justify-center gap-0.5 mt-1">
                                                    <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-red-500"></span>
                                                    <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-red-500"></span>
                                                    <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-red-500"></span>
                                                </div>
                                            @else
                                                <div class="flex items-center justify-center gap-1 mt-1">
                                                    <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full {{ $cell['afternoon_available'] ? 'bg-emerald-400' : 'bg-red-500' }}" title="Afternoon"></span>
                                                    <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full {{ $cell['evening_available'] ? 'bg-emerald-400' : 'bg-red-500' }}" title="Evening"></span>
                                                    <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full {{ $cell['night_available'] ? 'bg-emerald-400' : 'bg-red-500' }}" title="Night"></span>
                                                </div>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- LEGEND Box -->
                        <div class="w-full max-w-xs border border-[#C6A15B]/40 rounded-xl overflow-hidden bg-[#17130e] shadow-md">
                            <div class="bg-gradient-to-r from-[#9e7633] via-[#dfbe74] to-[#9e7633] text-[#16120b] font-bold text-[10px] tracking-widest font-sans uppercase px-3.5 py-1.5 text-center">
                                LEGEND
                            </div>
                            <div class="p-3 space-y-1.5 text-[11px] font-sans text-stone-300">
                                <div class="flex items-center gap-2">
                                    <svg class="w-3.5 h-3.5 text-[#C6A15B]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32l1.41-1.41"/></svg>
                                    <span>Afternoon (11 AM - 3 PM)</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-3.5 h-3.5 text-[#C6A15B]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a5 5 0 015 5H7a5 5 0 015-5zm-9 9h18M5 16h14M8 20h8"/></svg>
                                    <span>Evening (4 PM - 8 PM)</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-3.5 h-3.5 text-[#C6A15B]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
                                    <span>Night (9 PM - 1 AM)</span>
                                </div>
                                <div class="flex items-center gap-4 pt-2 border-t border-[#C6A15B]/20 font-sans text-[11px]">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full inline-block"></span>
                                        <span class="text-emerald-400 font-semibold">Available</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 bg-red-600 rounded-full inline-block"></span>
                                        <span class="text-red-400 font-semibold">Booked</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- RIGHT COLUMN: Selected Day Shifts Panel (4 cols) -->
                    <div class="lg:col-span-4 space-y-5">
                        
                        <!-- Selected Day Header -->
                        <div class="border-b border-[#C6A15B]/30 pb-3">
                            <span class="text-[11px] uppercase font-sans tracking-widest text-[#C6A15B] font-semibold block">Selected Day Shifts</span>
                            <h3 class="font-serif-luxury text-2xl sm:text-3xl font-medium text-white tracking-wide">
                                {{ $selectedDayDetails['formattedDate'] }}
                            </h3>
                        </div>

                        <!-- 3 Shift Cards -->
                        <div class="space-y-4">
                            
                            <!-- AFTERNOON SHIFT -->
                            <div class="border border-[#C6A15B]/35 rounded-xl bg-[#17130e] p-4 sm:p-5 space-y-3.5 shadow-md hover:border-[#dfbe74]/60 transition">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-serif-luxury text-base font-bold text-white tracking-wider">AFTERNOON</h4>
                                            <svg class="w-4 h-4 text-[#C6A15B]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32l1.41-1.41"/></svg>
                                        </div>
                                        <span class="text-[11px] font-sans text-stone-400 block mt-0.5">11 AM - 3 PM</span>
                                    </div>
                                    @if($selectedDayDetails['afternoon']['isBooked'])
                                        <span class="px-2.5 py-0.5 rounded-md text-[10px] font-sans font-semibold uppercase tracking-wider bg-red-950/80 border border-red-500/80 text-red-400">
                                            BOOKED
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-md text-[10px] font-sans font-semibold uppercase tracking-wider bg-emerald-950/80 border border-emerald-500/80 text-emerald-400">
                                            AVAILABLE
                                        </span>
                                    @endif
                                </div>
                                <button type="button" 
                                        wire:click="triggerBooking('AFTERNOON', {{ $selectedDayDetails['afternoon']['isBooked'] ? 'true' : 'false' }})"
                                        class="w-full py-2.5 px-4 rounded-lg font-sans font-semibold text-xs uppercase tracking-wider transition shadow cursor-pointer
                                            {{ $selectedDayDetails['afternoon']['isBooked'] 
                                                ? 'bg-gradient-to-r from-[#7a5823] via-[#b39556] to-[#7a5823] text-stone-200 hover:brightness-110' 
                                                : 'bg-gradient-to-r from-[#9e7633] via-[#dfbe74] to-[#9e7633] text-[#16120b] hover:brightness-110 active:scale-98' 
                                            }}">
                                    BOOK NOW
                                </button>
                            </div>

                            <!-- EVENING SHIFT -->
                            <div class="border border-[#C6A15B]/35 rounded-xl bg-[#17130e] p-4 sm:p-5 space-y-3.5 shadow-md hover:border-[#dfbe74]/60 transition">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-serif-luxury text-base font-bold text-white tracking-wider">EVENING</h4>
                                            <svg class="w-4 h-4 text-[#C6A15B]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a5 5 0 015 5H7a5 5 0 015-5zm-9 9h18M5 16h14M8 20h8"/></svg>
                                        </div>
                                        <span class="text-[11px] font-sans text-stone-400 block mt-0.5">4 PM - 8 PM</span>
                                    </div>
                                    @if($selectedDayDetails['evening']['isBooked'])
                                        <span class="px-2.5 py-0.5 rounded-md text-[10px] font-sans font-semibold uppercase tracking-wider bg-red-950/80 border border-red-500/80 text-red-400">
                                            BOOKED
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-md text-[10px] font-sans font-semibold uppercase tracking-wider bg-emerald-950/80 border border-emerald-500/80 text-emerald-400">
                                            AVAILABLE
                                        </span>
                                    @endif
                                </div>
                                <button type="button" 
                                        wire:click="triggerBooking('EVENING', {{ $selectedDayDetails['evening']['isBooked'] ? 'true' : 'false' }})"
                                        class="w-full py-2.5 px-4 rounded-lg font-sans font-semibold text-xs uppercase tracking-wider transition shadow cursor-pointer
                                            {{ $selectedDayDetails['evening']['isBooked'] 
                                                ? 'bg-gradient-to-r from-[#7a5823] via-[#b39556] to-[#7a5823] text-stone-200 hover:brightness-110' 
                                                : 'bg-gradient-to-r from-[#9e7633] via-[#dfbe74] to-[#9e7633] text-[#16120b] hover:brightness-110 active:scale-98' 
                                            }}">
                                    BOOK NOW
                                </button>
                            </div>

                            <!-- NIGHT SHIFT -->
                            <div class="border border-[#C6A15B]/35 rounded-xl bg-[#17130e] p-4 sm:p-5 space-y-3.5 shadow-md hover:border-[#dfbe74]/60 transition">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-serif-luxury text-base font-bold text-white tracking-wider">NIGHT</h4>
                                            <svg class="w-4 h-4 text-[#C6A15B]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
                                        </div>
                                        <span class="text-[11px] font-sans text-stone-400 block mt-0.5">9 PM - 1 AM</span>
                                    </div>
                                    @if($selectedDayDetails['night']['isBooked'])
                                        <span class="px-2.5 py-0.5 rounded-md text-[10px] font-sans font-semibold uppercase tracking-wider bg-red-950/80 border border-red-500/80 text-red-400">
                                            BOOKED
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-md text-[10px] font-sans font-semibold uppercase tracking-wider bg-emerald-950/80 border border-emerald-500/80 text-emerald-400">
                                            AVAILABLE
                                        </span>
                                    @endif
                                </div>
                                <button type="button" 
                                        wire:click="triggerBooking('NIGHT', {{ $selectedDayDetails['night']['isBooked'] ? 'true' : 'false' }})"
                                        class="w-full py-2.5 px-4 rounded-lg font-sans font-semibold text-xs uppercase tracking-wider transition shadow cursor-pointer
                                            {{ $selectedDayDetails['night']['isBooked'] 
                                                ? 'bg-gradient-to-r from-[#7a5823] via-[#b39556] to-[#7a5823] text-stone-200 hover:brightness-110' 
                                                : 'bg-gradient-to-r from-[#9e7633] via-[#dfbe74] to-[#9e7633] text-[#16120b] hover:brightness-110 active:scale-98' 
                                            }}">
                                    BOOK NOW
                                </button>
                            </div>

                        </div>

                    </div>

                </div>
            </div>
        </div>
    </section>
</div>
