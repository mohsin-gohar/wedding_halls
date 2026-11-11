<div class="bg-[#F8F5EF] dark:bg-[#171513] text-[#171513] dark:text-[#F8F5EF] min-h-screen transition-colors">

    {{-- ===================================================================
         SECTION 1: PAGE HERO & ARCHITECTURAL HEADLINE
         =================================================================== --}}
    <section class="parallax-window relative bg-[#171513] text-white py-20 border-b border-[#C6A15B]/30 overflow-hidden">
        <div class="parallax-bg opacity-75" data-parallax="0.25"
             style="background-image: url('https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=2000&auto=format&fit=crop');">
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#171513] via-[#171513]/40 to-[#171513]/65"></div>
        <div class="absolute inset-0 bg-black/20"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 text-center">
            <div class="inline-flex items-center gap-2.5 px-4 py-1.5 border border-[#C6A15B]/50 bg-[#171513]/90 text-[#C6A15B] text-[11px] font-semibold tracking-[0.22em] uppercase font-mono">
                <span class="w-1.5 h-1.5 bg-[#C6A15B]"></span>
                <span>Luxury Banquet Discovery</span>
            </div>

            <h1 class="font-serif-luxury text-4xl sm:text-6xl text-white font-normal max-w-4xl mx-auto leading-tight">
                Find &amp; Reserve Pakistan's <br class="hidden sm:block">
                <span class="italic text-[#C6A15B]">Premier Wedding Spaces</span>
            </h1>

            <p class="text-sm sm:text-base text-stone-300 max-w-2xl mx-auto font-light leading-relaxed">
                Explore high-ceiling crystal ballrooms, open marquees, and botanical wedding lawns with real-time shift availability and verified catering menus.
            </p>
        </div>
    </section>


    {{-- ===================================================================
         SECTION 2: COMPREHENSIVE MULTI-FILTER BAR
         =================================================================== --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-8 relative z-20">
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 sm:p-8 shadow-xl space-y-5">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                {{-- Search Keyword --}}
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-stone-700 dark:text-stone-200 mb-1.5 font-mono">Search Hall / Area</label>
                    <input type="text"
                           wire:model.live.debounce.300ms="searchQuery"
                           placeholder="e.g. Royal Ballroom, Gulberg..."
                           class="input-luxury text-sm">
                </div>

                {{-- City --}}
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-stone-700 dark:text-stone-200 mb-1.5 font-mono">City</label>
                    <select wire:model.live="city" class="input-luxury text-sm">
                        <option value="All">All Cities (Pakistan)</option>
                        @foreach($cities as $c)
                            <option value="{{ $c }}">{{ $c }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Minimum Capacity --}}
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-stone-700 dark:text-stone-200 mb-1.5 font-mono">
                        Minimum Capacity: <span class="text-[#8F7239] dark:text-[#C6A15B]">{{ $minCapacity }}+</span> Guests
                    </label>
                    <input type="range" wire:model.live="minCapacity" min="100" max="1500" step="50" class="w-full accent-[#C6A15B] mt-3">
                </div>
            </div>

        </div>
    </section>


    {{-- ===================================================================
         SECTION 3: FEATURED HALLS SHOWCASE
         =================================================================== --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 space-y-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-[#E7E5E4] dark:border-white/15 gap-2">
            <div>
                <span class="text-[10px] tracking-[0.22em] text-[#8F7239] dark:text-[#C6A15B] uppercase font-semibold">Available Spaces</span>
                <h2 class="font-serif-luxury text-3xl text-[#171513] dark:text-white font-normal">Featured Wedding Halls &amp; Lawns ({{ $halls->total() }})</h2>
            </div>
            <a href="{{ route('public.availability') }}" class="btn-outline-gold !text-xs !py-2 !px-4">
                CHECK SHIFT AVAILABILITY &rarr;
            </a>
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
                <a href="{{ route('hall.showcase', $hall->slug) }}"
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
                            <h3 class="font-serif-luxury text-xl sm:text-2xl text-[#171513] dark:text-white font-medium leading-snug group-hover:text-[#8F7239] dark:group-hover:text-[#C6A15B] transition-colors">
                                {{ $hall->name }}
                            </h3>

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
                <div class="col-span-full py-16 text-center border border-dashed border-[#E7E5E4] dark:border-white/15">
                    <span class="text-[10px] tracking-[0.22em] text-[#8F7239] dark:text-[#C6A15B] uppercase font-semibold">No Matches</span>
                    <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white mt-2">No halls match your current filters.</h3>
                    <p class="text-sm text-stone-500 dark:text-stone-400 mt-2">Try widening your search or lowering the minimum capacity.</p>
                </div>
            @endforelse
        </div>

        <div class="pt-6">
            {{ $halls->links() }}
        </div>
    </div>
</div>
