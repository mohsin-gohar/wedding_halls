<div class="bg-[#F8F5EF] dark:bg-[#171513] text-[#171513] dark:text-[#F8F5EF] min-h-screen">

    @php
        $settings = is_array($hall->website_settings) ? $hall->website_settings : [];
    @endphp

    @if(($settings['show_announcement'] ?? false) && !empty($settings['announcement_banner']))
        <div class="bg-[#C6A15B] text-[#171513] py-2.5 px-4 text-center font-mono text-xs font-bold tracking-wider uppercase flex items-center justify-center gap-2 shadow-md">
            <span>✨</span>
            <span>{{ $settings['announcement_banner'] }}</span>
            <span>✨</span>
        </div>
    @endif

    <!-- ===================================================================
         SECTION 1: CINEMATIC GRAND HERO & IDENTITY
         =================================================================== -->
    <section class="relative bg-[#171513] text-white min-h-[580px] flex items-center justify-center overflow-hidden border-b border-[#C6A15B]/30">
        <!-- Parallax Cover Image -->
        <div class="absolute inset-0 bg-cover bg-center bg-fixed opacity-75 scale-100"
             style="background-image: url('{{ $activePhoto }}');">
        </div>
        <!-- Gradient Overlays: Subtle radial and vertical vignette for clear visibility + readability -->
        <div class="absolute inset-0 bg-gradient-to-t from-[#171513] via-[#171513]/40 to-[#171513]/60"></div>
        <div class="absolute inset-0 bg-black/20"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 w-full space-y-6">
            
            <!-- Breadcrumbs & Venue Owner Edit Link -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-white/10 pb-4">
                <nav class="flex items-center gap-2 text-xs font-mono uppercase tracking-wider text-stone-400">
                    <a href="{{ route('home') }}" class="hover:text-white transition">Home</a>
                    <span>/</span>
                    <a href="{{ route('public.venues') }}" class="hover:text-white transition">Venues</a>
                    <span>/</span>
                    <span class="text-[#C6A15B]">{{ $hall->name }}</span>
                </nav>

                @auth
                    @if(auth()->user()->tenant_id === $hall->tenant_id || auth()->user()->isSuperAdmin())
                        <div class="flex items-center gap-3">
                            <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-[#C6A15B] bg-[#C6A15B]/15 px-2.5 py-1 border border-[#C6A15B]/40">
                                Rent Admin Mode
                            </span>
                            <a href="{{ route('hall.media') }}" class="btn-gold !h-8 !px-3 text-xs font-mono">
                                Edit Photos &amp; Services Catalog &rarr;
                            </a>
                        </div>
                    @endif
                @endauth
            </div>

            <!-- Hall Title & Badges -->
            <div class="space-y-4 max-w-4xl">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="px-3 py-1 bg-[#C6A15B] text-[#171513] text-[10px] font-bold uppercase tracking-widest font-mono">
                        Verified Luxury Banquet
                    </span>
                    <span class="px-3 py-1 bg-white/10 text-stone-200 text-[10px] font-mono uppercase tracking-widest border border-white/15">
                        {{ $hall->venue->city ?? 'Lahore' }} • {{ $hall->venue->area ?? 'Gulberg' }}
                    </span>
                    @if(!empty($videos) && count($videos) > 0)
                        <span class="px-3 py-1 bg-[#171513]/90 text-[#F8F5EF] text-[10px] font-mono uppercase tracking-widest border border-[#C6A15B]/45 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-[#C6A15B] animate-pulse"></span>
                            4K Virtual Tour Available
                        </span>
                    @endif
                </div>

                <h1 class="font-serif-luxury text-4xl sm:text-6xl md:text-7xl font-normal text-white leading-tight">
                    {{ $hall->name }}
                </h1>

                @if(!empty($hall->tagline))
                    <p class="text-sm sm:text-lg text-[#C6A15B] font-serif italic max-w-2xl">
                        "{{ $hall->tagline }}"
                    </p>
                @endif

                <p class="text-xs sm:text-sm text-stone-300 font-light max-w-3xl leading-relaxed">
                    {{ $hall->description ?? 'Spectacular wedding banquet ballroom featuring Austrian crystal chandeliers, high-ceiling acoustics, climate control, VIP bridal suites, and standby CAT generators.' }}
                </p>
            </div>

            <!-- Pricing Bar & Quick Concierge CTAs -->
            <div class="pt-6 border-t border-white/10 flex flex-wrap items-center justify-between gap-6">
                <div class="flex items-baseline gap-3">
                    <span class="text-xs font-mono uppercase tracking-wider text-stone-400">Base Shift Rental:</span>
                    <strong class="font-cinzel text-3xl sm:text-4xl text-[#C6A15B] font-bold">
                        PKR {{ number_format($hall->base_rent) }}
                    </strong>
                    <span class="text-xs text-stone-400 font-mono">/ Event Shift</span>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button onclick="Livewire.dispatch('openWalkInModal', { hall_id: '{{ $hall->id }}' })" 
                            class="btn-gold !h-11 !px-6 text-xs font-bold uppercase tracking-wider font-mono shadow-xl">
                        Reserve Event Shift &rarr;
                    </button>

                    @php
                        $rawPhone = $hall->venue->phone ?? $hall->tenant->phone ?? '923008472910';
                        $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
                        if (empty($cleanPhone)) { $cleanPhone = '923008472910'; }
                    @endphp
                    <a href="https://wa.me/{{ $cleanPhone }}?text=Hello%20{{ urlencode($hall->name) }},%20I%20am%20interested%20in%20reserving%20an%20event%20date%20at%20your%20venue.%20Please%20share%20details." 
                       target="_blank"
                       class="btn-outline-gold !h-11 !px-5 text-xs font-semibold text-white border-white/20 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#C6A15B]"></span>
                        <span>WhatsApp Concierge</span>
                    </a>

                    <a href="#shift-matrix" class="px-4 py-2.5 border border-white/20 text-white text-xs font-mono uppercase hover:bg-white/10 transition">
                        View 14-Day Calendar &darr;
                    </a>
                </div>
            </div>

        </div>
    </section>


    <!-- ===================================================================
         SECTION 2: KEY SPECIFICATIONS & ARCHITECTURAL METRICS (4 Opulent Cards)
         =================================================================== -->
    <section class="py-12 border-b border-[#E7E5E4] dark:border-white/10 bg-white dark:bg-[#171513]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                
                <!-- Metric 1: Capacity -->
                <div class="p-6 border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#171513] hover:bg-white dark:hover:bg-[#171513] hover:border-[#C6A15B] hover:shadow-[0_8px_25px_rgba(198, 161, 91,0.12)] transition duration-300 space-y-2 relative overflow-hidden group">
                    <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 transition duration-300"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-mono uppercase font-bold tracking-widest text-[#8F7239]">Banquet Capacity</span>
                        <span class="text-xs">👥</span>
                    </div>
                    <strong class="font-cinzel text-2xl sm:text-3xl text-[#171513] dark:text-white block">
                        {{ $hall->min_capacity }} - {{ $hall->max_capacity }}
                    </strong>
                    <span class="text-[11px] text-stone-500 dark:text-stone-400 font-sans block">Seated Banquet Dining Format</span>
                </div>

                <!-- Metric 2: Climate Control -->
                <div class="p-6 border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#171513] hover:bg-white dark:hover:bg-[#171513] hover:border-[#C6A15B] hover:shadow-[0_8px_25px_rgba(198, 161, 91,0.12)] transition duration-300 space-y-2 relative overflow-hidden group">
                    <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 transition duration-300"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-mono uppercase font-bold tracking-widest text-[#8F7239]">Climate Control</span>
                        <span class="text-xs">❄️</span>
                    </div>
                    <strong class="font-cinzel text-2xl sm:text-3xl text-[#171513] dark:text-white block">
                        {{ $hall->has_air_conditioning ? 'Central Chiller' : 'Natural Ventilation' }}
                    </strong>
                    <span class="text-[11px] text-stone-500 dark:text-stone-400 font-sans block">Zero Thermal Drift Guaranteed</span>
                </div>

                <!-- Metric 3: Generator -->
                <div class="p-6 border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#171513] hover:bg-white dark:hover:bg-[#171513] hover:border-[#C6A15B] hover:shadow-[0_8px_25px_rgba(198, 161, 91,0.12)] transition duration-300 space-y-2 relative overflow-hidden group">
                    <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 transition duration-300"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-mono uppercase font-bold tracking-widest text-[#8F7239]">Power Reliability</span>
                        <span class="text-xs">⚡</span>
                    </div>
                    <strong class="font-cinzel text-2xl sm:text-3xl text-[#171513] dark:text-white block">
                        {{ $hall->has_generator_backup ? '100% Cat Power' : 'Standard Grid' }}
                    </strong>
                    <span class="text-[11px] text-stone-500 dark:text-stone-400 font-sans block">Zero-Flicker Automatic Synchronization</span>
                </div>

                <!-- Metric 4: VIP Suites -->
                <div class="p-6 border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#171513] hover:bg-white dark:hover:bg-[#171513] hover:border-[#C6A15B] hover:shadow-[0_8px_25px_rgba(198, 161, 91,0.12)] transition duration-300 space-y-2 relative overflow-hidden group">
                    <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 transition duration-300"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-mono uppercase font-bold tracking-widest text-[#8F7239]">VIP Staging</span>
                        <span class="text-xs">👑</span>
                    </div>
                    <strong class="font-cinzel text-2xl sm:text-3xl text-[#171513] dark:text-white block">
                        {{ $hall->has_bridal_room ? 'Bridal Suite' : 'Standard Lounge' }}
                    </strong>
                    <span class="text-[11px] text-stone-500 dark:text-stone-400 font-sans block">Private Dressing Vault &amp; Vanity</span>
                </div>

            </div>
        </div>
    </section>


    <!-- ===================================================================
         SECTION 3: HIGH-DEFINITION MEDIA SHOWCASE & 4K VIRTUAL TOUR
         =================================================================== -->
    <section class="py-16 border-b border-[#E7E5E4] dark:border-white/10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
        <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-4 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <span class="text-[10px] font-mono uppercase tracking-[0.2em] text-[#8F7239] block font-bold">Visual Splendor</span>
                <h2 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                    Interactive Photography &amp; 4K Tour
                </h2>
            </div>
            <p class="text-xs text-stone-500 dark:text-stone-400 font-mono">
                Click any gallery photo below to inspect high-resolution hall details
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- Active Spotlight Image (7 cols) -->
            <div class="lg:col-span-7 space-y-4">
                <div class="aspect-[16/10] overflow-hidden relative border border-[#E7E5E4] dark:border-white/10 bg-stone-950 shadow-lg">
                    <img src="{{ $activePhoto }}" alt="{{ $hall->name }}" class="w-full h-full object-cover transition-all duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>
                    <div class="absolute bottom-4 left-4 right-4 flex items-center justify-between text-white text-xs font-mono">
                        <span>{{ $hall->name }} Spotlight Gallery</span>
                        <span class="text-[#C6A15B]">Click thumbnail below to preview</span>
                    </div>
                </div>

                <!-- Thumbnails Strip -->
                @php
                    $displayImages = !empty($images) ? $images : [
                        'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=1200&auto=format&fit=crop',
                        'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=1200&auto=format&fit=crop',
                        'https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?q=80&w=1200&auto=format&fit=crop',
                        'https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=1200&auto=format&fit=crop'
                    ];
                @endphp
                <div class="grid grid-cols-4 sm:grid-cols-6 gap-3">
                    @foreach($displayImages as $photo)
                        <button type="button" wire:click="setPhoto('{{ $photo }}')" 
                                class="aspect-[4/3] overflow-hidden border transition cursor-pointer {{ $activePhoto === $photo ? 'border-[#171513] dark:border-white ring-2 ring-[#C6A15B]' : 'border-[#E7E5E4] dark:border-white/10 opacity-75 hover:opacity-100' }}">
                            <img src="{{ $photo }}" alt="Thumbnail" class="w-full h-full object-cover">
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- 4K Video Walkthrough Embed (5 cols) -->
            <div class="lg:col-span-5 space-y-4">
                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-6 space-y-4 shadow-sm">
                    <div class="flex items-center gap-2 border-b border-[#E7E5E4] dark:border-white/10 pb-3">
                        <span class="w-2.5 h-2.5 bg-[#171513] rounded-full animate-pulse"></span>
                        <h3 class="font-cinzel text-sm font-bold uppercase text-[#171513] dark:text-white tracking-wider">
                            4K Cinematic Video Tour
                        </h3>
                    </div>

                    @if(!empty($videos) && count($videos) > 0)
                        <div class="aspect-video border border-[#E7E5E4] dark:border-white/10 overflow-hidden bg-black">
                            <iframe src="{{ $videos[0] }}" class="w-full h-full" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
                        </div>
                    @else
                        <div class="aspect-video bg-stone-900 border border-[#E7E5E4] dark:border-white/10 flex flex-col items-center justify-center p-6 text-center text-stone-400 space-y-2">
                            <span class="text-3xl">🎥</span>
                            <span class="text-xs font-mono">Video walkthrough being updated by Rent Admin.</span>
                        </div>
                    @endif

                    <div class="space-y-2 pt-2 text-xs text-stone-600 dark:text-stone-300">
                        <div class="flex items-center gap-2">
                            <span class="text-[#8F7239] dark:text-[#C6A15B] font-bold">✓</span>
                            <span>Recorded on-site with cinematic 4K resolution</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[#8F7239] dark:text-[#C6A15B] font-bold">✓</span>
                            <span>True lighting acoustics &amp; ceiling heights</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- ===================================================================
         SECTION 4: BESPOKE SERVICES & PRODUCTS CATALOG (E-Commerce Storefront)
         =================================================================== -->
    <section class="py-16 bg-[#F8F5EF]/50 dark:bg-[#171513] border-b border-[#E7E5E4] dark:border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-[#E7E5E4] dark:border-white/10 pb-4">
                <div>
                    <span class="text-[10px] font-mono uppercase tracking-[0.2em] text-[#8F7239] block font-bold">Bespoke Offerings</span>
                    <h2 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                        Venue Services &amp; Product Add-ons Catalog
                    </h2>
                </div>
                <p class="text-xs text-stone-600 dark:text-stone-400 font-sans max-w-md">
                    Customized directly by {{ $hall->name }}'s Rent Admin. Reserve these optional luxury amenities and decor enhancements for your event.
                </p>
            </div>

            @php
                $catalogServices = is_array($hall->services) && count($hall->services) > 0 ? $hall->services : [
                    [
                        'title' => 'VIP Bridal Luxury Dressing Suite',
                        'price' => 25000,
                        'is_complimentary' => true,
                        'category' => 'VIP Hospitality',
                        'description' => 'Private air-conditioned suite with illuminated Hollywood makeup mirror and dedicated dressing concierge.',
                        'icon' => 'sparkles'
                    ],
                    [
                        'title' => '500 kVA Caterpillar Standby Power Generator',
                        'price' => 35000,
                        'is_complimentary' => true,
                        'category' => 'Infrastructure',
                        'description' => 'Zero-flicker automatic changeover with diesel backup covering 100% chiller AC load.',
                        'icon' => 'bolt'
                    ],
                    [
                        'title' => 'Bespoke Fresh Rose Stage Decor & Walkway',
                        'price' => 85000,
                        'is_complimentary' => false,
                        'category' => 'Decor & Ambience',
                        'description' => 'Fresh imported gladiolus, red roses, golden filigree stage sofa, and floral aisle runners.',
                        'icon' => 'star'
                    ],
                    [
                        'title' => 'Executive Chauffeur Valet Parking Service',
                        'price' => 20000,
                        'is_complimentary' => false,
                        'category' => 'Guest Logistics',
                        'description' => 'Dedicated 8-member uniformed valet team with electronic key logging and 250+ paved parking.',
                        'icon' => 'truck'
                    ]
                ];
            @endphp

            <div class="space-y-4">
                @foreach($catalogServices as $srv)
                    <div class="editorial-card p-5 sm:p-6 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 hover:border-[#C6A15B]/60 dark:hover:border-[#C6A15B]/50 transition-all duration-300 shadow-xs hover:shadow-md flex flex-col md:flex-row md:items-center justify-between gap-5 group">
                        <!-- Left & Middle: Icon + Meta + Details -->
                        <div class="flex items-start sm:items-center gap-4 sm:gap-5 flex-1 min-w-0">
                            <!-- Icon Box -->
                            <div class="w-12 h-12 shrink-0 bg-[#F8F5EF] dark:bg-white/5 border border-[#C6A15B]/50 text-[#C6A15B] flex items-center justify-center text-xl shadow-xs group-hover:scale-105 group-hover:border-[#C6A15B] transition-transform">
                                @if(($srv['icon'] ?? '') === 'bolt') ⚡
                                @elseif(($srv['icon'] ?? '') === 'camera') 🎥
                                @elseif(($srv['icon'] ?? '') === 'truck') 🚗
                                @elseif(($srv['icon'] ?? '') === 'star') ⭐
                                @else ✨
                                @endif
                            </div>

                            <!-- Details -->
                            <div class="space-y-1.5 flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-serif-luxury text-lg sm:text-xl text-[#171513] dark:text-white font-medium group-hover:text-[#8F7239] dark:group-hover:text-[#C6A15B] transition-colors truncate">
                                        {{ $srv['title'] ?? 'Custom Service' }}
                                    </h3>
                                    <span class="text-[9px] font-mono uppercase px-2 py-0.5 bg-[#F8F5EF] dark:bg-white/10 border border-[#E7E5E4] dark:border-white/15 font-semibold text-stone-600 dark:text-stone-300 tracking-wider">
                                        {{ $srv['category'] ?? 'Service' }}
                                    </span>
                                </div>
                                <p class="text-xs sm:text-sm text-stone-600 dark:text-stone-300 font-normal leading-relaxed max-w-3xl">
                                    {{ $srv['description'] ?? 'Premium wedding amenity customized by banquet management.' }}
                                </p>
                            </div>
                        </div>

                        <!-- Right: Pricing & Action -->
                        <div class="pt-4 md:pt-0 border-t md:border-t-0 md:border-l border-[#E7E5E4] dark:border-white/10 md:pl-6 flex items-center justify-between md:justify-end gap-5 shrink-0">
                            <div class="text-left md:text-right">
                                <span class="text-[9px] uppercase font-bold text-stone-400 font-mono block tracking-wider">PRICING</span>
                                @if(!empty($srv['is_complimentary']))
                                    <span class="inline-block text-xs font-bold font-mono text-[#8F7239] dark:text-[#C6A15B] bg-[#C6A15B]/15 dark:bg-[#171513]/85 px-2.5 py-0.5 border border-[#C6A15B]/60 dark:border-[#C6A15B]/70 tracking-wider">
                                        COMPLIMENTARY
                                    </span>
                                @else
                                    <strong class="font-cinzel text-lg sm:text-xl text-[#171513] dark:text-white tracking-wide">
                                        PKR {{ number_format($srv['price'] ?? 0) }}
                                    </strong>
                                @endif
                            </div>

                            <button onclick="Livewire.dispatch('openWalkInModal', { hall_id: '{{ $hall->id }}' })" 
                                    class="btn-obsidian dark:bg-white/10 dark:text-white dark:border-white/20 dark:hover:bg-[#C6A15B] dark:hover:text-[#171513] !h-9 !px-4 text-xs font-mono tracking-wider flex items-center gap-1.5 whitespace-nowrap shadow-xs hover:shadow transition">
                                <span>Inquire</span>
                                <span>&rarr;</span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

        </div>
    </section>


    <!-- ===================================================================
         SECTION 5: CATERING PACKAGES & INTERACTIVE HEADCOUNT ESTIMATOR
         =================================================================== -->
    <section class="py-16 border-b border-[#E7E5E4] dark:border-white/10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
        <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-4 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <span class="text-[10px] font-mono uppercase tracking-[0.2em] text-[#8F7239] block font-bold">Pakistani Bawarchi Feasts</span>
                <h2 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                    Banquet Catering &amp; Per-Head Menus
                </h2>
            </div>
            <p class="text-xs text-stone-500 dark:text-stone-400 font-mono">
                Five-star Mughlai recipes with standardized KOT kitchen ingredient ratios
            </p>
        </div>

        <!-- 3 Packages Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($packages as $pkg)
                <div class="editorial-card p-6 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-5 flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-[#E7E5E4] dark:border-white/10">
                            <span class="text-[10px] font-mono uppercase font-bold text-[#8F7239]">Pakistani Feast</span>
                            <span class="text-[11px] font-mono text-stone-500 dark:text-stone-400">Min {{ $pkg->min_guests }} Heads</span>
                        </div>

                        <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-medium">{{ $pkg->name }}</h3>
                        
                        <div class="flex items-baseline gap-2">
                            <span class="font-cinzel text-2xl font-bold text-[#171513] dark:text-white">PKR {{ number_format($pkg->per_head_price) }}</span>
                            <span class="text-xs text-stone-500 dark:text-stone-400 font-mono">/ Guest</span>
                        </div>

                        <p class="text-xs text-stone-600 dark:text-stone-300 font-normal leading-relaxed">
                            {{ $pkg->description }}
                        </p>

                        @if(is_array($pkg->menu_items_json))
                            <div class="pt-3 border-t border-[#E7E5E4] dark:border-white/10 space-y-2 text-xs">
                                @foreach($pkg->menu_items_json as $cat => $items)
                                    <div>
                                        <span class="text-[10px] uppercase font-bold text-[#8F7239] font-mono">{{ $cat }}:</span>
                                        <p class="text-stone-700 dark:text-stone-200 font-medium">
                                             {{ is_array($items) ? implode(' • ', $items) : $items }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10">
                        <button onclick="Livewire.dispatch('openWalkInModal', { hall_id: '{{ $hall->id }}', package_id: '{{ $pkg->id }}' })" 
                                class="btn-gold !w-full !h-9 text-xs font-mono font-bold uppercase">
                            Select This Menu &rarr;
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </section>


    <!-- ===================================================================
         SECTION 6: REAL-TIME 14-DAY SHIFT AVAILABILITY MATRIX
         =================================================================== -->
    <section class="py-16 bg-white dark:bg-[#171513] border-b border-[#E7E5E4] dark:border-white/10" id="shift-matrix">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E7E5E4] dark:border-white/10 pb-4">
                <div class="space-y-1">
                    <span class="text-[10px] uppercase font-bold tracking-[0.2em] text-[#8F7239] font-mono">Atomic Shift Locking</span>
                    <h3 class="font-serif-luxury text-3xl text-[#171513] dark:text-white font-normal">Upcoming 14-Day Shift Availability</h3>
                </div>
                <div class="flex items-center gap-4 text-xs font-mono font-medium text-stone-600 dark:text-stone-300">
                    <div class="flex items-center gap-1.5"><span class="w-3 h-3 bg-[#C6A15B]/20 border border-[#C6A15B]/60"></span> Available</div>
                    <div class="flex items-center gap-1.5"><span class="w-3 h-3 bg-[#171513]/10 border border-[#C6A15B]/60"></span> Booked</div>
                    <div class="flex items-center gap-1.5"><span class="w-3 h-3 bg-[#C6A15B]/20 border border-[#C6A15B]/60"></span> Tentative Hold</div>
                </div>
            </div>

            <!-- Shifts Grid Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse border border-[#E7E5E4] dark:border-white/10">
                    <thead>
                        <tr class="bg-[#F8F5EF] dark:bg-[#171513] border-b border-[#E7E5E4] dark:border-white/10 text-[10px] uppercase font-bold tracking-wider text-stone-600 dark:text-stone-300 font-mono">
                            <th class="p-3.5 border-r border-[#E7E5E4] dark:border-white/10">Calendar Date</th>
                            <th class="p-3.5 border-r border-[#E7E5E4] dark:border-white/10 text-center">Afternoon Lunch (12:00 PM - 4:00 PM)</th>
                            <th class="p-3.5 border-r border-[#E7E5E4] dark:border-white/10 text-center">Evening Dinner (6:30 PM - 10:30 PM)</th>
                            <th class="p-3.5 text-center">Night Mehndi (11:00 PM - 3:00 AM)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4] dark:divide-white/10 font-mono">
                        @foreach($days as $day)
                            @php
                                $dStr = $day->format('Y-m-d');
                                $bAfternoon = $bookings->get($dStr . '_AFTERNOON');
                                $bEvening = $bookings->get($dStr . '_EVENING');
                                $bNight = $bookings->get($dStr . '_NIGHT');
                            @endphp
                            <tr class="hover:bg-[#F8F5EF] dark:hover:bg-white/5 transition">
                                <td class="p-3.5 border-r border-[#E7E5E4] dark:border-white/10 font-semibold text-[#171513] dark:text-white whitespace-nowrap font-sans">
                                    {{ $day->format('D, d M Y') }}
                                </td>
                                
                                <!-- Afternoon -->
                                <td class="p-2.5 border-r border-[#E7E5E4] dark:border-white/10 text-center">
                                    @if($bAfternoon)
                                        <span class="inline-block px-3 py-1 text-[10px] font-bold uppercase tracking-wider {{ $bAfternoon->status === 'CONFIRMED' ? 'bg-[#171513]/10 text-[#171513] border border-[#C6A15B]/50' : 'bg-[#C6A15B]/20 text-[#8F7239] border border-[#C6A15B]/60' }}">
                                            {{ $bAfternoon->status === 'CONFIRMED' ? 'Booked (' . $bAfternoon->event_type . ')' : 'Hold' }}
                                        </span>
                                    @else
                                        <button onclick="Livewire.dispatch('openWalkInModal', { hall_id: '{{ $hall->id }}', event_date: '{{ $dStr }}', slot_type: 'AFTERNOON' })" 
                                                class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider bg-[#C6A15B]/15 text-[#8F7239] border border-[#C6A15B]/60 hover:bg-[#C6A15B] hover:text-white transition cursor-pointer">
                                            Available — Book
                                        </button>
                                    @endif
                                </td>

                                <!-- Evening -->
                                <td class="p-2.5 border-r border-[#E7E5E4] dark:border-white/10 text-center">
                                    @if($bEvening)
                                        <span class="inline-block px-3 py-1 text-[10px] font-bold uppercase tracking-wider {{ $bEvening->status === 'CONFIRMED' ? 'bg-[#171513]/10 text-[#171513] border border-[#C6A15B]/50' : 'bg-[#C6A15B]/20 text-[#8F7239] border border-[#C6A15B]/60' }}">
                                            {{ $bEvening->status === 'CONFIRMED' ? 'Booked (' . $bEvening->event_type . ')' : 'Hold' }}
                                        </span>
                                    @else
                                        <button onclick="Livewire.dispatch('openWalkInModal', { hall_id: '{{ $hall->id }}', event_date: '{{ $dStr }}', slot_type: 'EVENING' })" 
                                                class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider bg-[#C6A15B]/15 text-[#8F7239] border border-[#C6A15B]/60 hover:bg-[#C6A15B] hover:text-white transition cursor-pointer">
                                            Available — Book
                                        </button>
                                    @endif
                                </td>

                                <!-- Night -->
                                <td class="p-2.5 text-center">
                                    @if($bNight)
                                        <span class="inline-block px-3 py-1 text-[10px] font-bold uppercase tracking-wider {{ $bNight->status === 'CONFIRMED' ? 'bg-[#171513]/10 text-[#171513] border border-[#C6A15B]/50' : 'bg-[#C6A15B]/20 text-[#8F7239] border border-[#C6A15B]/60' }}">
                                            {{ $bNight->status === 'CONFIRMED' ? 'Booked (' . $bNight->event_type . ')' : 'Hold' }}
                                        </span>
                                    @else
                                        <button onclick="Livewire.dispatch('openWalkInModal', { hall_id: '{{ $hall->id }}', event_date: '{{ $dStr }}', slot_type: 'NIGHT' })" 
                                                class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider bg-[#C6A15B]/15 text-[#8F7239] border border-[#C6A15B]/60 hover:bg-[#C6A15B] hover:text-white transition cursor-pointer">
                                            Available — Book
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </section>


    <!-- ===================================================================
         SECTION 7: VERIFIED VIP CLIENT REVIEWS & EXPERIENCE TESTIMONIALS
         =================================================================== -->
    <section class="py-16 bg-[#F8F5EF] dark:bg-[#171513] border-b border-[#E7E5E4] dark:border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            <div class="text-center space-y-2 max-w-2xl mx-auto">
                <span class="text-[10px] font-mono uppercase tracking-[0.2em] text-[#8F7239] font-bold block">
                    Verified Couple Experiences
                </span>
                <h2 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                    Treasured Memories &amp; Reviews
                </h2>
                <p class="text-xs text-stone-500 dark:text-stone-400 font-sans">
                    Read genuine reviews from families who celebrated their Barat, Valima, and Mehndi nights at this banquet hall.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @if(!empty($settings['reviews']))
                    @foreach($settings['reviews'] as $rev)
                        <div class="editorial-card p-6 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-4">
                            <div class="flex items-center gap-1 text-[#C6A15B] text-sm">
                                @for($r = 0; $r < ($rev['rating'] ?? 5); $r++) ★ @endfor
                            </div>
                            <p class="text-xs text-stone-600 dark:text-stone-300 italic font-serif leading-relaxed">
                                "{{ $rev['quote'] }}"
                            </p>
                            <div class="pt-3 border-t border-[#E7E5E4] dark:border-white/10 flex items-center justify-between text-xs">
                                <div>
                                    <strong class="text-[#171513] dark:text-white block font-sans">{{ $rev['author'] }}</strong>
                                    <span class="text-[10px] text-stone-400 font-mono">{{ $rev['event'] }}</span>
                                </div>
                                <span class="text-[9px] font-mono uppercase px-2 py-0.5 bg-[#C6A15B]/15 text-[#8F7239] border border-[#C6A15B]/60 font-bold">
                                    Verified Host
                                </span>
                            </div>
                        </div>
                    @endforeach
                @else
                    <!-- Review 1 -->
                    <div class="editorial-card p-6 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-4">
                        <div class="flex items-center gap-1 text-[#C6A15B] text-sm">
                            ★★★★★
                        </div>
                        <p class="text-xs text-stone-600 dark:text-stone-300 italic font-serif leading-relaxed">
                            "The crystal ballroom atmosphere for our Barat event was breathtaking. The air conditioning was freezing despite 600 guests, and the Mutton Qorma was praised by all our elder relatives."
                        </p>
                        <div class="pt-3 border-t border-[#E7E5E4] dark:border-white/10 flex items-center justify-between text-xs">
                            <div>
                                <strong class="text-[#171513] dark:text-white block font-sans">Chaudhry Bilal &amp; Ayesha</strong>
                                <span class="text-[10px] text-stone-400 font-mono">Barat Function (550 Guests)</span>
                            </div>
                            <span class="text-[9px] font-mono uppercase px-2 py-0.5 bg-[#C6A15B]/15 text-[#8F7239] border border-[#C6A15B]/60 font-bold">
                                Verified Host
                            </span>
                        </div>
                    </div>

                    <!-- Review 2 -->
                    <div class="editorial-card p-6 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-4">
                        <div class="flex items-center gap-1 text-[#C6A15B] text-sm">
                            ★★★★★
                        </div>
                        <p class="text-xs text-stone-600 dark:text-stone-300 italic font-serif leading-relaxed">
                            "What impressed us most was the atomic booking lock. We received our official A4 contract and WhatsApp receipt within seconds. Valet parking handled 200 cars without any congestion."
                        </p>
                        <div class="pt-3 border-t border-[#E7E5E4] dark:border-white/10 flex items-center justify-between text-xs">
                            <div>
                                <strong class="text-[#171513] dark:text-white block font-sans">Mian Salman Tariq</strong>
                                <span class="text-[10px] text-stone-400 font-mono">Valima Reception (400 Guests)</span>
                            </div>
                            <span class="text-[9px] font-mono uppercase px-2 py-0.5 bg-[#C6A15B]/15 text-[#8F7239] border border-[#C6A15B]/60 font-bold">
                                Verified Host
                            </span>
                        </div>
                    </div>

                    <!-- Review 3 -->
                    <div class="editorial-card p-6 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-4">
                        <div class="flex items-center gap-1 text-[#C6A15B] text-sm">
                            ★★★★★
                        </div>
                        <p class="text-xs text-stone-600 dark:text-stone-300 italic font-serif leading-relaxed">
                            "Our Mehndi night dholak setup and fairy light walkway looked straight out of a royal fairy tale. Standby generator switched over instantly during a brief power outage. Highly recommended!"
                        </p>
                        <div class="pt-3 border-t border-[#E7E5E4] dark:border-white/10 flex items-center justify-between text-xs">
                            <div>
                                <strong class="text-[#171513] dark:text-white block font-sans">Dr. Naveed &amp; Fatima</strong>
                                <span class="text-[10px] text-stone-400 font-mono">Festive Mehndi Night (300 Guests)</span>
                            </div>
                            <span class="text-[9px] font-mono uppercase px-2 py-0.5 bg-[#C6A15B]/15 text-[#8F7239] border border-[#C6A15B]/60 font-bold">
                                Verified Host
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>


    <!-- ===================================================================
         SECTION 8: VENUE POLICIES, OPERATING HOURS & CONCIERGE BOOKING DESK
         =================================================================== -->
    <section class="py-16 bg-[#171513] text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
                
                <!-- Left Details & Policies (7 cols) -->
                <div class="lg:col-span-7 space-y-6">
                    <span class="text-xs font-mono uppercase tracking-[0.2em] text-[#C6A15B] block font-bold">
                        Banqueting Terms &amp; Operations
                    </span>
                    <h2 class="font-serif-luxury text-3xl sm:text-5xl font-normal text-white">
                        Book Your Date with Guaranteed Confidence
                    </h2>
                    
                    <div class="space-y-4 font-mono text-xs text-stone-300">
                        <!-- Operating Shift Timings -->
                        <div class="p-4 bg-[#171513] border border-white/10 space-y-1">
                            <span class="text-[#C6A15B] text-[10px] uppercase font-bold block">Operating Timings</span>
                            <p class="text-white">
                                {{ $hall->operating_hours ?: 'Afternoon Shift: 11:30 AM - 3:30 PM | Evening Dinner: 7:00 PM - 12:00 AM' }}
                            </p>
                        </div>

                        <!-- Policies & Deposit -->
                        <div class="p-4 bg-[#171513] border border-white/10 space-y-1">
                            <span class="text-[#C6A15B] text-[10px] uppercase font-bold block">Booking &amp; Payment Rules</span>
                            <p class="text-stone-300 font-sans leading-relaxed text-xs">
                                {{ $hall->policies ?: '50% Token advance required to lock event shift atomically. Sound and music permissible till 10:00 PM per city bylaws. Outside catering subject to kitchen NOC.' }}
                            </p>
                        </div>

                        <!-- Custom Hall FAQs -->
                        @if(!empty($settings['faqs']))
                            <div class="p-4 bg-[#171513] border border-white/10 space-y-2">
                                <span class="text-[#C6A15B] text-[10px] uppercase font-bold block">Frequently Asked Questions (FAQ)</span>
                                <div class="space-y-2">
                                    @foreach($settings['faqs'] as $faq)
                                        <div class="border-b border-white/5 pb-2">
                                            <strong class="text-white text-xs block font-sans">Q: {{ $faq['question'] }}</strong>
                                            <p class="text-stone-400 text-[11px] font-sans mt-0.5">A: {{ $faq['answer'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center gap-4 text-xs font-mono text-stone-400 pt-2">
                        <span>📍 {{ $hall->venue->address ?? 'Main Commercial Boulevard, Lahore' }}</span>
                        <span>•</span>
                        <span>📞 {{ $hall->venue->phone ?? $hall->tenant->phone ?? '+92 300 8472910' }}</span>
                    </div>
                </div>

                <!-- Right Booking Box (5 cols) -->
                <div class="lg:col-span-5 bg-[#171513] border-2 border-[#C6A15B] p-8 space-y-6 shadow-2xl text-center">
                    <div class="space-y-2">
                        <span class="text-[10px] font-mono uppercase tracking-widest text-[#C6A15B] block font-bold">
                            Direct Reservation Desk
                        </span>
                        <h3 class="font-serif-luxury text-2xl text-white font-normal">Ready to Confirm {{ $hall->name }}?</h3>
                        <p class="text-xs text-stone-400 font-sans">
                            Lock your preferred shift instantly. Our software ensures zero double-booking concurrency.
                        </p>
                    </div>

                    <div class="space-y-3 pt-2">
                          <button onclick="Livewire.dispatch('openWalkInModal', { hall_id: '{{ $hall->id }}' })" 
                                  class="btn-gold !w-full !h-12 text-xs font-bold uppercase font-mono tracking-wider shadow-[0_0_25px_rgba(198,161,91,0.4)] hover:shadow-[0_0_35px_rgba(198,161,91,0.6)] transition-all duration-300 group relative overflow-hidden">
                              <span class="relative z-10">Instant Shift Reservation &rarr;</span>
                              <span class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></span>
                          </button>

                          <a href="https://wa.me/{{ $cleanPhone }}?text=Hello%20{{ urlencode($hall->name) }},%20I%20would%20like%20to%20reserve%20an%20event%20shift." 
                             target="_blank"
                             class="btn-outline-gold !w-full !h-11 text-xs font-semibold flex items-center justify-center gap-2 shadow-[0_0_15px_rgba(198,161,91,0.3)] hover:shadow-[0_0_25px_rgba(198,161,91,0.5)] transition-all duration-300 group">
                              <span class="relative z-10">Chat on WhatsApp</span>
                          </a>

                        <a href="tel:{{ $rawPhone }}" 
                           class="w-full h-10 bg-transparent border border-white/10 text-stone-300 text-xs font-mono flex items-center justify-center gap-2 hover:bg-white/5 transition block">
                            Direct Hotline: {{ $rawPhone }}
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </section>

</div>
