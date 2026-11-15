<div class="space-y-8 max-w-7xl mx-auto pb-16">
    <!-- Header Banner -->
    <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-6 sm:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6 shadow-xl transition-colors duration-200">
        <div class="space-y-2">
            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 border border-[#C6A15B]/50 bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] text-[10px] font-mono uppercase tracking-widest font-bold">
                🎛️ Vendor Marketplace ERP • Service Catalog
            </div>
            <h1 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                Service Catalog
            </h1>
            <p class="text-xs text-stone-600 dark:text-stone-300 font-sans max-w-2xl leading-relaxed">
                Manage your pricing catalogue — photography hours, per-head catering, decoration projects, beauty appointments — as services with fixed or range pricing.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button wire:click="openCreateModal" class="btn-gold !h-10 !px-5 text-xs font-bold uppercase font-mono shadow-md">
                + Add Service
            </button>
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

    <!-- Modal -->
    @if($showServiceModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true" role="dialog" aria-labelledby="service-modal-title">
            <div class="absolute inset-0 bg-[#171513]/70 backdrop-blur-sm" wire:click="closeServiceModal"></div>
            <div class="relative mx-auto mt-16 w-full max-w-2xl rounded-2xl bg-white dark:bg-[#171513] border border-[#C6A15B]/50 shadow-2xl p-6">
                <h2 id="service-modal-title" class="font-serif-luxury text-2xl text-[#171513] dark:text-white mb-4">{{ $isEditing ? 'Edit Service' : 'Add Service' }}</h2>

                <form wire:submit="saveService" class="space-y-4">
                    <div>
                        <label for="svc-name" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Service name <span class="text-[#8F7239]">*</span></label>
                        <input id="svc-name" type="text" wire:model="serviceName" maxlength="150" placeholder="e.g. Baraat Cinematic Coverage">
                    </div>
                    <div>
                        <label for="svc-desc" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Description</label>
                        <textarea id="svc-desc" wire:model="serviceDescription" rows="3" class="input-luxury w-full text-sm resize-y"></textarea>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="svc-pricing" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Pricing type</label>
                            <select id="svc-pricing" wire:model="pricingType" class="input-luxury w-full text-sm">
                                <option value="FIXED">Fixed</option>
                                <option value="PER_HEAD">Per head</option>
                                <option value="HOURLY">Hourly</option>
                                <option value="DAY">Per day / event</option>
                                <option value="PROJECT">Project</option>
                                <option value="CONSULT">Consultation</option>
                            </select>
                        </div>
                        <div>
                            <label for="svc-unit" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Unit label</label>
                            <input id="svc-unit" type="text" wire:model="serviceUnit" maxLength="40" placeholder="per event">
                        </div>
                        <div>
                            <label for="svc-price-from" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Price from (PKR)</label>
                            <input id="svc-price-from" type="number" step="0.01" min="0" wire:model="priceFrom" class="input-luxury w-full text-sm">
                        </div>
                        <div>
                            <label for="svc-price-to" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Price to (PKR, optional)</label>
                            <input id="svc-price-to" type="number" step="0.01" min="0" wire:model="priceTo" class="input-luxury w-full text-sm">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="svc-category" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Category</label>
                            <select id="svc-category" wire:model="vendorCategoryId" class="input-luxury w-full text-sm">
                                <option value="">General (cross-category)</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category['id'] }}">{{ $category['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="closeServiceModal" class="btn-outline-gold !h-10 !px-5 text-[11px] font-mono uppercase">Close</button>
                        <button type="submit" class="btn-gold !h-10 !px-6 text-[11px] font-mono uppercase">Save Service</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Services list -->
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 shadow-sm dark:shadow-xl">
        <div class="px-5 py-3 border-b border-[#C6A15B]/20 flex items-center justify-between">
            <h2 class="text-[11px] uppercase tracking-[0.14em] font-bold text-stone-500 dark:text-stone-400">Catalogue ({{ $serviceCount }})</h2>
        </div>
        <div class="divide-y divide-[#E7E5E4] dark:divide-white/10">
            @forelse($services as $service)
                <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-sm text-[#171513] dark:text-white truncate">{{ $service['name'] }}</p>
                        <p class="text-xs text-stone-600 dark:text-stone-300 font-sans truncate">{{ $service['description'] }}</p>
                        <p class="text-[11px] text-stone-500 dark:text-stone-400 font-sans mt-1">
                            {{ ucfirst($service['pricing_type']) }} · {{ number_format((float) ($service['price_from'] ?? 0)) }} – {{ number_format((float) ($service['price_to'] ?? 0)) }} PKR {{ $service['unit'] }}
                            @if($service['vendorCategory']) · {{ $service['vendorCategory']['name'] }} @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button wire:click="toggleService({{ $service['id'] }})" class="btn-outline-gold !h-9 !px-4 text-[10px] font-mono uppercase">
                            {{ $service['is_active'] ? 'Deactivate' : 'Activate' }}
                        </button>
                        <button wire:click="openEditModal({{ $service['id'] }})" class="btn-outline-gold !h-9 !px-4 text-[10px] font-mono uppercase">Edit</button>
                    </div>
                </div>
            @empty
                <div class="px-5 py-10 text-center">
                    <p class="text-sm text-stone-600 dark:text-stone-300">No services yet — add your first service to go live on the marketplace.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
