<div class="space-y-8 max-w-7xl mx-auto pb-16">
    
    <!-- Header Banner -->
    <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-6 sm:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6 shadow-xl transition-colors duration-200">
        <div class="space-y-2">
            <div class="flex items-center gap-2 font-mono text-[11px] text-[#8F7239] dark:text-[#C6A15B] uppercase tracking-widest font-bold">
                <span>Rent Admin Suite</span>
                <span class="text-stone-400 dark:text-stone-600">•</span>
                <span>Hall Website &amp; Identity Manager</span>
            </div>
            <h1 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                {{ $name ?: 'Your Hall Website & Settings' }}
            </h1>
            <p class="text-xs text-stone-600 dark:text-stone-300 font-sans max-w-2xl leading-relaxed">
                Configure your hall name, banquet brand, address, shift rental rates, photo gallery, and 4K video showcase. All updates appear immediately on your live hall website.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            @if($hall)
                <a href="{{ route('hall.showcase', $hall->slug) }}" target="_blank"
                   class="btn-outline-gold !h-9 !px-4 text-xs font-mono uppercase tracking-wider flex items-center gap-2 font-bold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    <span>View Live Hall Website ↗</span>
                </a>
            @endif

            <button wire:click="openCreateModal" class="px-4 py-2 bg-stone-100 dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/20 text-[#171513] dark:text-white text-xs font-mono uppercase tracking-wider hover:border-[#C6A15B] transition font-bold">
                + Add Another Hall
            </button>

         <button wire:click="save" class="btn-gold !h-10 !px-6 text-xs font-bold font-mono uppercase tracking-wider shadow-lg">
             Save All Changes
         </button>
        </div>
    </div>

    @if(session('message'))
        <div class="p-4 bg-[#171513]/85 border border-[#C6A15B]/45 text-[#C6A15B] text-xs font-mono flex items-center gap-3">
            <span class="text-[#8F7239] font-bold">✓</span>
            <span>{{ session('message') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-[#171513]/85 border border-[#C6A15B]/45 text-[#F8F5EF] text-xs font-mono flex items-center gap-3">
            <span class="text-[#171513] font-bold">⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Hall Selector (If Multiple Halls Exist) -->
    @if($allHalls->count() > 1)
        <div class="bg-[#171513] border border-white/10 p-4 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="text-xs font-mono text-white/60 uppercase">Managing Hall:</span>
                <div class="flex flex-wrap gap-2">
                    @foreach($allHalls as $h)
                        <button type="button" wire:click="switchHall('{{ $h->id }}')" 
                                class="px-3 py-1 text-xs font-mono border transition {{ $selectedHallId === $h->id ? 'bg-[#C6A15B] text-[#171513] border-[#C6A15B] font-bold' : 'bg-[#171513] text-white/80 border-white/15 hover:border-white/40' }}">
                            {{ $h->name }}
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" wire:click="deleteHall('{{ $selectedHallId }}')"
                        wire:confirm="Are you sure you want to permanently delete this hall space? This action cannot be undone."
                        class="px-3 py-1 bg-[#171513]/90 border border-[#C6A15B]/50 text-[#F8F5EF] hover:text-white hover:bg-[#171513] text-xs font-mono uppercase transition">
                    Delete Current Hall ✕
                </button>
                <span class="text-[11px] font-mono text-[#C6A15B]">Total {{ $allHalls->count() }} Halls in Complex</span>
            </div>
        </div>
    @endif

    <!-- Main Grid: Left Settings (Name, Venue, Specs), Right Gallery & Video -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left: Specifications & Identity (7 cols) -->
        <div class="lg:col-span-7 space-y-6">
            
            <!-- Hall & Branding Card -->
            <div class="bg-[#171513] border border-white/10 p-6 space-y-5">
                <div class="border-b border-white/10 pb-3 flex items-center justify-between">
                    <span class="text-xs font-mono uppercase tracking-widest text-[#C6A15B]">1. Brand & Hall Identity</span>
                    <span class="text-[10px] font-mono text-white/40">UUID: {{ substr($selectedHallId, 0, 8) }}</span>
                </div>

                <div class="space-y-4">
                    <!-- Hall Name -->
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-white/80 mb-1">
                            Hall / Marquee Name *
                        </label>
                        <input type="text" wire:model="name"
                               placeholder="e.g. Royal Grand Ballroom"
                               class="w-full bg-[#171513] border border-white/15 text-white text-sm px-3.5 py-2.5 focus:border-[#C6A15B] outline-none">
                        @error('name') <span class="text-rose-400 text-[11px] mt-1 block font-mono">{{ $message }}</span> @enderror
                        <p class="text-[11px] text-white/40 mt-1">This is the title displayed prominently on your hall's website.</p>
                    </div>

                    <!-- Complex / Business Name -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider text-white/80 mb-1">
                                Venue Complex / Business Name *
                            </label>
                            <input type="text" wire:model="venue_name"
                                   placeholder="e.g. Royal Palm Banquets Complex"
                                   class="w-full bg-[#171513] border border-white/15 text-white text-sm px-3.5 py-2.5 focus:border-[#C6A15B] outline-none">
                            @error('venue_name') <span class="text-rose-400 text-[11px] mt-1 block font-mono">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider text-white/80 mb-1">
                                City *
                            </label>
                            <input type="text" wire:model="city" placeholder="e.g. Lahore"
                                   class="w-full bg-[#171513] border border-white/15 text-white text-sm px-3.5 py-2.5 focus:border-[#C6A15B] outline-none">
                        </div>
                    </div>

                    <!-- Address & Phone -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider text-white/80 mb-1">
                                Physical Address / Landmark
                            </label>
                            <input type="text" wire:model="address"
                                   placeholder="Plot 42, Main Boulevard, Gulberg III"
                                   class="w-full bg-[#171513] border border-white/15 text-white text-sm px-3.5 py-2.5 focus:border-[#C6A15B] outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider text-white/80 mb-1">
                                Direct Phone / WhatsApp
                            </label>
                            <input type="text" wire:model="phone"
                                   placeholder="+92 300 1234567"
                                   class="w-full bg-[#171513] border border-white/15 text-white text-sm px-3.5 py-2.5 focus:border-[#C6A15B] outline-none">
                        </div>
                    </div>

                    <!-- Location: Latitude / Longitude / Google Maps -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider text-white/80 mb-1">
                                Latitude
                            </label>
                            <input type="number" step="0.000001" wire:model="latitude"
                                   placeholder="e.g. 31.5497"
                                   class="w-full bg-[#171513] border border-white/15 text-white text-sm px-3.5 py-2.5 focus:border-[#C6A15B] outline-none font-mono">
                            @error('latitude') <span class="text-rose-400 text-[11px] mt-1 block font-mono">{{ $message }}</span> @enderror
                            <p class="text-[11px] text-white/40 mt-1">Map latitude coordinate of your hall.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider text-white/80 mb-1">
                                Longitude
                            </label>
                            <input type="number" step="0.000001" wire:model="longitude"
                                   placeholder="e.g. 74.3436"
                                   class="w-full bg-[#171513] border border-white/15 text-white text-sm px-3.5 py-2.5 focus:border-[#C6A15B] outline-none font-mono">
                            @error('longitude') <span class="text-rose-400 text-[11px] mt-1 block font-mono">{{ $message }}</span> @enderror
                            <p class="text-[11px] text-white/40 mt-1">Map longitude coordinate of your hall.</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-white/80 mb-1">
                            Google Maps Share URL
                        </label>
                        <input type="url" wire:model="google_maps_url"
                               placeholder="https://maps.google.com/?q=..."
                               class="w-full bg-[#171513] border border-white/15 text-white text-sm px-3.5 py-2.5 focus:border-[#C6A15B] outline-none">
                        @error('google_maps_url') <span class="text-rose-400 text-[11px] mt-1 block font-mono">{{ $message }}</span> @enderror
                        <p class="text-[11px] text-white/40 mt-1">Copied share link from Google Maps for your hall's location.</p>
                    </div>

                    <!-- Tagline -->
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-white/80 mb-1">
                            Hall Subtitle / Tagline
                        </label>
                        <input type="text" wire:model="tagline"
                               placeholder="e.g. Pakistan's Crown Jewel in Royal Banqueting & Celebrations"
                               class="w-full bg-[#171513] border border-white/15 text-white text-sm px-3.5 py-2.5 focus:border-[#C6A15B] outline-none">
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-white/80 mb-1">
                            Hall Editorial Description
                        </label>
                        <textarea wire:model="description" rows="3"
                                  placeholder="Describe the crystal chandeliers, sound system, bridal room, and decor setup..."
                                  class="w-full bg-[#171513] border border-white/15 text-white text-sm p-3 focus:border-[#C6A15B] outline-none"></textarea>
                    </div>

                    <!-- Operating Hours & Policies -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider text-white/80 mb-1">
                                Operating Shift Timings
                            </label>
                            <input type="text" wire:model="operating_hours"
                                   placeholder="Morning (11:30 AM - 3:30 PM) | Evening (7:00 PM - 12:00 AM)"
                                   class="w-full bg-[#171513] border border-white/15 text-white text-sm px-3.5 py-2.5 focus:border-[#C6A15B] outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider text-white/80 mb-1">
                                Booking Deposit &amp; Hall Rules
                            </label>
                            <textarea wire:model="policies" rows="2"
                                      placeholder="e.g. 50% Advance deposit required. Sound permitted till 10 PM."
                                      class="w-full bg-[#171513] border border-white/15 text-white text-sm p-2.5 focus:border-[#C6A15B] outline-none"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Capacity, Pricing & Facilities Card -->
            <div class="bg-[#171513] border border-white/10 p-6 space-y-5">
                <div class="border-b border-white/10 pb-3">
                    <span class="text-xs font-mono uppercase tracking-widest text-[#C6A15B]">2. Capacity &amp; Shift Pricing (PKR)</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-white/80 mb-1">
                            Base Shift Rent (PKR) *
                        </label>
                        <input type="number" wire:model="base_rent" step="5000"
                               class="w-full bg-[#171513] border border-white/15 text-white text-sm px-3.5 py-2.5 focus:border-[#C6A15B] outline-none font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-white/80 mb-1">
                            Min Capacity *
                        </label>
                        <input type="number" wire:model="min_capacity" step="10"
                               class="w-full bg-[#171513] border border-white/15 text-white text-sm px-3.5 py-2.5 focus:border-[#C6A15B] outline-none font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-white/80 mb-1">
                            Max Capacity *
                        </label>
                        <input type="number" wire:model="max_capacity" step="25"
                               class="w-full bg-[#171513] border border-white/15 text-white text-sm px-3.5 py-2.5 focus:border-[#C6A15B] outline-none font-mono">
                    </div>
                </div>

                <!-- Facilities Switches -->
                <div class="pt-4 border-t border-white/10 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="has_air_conditioning" class="accent-[#C6A15B] w-4 h-4">
                        <span class="text-xs font-mono text-white/80">Central AC</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="has_generator_backup" class="accent-[#C6A15B] w-4 h-4">
                        <span class="text-xs font-mono text-white/80">100% Generator Backup</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="has_bridal_room" class="accent-[#C6A15B] w-4 h-4">
                        <span class="text-xs font-mono text-white/80">VIP Bridal Suite</span>
                    </label>
                </div>
            </div>

            <!-- Services & Products Add-ons Catalog Card -->
            <div class="bg-[#171513] border border-white/10 p-6 space-y-6">
                <div class="border-b border-white/10 pb-3 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-mono uppercase tracking-widest text-[#C6A15B] block font-bold">3. Services &amp; Products Catalog</span>
                        <p class="text-[11px] text-white/50 font-sans mt-0.5">Manage your venue services, amenities, and decor add-ons like an online store catalog.</p>
                    </div>
                    <span class="text-[11px] font-mono text-[#C6A15B]">{{ count($services) }} Items in Catalog</span>
                </div>

                <!-- Add New Service Form -->
                <div class="bg-[#171513] border border-[#C6A15B]/30 p-4 space-y-4">
                    <span class="text-[10px] font-mono uppercase tracking-widest text-[#C6A15B] block font-bold">+ Add Service / Product to Catalog</span>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-white/70 mb-1">
                                Service / Product Title *
                            </label>
                            <input type="text" wire:model="newServiceTitle" placeholder="e.g. Royal Fresh Rose Stage Decor"
                                   class="w-full bg-[#171513] border border-white/15 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                            @error('newServiceTitle') <span class="text-rose-400 text-[10px] font-mono mt-0.5 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-white/70 mb-1">
                                Category *
                            </label>
                            <select wire:model="newServiceCategory" class="w-full bg-[#171513] border border-white/15 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                                <option value="Decor & Ambience">Decor &amp; Ambience</option>
                                <option value="VIP Hospitality">VIP Hospitality</option>
                                <option value="Infrastructure">Infrastructure &amp; Power</option>
                                <option value="Guest Logistics">Guest Logistics &amp; Valet</option>
                                <option value="Multimedia & Video">Multimedia &amp; Video</option>
                                <option value="Catering Add-on">Catering Add-on</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-center">
                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-white/70 mb-1">
                                Price (PKR)
                            </label>
                            <input type="number" wire:model="newServicePrice" step="500" placeholder="0"
                                   class="w-full bg-[#171513] border border-white/15 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>

                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-white/70 mb-1">
                                Icon Theme
                            </label>
                            <select wire:model="newServiceIcon" class="w-full bg-[#171513] border border-white/15 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                                <option value="sparkles">✨ Sparkles (Luxury)</option>
                                <option value="star">⭐ Star (VIP)</option>
                                <option value="bolt">⚡ Bolt (Generator/Power)</option>
                                <option value="truck">🚗 Car (Valet/Transport)</option>
                                <option value="camera">🎥 Camera (Media/LED)</option>
                                <option value="utensils">🍽️ Utensils (Catering)</option>
                            </select>
                        </div>

                        <div class="pt-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="newServiceComplimentary" class="accent-[#C6A15B] w-4 h-4">
                                <span class="text-xs font-mono text-[#8F7239] font-semibold">Complimentary (Free)</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-white/70 mb-1">
                                Service Highlights / Description
                            </label>
                            <input type="text" wire:model="newServiceDescription" placeholder="Brief explanation displayed on your hall's public website..."
                                   class="w-full bg-[#171513] border border-white/15 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                        </div>

                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-white/70 mb-1">
                                Service Photo URL <span class="text-white/40 font-normal">(Optional - auto-assigned if blank)</span>
                            </label>
                            <input type="url" wire:model="newServiceImage" placeholder="https://images.unsplash.com/... (or leave blank)"
                                   class="w-full bg-[#171513] border border-white/15 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                        </div>
                    </div>

                    <div class="pt-1 flex justify-end">
                        <button type="button" wire:click="addService" class="btn-gold !h-8 !px-4 text-xs font-bold uppercase font-mono">
                            + Add Service to Live Catalog
                        </button>
                    </div>
                </div>

                <!-- Existing Services List -->
                <div class="space-y-3 pt-2">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-white/60 block">Current Catalog Items:</span>
                    
                    @forelse($services as $sIdx => $srv)
                        <div class="bg-[#171513] border border-white/10 p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:border-white/20 transition rounded-lg">
                            <div class="flex items-start sm:items-center gap-3.5 min-w-0">
                                <!-- Service Thumbnail Image -->
                                <div class="w-14 h-14 shrink-0 rounded-lg overflow-hidden bg-stone-900 border border-white/15 relative">
                                    <img src="{{ \App\Models\Hall::resolveServiceImage($srv) }}" 
                                         alt="{{ $srv['title'] ?? 'Service' }}" 
                                         class="w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-black/15"></div>
                                </div>

                                <div class="space-y-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="font-bold text-white text-xs truncate">{{ $srv['title'] ?? 'Custom Service' }}</h4>
                                        <span class="text-[9px] font-mono uppercase px-2 py-0.5 rounded bg-white/10 text-white/80">
                                            {{ $srv['category'] ?? 'General' }}
                                        </span>
                                        @if(!empty($srv['is_complimentary']))
                                            <span class="text-[9px] font-mono uppercase px-2 py-0.5 rounded bg-[#171513] text-[#C6A15B] border border-[#C6A15B]/45 font-semibold">
                                                Complimentary
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-[11px] text-white/60 font-sans max-w-xl line-clamp-1">{{ $srv['description'] ?? '' }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 shrink-0">
                                <div class="text-right font-mono">
                                    @if(!empty($srv['is_complimentary']))
                                        <span class="text-[#8F7239] text-xs font-bold">FREE</span>
                                    @else
                                        <span class="text-white text-xs font-bold">PKR {{ number_format($srv['price'] ?? 0) }}</span>
                                    @endif
                                </div>

                                <button type="button" wire:click="toggleServiceComplimentary({{ $sIdx }})" 
                                        class="px-2 py-1 text-[10px] font-mono border border-white/20 text-white/70 hover:text-white transition">
                                    {{ !empty($srv['is_complimentary']) ? 'Make Paid' : 'Make Free' }}
                                </button>

                                <button type="button" wire:click="removeService({{ $sIdx }})" 
                                        class="px-2 py-1 text-[10px] font-mono bg-[#171513]/85 text-[#F8F5EF] border border-[#C6A15B]/40 hover:bg-[#171513] transition">
                                    &times; Delete
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-white/40 text-xs font-mono border border-dashed border-white/10">
                            No custom services added yet. Use the form above to add your decor, valet, or generator add-ons.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Website Builder: Section Visibility & Custom Announcement Card -->
            <div class="bg-[#171513] border border-white/10 p-6 space-y-6">
                <div class="border-b border-white/10 pb-3 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-mono uppercase tracking-widest text-[#C6A15B] block font-bold">4. Website Builder &amp; Section Layout</span>
                        <p class="text-[11px] text-white/50 font-sans mt-0.5">Control which opulent sections appear on your public venue landing page.</p>
                    </div>
                    <span class="text-[11px] font-mono text-[#C6A15B]">2026 Modern Architecture</span>
                </div>

                <!-- Custom Announcement Banner -->
                <div class="bg-[#171513] border border-white/15 p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] font-semibold">
                            Announcement Headline Strip
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer font-mono text-[11px]">
                            <input type="checkbox" wire:model="website_settings.show_announcement" class="accent-[#C6A15B] w-4 h-4">
                            <span class="text-stone-300">Display on Live Site</span>
                        </label>
                    </div>
                    <input type="text" wire:model="website_settings.announcement_banner" 
                           placeholder="e.g. Winter 2026 Bookings Open — Complimentary VIP Bridal Room & Valet Included!"
                           class="w-full bg-[#171513] border border-white/15 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B]">
                </div>

                <!-- Section Visibility Switches -->
                <div class="space-y-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-white/70 block font-semibold">Landing Page Section Modules:</span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="flex items-center justify-between p-3 bg-[#171513] border border-white/10 cursor-pointer">
                            <span class="text-xs font-mono text-stone-300">🎥 4K Virtual Video Showcase</span>
                            <input type="checkbox" wire:model="website_settings.show_video_tour" class="accent-[#C6A15B] w-4 h-4">
                        </label>

                        <label class="flex items-center justify-between p-3 bg-[#171513] border border-white/10 cursor-pointer">
                            <span class="text-xs font-mono text-stone-300">✨ Services &amp; Decor Catalog</span>
                            <input type="checkbox" wire:model="website_settings.show_services" class="accent-[#C6A15B] w-4 h-4">
                        </label>

                        <label class="flex items-center justify-between p-3 bg-[#171513] border border-white/10 cursor-pointer">
                            <span class="text-xs font-mono text-stone-300">🍽️ Catering Packages &amp; Menus</span>
                            <input type="checkbox" wire:model="website_settings.show_packages" class="accent-[#C6A15B] w-4 h-4">
                        </label>

                        <label class="flex items-center justify-between p-3 bg-[#171513] border border-white/10 cursor-pointer">
                            <span class="text-xs font-mono text-stone-300">📅 Live Shift Availability Grid</span>
                            <input type="checkbox" wire:model="website_settings.show_shift_matrix" class="accent-[#C6A15B] w-4 h-4">
                        </label>

                        <label class="flex items-center justify-between p-3 bg-[#171513] border border-white/10 cursor-pointer">
                            <span class="text-xs font-mono text-stone-300">❓ Custom FAQs Accordion</span>
                            <input type="checkbox" wire:model="website_settings.show_faqs" class="accent-[#C6A15B] w-4 h-4">
                        </label>

                        <label class="flex items-center justify-between p-3 bg-[#171513] border border-white/10 cursor-pointer">
                            <span class="text-xs font-mono text-stone-300">⭐ Client Testimonials / Reviews</span>
                            <input type="checkbox" wire:model="website_settings.show_reviews" class="accent-[#C6A15B] w-4 h-4">
                        </label>
                    </div>
                </div>
            </div>

            <!-- Custom FAQs & Reviews Card -->
            <div class="bg-[#171513] border border-white/10 p-6 space-y-6">
                <div class="border-b border-white/10 pb-3 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-mono uppercase tracking-widest text-[#C6A15B] block font-bold">5. FAQs &amp; Client Testimonials</span>
                        <p class="text-[11px] text-white/50 font-sans mt-0.5">Build trust with prospective couples by adding authentic questions and real wedding feedback.</p>
                    </div>
                </div>

                <!-- Add FAQ Sub-form -->
                <div class="bg-[#171513] border border-white/10 p-4 space-y-3">
                    <span class="text-[10px] font-mono uppercase text-[#C6A15B] tracking-wider font-bold">+ Add FAQ Question</span>
                    <input type="text" wire:model="newFaqQuestion" placeholder="e.g. Can we bring our own event floral decorator?"
                           class="w-full bg-[#171513] border border-white/15 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                    <textarea wire:model="newFaqAnswer" rows="2" placeholder="Answer text..."
                              class="w-full bg-[#171513] border border-white/15 text-white text-xs p-2.5 outline-none focus:border-[#C6A15B]"></textarea>
                    <div class="flex justify-end">
                        <button type="button" wire:click="addFaq" class="btn-gold !h-8 !px-3 text-xs font-mono uppercase">
                            + Add FAQ to Website
                        </button>
                    </div>
                </div>

                <!-- Current FAQs -->
                @if(!empty($website_settings['faqs']))
                    <div class="space-y-2">
                        <span class="text-[10px] font-mono uppercase text-white/60">Active FAQs on Website:</span>
                        @foreach($website_settings['faqs'] as $fIdx => $faqItem)
                            <div class="p-3 bg-[#171513] border border-white/10 flex items-start justify-between gap-3 text-xs">
                                <div>
                                    <strong class="text-white block">{{ $faqItem['question'] }}</strong>
                                    <span class="text-stone-400 text-[11px] mt-0.5 block">{{ $faqItem['answer'] }}</span>
                                </div>
                                <button type="button" wire:click="removeFaq({{ $fIdx }})" class="text-[#171513] hover:text-[#F8F5EF] font-mono text-xs">
                                    Remove
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- Add Testimonial Sub-form -->
                <div class="bg-[#171513] border border-white/10 p-4 space-y-3 pt-4 border-t border-white/15">
                    <span class="text-[10px] font-mono uppercase text-[#C6A15B] tracking-wider font-bold">+ Add Client Testimonial</span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <input type="text" wire:model="newReviewAuthor" placeholder="Client Name (e.g. Usman &amp; Ayesha)"
                               class="w-full bg-[#171513] border border-white/15 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                        <input type="text" wire:model="newReviewEvent" placeholder="Event Type (e.g. Barat Reception — 600 Guests)"
                               class="w-full bg-[#171513] border border-white/15 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                    </div>
                    <textarea wire:model="newReviewQuote" rows="2" placeholder="Testimonial text (e.g. The grand crystal chandeliers and cooling were exceptional...)"
                              class="w-full bg-[#171513] border border-white/15 text-white text-xs p-2.5 outline-none focus:border-[#C6A15B]"></textarea>
                    <div class="flex justify-end">
                        <button type="button" wire:click="addReview" class="btn-gold !h-8 !px-3 text-xs font-mono uppercase">
                            + Add Testimonial to Website
                        </button>
                    </div>
                </div>

                <!-- Current Reviews -->
                @if(!empty($website_settings['reviews']))
                    <div class="space-y-2">
                        <span class="text-[10px] font-mono uppercase text-white/60">Active Testimonials:</span>
                        @foreach($website_settings['reviews'] as $rIdx => $revItem)
                            <div class="p-3 bg-[#171513] border border-white/10 flex items-start justify-between gap-3 text-xs">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <strong class="text-white">{{ $revItem['author'] }}</strong>
                                        <span class="text-[10px] text-[#C6A15B] font-mono">★★★★★ ({{ $revItem['event'] }})</span>
                                    </div>
                                    <p class="text-stone-400 italic text-[11px] mt-1">"{{ $revItem['quote'] }}"</p>
                                </div>
                                <button type="button" wire:click="removeReview({{ $rIdx }})" class="text-[#171513] hover:text-[#F8F5EF] font-mono text-xs">
                                    Remove
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

        <!-- Right: Photography & 4K Video Tour Showcase (5 cols) -->
        <div class="lg:col-span-5 space-y-6">
            
            <!-- Direct Hall Website Shortcut Card -->
            @if($hall)
                <div class="bg-[#171513] border border-[#C6A15B] p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-mono uppercase tracking-widest text-[#C6A15B]">Your Live Website Link</span>
                        <span class="inline-block w-2 h-2 rounded-full bg-[#C6A15B] animate-pulse"></span>
                    </div>
                    <p class="text-xs text-white/70">
                        Share this dedicated URL with your wedding inquiry clients:
                    </p>
                    <div class="bg-[#171513] p-2.5 border border-white/15 flex items-center justify-between gap-2">
                        <input type="text" readonly value="{{ route('hall.showcase', $hall->slug) }}" id="hallUrlInput"
                               class="bg-transparent text-xs font-mono text-[#C6A15B] w-full outline-none select-all">
                        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('hallUrlInput').value); window.luxuryToast('Hall website URL copied to clipboard!', 'gold');"
                                class="px-2.5 py-1 bg-[#C6A15B] text-[#171513] text-[10px] font-mono font-bold uppercase tracking-wider shrink-0">
                            Copy
                        </button>
                    </div>
                    <a href="{{ route('hall.showcase', $hall->slug) }}" target="_blank"
                       class="text-xs font-mono text-[#C6A15B] hover:underline flex items-center gap-1.5 pt-1">
                        <span>Open &amp; Test Live Hall Page &rarr;</span>
                    </a>
                </div>
            @endif

            <!-- Photos Manager Card -->
            <div class="bg-[#171513] border border-white/10 p-6 space-y-4">
                <div class="border-b border-white/10 pb-3 flex items-center justify-between">
                    <span class="text-xs font-mono uppercase tracking-widest text-[#C6A15B]">Hall Photography</span>
                    <span class="text-[10px] font-mono text-white/50">{{ count($images) }} Photos</span>
                </div>

                <!-- Add Photo Input -->
                <div class="flex gap-2">
                    <input type="url" wire:model="newImageUrl" placeholder="Paste image URL (Unsplash, Cloudinary, Imgur)..."
                           class="flex-1 bg-[#171513] border border-white/15 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                    <button wire:click="addImage" class="px-3 py-2 bg-[#C6A15B] text-[#171513] text-xs font-bold font-mono uppercase tracking-wider">
                        + Add URL
                    </button>
                </div>
                @error('newImageUrl') <span class="text-rose-400 text-[10px] font-mono block">{{ $message }}</span> @enderror

                <!-- OR upload from device -->
                <div class="flex items-center gap-2 pt-1">
                    <input type="file" wire:model="imageUploads" multiple accept="image/*"
                           class="flex-1 text-xs text-white/70 file:mr-2 file:px-3 file:py-1.5 file:bg-white/10 file:border-0 file:text-white file:text-[10px] file:font-mono file:uppercase">
                    <button wire:click="uploadImages" wire:loading.attr="disabled" class="px-3 py-2 bg-white/10 text-white text-xs font-bold font-mono uppercase tracking-wider hover:bg-white/20">
                        Upload Photos
                    </button>
                </div>
                <div wire:loading wire:target="imageUploads" class="text-[10px] font-mono text-[#C6A15B]">Uploading preview...</div>
                @error('imageUploads.*') <span class="text-rose-400 text-[10px] font-mono block">{{ $message }}</span> @enderror

                <!-- Images Grid -->
                <div class="grid grid-cols-2 gap-3 pt-2">
                    @forelse($images as $idx => $img)
                        <div class="group relative aspect-[4/3] bg-black border border-white/10 overflow-hidden">
                            <img src="{{ $img }}" alt="Hall Photo" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-black/70 opacity-0 group-hover:opacity-100 transition flex flex-col items-center justify-center gap-2 p-2">
                                <button type="button" wire:click="setCoverImage('{{ $img }}')"
                                        class="px-2 py-1 bg-[#C6A15B] text-[#171513] text-[9px] font-mono uppercase font-bold w-full text-center">
                                    Set as Cover
                                </button>
                                <button type="button" wire:click="removeImage({{ $idx }})"
                                        class="px-2 py-1 bg-[#171513] text-[#F8F5EF] text-[9px] font-mono uppercase font-bold w-full text-center">
                                    Remove
                                </button>
                            </div>
                            @if($cover_image === $img)
                                <span class="absolute top-1 left-1 px-1.5 py-0.5 bg-[#C6A15B] text-[#171513] text-[8px] font-mono uppercase font-bold">
                                    Cover
                                </span>
                            @endif
                        </div>
                    @empty
                        <div class="col-span-2 p-6 text-center text-white/40 text-xs font-mono border border-dashed border-white/15">
                            No photos added yet. Add a direct image URL above.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- 4K Video Walkthrough Card -->
            <div class="bg-[#171513] border border-white/10 p-6 space-y-4">
                <div class="border-b border-white/10 pb-3 flex items-center justify-between">
                    <span class="text-xs font-mono uppercase tracking-widest text-[#C6A15B]">4K Video Walkthrough</span>
                    <span class="text-[10px] font-mono text-white/50">{{ count($videos) }} Videos</span>
                </div>

                <div class="flex gap-2">
                    <input type="url" wire:model="newVideoUrl" placeholder="Paste YouTube / Vimeo / Dailymotion / direct video URL"
                           class="flex-1 bg-[#171513] border border-white/15 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                    <button wire:click="addVideo" class="px-3 py-2 bg-[#C6A15B] text-[#171513] text-xs font-bold font-mono uppercase tracking-wider">
                        + Add URL
                    </button>
                </div>
                @error('newVideoUrl') <span class="text-rose-400 text-[10px] font-mono block">{{ $message }}</span> @enderror

                <!-- OR upload from device -->
                <div class="flex items-center gap-2 pt-1">
                    <input type="file" wire:model="videoUploads" multiple accept="video/mp4,video/webm,video/ogg,video/quicktime"
                           class="flex-1 text-xs text-white/70 file:mr-2 file:px-3 file:py-1.5 file:bg-white/10 file:border-0 file:text-white file:text-[10px] file:font-mono file:uppercase">
                    <button wire:click="uploadVideos" wire:loading.attr="disabled" class="px-3 py-2 bg-white/10 text-white text-xs font-bold font-mono uppercase tracking-wider hover:bg-white/20">
                        Upload Videos
                    </button>
                </div>
                <div wire:loading wire:target="videoUploads" class="text-[10px] font-mono text-[#C6A15B]">Uploading preview...</div>
                @error('videoUploads.*') <span class="text-rose-400 text-[10px] font-mono block">{{ $message }}</span> @enderror

                @foreach($videos as $vIdx => $vid)
                    <div class="space-y-2 pt-2">
                        <div class="aspect-video bg-black border border-white/10 overflow-hidden">
                            @if(\App\Support\Media::isDirectVideo($vid))
                                <video src="{{ $vid }}" controls class="w-full h-full"></video>
                            @else
                                <iframe src="{{ \App\Support\Media::videoEmbedUrl($vid) }}" class="w-full h-full" allowfullscreen></iframe>
                            @endif
                        </div>
                        <button type="button" wire:click="removeVideo({{ $vIdx }})"
                                class="text-rose-400 hover:text-rose-300 text-xs font-mono uppercase underline">
                            Remove Video
                        </button>
                    </div>
                @endforeach
            </div>

        </div>

    </div>

    <!-- Modal: Add New Hall to Complex -->
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4">
            <div class="bg-[#171513] border border-[#C6A15B] max-w-lg w-full p-6 sm:p-8 space-y-6 shadow-2xl">
                <div class="border-b border-white/10 pb-4 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono uppercase tracking-widest text-[#C6A15B]">Multi-Hall Expansion</span>
                        <h3 class="font-serif text-2xl text-white font-normal mt-0.5">Add Another Hall to Complex</h3>
                    </div>
                    <button wire:click="closeCreateModal" class="text-white/60 hover:text-white text-lg font-mono">&times;</button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-white/80 mb-1">
                            New Hall Name *
                        </label>
                        <input type="text" wire:model="newHallName" placeholder="e.g. Imperial Marquee / Banquet Hall B"
                               class="w-full bg-[#171513] border border-white/15 text-white text-sm px-3.5 py-2.5 focus:border-[#C6A15B] outline-none">
                        @error('newHallName') <span class="text-rose-400 text-[10px] font-mono">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider text-white/80 mb-1">
                                Max Capacity *
                            </label>
                            <input type="number" wire:model="newHallCapacity" step="50"
                                   class="w-full bg-[#171513] border border-white/15 text-white text-sm px-3.5 py-2.5 focus:border-[#C6A15B] outline-none font-mono">
                        </div>

                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider text-white/80 mb-1">
                                Base Shift Rent (PKR) *
                            </label>
                            <input type="number" wire:model="newHallRent" step="5000"
                                   class="w-full bg-[#171513] border border-white/15 text-white text-sm px-3.5 py-2.5 focus:border-[#C6A15B] outline-none font-mono">
                        </div>
                    </div>
                </div>

                <div class="border-t border-white/10 pt-4 flex items-center justify-end gap-3">
                    <button wire:click="closeCreateModal" class="px-4 py-2 border border-white/20 text-white text-xs font-mono uppercase">
                        Cancel
                    </button>
                    <button wire:click="createNewHall" class="px-5 py-2 bg-[#C6A15B] text-[#171513] text-xs font-bold font-mono uppercase">
                        Create Hall &rarr;
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
