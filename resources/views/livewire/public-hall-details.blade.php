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
                    <button wire:click="toggleWishlist" 
                            class="@if($isWishlisted) bg-[#C6A15B] text-[#171513] @else border border-white/20 bg-white/10 @endif !h-11 !px-5 text-xs font-semibold flex items-center gap-2 transition-all duration-300">
                        <span class="w-2.5 h-2.5 @if($isWishlisted) bg-[#171513] @else bg-transparent @endif rounded-full border @if($isWishlisted) border-[#171513] @else border-white/40 @endif"></span>
                        <span>@if($isWishlisted) Remove from Wishlist @else Add to Wishlist @endif</span>
                        <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded-full">{{ $wishlistCount }}</span>
                    </button>

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

                <!-- Public Hall Analytics Badges -->
                <div class="mt-6 pt-6 border-t border-white/10 flex items-center gap-6 text-xs font-mono">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-[#C6A15B]"></span>
                        <span class="text-stone-300">{{ $totalViews }} Views</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-[#8F7239]"></span>
                        <span class="text-stone-300">{{ $wishlistCount }} Wishlists</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-[#171513]"></span>
                        <span class="text-stone-300">{{ $hall->min_capacity }} - {{ $hall->max_capacity }} Capacity</span>
                    </div>
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
                    <div class="editorial-card p-5 sm:p-6 bg-white dark:bg-[#171513] border border-[#C6A15B]/30 hover:border-[#C6A15B] transition-all duration-300 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] hover:shadow-[0_12px_32px_-6px_rgba(198,161,91,0.18)] flex flex-col md:flex-row md:items-center justify-between gap-6 group relative overflow-hidden">
                        <!-- Top hairline gold accent -->
                        <div class="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-transparent via-[#C6A15B]/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                        <!-- Left & Middle: Icon + Meta + Details -->
                        <div class="flex items-start sm:items-center gap-5 flex-1 min-w-0">
                            <!-- Icon Box with Gold Border & Gradient -->
                            <div class="w-13 h-13 shrink-0 bg-gradient-to-br from-[#F8F5EF] to-white dark:from-[#171513] dark:to-stone-900 border border-[#C6A15B]/50 text-[#C6A15B] flex items-center justify-center text-2xl shadow-sm group-hover:scale-105 group-hover:border-[#C6A15B] group-hover:shadow-[0_0_15px_rgba(198,161,91,0.25)] transition-all duration-300">
                                @if(($srv['icon'] ?? '') === 'bolt') ⚡
                                @elseif(($srv['icon'] ?? '') === 'camera') 🎥
                                @elseif(($srv['icon'] ?? '') === 'truck') 🚗
                                @elseif(($srv['icon'] ?? '') === 'star') ⭐
                                @else ✨
                                @endif
                            </div>

                            <!-- Details -->
                            <div class="space-y-1.5 flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <h3 class="font-serif-luxury text-lg sm:text-xl text-[#171513] dark:text-white font-medium group-hover:text-[#8F7239] dark:group-hover:text-[#C6A15B] transition-colors truncate">
                                        {{ $srv['title'] ?? 'Custom Service' }}
                                    </h3>
                                    <span class="text-[9px] font-mono uppercase px-2.5 py-0.5 bg-[#C6A15B]/10 border border-[#C6A15B]/30 font-semibold text-[#8F7239] dark:text-[#C6A15B] tracking-wider">
                                        {{ $srv['category'] ?? 'Service' }}
                                    </span>
                                </div>
                                <p class="text-xs sm:text-[13px] text-stone-600 dark:text-stone-300 font-normal leading-relaxed max-w-3xl">
                                    {{ $srv['description'] ?? 'Premium wedding amenity customized by banquet management.' }}
                                </p>
                            </div>
                        </div>

                        <!-- Right: Pricing & Action -->
                        <div class="pt-4 md:pt-0 border-t md:border-t-0 md:border-l border-[#E7E5E4] dark:border-white/10 md:pl-7 flex items-center justify-between md:justify-end gap-6 shrink-0">
                            <div class="text-left md:text-right">
                                <span class="text-[9px] uppercase font-bold text-stone-400 font-mono block tracking-widest">PRICING</span>
                                @if(!empty($srv['is_complimentary']))
                                    <span class="inline-block text-xs font-bold font-mono text-[#8F7239] dark:text-[#C6A15B] bg-[#C6A15B]/15 dark:bg-[#171513] px-3 py-1 border border-[#C6A15B]/60 tracking-wider">
                                        COMPLIMENTARY
                                    </span>
                                @else
                                    <strong class="font-cinzel text-lg sm:text-2xl text-[#171513] dark:text-[#C6A15B] tracking-wide">
                                        PKR {{ number_format($srv['price'] ?? 0) }}
                                    </strong>
                                @endif
                            </div>

                            <button onclick="Livewire.dispatch('openWalkInModal', { hall_id: '{{ $hall->id }}' })" 
                                    class="btn-gold !h-10 !px-5 text-xs font-mono tracking-wider flex items-center gap-1.5 whitespace-nowrap shadow-sm hover:shadow-[0_0_20px_rgba(198,161,91,0.4)] transition-all duration-300">
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
         SECTION 5.5: VENUE LOCATION & GOOGLE MAPS
         =================================================================== -->
    @php
        $venue = $hall->venue;
        $latitude = $venue->latitude ?? null;
        $longitude = $venue->longitude ?? null;
        $googleMapsUrl = $venue->google_maps_url ?? null;
        $hasLocation = !empty($latitude) && !empty($longitude);
    @endphp

    @if($hasLocation || !empty($googleMapsUrl))
    <section class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
        <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-4 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <span class="text-[10px] font-mono uppercase tracking-[0.2em] text-[#8F7239] font-bold block">Venue Location</span>
                <h2 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">Find Us on the Map</h2>
            </div>
            <p class="text-xs text-stone-600 dark:text-stone-400 font-mono">Get directions to {{ $hall->venue->name ?? $hall->name }} using Google Maps</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
            <!-- Google Maps Embed -->
            <div class="aspect-[16/9] rounded-2xl overflow-hidden border border-[#E7E5E4] dark:border-white/10 shadow-xl bg-[#171513]">
                @if(!empty($googleMapsUrl))
                    <iframe class="w-full h-full" src="{{ $googleMapsUrl }}" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                @else
                    <iframe class="w-full h-full" src="https://www.google.com/maps/embed/v1/place?key=AIzaSyAJ8niE3hXLhBX0niwdZv8p1mDz3bq3Qqs&q={{ urlencode($venue->city ?? 'Lahore') }},+{{ urlencode($venue->address ?? 'Pakistan') }}" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                @endif
            </div>
        </div>
    </section>

            <div class="space-y-6">
                <div class="p-6 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 rounded-2xl space-y-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        <span class="w-8 h-8 rounded bg-[#C6A15B]/10 flex items-center justify-center text-[#C6A15B] text-xs font-bold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 01-1.585-1.585l-4.243-4.244A8 8 0 010 8.828c0-1.567.439-3.056 1.282-4.245l4.244-4.244a4 4 0 011.282-.878V1c0-.222.117-.41.29-.557a10 10 0 011.401-.263c2.007-.214 4.032.179 5.478 1.067l4.243 4.243a7.993 7.993 0 011.345 3.027l4.243 4.243c.578.578.868 1.35.93 2.095l.051.425a1 1 0 01-.104.49c-.156.49-.462.91-.867 1.2l-4.243 4.243a7.98 7.98 0 01-3.026 1.346c-1.038.513-2.177.8-3.286.855l-.568.016c-.645.017-1.29.012-1.935-.015c-1.43-.139-2.798-.506-3.962-1.179l-4.244-4.244a8 8 0 01-1.282-1.585l-4.243-4.244A8 8 0 014.172 1.347l4.244-4.244a2 2 0 01.641-.439l4.243-4.244a2 2 0 011.416-.278c1.283-.137 2.562.175 3.483.836l4.244 4.244c.414.415.784.89.997 1.401l4.244 4.244a3.993 3.993 0 011.211 3.627l.049.438a1 1 0 01-.15.482c-.171.455-.502.842-.907 1.166l-4.243 4.243a7.98 7.98 0 01-3.026 1.346c-1.038.513-2.177.8-3.286.855l-.568.016c-.645.017-1.29.012-1.935-.015c-1.43-.139-2.798-.506-3.962-1.179l-4.244-4.244a8 8 0 01-1.282-1.585l-4.243-4.244A8 8 0 014.172 1.347l4.244-4.244a2 2 0 01.641-.439l4.243-4.244a2 2 0 011.416-.278c1.283-.137 2.562.175 3.483.836l4.244 4.244c.414.415.784.89.997 1.401l4.244 4.244a3.993 3.993 0 011.211 3.627l.049.438a1 1 0 01-.15.482c-.171.455-.502.842-.907 1.166z"/></svg>
                        </span>
                        <div>
                            <h4 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-medium">Venue Location</h4>
                            <p class="text-xs text-stone-600 dark:text-stone-400 font-mono">
                                {{ $hall->venue->name ?? $hall->name }}<br>
                                {{ $venue->city ?? 'Lahore' }}, {{ $venue->address ?? 'Pakistan' }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        @if($latitude && $longitude)
                            <div class="p-3 bg-[#171513]/5 border border-[#C6A15B]/30 rounded-lg text-center">
                                <span class="text-[9px] font-mono uppercase text-[#8F7239] block">Latitude</span>
                                <span class="text-xs font-mono text-white dark:text-stone-300">{{ number_format((float)$latitude, 6) }}</span>
                            </div>
                            <div class="p-3 bg-[#171513]/5 border border-[#C6A15B]/30 rounded-lg text-center">
                                <span class="text-[9px] font-mono uppercase text-[#8F7239] block">Longitude</span>
                                <span class="text-xs font-mono text-white dark:text-stone-300">{{ number_format((float)$longitude, 6) }}</span>
                            </div>
                        @endif
                        <div class="p-3 bg-[#171513]/5 border border-[#C6A15B]/30 rounded-lg text-center">
                            <span class="text-[9px] font-mono uppercase text-[#8F7239] block">City</span>
                            <span class="text-xs font-mono text-white dark:text-stone-300">{{ $venue->city ?? 'Lahore' }}</span>
                        </div>
                        <div class="p-3 bg-[#171513]/5 border border-[#C6A15B]/30 rounded-lg text-center">
                            <span class="text-[9px] font-mono uppercase text-[#8F7239] block">Capacity</span>
                            <span class="text-xs font-mono text-white dark:text-stone-300">{{ $hall->min_capacity }} - {{ $hall->max_capacity }}</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('public.venue.details', $hall->slug) }}" class="px-4 py-2 bg-[#171513] border border-white/10 text-white text-xs font-mono uppercase hover:bg-[#C6A15B] hover:text-[#171513] transition group relative overflow-hidden">
                            View Full Details
                        </a>
                        @if(!empty($googleMapsUrl))
                            <a href="{{ $googleMapsUrl }}" target="_blank" class="px-4 py-2 bg-[#C6A15B] text-[#171513] text-xs font-mono uppercase hover:bg-[#8F7239] transition">Open in Google Maps</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
    <!-- ===================================================================
         SECTION 5.6: LOCATION DETAILS & DIRECTIONS
         =================================================================== -->
    <section class="py-16 bg-[#F8F5EF] dark:bg-[#171513] border-b border-[#E7E5E4] dark:border-white/10" id="shift-matrix" x-data="{ selectedDate: '{{ $today->format('Y-m-d') }}' }">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            
            <!-- Section Header -->
            <div class="text-center space-y-2 max-w-3xl mx-auto">
                <div class="inline-flex items-center gap-2 text-[10px] font-mono uppercase tracking-[0.24em] text-[#8F7239] dark:text-[#C6A15B] font-bold">
                    <span>Upcoming 14-Day Shift Availability</span>
                    <span>•</span>
                    <span>3-Month Interactive Calendar</span>
                </div>
                <h3 class="font-serif-luxury text-3xl sm:text-5xl text-[#171513] dark:text-white font-normal">
                    Check Date &amp; Shift Availability
                </h3>
                <p class="text-xs sm:text-sm text-stone-600 dark:text-stone-300 font-sans">
                    Browse upcoming dates across the next 3 months. Click any date on the calendar to view its Afternoon, Evening, and Night shifts.
                </p>
            </div>

            <!-- Month Quick Select Pills + Shift Status Legend & Shift Guide -->
            <div class="space-y-3 max-w-5xl mx-auto">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <!-- 3 Month Shortcut Pills -->
                    <div class="inline-flex border border-[#C6A15B]/40 bg-white dark:bg-[#171513] p-1 font-mono text-xs rounded-xl shadow-xs">
                        @foreach($upcomingMonths as $offset => $m)
                            <button type="button" 
                                    wire:click="setMonth({{ $offset }})"
                                    class="px-3.5 sm:px-5 py-1.5 rounded-lg transition-all duration-200 cursor-pointer font-bold {{ $monthOffset === $offset ? 'bg-[#171513] text-[#C6A15B] dark:bg-[#C6A15B] dark:text-[#171513] shadow-xs' : 'text-stone-600 dark:text-stone-400 hover:text-[#171513] dark:hover:text-white' }}">
                                {{ $m['label'] }}
                            </button>
                        @endforeach
                    </div>

                    <!-- Shift Status Legend -->
                    <div class="flex flex-wrap items-center gap-4 text-xs font-mono font-medium text-stone-600 dark:text-stone-300">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Available (Open)
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Partial Booked
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-red-600"></span> Fully Booked (🔒)
                        </span>
                    </div>
                </div>

                <!-- Shift Acronym Key (Explaining L, D, N) -->
                <div class="bg-amber-500/10 dark:bg-[#C6A15B]/10 border border-[#C6A15B]/30 rounded-lg px-4 py-2 text-center sm:text-left flex flex-wrap items-center justify-between gap-2 text-xs font-mono text-stone-700 dark:text-stone-300">
                    <span class="font-bold text-[#8F7239] dark:text-[#C6A15B]">📌 Shift Guide:</span>
                    <div class="flex flex-wrap items-center gap-3 sm:gap-6">
                        <span><strong class="text-emerald-700 dark:text-emerald-400">Afternoon:</strong> 12:00 PM – 4:00 PM (Lunch)</span>
                        <span><strong class="text-emerald-700 dark:text-emerald-400">Evening:</strong> 6:30 PM – 10:30 PM (Dinner)</span>
                        <span><strong class="text-emerald-700 dark:text-emerald-400">Night:</strong> 11:00 PM – 3:00 AM (Late Night)</span>
                    </div>
                </div>
            </div>

            <!-- THE AUTHENTIC CALENDAR CARD (Accurately modeled after reference flip-calendar image, full luxury width) -->
            <div class="max-w-5xl mx-auto w-full relative pt-5">
                
                <!-- Physical Binder Hanging Loops (Tabs protruding above the calendar card) -->
                <div class="absolute top-0 left-[25%] -translate-x-1/2 w-8 h-12 bg-slate-900 dark:bg-stone-950 rounded-full border-2 border-slate-700 dark:border-stone-600 shadow-xl z-20 flex items-center justify-center">
                    <div class="w-4 h-4 rounded-full bg-slate-950 border border-slate-600/50"></div>
                </div>
                <div class="absolute top-0 right-[25%] translate-x-1/2 w-8 h-12 bg-slate-900 dark:bg-stone-950 rounded-full border-2 border-slate-700 dark:border-stone-600 shadow-xl z-20 flex items-center justify-center">
                    <div class="w-4 h-4 rounded-full bg-slate-950 border border-slate-600/50"></div>
                </div>

                <!-- Main Calendar Block with Rounded Corners and Soft Shadow (Like Reference Image) -->
                <div class="bg-white dark:bg-[#171513] border-2 border-[#C6A15B]/40 rounded-3xl shadow-[0_25px_60px_-15px_rgba(0,0,0,0.3)] dark:shadow-[0_25px_60px_-15px_rgba(0,0,0,0.8)] overflow-hidden">
                    
                    <!-- Iconic Red Binder Header (Matching Reference Image with Luxury Gold Trim) -->
                    <div class="relative bg-gradient-to-r from-[#b91c1c] via-[#dc2626] to-[#b91c1c] text-white pt-8 pb-7 px-6 sm:px-12 flex items-center justify-between border-b-2 border-red-800 shadow-inner">
                        
                        <!-- Left Binder Eyelet Hole (matching image cutout) -->
                        <div class="absolute top-3 left-[25%] -translate-x-1/2 w-10 h-10 rounded-full bg-white dark:bg-[#171513] border-[3px] border-slate-800 dark:border-stone-700 shadow-inner z-10 flex items-center justify-center pointer-events-none">
                            <div class="w-5 h-5 rounded-full bg-slate-900"></div>
                        </div>
                        <!-- Right Binder Eyelet Hole (matching image cutout) -->
                        <div class="absolute top-3 right-[25%] translate-x-1/2 w-10 h-10 rounded-full bg-white dark:bg-[#171513] border-[3px] border-slate-800 dark:border-stone-700 shadow-inner z-10 flex items-center justify-center pointer-events-none">
                            <div class="w-5 h-5 rounded-full bg-slate-900"></div>
                        </div>

                        <!-- Left: Previous Month Button + Month Name (Bold Uppercase) -->
                        <div class="flex items-center gap-4 z-10">
                            <button type="button" 
                                    wire:click="prevMonth"
                                    {{ $monthOffset === 0 ? 'disabled' : '' }}
                                    class="w-10 h-10 rounded-full border border-white/40 bg-black/25 hover:bg-black/50 flex items-center justify-center text-white disabled:opacity-25 disabled:cursor-not-allowed transition cursor-pointer shadow-md"
                                    title="Previous Month">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <span class="font-sans font-black text-2xl sm:text-4xl uppercase tracking-wider text-white drop-shadow-md select-none">
                                {{ $monthName }}
                            </span>
                        </div>

                        <!-- Right: Year (Extra Large Bold Numbers) + Next Month Button -->
                        <div class="flex items-center gap-4 z-10">
                            <span class="font-sans font-black text-2xl sm:text-4xl text-white tracking-wide drop-shadow-md select-none">
                                {{ $year }}
                            </span>
                            <button type="button" 
                                    wire:click="nextMonth"
                                    {{ $monthOffset === 2 ? 'disabled' : '' }}
                                    class="w-10 h-10 rounded-full border border-white/40 bg-black/25 hover:bg-black/50 flex items-center justify-center text-white disabled:opacity-25 disabled:cursor-not-allowed transition cursor-pointer shadow-md"
                                    title="Next Month">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Weekdays Header (SUN MON TUE WED THU FRI SAT - Sunday in Bold Red as in image) -->
                    <div class="grid grid-cols-7 border-b border-stone-200 dark:border-white/10 bg-[#FAFAFA] dark:bg-stone-950 text-center py-3.5 text-xs sm:text-sm font-sans font-bold tracking-wider">
                        <div class="text-red-600 dark:text-red-500 font-extrabold">SUN</div>
                        <div class="text-[#1e293b] dark:text-stone-300">MON</div>
                        <div class="text-[#1e293b] dark:text-stone-300">TUE</div>
                        <div class="text-[#1e293b] dark:text-stone-300">WED</div>
                        <div class="text-[#1e293b] dark:text-stone-300">THU</div>
                        <div class="text-[#1e293b] dark:text-stone-300">FRI</div>
                        <div class="text-[#1e293b] dark:text-stone-300">SAT</div>
                    </div>

                    <!-- Days 7-Column Grid (Centered numbers matching calendar sheet aesthetic) -->
                    <div class="grid grid-cols-7 divide-x divide-y divide-stone-100 dark:divide-white/5 bg-white dark:bg-[#171513]">
                        {{-- Leading blank slots for starting weekday offset --}}
                        @for($i = 0; $i < $startDayOfWeek; $i++)
                            <div class="min-h-[100px] sm:min-h-[120px] bg-stone-50/40 dark:bg-white/[0.01]"></div>
                        @endfor

                        {{-- Full Month Calendar Days --}}
                        @foreach($calendarDays as $cDay)
                            @php
                                $cStr = $cDay->format('Y-m-d');
                                $isSunday = $cDay->dayOfWeek === 0;
                                $isToday = $cDay->isToday();
                                $isPast = $cDay->lt($today);

                                $bAfternoon = $bookings->get($cStr . '_AFTERNOON');
                                $bEvening = $bookings->get($cStr . '_EVENING');
                                $bNight = $bookings->get($cStr . '_NIGHT');

                                $bookedCount = ($bAfternoon ? 1 : 0) + ($bEvening ? 1 : 0) + ($bNight ? 1 : 0);
                                $isSelectable = !$isPast && $bookedCount < 3;
                            @endphp
                            <div @if($isSelectable) wire:click="setSelectedDate('{{ $cStr }}')" wire:keydown.enter="setSelectedDate('{{ $cStr }}')" wire:keydown.space.prevent="setSelectedDate('{{ $cStr }}')" role="button" tabindex="0" aria-label="View availability for {{ $cDay->format('F j, Y') }}" @else aria-disabled="true" @endif
                                 class="min-h-[100px] sm:min-h-[120px] p-1 sm:p-1.5 flex flex-col justify-between transition-all relative group {{ $isSelectable ? 'cursor-pointer' : 'cursor-not-allowed' }} {{ $isPast ? 'opacity-35 bg-stone-50/50 dark:bg-white/[0.01]' : ($bookedCount === 3 ? 'bg-red-50/70 dark:bg-red-950/30' : ($bookedCount > 0 ? 'bg-amber-50/40 dark:bg-amber-950/20 hover:bg-amber-100/50' : 'hover:bg-emerald-50/30 dark:hover:bg-[#C6A15B]/10')) }} {{ $selectedDate === $cStr ? 'ring-2 ring-[#C6A15B] ring-inset bg-[#C6A15B]/20 font-bold border-[#C6A15B]' : '' }}">
                                
                                <!-- Large Clean Date Number (Sunday in Red matching reference image) -->
                                <div class="w-full flex items-center justify-between px-0.5 sm:px-1">
                                    <span class="text-base sm:text-xl font-bold font-sans {{ $isPast ? 'text-stone-400' : ($bookedCount === 3 ? 'text-red-700 dark:text-red-400 font-extrabold line-through' : ($isSunday ? 'text-red-600 dark:text-red-500 font-extrabold' : ($isToday ? 'text-[#8F7239] dark:text-[#C6A15B] font-extrabold' : 'text-[#1e293b] dark:text-stone-200'))) }}">
                                        {{ $cDay->format('j') }}
                                    </span>
                                    @if($isToday)
                                        <span class="text-[8px] font-mono font-bold uppercase px-1 bg-red-600 text-white rounded-xs">Today</span>
                                    @elseif($bookedCount === 3 && !$isPast)
                                        <span class="text-[8px] font-mono font-bold text-red-600 dark:text-red-400" title="All Shifts Booked">🔒</span>
                                    @endif
                                </div>

                                <!-- Explicit Shift Labels: Afternoon Book, Evening Book, Night Book -->
                                <div class="w-full pt-1">
                                    @if(!$isPast)
                                        @if($bookedCount === 0)
                                            <!-- All Shifts Free -->
                                            <div class="text-center py-2 space-y-0.5">
                                                <span class="inline-block w-full text-[7.5px] sm:text-[9px] font-mono font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-500/15 border border-emerald-500/30 px-1 py-0.5 rounded truncate">
                                                    All Shifts Open
                                                </span>
                                                <span class="text-[7.5px] sm:text-[8.5px] font-mono text-stone-500 dark:text-stone-400 block">
                                                    3 Available
                                                </span>
                                            </div>
                                        @else
                                            <!-- One or more shifts are booked -->
                                            <div class="space-y-0.5 sm:space-y-1">
                                                <!-- Afternoon Shift -->
                                                @if($bAfternoon)
                                                    <div class="w-full text-[7.5px] sm:text-[8.5px] font-mono font-bold uppercase px-0.5 sm:px-1 py-0.5 bg-red-600 text-white rounded text-center truncate shadow-2xs" title="Afternoon Lunch Booked">
                                                        <span class="hidden sm:inline">Afternoon</span><span class="sm:hidden">Aft</span> Book
                                                    </div>
                                                @else
                                                    <div class="w-full text-[7px] sm:text-[8px] font-mono font-medium text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-500/30 px-0.5 sm:px-1 py-0.2 sm:py-0.5 rounded text-center truncate" title="Afternoon Lunch Available">
                                                        <span class="hidden sm:inline">Afternoon</span><span class="sm:hidden">Aft</span> Open
                                                    </div>
                                                @endif

                                                <!-- Evening Shift -->
                                                @if($bEvening)
                                                    <div class="w-full text-[7.5px] sm:text-[8.5px] font-mono font-bold uppercase px-0.5 sm:px-1 py-0.5 bg-red-600 text-white rounded text-center truncate shadow-2xs" title="Evening Dinner Booked">
                                                        <span class="hidden sm:inline">Evening</span><span class="sm:hidden">Eve</span> Book
                                                    </div>
                                                @else
                                                    <div class="w-full text-[7px] sm:text-[8px] font-mono font-medium text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-500/30 px-0.5 sm:px-1 py-0.2 sm:py-0.5 rounded text-center truncate" title="Evening Dinner Available">
                                                        <span class="hidden sm:inline">Evening</span><span class="sm:hidden">Eve</span> Open
                                                    </div>
                                                @endif

                                                <!-- Night Shift -->
                                                @if($bNight)
                                                    <div class="w-full text-[7.5px] sm:text-[8.5px] font-mono font-bold uppercase px-0.5 sm:px-1 py-0.5 bg-red-600 text-white rounded text-center truncate shadow-2xs" title="Late Night Mehndi Booked">
                                                        <span class="hidden sm:inline">Night</span><span class="sm:hidden">Ngt</span> Book
                                                    </div>
                                                @else
                                                    <div class="w-full text-[7px] sm:text-[8px] font-mono font-medium text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-500/30 px-0.5 sm:px-1 py-0.2 sm:py-0.5 rounded text-center truncate" title="Late Night Mehndi Available">
                                                        <span class="hidden sm:inline">Night</span><span class="sm:hidden">Ngt</span> Open
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    @else
                                        <div class="text-center py-2">
                                            <span class="text-[8px] font-mono text-stone-400 block">-</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- SELECTED DATE SHIFT EXPANSION PANEL (Interactive Shift Drawer) -->
                    <div class="border-t-2 border-[#C6A15B]/30 bg-[#F8F5EF] dark:bg-stone-950 p-6 sm:p-7 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <span class="text-[10px] uppercase font-bold tracking-[0.2em] text-[#8F7239] dark:text-[#C6A15B] font-mono block">Selected Event Date Status</span>
                                <h4 class="font-serif-luxury text-2xl text-[#171513] dark:text-white">{{ $selectedDate ? \Carbon\Carbon::parse($selectedDate)->format('l, d F Y') : 'No bookable shifts remain in ' . $monthTitle }}</h4>
                            </div>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs font-mono text-stone-500 dark:text-stone-400">
                                <span>Base shift rental: <strong class="text-[#8F7239] dark:text-[#C6A15B]">PKR {{ number_format($hall->base_rent) }}</strong></span>
                                <span>Capacity: {{ number_format($hall->min_capacity) }}–{{ number_format($hall->max_capacity) }} guests</span>
                                <span>Green = available | Red = booked</span>
                            </div>
                        </div>

                        <!-- 3 Shift Cards for Selected Date -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            @if($selectedDate)
                                @foreach($calendarDays as $dayCard)
                                    @php
                                        $cardStr = $dayCard->format('Y-m-d');
                                        $cardAfternoon = $bookings->get($cardStr . '_AFTERNOON');
                                        $cardEvening = $bookings->get($cardStr . '_EVENING');
                                        $cardNight = $bookings->get($cardStr . '_NIGHT');
                                    @endphp
                                    @if($selectedDate === $cardStr)
                                        <div class="contents">
                                        <!-- Afternoon Shift -->
                                        <div class="p-4 bg-white dark:bg-[#171513] border-2 {{ $cardAfternoon ? 'border-red-400 dark:border-red-900/60 bg-red-50/20' : 'border-emerald-500/40 hover:border-emerald-500' }} rounded-xl space-y-3 flex flex-col justify-between shadow-xs transition-colors">
                                            <div>
                                                <div class="flex items-center justify-between pb-1 border-b border-stone-200 dark:border-white/10">
                                                    <span class="text-[10px] font-mono uppercase font-bold text-[#8F7239] dark:text-[#C6A15B]">Shift 1 (12:00 PM - 4:00 PM)</span>
                                                    @if($cardAfternoon)
                                                        <span class="text-[9px] font-mono font-bold text-red-600 bg-red-100 dark:bg-red-950 px-1.5 py-0.5 rounded">🔒 BOOKED</span>
                                                    @else
                                                        <span class="text-[9px] font-mono font-bold text-emerald-600 bg-emerald-100 dark:bg-emerald-950 px-1.5 py-0.5 rounded">✓ AVAILABLE</span>
                                                    @endif
                                                </div>
                                                <h5 class="font-serif-luxury text-lg text-[#171513] dark:text-white font-medium pt-2">Afternoon Lunch</h5>
                                            </div>
                                            @if($cardAfternoon)
                                                <div class="py-2.5 px-3 bg-red-100 dark:bg-red-950/60 border border-red-300 dark:border-red-900/60 text-red-700 dark:text-red-400 text-xs font-mono font-bold text-center rounded-lg space-y-0.5">
                                                    <div>🔒 Already Booked</div>
                                                    <div class="text-[10px] font-sans font-normal opacity-90">Reserved for {{ $cardAfternoon->event_type ?? 'Event' }}</div>
                                                </div>
                                            @else
                                                <button onclick="Livewire.dispatch('openWalkInModal', { hall_id: '{{ $hall->id }}', event_date: '{{ $cardStr }}', slot_type: 'AFTERNOON' })"
                                                        class="btn-gold !w-full !h-10 text-xs font-mono uppercase tracking-wider font-bold rounded-lg cursor-pointer shadow-sm hover:shadow-md transition">
                                                    Reserve Lunch Slot &rarr;
                                                </button>
                                            @endif
                                        </div>

                                        <!-- Evening Shift -->
                                        <div class="p-4 bg-white dark:bg-[#171513] border-2 {{ $cardEvening ? 'border-red-400 dark:border-red-900/60 bg-red-50/20' : 'border-emerald-500/40 hover:border-emerald-500' }} rounded-xl space-y-3 flex flex-col justify-between shadow-xs transition-colors">
                                            <div>
                                                <div class="flex items-center justify-between pb-1 border-b border-stone-200 dark:border-white/10">
                                                    <span class="text-[10px] font-mono uppercase font-bold text-[#8F7239] dark:text-[#C6A15B]">Shift 2 (6:30 PM - 10:30 PM)</span>
                                                    @if($cardEvening)
                                                        <span class="text-[9px] font-mono font-bold text-red-600 bg-red-100 dark:bg-red-950 px-1.5 py-0.5 rounded">🔒 BOOKED</span>
                                                    @else
                                                        <span class="text-[9px] font-mono font-bold text-emerald-600 bg-emerald-100 dark:bg-emerald-950 px-1.5 py-0.5 rounded">✓ AVAILABLE</span>
                                                    @endif
                                                </div>
                                                <h5 class="font-serif-luxury text-lg text-[#171513] dark:text-white font-medium pt-2">Evening Dinner</h5>
                                            </div>
                                            @if($cardEvening)
                                                <div class="py-2.5 px-3 bg-red-100 dark:bg-red-950/60 border border-red-300 dark:border-red-900/60 text-red-700 dark:text-red-400 text-xs font-mono font-bold text-center rounded-lg space-y-0.5">
                                                    <div>🔒 Already Booked</div>
                                                    <div class="text-[10px] font-sans font-normal opacity-90">Reserved for {{ $cardEvening->event_type ?? 'Event' }}</div>
                                                </div>
                                            @else
                                                <button onclick="Livewire.dispatch('openWalkInModal', { hall_id: '{{ $hall->id }}', event_date: '{{ $cardStr }}', slot_type: 'EVENING' })"
                                                        class="btn-gold !w-full !h-10 text-xs font-mono uppercase tracking-wider font-bold rounded-lg cursor-pointer shadow-sm hover:shadow-md transition">
                                                    Reserve Dinner Slot &rarr;
                                                </button>
                                            @endif
                                        </div>

                                        <!-- Night Shift -->
                                        <div class="p-4 bg-white dark:bg-[#171513] border-2 {{ $cardNight ? 'border-red-400 dark:border-red-900/60 bg-red-50/20' : 'border-emerald-500/40 hover:border-emerald-500' }} rounded-xl space-y-3 flex flex-col justify-between shadow-xs transition-colors">
                                            <div>
                                                <div class="flex items-center justify-between pb-1 border-b border-stone-200 dark:border-white/10">
                                                    <span class="text-[10px] font-mono uppercase font-bold text-[#8F7239] dark:text-[#C6A15B]">Shift 3 (11:00 PM - 3:00 AM)</span>
                                                    @if($cardNight)
                                                        <span class="text-[9px] font-mono font-bold text-red-600 bg-red-100 dark:bg-red-950 px-1.5 py-0.5 rounded">🔒 BOOKED</span>
                                                    @else
                                                        <span class="text-[9px] font-mono font-bold text-emerald-600 bg-emerald-100 dark:bg-emerald-950 px-1.5 py-0.5 rounded">✓ AVAILABLE</span>
                                                    @endif
                                                </div>
                                                <h5 class="font-serif-luxury text-lg text-[#171513] dark:text-white font-medium pt-2">Late Night Mehndi</h5>
                                            </div>
                                            @if($cardNight)
                                                <div class="py-2.5 px-3 bg-red-100 dark:bg-red-950/60 border border-red-300 dark:border-red-900/60 text-red-700 dark:text-red-400 text-xs font-mono font-bold text-center rounded-lg space-y-0.5">
                                                    <div>🔒 Already Booked</div>
                                                    <div class="text-[10px] font-sans font-normal opacity-90">Reserved for {{ $cardNight->event_type ?? 'Event' }}</div>
                                                </div>
                                            @else
                                                <button onclick="Livewire.dispatch('openWalkInModal', { hall_id: '{{ $hall->id }}', event_date: '{{ $cardStr }}', slot_type: 'NIGHT' })"
                                                        class="btn-gold !w-full !h-10 text-xs font-mono uppercase tracking-wider font-bold rounded-lg cursor-pointer shadow-sm hover:shadow-md transition">
                                                    Reserve Night Slot &rarr;
                                                </button>
                                            @endif
                                        </div>
                                        </div>
                                    @endif
                                @endforeach
                            @else
                                <div class="sm:col-span-3 rounded-xl border border-dashed border-[#C6A15B]/50 bg-white/60 dark:bg-white/[0.03] px-5 py-8 text-center">
                                    <p class="font-serif-luxury text-xl text-[#171513] dark:text-white">No open shifts in {{ $monthTitle }}</p>
                                    <p class="mt-1 text-xs font-mono text-stone-500 dark:text-stone-400">Choose another available month to continue your reservation.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
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
