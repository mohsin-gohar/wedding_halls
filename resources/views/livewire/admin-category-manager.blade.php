<div class="space-y-8 max-w-7xl mx-auto pb-16">
    <!-- Header Banner -->
    <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-6 sm:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6 shadow-xl transition-colors duration-200">
        <div class="space-y-2">
            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 border border-[#C6A15B]/50 bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] text-[10px] font-mono uppercase tracking-widest font-bold">
                🗂️ Platform • Marketplace Taxonomy
            </div>
            <h1 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                Categories &amp; Subcategories
            </h1>
            <p class="text-xs text-stone-600 dark:text-stone-300 font-sans max-w-2xl leading-relaxed">
                The live marketplace taxonomy — vendor registration, service catalogues, RFQs and booking modes all resolve through these rows. No developer required.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button wire:click="openCreateModal(null)" class="btn-gold !h-10 !px-5 text-xs font-bold uppercase font-mono shadow-md">
                + New Category
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
    @if($showCategoryModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true" role="dialog" aria-labelledby="category-modal-title">
            <div class="absolute inset-0 bg-[#171513]/70 backdrop-blur-sm" wire:click="closeModal"></div>
            <div class="relative mx-auto mt-10 mb-10 w-full max-w-2xl rounded-2xl bg-white dark:bg-[#171513] border border-[#C6A15B]/50 shadow-2xl p-6">
                <h2 id="category-modal-title" class="font-serif-luxury text-2xl text-[#171513] dark:text-white mb-4">
                    {{ $isEditing ? 'Edit Category' : ($parentId ? 'New Subcategory' : 'New Category') }}
                </h2>

                <form wire:submit="saveCategory" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="cat-name" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Name <span class="text-[#8F7239]">*</span></label>
                            <input id="cat-name" type="text" wire:model="name" maxlength="120" placeholder="e.g. Luxury Wedding Cars">
                            @error('name') <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="cat-slug" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Slug (auto from name if empty)</label>
                            <input id="cat-slug" type="text" wire:model="slug" maxlength="140" placeholder="luxury-wedding-cars">
                            @error('slug') <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="cat-parent" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Parent (leave empty for top-level)</label>
                            <select id="cat-parent" wire:model.live="parentId" class="input-luxury w-full text-sm">
                                <option value="">— Top-level category —</option>
                                @foreach($parentOptions as $option)
                                    <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                                @endforeach
                            </select>
                            @error('parentId') <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="cat-booking" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Booking mode <span class="text-[#8F7239]">*</span></label>
                            <select id="cat-booking" wire:model="bookingMode" class="input-luxury w-full text-sm">
                                @foreach(\App\Models\VendorCategory::BOOKING_MODES as $mode)
                                    <option value="{{ $mode }}">{{ str_replace('_', ' ', $mode) }}</option>
                                @endforeach
                            </select>
                            @error('bookingMode') <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="cat-tagline" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Tagline</label>
                            <input id="cat-tagline" type="text" wire:model="tagline" maxlength="180" placeholder="Short marketplace tagline">
                            @error('tagline') <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="cat-icon" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Icon key</label>
                            <input id="cat-icon" type="text" wire:model="icon" maxlength="60" placeholder="camera, truck, sparkles…">
                            @error('icon') <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="cat-color" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Hero color <span class="text-[#8F7239]">*</span></label>
                            <div class="flex items-center gap-2">
                                <input id="cat-color" type="color" wire:model.live="heroColor" class="h-10 w-12 rounded border border-[#C6A15B]/40 bg-transparent cursor-pointer">
                                <input type="text" wire:model.live="heroColor" maxlength="7" class="input-luxury w-full text-sm font-mono" placeholder="#C6A15B">
                            </div>
                            @error('heroColor') <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="cat-image" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Image URL</label>
                            <input id="cat-image" type="text" wire:model="image" maxlength="255" placeholder="/storage/categories/…">
                            @error('image') <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="cat-desc" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Description</label>
                        <textarea id="cat-desc" wire:model="description" rows="3" class="input-luxury w-full text-sm resize-y" maxlength="1000"></textarea>
                        @error('description') <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="border-t border-[#C6A15B]/20 pt-4 space-y-4">
                        <p class="text-[10px] uppercase tracking-[0.14em] font-bold text-stone-500 dark:text-stone-400">SEO</p>
                        <div>
                            <label for="cat-seo-title" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">SEO title (max 160)</label>
                            <input id="cat-seo-title" type="text" wire:model="seoTitle" maxlength="160" placeholder="Category page title for search engines">
                            @error('seoTitle') <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="cat-seo-desc" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">SEO description (max 255)</label>
                            <textarea id="cat-seo-desc" wire:model="seoDescription" rows="2" class="input-luxury w-full text-sm resize-y" maxlength="255"></textarea>
                            @error('seoDescription') <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="closeModal" class="btn-outline-gold !h-10 !px-5 text-[11px] font-mono uppercase">Close</button>
                        <button type="submit" class="btn-gold !h-10 !px-6 text-[11px] font-mono uppercase">{{ $isEditing ? 'Save Changes' : 'Create' }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Taxonomy tree -->
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 shadow-sm dark:shadow-xl">
        <div class="px-5 py-3 border-b border-[#C6A15B]/20 flex items-center justify-between">
            <h2 class="text-[11px] uppercase tracking-[0.14em] font-bold text-stone-500 dark:text-stone-400">
                Taxonomy ({{ $tree->count() }} top-level)
            </h2>
        </div>

        <div class="divide-y divide-[#E7E5E4] dark:divide-white/10">
            @forelse($tree as $category)
                <!-- Top-level row -->
                <div class="px-5 py-4">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-block h-3 w-3 rounded-full border border-black/10" style="background: {{ $category->hero_color }}"></span>
                                <p class="font-medium text-sm text-[#171513] dark:text-white">{{ $category->name }}</p>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider {{ $category->is_active ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : 'bg-red-500/15 text-red-600 dark:text-red-400' }}">
                                    {{ $category->is_active ? 'Active' : 'Disabled' }}
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider bg-black/5 dark:bg-white/10 text-stone-500 dark:text-stone-400">
                                    {{ str_replace('_', ' ', $category->booking_mode) }}
                                </span>
                            </div>
                            <p class="text-xs text-stone-600 dark:text-stone-300 font-sans truncate mt-1">{{ $category->tagline }}</p>
                            <p class="text-[11px] text-stone-500 dark:text-stone-400 font-mono mt-0.5">
                                /{{ $category->slug }} · sort {{ $category->sort }} · {{ $category->children->count() }} subcategories
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button wire:click="moveCategory({{ $category->id }}, 'up')" title="Move up" class="btn-outline-gold !h-9 !px-3 text-[10px] font-mono uppercase">↑</button>
                            <button wire:click="moveCategory({{ $category->id }}, 'down')" title="Move down" class="btn-outline-gold !h-9 !px-3 text-[10px] font-mono uppercase">↓</button>
                            <button wire:click="openCreateModal({{ $category->id }})" class="btn-outline-gold !h-9 !px-4 text-[10px] font-mono uppercase">+ Sub</button>
                            <button wire:click="toggleCategory({{ $category->id }})" class="btn-outline-gold !h-9 !px-4 text-[10px] font-mono uppercase">{{ $category->is_active ? 'Disable' : 'Enable' }}</button>
                            <button wire:click="openEditModal({{ $category->id }})" class="btn-outline-gold !h-9 !px-4 text-[10px] font-mono uppercase">Edit</button>
                            <button wire:click="deleteCategory({{ $category->id }})" wire:confirm="Delete this category? This is only possible when nothing references it." class="!h-9 !px-4 text-[10px] font-mono uppercase rounded-lg border border-red-500/40 text-red-500 hover:bg-red-500/10 transition-colors">Del</button>
                        </div>
                    </div>

                    <!-- Subcategories -->
                    @if($category->children->isNotEmpty())
                        <div class="mt-3 ml-4 sm:ml-8 space-y-2 border-l-2 border-[#C6A15B]/25 pl-4">
                            @foreach($category->children as $child)
                                <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-xs font-medium text-[#171513] dark:text-white/90">{{ $child->name }}</p>
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-mono uppercase tracking-wider {{ $child->is_active ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : 'bg-red-500/15 text-red-600 dark:text-red-400' }}">
                                                {{ $child->is_active ? 'Active' : 'Disabled' }}
                                            </span>
                                        </div>
                                        <p class="text-[10px] text-stone-500 dark:text-stone-400 font-mono">/{{ $child->slug }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button wire:click="moveCategory({{ $child->id }}, 'up')" title="Move up" class="btn-outline-gold !h-8 !px-2.5 text-[10px] font-mono uppercase">↑</button>
                                        <button wire:click="moveCategory({{ $child->id }}, 'down')" title="Move down" class="btn-outline-gold !h-8 !px-2.5 text-[10px] font-mono uppercase">↓</button>
                                        <button wire:click="toggleCategory({{ $child->id }})" class="btn-outline-gold !h-8 !px-3 text-[10px] font-mono uppercase">{{ $child->is_active ? 'Disable' : 'Enable' }}</button>
                                        <button wire:click="openEditModal({{ $child->id }})" class="btn-outline-gold !h-8 !px-3 text-[10px] font-mono uppercase">Edit</button>
                                        <button wire:click="deleteCategory({{ $child->id }})" wire:confirm="Delete this subcategory? This is only possible when nothing references it." class="!h-8 !px-3 text-[10px] font-mono uppercase rounded-lg border border-red-500/40 text-red-500 hover:bg-red-500/10 transition-colors">Del</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="px-5 py-10 text-center">
                    <p class="text-sm text-stone-600 dark:text-stone-300">No categories yet — create the first top-level category.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
