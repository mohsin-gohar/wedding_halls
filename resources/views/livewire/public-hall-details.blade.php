<div class="bg-[#F8F5EF] dark:bg-[#171513] text-[#171513] dark:text-[#F8F5EF] min-h-screen"
     x-data="{ 
         bookedAlertOpen: false, 
         alertShift: '', 
         alertDate: '', 
         alertNote: '', 
         openBookedAlert(shift, date, note = '') { 
             this.alertShift = shift; 
             this.alertDate = date; 
             this.alertNote = note; 
             this.bookedAlertOpen = true; 
         } 
     }">

    @php
        $settings = is_array($hall->website_settings) ? $hall->website_settings : [];
        $venueJsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'EventVenue',
            'name' => $hall->name,
            'description' => $hall->description ?? 'Luxury wedding banquet ballroom and marquee on EventHub Pakistan.',
            'url' => route('hall.showcase', $hall->slug),
            'image' => $activePhoto,
            'maximumAttendeeCapacity' => (int) $hall->max_capacity,
            'priceRange' => 'PKR ' . number_format((float) $hall->base_rent),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $hall->venue->address ?? ($hall->venue->area ?? 'Main Boulevard'),
                'addressLocality' => $hall->venue->city ?? 'Lahore',
                'addressCountry' => 'PK',
            ],
        ];
    @endphp

    <script type="application/ld+json">{!! json_encode($venueJsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

    @if(($settings['show_announcement'] ?? false) && !empty($settings['announcement_banner']))
        <div class="bg-[#C6A15B] text-[#171513] py-2 px-4 text-center font-sans text-xs font-semibold tracking-wider uppercase flex items-center justify-center gap-2 shadow-sm">
            <span>{{ $settings['announcement_banner'] }}</span>
        </div>
    @endif

    <!-- ===================================================================
         SECTION 1: CINEMATIC GRAND HERO & IDENTITY
         =================================================================== -->
    <section class="relative bg-[#171513] text-white min-h-[580px] flex items-center justify-center overflow-hidden border-b border-[#C6A15B]/25">
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
                <nav class="flex items-center gap-2 text-xs font-sans tracking-wide text-stone-400">
                    <a href="{{ route('home') }}" class="hover:text-white transition">Home</a>
                    <span>/</span>
                    <a href="{{ route('public.venues') }}" class="hover:text-white transition">Venues</a>
                    <span>/</span>
                    <span class="text-[#C6A15B] font-medium">{{ $hall->name }}</span>
                </nav>

                @auth
                    @if(auth()->user()->tenant_id === $hall->tenant_id || auth()->user()->isSuperAdmin())
                        <div class="flex items-center gap-3">
                            <span class="text-[11px] font-sans font-semibold uppercase tracking-wider text-[#C6A15B] bg-[#C6A15B]/15 px-3 py-1 rounded-full border border-[#C6A15B]/40">
                                Rent Admin Mode
                            </span>
                            <a href="{{ route('hall.media') }}" class="btn-gold !h-8 !px-3.5 text-xs font-sans font-semibold">
                                Edit Photos &amp; Services Catalog &rarr;
                            </a>
                        </div>
                    @endif
                @endauth
            </div>

            <!-- Hall Title & Badges -->
            <div class="space-y-4 max-w-4xl">
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="px-3.5 py-1 rounded-full bg-[#C6A15B] text-[#171513] text-[11px] font-bold uppercase tracking-wider font-sans">
                        Verified Luxury Banquet
                    </span>
                    <span class="px-3.5 py-1 rounded-full bg-white/10 text-stone-200 text-[11px] font-sans tracking-wide border border-white/15">
                        {{ $hall->venue->city ?? 'Lahore' }} • {{ $hall->venue->area ?? 'Gulberg' }}
                    </span>
                    @if(!empty($videos) && count($videos) > 0)
                        <span class="px-3.5 py-1 rounded-full bg-[#171513]/90 text-[#F8F5EF] text-[11px] font-sans tracking-wide border border-[#C6A15B]/45 flex items-center gap-1.5">
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
                    <span class="text-xs font-sans uppercase tracking-wider text-stone-400">Base Shift Rental:</span>
                    <strong class="font-cinzel text-3xl sm:text-4xl text-[#C6A15B] font-bold">
                        PKR {{ number_format($hall->base_rent) }}
                    </strong>
                    <span class="text-xs text-stone-400 font-sans">/ Event Shift</span>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button wire:click="toggleWishlist" 
                            class="@if($isWishlisted) bg-[#C6A15B] text-[#171513] @else border border-white/20 bg-white/10 text-white @endif !h-11 !px-5 text-xs font-semibold rounded-lg flex items-center gap-2 transition-all duration-300 cursor-pointer">
                        <span class="w-2 h-2 @if($isWishlisted) bg-[#171513] @else bg-transparent @endif rounded-full border @if($isWishlisted) border-[#171513] @else border-white/60 @endif"></span>
                        <span>@if($isWishlisted) In Wishlist @else Add to Wishlist @endif</span>
                        <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded-full">{{ $wishlistCount }}</span>
                    </button>

                    <button onclick="Livewire.dispatch('openWalkInModal', { hall_id: '{{ $hall->id }}' })" 
                            class="btn-gold !h-11 !px-6 text-xs font-bold font-sans tracking-wide">
                        Reserve Event Shift &rarr;
                    </button>

                    @php
                        $hallWhatsappUrl = $hall->getWhatsappUrl("Hello {$hall->name}, I am interested in reserving an event date at your venue. Please share availability and packages.");
                        $hallPhone = $hall->getWhatsappNumber();
                    @endphp
                    @if($hallWhatsappUrl)
                        <a href="{{ $hallWhatsappUrl }}" 
                           target="_blank"
                           class="btn-outline-gold !h-11 !px-5 text-xs font-semibold text-white border-white/20 flex items-center gap-2 rounded-lg">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>WhatsApp Concierge</span>
                        </a>
                    @endif

                    <a href="#shift-matrix" class="px-4 py-2.5 rounded-lg border border-white/20 text-white text-xs font-sans tracking-wide hover:bg-white/10 transition">
                        View 14-Day Calendar &darr;
                    </a>
                </div>

                <!-- Public Hall Analytics Badges -->
                <div class="mt-6 pt-6 border-t border-white/10 flex items-center gap-6 text-xs font-sans text-stone-300">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-[#C6A15B]"></span>
                        <span>{{ $totalViews }} Views</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-[#8F7239]"></span>
                        <span>{{ $wishlistCount }} Wishlists</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-[#C6A15B]/50"></span>
                        <span>{{ $hall->min_capacity }} - {{ $hall->max_capacity }} Capacity</span>
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
            <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
                
                <!-- Metric 1: Capacity -->
                <div class="p-6 rounded-xl border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#1a1714] hover:border-[#C6A15B]/60 hover:shadow-md transition-all duration-300 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-sans uppercase font-semibold tracking-wider text-[#8F7239] dark:text-[#C6A15B]">Banquet Capacity</span>
                        <span class="text-xs">👥</span>
                    </div>
                    <strong class="font-cinzel text-2xl sm:text-3xl text-[#171513] dark:text-white block">
                        {{ $hall->min_capacity }} - {{ $hall->max_capacity }}
                    </strong>
                    <span class="text-[11px] text-stone-500 dark:text-stone-400 font-sans block">Seated Banquet Dining Format</span>
                </div>

                <!-- Metric 2: Climate Control -->
                <div class="p-6 rounded-xl border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#1a1714] hover:border-[#C6A15B]/60 hover:shadow-md transition-all duration-300 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-sans uppercase font-semibold tracking-wider text-[#8F7239] dark:text-[#C6A15B]">Climate Control</span>
                        <span class="text-xs">❄️</span>
                    </div>
                    <strong class="font-cinzel text-2xl sm:text-3xl text-[#171513] dark:text-white block">
                        {{ $hall->has_air_conditioning ? 'Central Chiller' : 'Natural Ventilation' }}
                    </strong>
                    <span class="text-[11px] text-stone-500 dark:text-stone-400 font-sans block">Zero Thermal Drift Guaranteed</span>
                </div>

                <!-- Metric 3: Generator -->
                <div class="p-6 rounded-xl border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#1a1714] hover:border-[#C6A15B]/60 hover:shadow-md transition-all duration-300 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-sans uppercase font-semibold tracking-wider text-[#8F7239] dark:text-[#C6A15B]">Power Reliability</span>
                        <span class="text-xs">⚡</span>
                    </div>
                    <strong class="font-cinzel text-2xl sm:text-3xl text-[#171513] dark:text-white block">
                        {{ $hall->has_generator_backup ? '100% Cat Power' : 'Standard Grid' }}
                    </strong>
                    <span class="text-[11px] text-stone-500 dark:text-stone-400 font-sans block">Zero-Flicker Automatic Synchronization</span>
                </div>

                <!-- Metric 4: VIP Suites -->
                <div class="p-6 rounded-xl border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#1a1714] hover:border-[#C6A15B]/60 hover:shadow-md transition-all duration-300 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-sans uppercase font-semibold tracking-wider text-[#8F7239] dark:text-[#C6A15B]">VIP Staging</span>
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
                <span class="text-[11px] font-sans uppercase tracking-[0.16em] text-[#8F7239] dark:text-[#C6A15B] block font-semibold">Visual Splendor</span>
                <h2 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                    Interactive Photography &amp; 4K Tour
                </h2>
            </div>
            <p class="text-xs text-stone-500 dark:text-stone-400 font-sans">
                Click any gallery photo below to inspect high-resolution hall details
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- Active Spotlight Image (7 cols) -->
            <div class="lg:col-span-7 space-y-4">
                <div class="aspect-[16/10] overflow-hidden relative border border-[#E7E5E4] dark:border-white/10 bg-stone-950 shadow-lg">
                    <img src="{{ $activePhoto }}" alt="{{ $hall->name }}" class="w-full h-full object-cover transition-all duration-500" onerror="this.src='https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=1200&auto=format&fit=crop'">
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
                            <img src="{{ $photo }}" alt="Thumbnail" class="w-full h-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=400&auto=format&fit=crop'">
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
                        <div class="space-y-3">
                            @foreach($videos as $vid)
                                <div class="aspect-video border border-[#E7E5E4] dark:border-white/10 overflow-hidden bg-black">
                                    @if(\App\Support\Media::isDirectVideo($vid))
                                        <video src="{{ $vid }}" controls class="w-full h-full" preload="metadata"></video>
                                    @else
                                        <iframe src="{{ \App\Support\Media::videoEmbedUrl($vid) }}" class="w-full h-full" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
                                    @endif
                                </div>
                            @endforeach
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
                    <span class="text-[11px] font-sans uppercase tracking-[0.16em] text-[#8F7239] dark:text-[#C6A15B] block font-semibold">Bespoke Offerings</span>
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
                        'image' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?q=80&w=800&auto=format&fit=crop',
                        'icon' => 'sparkles'
                    ],
                    [
                        'title' => '500 kVA Caterpillar Standby Power Generator',
                        'price' => 35000,
                        'is_complimentary' => true,
                        'category' => 'Infrastructure',
                        'description' => 'Zero-flicker automatic changeover with diesel backup covering 100% chiller AC load.',
                        'image' => 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?q=80&w=800&auto=format&fit=crop',
                        'icon' => 'bolt'
                    ],
                    [
                        'title' => 'Bespoke Fresh Rose Stage Decor & Walkway',
                        'price' => 85000,
                        'is_complimentary' => false,
                        'category' => 'Decor & Ambience',
                        'description' => 'Fresh imported gladiolus, red roses, golden filigree stage sofa, and floral aisle runners.',
                        'image' => 'https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=800&auto=format&fit=crop',
                        'icon' => 'star'
                    ],
                    [
                        'title' => 'Executive Chauffeur Valet Parking Service',
                        'price' => 20000,
                        'is_complimentary' => false,
                        'category' => 'Guest Logistics',
                        'description' => 'Dedicated 8-member uniformed valet team with electronic key logging and 250+ paved parking.',
                        'image' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?q=80&w=800&auto=format&fit=crop',
                        'icon' => 'truck'
                    ],
                    [
                        'title' => '4K Drone Aerial Cinematography & LED P3 Wall',
                        'price' => 60000,
                        'is_complimentary' => false,
                        'category' => 'Multimedia & Video',
                        'description' => 'Dual 16x10 ft P3 ultra-HD stage display screens with live dual-camera vision mixer for program broadcast.',
                        'image' => 'https://images.unsplash.com/photo-1508614589041-895b88991e3e?q=80&w=800&auto=format&fit=crop',
                        'icon' => 'camera'
                    ]
                ];
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                @foreach($catalogServices as $srv)
                    @php
                        $srvImage = \App\Models\Hall::resolveServiceImage($srv);
                        $isComplimentary = !empty($srv['is_complimentary']);
                        $srvPrice = (float)($srv['price'] ?? 0);
                    @endphp
                    <div class="group relative rounded-2xl overflow-hidden bg-white dark:bg-[#1a1714] border border-[#E7E5E4] dark:border-[#C6A15B]/25 hover:border-[#C6A15B] transition-all duration-300 shadow-sm hover:shadow-2xl hover:-translate-y-1.5 flex flex-col">
                        <!-- Luxury Card Photo Header with Smooth Zoom & Badges -->
                        <div class="relative aspect-[16/10] overflow-hidden bg-stone-900">
                            <img src="{{ $srvImage }}" 
                                 alt="{{ $srv['title'] ?? 'Venue Service' }}" 
                                 class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-700 ease-out" 
                                 loading="lazy">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/20 to-black/10"></div>
                            
                            <!-- Category Badge (Top Left) -->
                            <span class="absolute top-3.5 left-3.5 text-[10px] font-sans font-bold uppercase tracking-wider px-3 py-1 rounded-full bg-[#171513]/90 text-[#C6A15B] border border-[#C6A15B]/40 backdrop-blur-md shadow-md">
                                {{ $srv['category'] ?? 'Service' }}
                            </span>

                            <!-- Status Pill (Top Right) -->
                            @if($isComplimentary)
                                <span class="absolute top-3.5 right-3.5 text-[10px] font-sans font-bold uppercase tracking-wider px-3 py-1 rounded-full bg-emerald-950/85 text-emerald-400 border border-emerald-500/50 backdrop-blur-md shadow-md flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span>Complimentary</span>
                                </span>
                            @else
                                <span class="absolute top-3.5 right-3.5 text-[10px] font-sans font-semibold uppercase tracking-wider px-2.5 py-1 rounded-full bg-black/65 text-[#F8F5EF] border border-white/20 backdrop-blur-md">
                                    Add-on
                                </span>
                            @endif

                            <!-- Bottom Image Subtle Label -->
                            <div class="absolute bottom-3 left-4 right-4">
                                <span class="text-[9px] uppercase tracking-[0.2em] font-sans font-semibold text-stone-300/90 block">
                                    Exclusive Amenity
                                </span>
                            </div>
                        </div>

                        <!-- Card Body (Title & Description) -->
                        <div class="p-5 sm:p-6 flex-1 flex flex-col justify-between space-y-4">
                            <div class="space-y-2">
                                <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-medium group-hover:text-[#8F7239] dark:group-hover:text-[#C6A15B] transition-colors leading-snug">
                                    {{ $srv['title'] ?? 'Custom Service' }}
                                </h3>
                                <p class="text-xs sm:text-[13px] text-stone-600 dark:text-stone-300 font-normal leading-relaxed line-clamp-3">
                                    {{ $srv['description'] ?? 'Premium wedding amenity customized by banquet management.' }}
                                </p>
                            </div>

                            <!-- Card Footer: Pricing & Action Button -->
                            <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10 flex items-center justify-between gap-3">
                                <div>
                                    <span class="text-[9px] uppercase tracking-wider font-semibold text-stone-400 block font-sans">
                                        Pricing
                                    </span>
                                    @if($isComplimentary)
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-cinzel text-base sm:text-lg font-bold text-emerald-600 dark:text-emerald-400">
                                                INCLUDED
                                            </span>
                                            <span class="text-[10px] font-sans font-semibold text-stone-400">(Free)</span>
                                        </div>
                                    @else
                                        <strong class="font-cinzel text-lg sm:text-xl text-[#171513] dark:text-[#C6A15B] tracking-wide block">
                                            PKR {{ number_format($srvPrice) }}
                                        </strong>
                                    @endif
                                </div>

                                <button onclick="Livewire.dispatch('openWalkInModal', { hall_id: '{{ $hall->id }}' })" 
                                        class="btn-gold !h-9 !px-4 text-xs font-sans font-semibold tracking-wide flex items-center gap-1.5 whitespace-nowrap rounded-lg shadow-sm hover:scale-[1.02] transition-transform">
                                    <span>Inquire</span>
                                    <span>&rarr;</span>
                                </button>
                            </div>
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
                <span class="text-[11px] font-sans uppercase tracking-[0.16em] text-[#8F7239] dark:text-[#C6A15B] block font-semibold">Pakistani Bawarchi Feasts</span>
                <h2 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                    Banquet Catering &amp; Per-Head Menus
                </h2>
            </div>
            <p class="text-xs text-stone-500 dark:text-stone-400 font-sans">
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

    @if($venue)
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
                @elseif(!empty($latitude) && !empty($longitude))
                    <iframe class="w-full h-full" src="https://www.google.com/maps?q={{ $latitude }},{{ $longitude }}&z=15&output=embed" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                @else
                    <iframe class="w-full h-full" src="https://www.google.com/maps?q={{ urlencode($venue->city ?? 'Lahore') }},+{{ urlencode($venue->address ?? 'Pakistan') }}&output=embed" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                @endif
            </div>

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
    </section>
    @endif
    <!-- ===================================================================
         SECTION 5.6: LUXURY SHIFT AVAILABILITY CALENDAR (MATCHING IMAGE 2)
         =================================================================== -->
    <section class="py-20 bg-[#0e0c0a] text-white border-t border-b border-[#C6A15B]/30 relative overflow-hidden" id="shift-matrix">
        <!-- Subtle royal dark damask background glow -->
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(198,161,91,0.08)_0%,transparent_70%)] pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10 relative z-10">
            <!-- Header matching Image 2 -->
            <div class="text-center space-y-2 max-w-2xl mx-auto">
                <span class="text-[10px] sm:text-[11px] font-mono uppercase tracking-[0.28em] text-[#C6A15B] font-bold block">
                    Upcoming 14-Day Shift Availability • Multi-Year Engine ({{ min($allowedYears) }}-{{ max($allowedYears) }})
                </span>
                <h3 class="font-serif-luxury text-3xl sm:text-5xl text-white font-normal tracking-wide">
                    Check Hall Availability
                </h3>
                <p class="text-xs sm:text-sm text-stone-300 font-sans font-light">
                    Visual guide and live availability for upcoming wedding dates for {{ $hall->name }}. Select any date to inspect individual shifts.
                </p>
            </div>

            <!-- Main Golden Card Container (As shown in Image 2) -->
            <div class="max-w-6xl mx-auto rounded-3xl border-2 border-[#C6A15B]/50 bg-[#14110d]/95 shadow-[0_25px_70px_rgba(0,0,0,0.9),0_0_40px_rgba(198,161,91,0.2)] p-5 sm:p-8 lg:p-10 backdrop-blur-md">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    
                    <!-- LEFT COLUMN: The Monthly Calendar + Legend (8 cols) -->
                    <div class="lg:col-span-8 space-y-6">
                        
                        <!-- Year Jump Quick Pills (Dynamic Range) -->
                        <div class="flex items-center justify-between flex-wrap gap-2.5 pb-1">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-[10px] font-mono text-[#C6A15B] uppercase font-bold tracking-wider">Year:</span>
                                @foreach($allowedYears as $yr)
                                    <button type="button" 
                                            wire:click="jumpToYear({{ $yr }})" 
                                            class="px-2.5 py-1 rounded-md text-xs font-mono font-bold transition cursor-pointer {{ $selectedYear === $yr ? 'bg-[#C6A15B] text-[#16120b] shadow-[0_0_12px_rgba(198,161,91,0.5)]' : 'bg-[#18140f] text-stone-300 border border-[#C6A15B]/30 hover:border-[#C6A15B] hover:text-white' }}">
                                        {{ $yr }}
                                    </button>
                                @endforeach
                            </div>
                            <span class="text-[10px] font-mono text-stone-400">Multi-Year Engine ({{ min($allowedYears) }}-{{ max($allowedYears) }})</span>
                        </div>

                        <!-- Month Header Golden Banner (Brushed Gold with Arrows & Year/Month Selectors) -->
                        <div class="rounded-xl overflow-hidden border border-[#C6A15B]/40 shadow-lg">
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
                                            class="bg-black/10 hover:bg-black/20 text-[#16120b] font-serif-luxury font-black text-base sm:text-2xl uppercase tracking-wider border-none rounded-md px-2 py-0.5 focus:ring-0 cursor-pointer">
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
                                            class="bg-black/10 hover:bg-black/20 text-[#16120b] font-serif-luxury font-black text-base sm:text-2xl uppercase tracking-wider border-none rounded-md px-2 py-0.5 focus:ring-0 cursor-pointer">
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
                                        <span class="text-[10px] sm:text-[11px] font-mono font-semibold tracking-[0.18em] text-[#C6A15B]/70 uppercase py-1">{{ $dow }}</span>
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
                                            $isSelected = ($selectedDate === $cell['date']);
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

                        <!-- LEGEND Box (Exact placement as bottom-left of calendar in Image 2) -->
                        <div class="w-full max-w-xs border border-[#C6A15B]/40 rounded-xl overflow-hidden bg-[#17130e] shadow-lg">
                            <div class="bg-gradient-to-r from-[#9e7633] via-[#dfbe74] to-[#9e7633] text-[#16120b] font-bold text-[10px] tracking-widest font-mono uppercase px-3.5 py-1.5 text-center">
                                LEGEND
                            </div>
                            <div class="p-3 space-y-1.5 text-[11px] font-sans text-stone-300">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs">☀️</span>
                                    <span>Afternoon (11 AM - 3 PM)</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs">🌄</span>
                                    <span>Evening (4 PM - 8 PM)</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs">🌙</span>
                                    <span>Night (9 PM - 1 AM)</span>
                                </div>
                                <div class="flex items-center gap-4 pt-2 border-t border-[#C6A15B]/20 font-mono text-[10px]">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 bg-emerald-500 rounded-xs inline-block"></span>
                                        <span class="text-emerald-400 font-bold">Available</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 bg-red-600 rounded-xs inline-block"></span>
                                        <span class="text-red-400 font-bold">Booked</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- RIGHT COLUMN: Selected Day Shifts Panel (4 cols) -->
                    <div class="lg:col-span-4 space-y-5">
                        
                        <!-- Selected Day Header -->
                        <div class="border-b border-[#C6A15B]/30 pb-3">
                            <span class="text-[10px] uppercase font-mono tracking-widest text-[#C6A15B] font-bold block">Selected Day Shifts</span>
                            <h4 class="font-serif-luxury text-2xl sm:text-3xl font-medium text-white tracking-wide">
                                {{ $selectedDayDetails['formattedDate'] }}
                            </h4>
                        </div>

                        <!-- 3 Shift Cards -->
                        <div class="space-y-4">
                            
                            <!-- AFTERNOON SHIFT -->
                            <div class="border border-[#C6A15B]/35 rounded-xl bg-[#17130e] p-4 sm:p-5 space-y-3.5 shadow-md hover:border-[#dfbe74]/60 transition">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h5 class="font-serif-luxury text-base font-bold text-white tracking-wider">AFTERNOON</h5>
                                            <span class="text-base">☀️</span>
                                        </div>
                                        <span class="text-[11px] font-mono text-stone-400 block mt-0.5">11 AM - 3 PM</span>
                                    </div>
                                    @if($selectedDayDetails['afternoon']['isBooked'])
                                        <span class="px-2.5 py-0.5 rounded text-[10px] font-mono font-bold uppercase tracking-wider bg-red-950/80 border border-red-500/80 text-red-400">
                                            BOOKED
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded text-[10px] font-mono font-bold uppercase tracking-wider bg-emerald-950/80 border border-emerald-500/80 text-emerald-400">
                                            AVAILABLE
                                        </span>
                                    @endif
                                </div>
                                @if($selectedDayDetails['afternoon']['isBooked'])
                                    <div class="text-[11px] font-mono text-red-400/90 flex items-center gap-1.5">
                                        <span>🔒 Already Booked</span>
                                    </div>
                                @endif
                                <button type="button" 
                                        wire:click="triggerBooking('AFTERNOON', {{ $selectedDayDetails['afternoon']['isBooked'] ? 'true' : 'false' }})"
                                        class="w-full py-2.5 px-4 rounded-lg font-mono font-bold text-xs uppercase tracking-wider transition shadow cursor-pointer
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
                                            <h5 class="font-serif-luxury text-base font-bold text-white tracking-wider">EVENING</h5>
                                            <span class="text-base">🌄</span>
                                        </div>
                                        <span class="text-[11px] font-mono text-stone-400 block mt-0.5">4 PM - 8 PM</span>
                                    </div>
                                    @if($selectedDayDetails['evening']['isBooked'])
                                        <span class="px-2.5 py-0.5 rounded text-[10px] font-mono font-bold uppercase tracking-wider bg-red-950/80 border border-red-500/80 text-red-400">
                                            BOOKED
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded text-[10px] font-mono font-bold uppercase tracking-wider bg-emerald-950/80 border border-emerald-500/80 text-emerald-400">
                                            AVAILABLE
                                        </span>
                                    @endif
                                </div>
                                @if($selectedDayDetails['evening']['isBooked'])
                                    <div class="text-[11px] font-mono text-red-400/90 flex items-center gap-1.5">
                                        <span>🔒 Already Booked</span>
                                    </div>
                                @endif
                                <button type="button" 
                                        wire:click="triggerBooking('EVENING', {{ $selectedDayDetails['evening']['isBooked'] ? 'true' : 'false' }})"
                                        class="w-full py-2.5 px-4 rounded-lg font-mono font-bold text-xs uppercase tracking-wider transition shadow cursor-pointer
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
                                            <h5 class="font-serif-luxury text-base font-bold text-white tracking-wider">NIGHT</h5>
                                            <span class="text-base">🌙</span>
                                        </div>
                                        <span class="text-[11px] font-mono text-stone-400 block mt-0.5">9 PM - 1 AM</span>
                                    </div>
                                    @if($selectedDayDetails['night']['isBooked'])
                                        <span class="px-2.5 py-0.5 rounded text-[10px] font-mono font-bold uppercase tracking-wider bg-red-950/80 border border-red-500/80 text-red-400">
                                            BOOKED
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded text-[10px] font-mono font-bold uppercase tracking-wider bg-emerald-950/80 border border-emerald-500/80 text-emerald-400">
                                            AVAILABLE
                                        </span>
                                    @endif
                                </div>
                                @if($selectedDayDetails['night']['isBooked'])
                                    <div class="text-[11px] font-mono text-red-400/90 flex items-center gap-1.5">
                                        <span>🔒 Already Booked</span>
                                    </div>
                                @endif
                                <button type="button" 
                                        wire:click="triggerBooking('NIGHT', {{ $selectedDayDetails['night']['isBooked'] ? 'true' : 'false' }})"
                                        class="w-full py-2.5 px-4 rounded-lg font-mono font-bold text-xs uppercase tracking-wider transition shadow cursor-pointer
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
                        @if($hallPhone = $hall->getWhatsappNumber())<span>📞 {{ $hallPhone }}</span>@endif
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
                                  class="btn-gold !w-full !h-12 text-xs font-bold font-sans tracking-wide rounded-xl shadow-md transition-all duration-200">
                              <span>Instant Shift Reservation &rarr;</span>
                          </button>

                          @if($hallWhatsappUrl = $hall->getWhatsappUrl("Hello {$hall->name}, I would like to reserve an event shift at your venue."))
                          <a href="{{ $hallWhatsappUrl }}" 
                             target="_blank"
                             class="btn-outline-gold !w-full !h-11 text-xs font-semibold flex items-center justify-center gap-2 rounded-xl transition-all duration-200">
                              <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                              <span>Chat with {{ $hall->name }} on WhatsApp</span>
                          </a>
                          @endif
                        @if($hallPhone = $hall->getWhatsappNumber())
                        <a href="tel:{{ $hallPhone }}" 
                           class="w-full h-10 bg-transparent border border-white/10 text-stone-300 text-xs font-sans rounded-xl flex items-center justify-center gap-2 hover:bg-white/5 transition block">
                            Direct Hotline: {{ $hallPhone }}
                        </a>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ===================================================================
         ROYAL LUXURY ALERT MODAL FOR BOOKED SHIFTS
         =================================================================== -->
    <template x-teleport="body">
        <div x-cloak 
             x-show="bookedAlertOpen" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/85 backdrop-blur-md"
             @keydown.escape.window="bookedAlertOpen = false"
             role="dialog"
             aria-modal="true"
             aria-labelledby="booked-alert-title">

            <div x-show="bookedAlertOpen"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-3"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-3"
                 class="relative w-full max-w-lg bg-[#171513] border border-[#C6A15B]/50 rounded-2xl shadow-[0_24px_64px_rgba(0,0,0,0.85)] text-white overflow-hidden p-6 sm:p-8 space-y-6"
                 @click.outside="bookedAlertOpen = false">

                <!-- Gold Crest Emblem -->
                <div class="relative mx-auto w-16 h-16 rounded-full bg-[#C6A15B]/10 border border-[#C6A15B]/50 flex items-center justify-center shadow-sm">
                    <svg class="w-8 h-8 text-[#DFBF7A]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span class="absolute -bottom-2 px-2.5 py-0.5 bg-[#C6A15B] text-[#171513] text-[9px] font-sans font-bold tracking-wider uppercase rounded-full shadow-xs">
                        LOCKED
                    </span>
                </div>

                <!-- Text Content -->
                <div class="text-center space-y-2">
                    <span class="text-[10px] font-sans uppercase tracking-[0.18em] text-[#C6A15B] font-semibold block">
                        Exclusive Private Reservation
                    </span>
                    <h3 id="booked-alert-title" class="font-serif-luxury text-2xl sm:text-3xl text-white font-medium">
                        Shift Already Locked &amp; Reserved
                    </h3>
                    <p class="text-sm font-sans text-stone-300 leading-relaxed">
                        The <span class="text-[#DFBF7A] font-bold" x-text="alertShift"></span> on <span class="text-white font-bold" x-text="alertDate"></span> is confirmed for an exclusive celebration.
                    </p>
                </div>

                <!-- Royal Policy Box -->
                <div class="p-4 rounded-xl bg-white/[0.03] border border-[#C6A15B]/40 space-y-2 text-xs text-stone-300 leading-relaxed">
                    <div class="flex items-center gap-2 text-[#DFBF7A] font-semibold text-xs">
                        <svg class="w-4 h-4 shrink-0 text-[#C6A15B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <span>Zero Double-Booking Guarantee</span>
                    </div>
                    <p>
                        To uphold our royal luxury hospitality standard, EventHub guarantees absolute private venue occupancy for every confirmed client. No secondary reservations are permitted for this shift.
                    </p>
                    <div class="pt-1 flex items-center justify-between text-[11px] font-sans text-[#C6A15B] border-t border-white/5">
                        <span>Status: Token Confirmed</span>
                        <span>Hall: {{ $hall->name }}</span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-1">
                    <button type="button" 
                            @click="bookedAlertOpen = false"
                            class="btn-gold !w-full sm:!w-auto !min-w-[160px] !h-11 font-sans tracking-wide text-xs font-bold rounded-xl cursor-pointer shadow-md transition">
                        Browse Open Shifts
                    </button>
                    <button type="button" 
                            @click="bookedAlertOpen = false"
                            class="w-full sm:w-auto px-5 h-11 border border-[#C6A15B]/40 hover:border-[#C6A15B] text-stone-300 hover:text-white text-xs font-sans tracking-wide rounded-xl transition cursor-pointer">
                        Dismiss Notice
                    </button>
                </div>
            </div>
        </div>
    </template>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        @livewire('hall-reviews', ['hallId' => $hall->id])
    </div>
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'EventVenue',
            'name' => $hall->name,
            'description' => $hall->description ?? $hall->tagline,
            'url' => url()->current(),
            'telephone' => optional($hall->tenant)->phone,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => optional($hall->venue)->address,
                'addressLocality' => optional($hall->venue)->city,
                'addressCountry' => 'PK',
            ],
            'maximumAttendeeCapacity' => $hall->max_capacity,
            'priceRange' => 'PKR ' . number_format($hall->base_rent),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>


</div>
