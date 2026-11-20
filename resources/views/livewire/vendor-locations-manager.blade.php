<div class="space-y-8 max-w-7xl mx-auto pb-16">
    <!-- Header Banner -->
    <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-6 sm:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6 shadow-xl transition-colors duration-200">
        <div class="space-y-2">
            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 border border-[#C6A15B]/50 bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] text-[10px] font-mono uppercase tracking-widest font-bold">
                📍 Vendor Marketplace ERP • Business Locations
            </div>
            <h1 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                Business Locations
            </h1>
            <p class="text-xs text-stone-600 dark:text-stone-300 font-sans max-w-2xl leading-relaxed">
                Operate every branch of your business from one place — studios, kitchens, warehouses or on-the-road units. Each location keeps its own address, service area and contact details.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="px-4 py-3 border border-[#C6A15B]/40 bg-[#C6A15B]/10 text-center">
                <p class="text-[10px] uppercase tracking-widest font-mono text-[#8F7239] dark:text-[#C6A15B]">Active branches</p>
                <p class="font-serif-luxury text-2xl text-[#171513] dark:text-white">{{ $this->locationCount }}</p>
            </div>
            @if($this->hasPortalPermission('locations.manage'))
                <button wire:click="openCreateModal" class="btn-gold !h-10 !px-5 text-xs font-bold uppercase font-mono shadow-md">
                    + Add Location
                </button>
            @endif
        </div>
    </div>

    @if($feedbackMessage)
        <div class="p-4 bg-[#171513]/90 border border-[#C6A15B]/60 text-[#C6A15B] text-xs font-semibold shadow-md">
            ✓ {{ $feedbackMessage }}
        </div>
    @endif

    @if($errorMessage)
        <div class="p-4 bg-[#171513]/90 border border-[#C6A15B]/60 text-[#F8F5EF] text-xs font-semibold shadow-md">
            ⚠️ {{ $errorMessage }}
        </div>
    @endif

    <!-- Locations -->
    @if($this->locations->isEmpty())
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-12 text-center shadow-sm dark:shadow-xl">
            <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white mb-2">No locations yet</h3>
            <p class="text-sm text-stone-600 dark:text-stone-300 max-w-lg mx-auto">
                Add your first branch so customers can see where you operate and plan events at the right place.
            </p>
            @if($this->hasPortalPermission('locations.manage'))
                <button wire:click="openCreateModal" class="btn-gold mt-6 !h-10 !px-5 text-xs font-bold uppercase font-mono">
                    + Add Location
                </button>
            @endif
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($this->locations as $location)
                <article class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 shadow-sm dark:shadow-xl p-5 sm:p-6 flex flex-col gap-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <span class="px-2.5 py-0.5 border border-[#C6A15B]/50 bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] text-[10px] font-mono uppercase tracking-widest font-bold">
                                    {{ str_replace('_', ' ', $location->branch_type) }}
                                </span>
                                @if($location->is_primary)
                                    <span class="px-2.5 py-0.5 bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513] text-[10px] font-mono uppercase tracking-widest font-bold">
                                        Head Office
                                    </span>
                                @endif
                                @unless($location->is_active)
                                    <span class="px-2.5 py-0.5 border border-stone-300 dark:border-white/20 text-stone-500 text-[10px] font-mono uppercase tracking-widest font-bold">
                                        Inactive
                                    </span>
                                @endunless
                            </div>
                            <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white truncate">{{ $location->name }}</h3>
                            <p class="text-xs text-stone-600 dark:text-stone-300 font-sans mt-1">
                                {{ $location->fullAddress() ?: 'Address not provided' }}
                            </p>
                        </div>
                    </div>

                    <dl class="grid grid-cols-2 gap-3 text-xs text-stone-600 dark:text-stone-300 font-sans">
                        <div>
                            <dt class="text-[10px] uppercase tracking-wider font-semibold text-stone-400">City</dt>
                            <dd>{{ $location->city ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] uppercase tracking-wider font-semibold text-stone-400">Phone</dt>
                            <dd>{{ $location->phone ?: '—' }}</dd>
                        </div>
                    </dl>

                    @if($this->hasPortalPermission('locations.manage'))
                        <div class="flex flex-wrap items-center gap-2 pt-2 mt-auto">
                            <button wire:click="openEditModal('{{ $location->id }}')" class="btn-outline-gold !h-9 !px-4 text-[10px] font-mono uppercase">
                                Edit
                            </button>
                            <button wire:click="toggleActive('{{ $location->id }}')" class="btn-outline-gold !h-9 !px-4 text-[10px] font-mono uppercase">
                                {{ $location->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    @endif

    <!-- Modal -->
    @if($showLocationModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true" role="dialog" aria-labelledby="location-modal-title">
            <div class="absolute inset-0 bg-[#171513]/70 backdrop-blur-sm" wire:click="closeModal"></div>
            <div class="relative mx-auto mt-16 w-full max-w-2xl rounded-2xl bg-white dark:bg-[#171513] border border-[#C6A15B]/50 shadow-2xl p-6">
                <h2 id="location-modal-title" class="font-serif-luxury text-2xl text-[#171513] dark:text-white mb-4">
                    {{ $isEditing ? 'Edit Location' : 'Add Location' }}
                </h2>

                <form wire:submit="saveLocation" class="space-y-4">
                    <div>
                        <label for="loc-name" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Branch name <span class="text-[#8F7239]">*</span></label>
                        <input id="loc-name" type="text" wire:model="locationName" maxlength="150" placeholder="e.g. DHA Studio">
                        @error('locationName') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="loc-type" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Branch type</label>
                            <select id="loc-type" wire:model="branchType" class="input-luxury w-full text-sm">
                                @foreach(\App\Models\VendorLocation::BRANCH_TYPES as $type)
                                    <option value="{{ $type }}">{{ ucfirst(strtolower(str_replace('_', ' ', $type))) }}</option>
                                @endforeach
                            </select>
                            @error('branchType') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="loc-city" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">City</label>
                            <input id="loc-city" type="text" wire:model="city" maxlength="80" placeholder="Karachi">
                        </div>
                    </div>

                    <div>
                        <label for="loc-address" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Street address</label>
                        <input id="loc-address" type="text" wire:model="address" maxlength="255">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="loc-area" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Area</label>
                            <input id="loc-area" type="text" wire:model="area" maxlength="120" placeholder="PECHS Block 2">
                        </div>
                        <div>
                            <label for="loc-province" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Province</label>
                            <input id="loc-province" type="text" wire:model="province" maxlength="80" placeholder="Sindh">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="loc-phone" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Phone</label>
                            <input id="loc-phone" type="text" wire:model="phone" maxlength="50" placeholder="+92 300 1234567">
                        </div>
                        <div>
                            <label for="loc-email" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Email</label>
                            <input id="loc-email" type="email" wire:model="email" maxlength="150">
                            @error('email') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label class="flex items-center gap-2 text-xs text-stone-600 dark:text-stone-300">
                            <input type="checkbox" wire:model="isPrimary" class="rounded border-stone-300 dark:border-white/20">
                            Primary head office
                        </label>
                        <label class="flex items-center gap-2 text-xs text-stone-600 dark:text-stone-300">
                            <input type="checkbox" wire:model="isActive" class="rounded border-stone-300 dark:border-white/20">
                            Visible to customers
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" wire:click="closeModal" class="btn-outline-gold !h-10 !px-5 text-[11px] font-mono uppercase">Cancel</button>
                        <button type="submit" class="btn-gold !h-10 !px-6 text-[11px] font-mono uppercase">
                            {{ $isEditing ? 'Save Changes →' : 'Add Location →' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
