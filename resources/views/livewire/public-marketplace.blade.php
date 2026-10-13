<div class="bg-[#F8F5EF] dark:bg-[#171513] text-[#171513] dark:text-[#F8F5EF] min-h-screen transition-colors">

    {{-- ===================================================================
         SECTION 1: PAGE HERO & ARCHITECTURAL HEADLINE
         =================================================================== --}}
    <section class="parallax-window relative bg-[#171513] text-white py-20 border-b border-[#C6A15B]/30 overflow-hidden">
        <div class="parallax-bg opacity-30" data-parallax="0.25"
             style="background-image: url('https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=2000&auto=format&fit=crop');">
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#171513] via-[#171513]/75 to-[#171513]/90"></div>

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
                <h2 class="font-serif-luxury text-3xl text-[#171513] dark:text-white font-normal">Featured Wedding Halls &amp; Lawns ({{ $halls->count() }})</h2>
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
                @endphp
                <div class="editorial-card flex flex-col justify-between group overflow-hidden bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 hover:border-[#C6A15B] dark:hover:border-[#C6A15B] shadow-sm hover:shadow-md transition">
                    <div>
                        <!-- Aspect 16:10 Photo Banner -->
                        <div class="relative aspect-[16/10] overflow-hidden bg-stone-900">
                            <img src="{{ $coverImg }}"
                                 alt="{{ $hall->name }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-black/20"></div>

                            <!-- Location Badge -->
                            <span class="absolute top-4 left-4 px-3 py-1 bg-[#171513]/90 text-white text-[10px] font-bold uppercase tracking-wider border border-[#C6A15B]/30 font-mono">
                                {{ $hall->venue->city ?? 'Lahore' }} • {{ $hall->venue->area ?? 'Gulberg' }}
                            </span>

                            <!-- Capacity Badge -->
                            <span class="absolute bottom-4 left-4 px-2.5 py-1 bg-black/80 text-stone-200 text-xs font-medium border border-white/10 font-mono">
                                {{ $hall->min_capacity }} - {{ $hall->max_capacity }} Guests
                            </span>

                            @if($hasVideo)
                                <span class="absolute top-4 right-4 px-2.5 py-1 bg-[#C6A15B] text-[#171513] text-[10px] font-bold uppercase tracking-wider flex items-center gap-1 shadow font-mono">
                                    <svg class="w-3 h-3 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    4K Tour
                                </span>
                            @endif
                        </div>

                        <!-- Content Block -->
                        <div class="p-6 space-y-3">
                            <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-medium leading-snug">
                                <a href="{{ route('public.venue.details', $hall->id) }}" class="hover:text-[#8F7239] dark:hover:text-[#C6A15B] transition">
                                    {{ $hall->name }}
                                </a>
                            </h3>
                            <p class="text-xs text-stone-600 dark:text-stone-300 font-normal leading-relaxed line-clamp-2">
                                {{ $hall->description ?? 'Spectacular 5-star wedding banquet ballroom with crystal chandeliers, climate control, and power backup.' }}
                            </p>

                            <!-- Amenities Strip -->
                            <div class="pt-3 border-t border-[#E7E5E4] dark:border-white/10 flex flex-wrap gap-2 text-[10px] text-stone-600 dark:text-stone-300 font-semibold uppercase tracking-wider font-mono">
                                @if($hall->has_air_conditioning)
                                    <span class="px-2 py-1 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10">Central AC</span>
                                @endif
                                @if($hall->has_generator_backup)
                                    <span class="px-2 py-1 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10">100% Generator</span>
                                @endif
                                @if($hall->has_bridal_room)
                                    <span class="px-2 py-1 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10">Bridal Lounge</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Card Action Footer -->
                    <div class="p-6 pt-0 border-t border-[#E7E5E4] dark:border-white/10 flex items-center justify-between mt-4">
                        <div>
                            <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 block font-mono">BASE SHIFT RENT</span>
                            <strong class="font-cinzel text-lg text-[#171513] dark:text-[#C6A15B]">PKR {{ number_format($hall->base_rent) }}</strong>
                        </div>
                        <button type="button"
                                onclick="Livewire.dispatch('openWalkInModal', { hall_id: '{{ $hall->id }}' })"
                                class="btn-obsidian !h-9 !px-4 text-xs font-semibold font-mono">
                            Reserve Date &rarr;
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-16 text-center border border-dashed border-[#E7E5E4] dark:border-white/15">
                    <span class="text-[10px] tracking-[0.22em] text-[#8F7239] dark:text-[#C6A15B] uppercase font-semibold">No Matches</span>
                    <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white mt-2">No halls match your current filters.</h3>
                    <p class="text-sm text-stone-500 dark:text-stone-400 mt-2">Try widening your search or lowering the minimum capacity.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>