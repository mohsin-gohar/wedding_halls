<div class="bg-[#F8F5EF] dark:bg-[#171513] text-[#171513] dark:text-[#F8F5EF] min-h-screen transition-colors">

    <!-- ===================================================================
         SECTION 1: PAGE HERO & ARCHITECTURAL HEADLINE
         =================================================================== -->
    <section class="parallax-window relative bg-[#171513] text-white py-20 border-b border-[#C6A15B]/30 overflow-hidden">
        <div class="parallax-bg opacity-30" data-parallax="0.25"
             style="background-image: url('https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=2000&auto=format&fit=crop');">
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#171513] via-[#171513]/75 to-[#171513]/90"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 text-center">
            <div class="inline-flex items-center gap-2.5 px-4 py-1.5 border border-[#C6A15B]/50 bg-[#171513]/90 text-[#C6A15B] text-[11px] font-semibold tracking-[0.22em] uppercase font-mono">
                <span class="w-1.5 h-1.5 bg-[#C6A15B]"></span>
                <span>Verified Pakistani Wedding Halls &amp; Royal Marquees</span>
            </div>

            <h1 class="font-serif-luxury text-4xl sm:text-6xl text-white font-normal max-w-4xl mx-auto leading-tight">
                Explore Prestigious Banquet Venues
            </h1>

            <p class="text-sm sm:text-base text-stone-300 max-w-2xl mx-auto font-light leading-relaxed">
                Filter by Pakistani city, guest capacity, event date, shift availability, and budget. Every hall is backed by atomic concurrency protection and real persistent database records.
            </p>
        </div>
    </section>


    <!-- ===================================================================
         SECTION 2: COMPREHENSIVE MULTI-FILTER BAR
         =================================================================== -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-8 relative z-20">
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 sm:p-8 shadow-xl space-y-4">
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Search Keyword -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-stone-700 mb-1.5 font-mono">Keyword Search</label>
                    <input type="text" 
                           wire:model.live.debounce.300ms="search" 
                           placeholder="Hall name, area, or landmark..." 
                           class="input-luxury text-sm">
                </div>

                <!-- City -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-stone-700 mb-1.5 font-mono">City</label>
                    <select wire:model.live="city" class="input-luxury text-sm">
                        <option value="All">All Cities (Pakistan)</option>
                        <option value="Lahore">Lahore</option>
                        <option value="Karachi">Karachi</option>
                        <option value="Islamabad">Islamabad</option>
                        <option value="Rawalpindi">Rawalpindi</option>
                        <option value="Faisalabad">Faisalabad</option>
                        <option value="Multan">Multan</option>
                        <option value="Peshawar">Peshawar</option>
                        <option value="Sialkot">Sialkot</option>
                    </select>
                </div>

                <!-- Event Date -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-stone-700 mb-1.5 font-mono">Event Date</label>
                    <input type="date" wire:model.live="date" class="input-luxury text-sm">
                </div>

                <!-- Shift -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-stone-700 mb-1.5 font-mono">Shift</label>
                    <select wire:model.live="shift" class="input-luxury text-sm">
                        <option value="ALL">Any Shift</option>
                        <option value="AFTERNOON">Afternoon Lunch (12:00 PM - 4:00 PM)</option>
                        <option value="EVENING">Evening Dinner (6:30 PM - 10:30 PM)</option>
                        <option value="NIGHT">Late Night Mehndi (11:00 PM - 3:00 AM)</option>
                    </select>
                </div>
            </div>

            <!-- Secondary Filters: Capacity, Facilities & Reset -->
            <div class="pt-4 border-t border-[#E7E5E4] flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-6">
                    <!-- Min Capacity -->
                    <div class="flex items-center gap-2 text-xs font-mono">
                        <span class="font-bold text-stone-700 uppercase tracking-wider text-[11px]">Min Guests:</span>
                        <select wire:model.live="minCapacity" class="border border-[#E7E5E4] bg-[#F8F5EF] px-2.5 py-1 text-xs font-semibold">
                            <option value="0">Any Capacity</option>
                            <option value="150">150+ Guests</option>
                            <option value="300">300+ Guests</option>
                            <option value="500">500+ Guests</option>
                            <option value="800">800+ Guests</option>
                            <option value="1000">1,000+ Guests</option>
                        </select>
                    </div>

                    <!-- Facilities Switches -->
                    <label class="flex items-center gap-2 text-xs text-stone-700 cursor-pointer font-mono">
                        <input type="checkbox" wire:model.live="hasAc" class="accent-[#C6A15B]">
                        <span>Central AC</span>
                    </label>

                    <label class="flex items-center gap-2 text-xs text-stone-700 cursor-pointer font-mono">
                        <input type="checkbox" wire:model.live="hasGenerator" class="accent-[#C6A15B]">
                        <span>100% Generator</span>
                    </label>

                    <label class="flex items-center gap-2 text-xs text-stone-700 cursor-pointer font-mono">
                        <input type="checkbox" wire:model.live="hasBridal" class="accent-[#C6A15B]">
                        <span>Bridal Room</span>
                    </label>
                </div>

                <!-- Sort & Clear -->
                <div class="flex items-center gap-3">
                    <select wire:model.live="sort" class="border border-[#E7E5E4] bg-[#F8F5EF] px-3 py-1.5 text-xs font-semibold font-mono">
                        <option value="popular">Sort: Featured</option>
                        <option value="price_asc">Price: Low to High</option>
                        <option value="price_desc">Price: High to Low</option>
                        <option value="capacity_desc">Capacity: Highest First</option>
                    </select>

                    <button wire:click="resetFilters" class="text-xs text-stone-500 hover:text-stone-900 underline font-semibold font-mono cursor-pointer">
                        Reset Filters
                    </button>
                </div>
            </div>

        </div>
    </section>


    <!-- ===================================================================
         SECTION 3: VERIFIED HALLS DIRECTORY & REAL-TIME AVAILABILITY
         =================================================================== -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 space-y-6">
        <div class="flex items-center justify-between text-xs text-stone-500 border-b border-[#E7E5E4] pb-3">
            <span>Showing <strong>{{ $halls->total() }}</strong> verified banquet halls</span>
            @if(!empty($date) && $shift !== 'ALL')
                <span class="text-[#8F7239] font-semibold font-mono">
                    ✓ Filtered for available slots on {{ \Carbon\Carbon::parse($date)->format('d M Y') }} ({{ $shift }})
                </span>
            @endif
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
                    <div class="p-6 pt-0 border-t border-[#E7E5E4] dark:border-white/10 flex items-center justify-between gap-3 mt-4">
                        <div>
                            <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 block font-mono">BASE SHIFT RENT</span>
                            <strong class="font-cinzel text-lg text-[#171513] dark:text-[#C6A15B]">PKR {{ number_format($hall->base_rent) }}</strong>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button"
                                    onclick="Livewire.dispatch('openWalkInModal', { hall_id: '{{ $hall->id }}' })"
                                    class="btn-gold !h-9 !px-4 text-xs font-semibold font-mono">
                                Reserve
                            </button>
                            <a href="{{ route('public.venue.details', $hall->id) }}" class="btn-obsidian !h-9 !px-4 text-xs font-semibold font-mono">
                                Details
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-3 text-center py-16 bg-white border border-[#E7E5E4] space-y-3">
                    <p class="font-serif-luxury text-2xl text-stone-800">No Venues Match Your Exact Criteria</p>
                    <p class="text-xs text-stone-500 max-w-md mx-auto">
                        Try broadening your city selection, adjusting capacity requirements, or resetting filters to explore more banquet halls.
                    </p>
                    <div class="pt-2">
                        <button wire:click="resetFilters" class="btn-gold !h-9 text-xs font-mono">
                            Clear All Filters
                        </button>
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
                        VenueCloud uses database atomic transactions (`SELECT FOR UPDATE`). When you confirm a wedding shift (Afternoon, Evening, or Night), our software locks that exact hall date immediately so no other client or operator can book the same slot.
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
                    VenueCloud Concierge
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

</div>
