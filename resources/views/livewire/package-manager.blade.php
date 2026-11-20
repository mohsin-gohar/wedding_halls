<div class="space-y-8 max-w-7xl mx-auto pb-16">

    <!-- Header Banner (Unified Luxury Obsidian) -->
    <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-6 sm:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6 shadow-xl transition-colors duration-200">
        <div class="space-y-2">
            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 border border-[#C6A15B]/50 bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] text-[10px] font-mono uppercase tracking-widest font-bold">
                🍽️ Vendor Banquet ERP • Catering &amp; Menus
            </div>
            <h1 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                Catering Packages &amp; Degi Menus
            </h1>
            <p class="text-xs text-stone-600 dark:text-stone-300 font-sans max-w-2xl leading-relaxed">
                Create and manage royal wedding catering packages, per-head banquet pricing, and itemized bawarchi food lists. All active menus appear dynamically on your live public hall storefront and walk-in reservation terminal.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button wire:click="openCreateModal" class="btn-gold !h-10 !px-5 text-xs font-bold uppercase font-mono shadow-md">
                + New Catering Package
            </button>
            <a href="{{ route('public.packages') }}" target="_blank" class="btn-outline-gold !h-10 !px-4 text-xs font-mono uppercase transition flex items-center gap-2 font-bold">
                <span>View Public Menu Page ↗</span>
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    @if($feedbackMessage)
        <div class="p-4 bg-[#171513]/90 border border-[#C6A15B]/60 text-[#C6A15B] text-xs font-semibold shadow-md flex justify-between items-center">
            <span>✓ {{ $feedbackMessage }}</span>
            <button wire:click="$set('feedbackMessage', null)" class="text-[#8F7239] font-bold text-sm">&times;</button>
        </div>
    @endif

    @if($errorMessage)
        <div class="p-4 bg-[#171513]/90 border border-[#C6A15B]/60 text-[#F8F5EF] text-xs font-semibold shadow-md flex justify-between items-center">
            <span>⚠️ {{ $errorMessage }}</span>
            <button wire:click="$set('errorMessage', null)" class="text-stone-300 hover:text-white font-bold text-sm">&times;</button>
        </div>
    @endif

    @if($tenantOptions->isNotEmpty())
        <div class="bg-[#171513]/90 border border-[#C6A15B]/50 p-4 text-xs font-mono text-[#C6A15B] flex flex-wrap items-center gap-3">
            <span class="uppercase tracking-widest font-bold">Platform admin — explicit tenant context required</span>
            <select wire:change="selectTenant($event.target.value)" class="bg-[#171513] border border-[#C6A15B]/40 px-3 py-2 text-white text-xs">
                <option value="">— Select a venue —</option>
                @foreach($tenantOptions as $option)
                    <option value="{{ $option->id }}" @selected($selectedTenantId === $option->id)>{{ $option->name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <!-- Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl space-y-1 relative overflow-hidden group hover:border-[#C6A15B] transition">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 block font-mono">Total Menu Tiers</span>
            <strong class="font-cinzel text-2xl text-[#171513] dark:text-white">{{ $totalPackages }}</strong>
            <span class="text-[11px] text-stone-400 block font-sans">Defined Banquet Packages</span>
        </div>

        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl space-y-1 relative overflow-hidden group hover:border-[#C6A15B] transition">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 block font-mono">Active on Website</span>
            <strong class="font-cinzel text-2xl text-[#8F7239] dark:text-[#C6A15B]">{{ $activePackages }}</strong>
            <span class="text-[11px] text-stone-400 block font-sans">Live for Online Inquiries</span>
        </div>

        <div class="bg-[#171513] text-white border border-[#C6A15B]/40 p-5 shadow-lg space-y-1 relative overflow-hidden group">
            <span class="text-[10px] uppercase font-bold tracking-wider text-[#C6A15B] block font-mono">Average Per-Head Rate</span>
            <strong class="font-cinzel text-xl text-white block">PKR {{ number_format($avgPrice) }}</strong>
            <span class="text-[10px] text-stone-400 block font-mono">Per Person Baseline</span>
        </div>
    </div>

    <!-- Filter Buttons -->
    <div class="flex items-center justify-between gap-4 border-b border-[#E7E5E4] dark:border-white/10 pb-3">
        <div class="flex items-center gap-2 text-xs font-mono font-bold uppercase">
            <button wire:click="$set('filterStatus', 'ALL')" class="px-3.5 py-1.5 transition {{ $filterStatus === 'ALL' ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513]' : 'bg-white dark:bg-[#171513] text-stone-700 dark:text-stone-300 border border-[#E7E5E4] dark:border-white/10' }}">
                All ({{ $totalPackages }})
            </button>
            <button wire:click="$set('filterStatus', 'ACTIVE')" class="px-3.5 py-1.5 transition {{ $filterStatus === 'ACTIVE' ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513]' : 'bg-white dark:bg-[#171513] text-stone-700 dark:text-stone-300 border border-[#E7E5E4] dark:border-white/10' }}">
                Active ({{ $activePackages }})
            </button>
            <button wire:click="$set('filterStatus', 'INACTIVE')" class="px-3.5 py-1.5 transition {{ $filterStatus === 'INACTIVE' ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513]' : 'bg-white dark:bg-[#171513] text-stone-700 dark:text-stone-300 border border-[#E7E5E4] dark:border-white/10' }}">
                Archived ({{ $totalPackages - $activePackages }})
            </button>
        </div>

        <button wire:click="openCreateModal" class="btn-gold !h-8 !px-3 text-xs font-mono uppercase">
            + Add Package
        </button>
    </div>

    <!-- Packages Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($packages as $pkg)
            <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 p-6 flex flex-col justify-between space-y-5 hover:border-[#C6A15B] transition-all duration-300 shadow-sm dark:shadow-xl group">
                <div class="space-y-4">
                    <!-- Card Top Header -->
                    <div class="flex items-start justify-between gap-3 border-b border-[#E7E5E4] dark:border-white/10 pb-3">
                        <div>
                            <span class="text-[10px] font-mono font-bold uppercase text-[#8F7239] dark:text-[#C6A15B] block">
                                Min {{ $pkg->min_guests }} Guests
                            </span>
                            <h3 class="font-serif text-xl text-[#171513] dark:text-white font-medium mt-0.5">
                                {{ $pkg->name }}
                            </h3>
                        </div>

                        <span class="px-2 py-0.5 text-[10px] font-mono font-bold uppercase shrink-0 {{ $pkg->is_active ? 'bg-[#C6A15B]/15 dark:bg-[#171513]/85 text-[#8F7239] dark:text-[#C6A15B] border border-[#C6A15B]/60 dark:border-[#C6A15B]/50' : 'bg-stone-200 dark:bg-stone-800 text-stone-700 dark:text-stone-400' }}">
                            {{ $pkg->is_active ? '● Published' : '○ Archived' }}
                        </span>
                    </div>

                    <!-- Pricing Display -->
                    <div class="p-3 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 flex items-baseline justify-between">
                        <span class="text-xs text-stone-500 font-mono">Rate Per Head:</span>
                        <div class="font-cinzel text-xl font-bold text-[#171513] dark:text-[#C6A15B]">
                            PKR {{ number_format($pkg->per_head_price) }}
                        </div>
                    </div>

                    <!-- Description -->
                    <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed font-sans line-clamp-2">
                        {{ $pkg->description ?: 'Comprehensive wedding feast prepared by master bawarchis with premium ingredients.' }}
                    </p>

                    <!-- Menu Items Dishes -->
                    <div class="space-y-2 pt-2 border-t border-[#E7E5E4] dark:border-white/10">
                        <span class="text-[10px] font-mono uppercase tracking-wider text-stone-500 dark:text-stone-400 block font-semibold">
                            Included Dishes ({{ is_array($pkg->menu_items_json) ? count($pkg->menu_items_json) : 0 }} items):
                        </span>
                        
                        <div class="flex flex-wrap gap-1.5 max-h-36 overflow-y-auto">
                            @if(is_array($pkg->menu_items_json))
                                @foreach($pkg->menu_items_json as $dish)
                                    <span class="px-2 py-0.5 bg-[#F8F5EF] dark:bg-[#171513] text-[#171513] dark:text-stone-200 border border-[#E7E5E4] dark:border-white/10 text-[11px] font-sans">
                                        {{ is_array($dish) ? implode(', ', $dish) : $dish }}
                                    </span>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Actions Footer -->
                <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10 flex items-center justify-between gap-2 font-sans">
                    <button type="button" wire:click="openEditModal('{{ $pkg->id }}')" class="tbtn tbtn-gold" title="Edit Menu & Pricing">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <span>Edit Menu</span>
                    </button>

                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="togglePackageStatus('{{ $pkg->id }}')" class="tbtn tbtn-outline">
                            <span>{{ $pkg->is_active ? 'Archive' : 'Publish' }}</span>
                        </button>
                        <button type="button" wire:click="deletePackage('{{ $pkg->id }}')" 
                                onclick="return confirm('Delete this catering package?') || event.stopImmediatePropagation()"
                                class="tbtn tbtn-red">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            <span>Delete</span>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3 p-12 bg-white dark:bg-[#171513] border border-dashed border-[#E7E5E4] dark:border-white/15 text-center space-y-3">
                <span class="text-3xl block">🍽️</span>
                <h4 class="font-serif text-lg text-[#171513] dark:text-white">No Catering Packages Found</h4>
                <p class="text-xs text-stone-500 dark:text-stone-400 max-w-md mx-auto">
                    Create your first custom catering package with biryani, qorma, naan, and sweets to showcase on your hall website.
                </p>
                <button wire:click="openCreateModal" class="btn-gold !h-9 !px-4 text-xs font-mono uppercase mt-2">
                    + Create First Package
                </button>
            </div>
        @endforelse
    </div>

    <!-- ===================================================================
         CREATE / EDIT PACKAGE MODAL
         =================================================================== -->
    @if($showPackageModal)
        <div class="fixed inset-0 z-50 bg-black/85 flex items-center justify-center p-4 overflow-y-auto">
            <div class="bg-[#171513] border-2 border-[#C6A15B] max-w-2xl w-full p-6 sm:p-8 space-y-6 shadow-2xl text-white my-8">
                <div class="border-b border-white/10 pb-4 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono uppercase tracking-widest text-[#C6A15B] block font-bold">Menu Builder Engine</span>
                        <h3 class="font-serif text-2xl text-white font-normal mt-0.5">
                            {{ $isEditing ? 'Edit Catering Package' : 'Create New Catering Package' }}
                        </h3>
                    </div>
                    <button wire:click="closePackageModal" class="text-white/60 hover:text-white text-2xl font-mono leading-none">&times;</button>
                </div>

                <div class="space-y-4 font-sans text-xs">
                    <div>
                        <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">
                            Package Title *
                        </label>
                        <input type="text" wire:model="packageName" placeholder="e.g. Royal Barat Gold Feast"
                               class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B]">
                        @error('packageName') <span class="text-rose-400 text-[10px] font-mono mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">
                                Per Head Rate (PKR) *
                            </label>
                            <input type="number" wire:model="perHeadPrice" step="50" min="500"
                                   class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B] font-mono">
                            @error('perHeadPrice') <span class="text-rose-400 text-[10px] font-mono mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">
                                Minimum Guests Required *
                            </label>
                            <input type="number" wire:model="minGuests" step="25" min="20"
                                   class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B] font-mono">
                            @error('minGuests') <span class="text-rose-400 text-[10px] font-mono mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">
                            Package Description &amp; Highlights
                        </label>
                        <textarea wire:model="packageDescription" rows="2" placeholder="Brief overview of preparation style, premium meat cuts, and dining ambience..."
                                  class="w-full bg-[#171513] border border-white/20 text-white text-xs p-2.5 outline-none focus:border-[#C6A15B]"></textarea>
                    </div>

                    <!-- Dynamic Dish / Menu Items Management -->
                    <div class="p-4 bg-[#171513] border border-[#C6A15B]/40 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-mono uppercase tracking-wider text-[#C6A15B] font-bold">
                                Included Food Items &amp; Dishes ({{ count($menuItems) }})
                            </span>
                            <span class="text-[10px] text-stone-400 font-mono">Press Add or click popular dishes</span>
                        </div>

                        <!-- Add Dish Input -->
                        <div class="flex gap-2">
                            <input type="text" wire:model="newMenuItemName" wire:keydown.enter.prevent="addMenuItem"
                                   placeholder="Type dish name (e.g. Mutton Pulao, Chicken Malai Boti)..."
                                   class="flex-1 bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                            <button type="button" wire:click="addMenuItem" class="btn-gold !h-9 !px-4 text-xs font-mono uppercase">
                                + Add Dish
                            </button>
                        </div>

                        <!-- Popular Quick Add Presets -->
                        <div class="space-y-1">
                            <span class="text-[10px] font-mono text-stone-400 block">Quick Suggestions:</span>
                            <div class="flex flex-wrap gap-1.5 text-[11px] font-mono">
                                <button type="button" wire:click="addPresetDish('Mutton Kunna Gosht')" class="px-2 py-0.5 bg-white/10 hover:bg-[#C6A15B] hover:text-[#171513] transition text-stone-300">
                                    + Mutton Kunna
                                </button>
                                <button type="button" wire:click="addPresetDish('Chicken Degi Biryani')" class="px-2 py-0.5 bg-white/10 hover:bg-[#C6A15B] hover:text-[#171513] transition text-stone-300">
                                    + Chicken Biryani
                                </button>
                                <button type="button" wire:click="addPresetDish('Beef Seekh Kabab')" class="px-2 py-0.5 bg-white/10 hover:bg-[#C6A15B] hover:text-[#171513] transition text-stone-300">
                                    + Seekh Kabab
                                </button>
                                <button type="button" wire:click="addPresetDish('Tandoori Roghni Naan')" class="px-2 py-0.5 bg-white/10 hover:bg-[#C6A15B] hover:text-[#171513] transition text-stone-300">
                                    + Roghni Naan
                                </button>
                                <button type="button" wire:click="addPresetDish('Zafrani Kheer')" class="px-2 py-0.5 bg-white/10 hover:bg-[#C6A15B] hover:text-[#171513] transition text-stone-300">
                                    + Zafrani Kheer
                                </button>
                                <button type="button" wire:click="addPresetDish('Shahi Zarda')" class="px-2 py-0.5 bg-white/10 hover:bg-[#C6A15B] hover:text-[#171513] transition text-stone-300">
                                    + Shahi Zarda
                                </button>
                                <button type="button" wire:click="addPresetDish('Kashmiri Chai & Almonds')" class="px-2 py-0.5 bg-white/10 hover:bg-[#C6A15B] hover:text-[#171513] transition text-stone-300">
                                    + Kashmiri Chai
                                </button>
                            </div>
                        </div>

                        <!-- Current Dishes Tags List -->
                        <div class="space-y-1.5 pt-2 border-t border-white/10 max-h-40 overflow-y-auto">
                            @forelse($menuItems as $mIdx => $mItem)
                                <div class="flex items-center justify-between p-2 bg-[#171513] border border-white/10 text-xs">
                                    <span class="text-white font-medium">🍽️ {{ $mItem }}</span>
                                    <button type="button" wire:click="removeMenuItem({{ $mIdx }})" class="text-[#171513] hover:text-[#F8F5EF] font-mono text-sm px-1">
                                        &times;
                                    </button>
                                </div>
                            @empty
                                <div class="text-stone-500 text-xs italic py-2 text-center">
                                    No dishes added yet. Use the field above or click quick suggestions.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Active Toggle -->
                    <div class="pt-2">
                        <label class="flex items-center gap-2 cursor-pointer font-mono text-xs">
                            <input type="checkbox" wire:model="isActive" class="accent-[#C6A15B] w-4 h-4">
                            <span>Publish on Public Hall Website &amp; Booking Terminals</span>
                        </label>
                    </div>
                </div>

                <div class="border-t border-white/10 pt-4 flex items-center justify-end gap-3 font-mono">
                    <button wire:click="closePackageModal" class="px-4 py-2 border border-white/20 text-white text-xs uppercase hover:bg-white/10 transition">
                        Cancel
                    </button>
                    <button wire:click="savePackage" class="btn-gold !h-9 !px-5 text-xs font-bold uppercase shadow-md">
                        {{ $isEditing ? 'Save Changes →' : 'Create Package →' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
