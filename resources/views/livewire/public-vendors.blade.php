<div class="bg-[#F8F5EF] dark:bg-[#171513] text-[#171513] dark:text-[#F8F5EF] min-h-screen transition-colors">
    <!-- ===================================================================
         SECTION 1: MARKETPLACE HERO
         =================================================================== -->
    <section class="relative bg-[#171513] text-white py-20 border-b border-[#C6A15B]/30 overflow-hidden">
        <div class="parallax-bg opacity-60" data-parallax="0.25"
             style="background-image: url('https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=2000&auto=format&fit=crop');"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#171513] via-[#171513]/50 to-[#171513]/70"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 text-center reveal-on-scroll">
            <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full border border-[#C6A15B]/40 bg-[#171513]/90 text-[#E8DCBE] text-[11px] font-semibold tracking-[0.18em] uppercase font-sans">
                <span class="w-1.5 h-1.5 rounded-full bg-[#C6A15B]"></span>
                <span>Every Wedding Service, One Royal Platform</span>
            </div>

            <h1 class="font-serif-luxury text-4xl sm:text-6xl text-white font-normal max-w-4xl mx-auto leading-tight">
                Pakistan's Complete Wedding Services Marketplace
            </h1>

            <p class="text-sm sm:text-base text-stone-300 max-w-2xl mx-auto font-light leading-relaxed">
                Discover and book verified photographers, caterers, decorators, makeup artists, planners and transport — all under one roof.
            </p>
        </div>
    </section>

    <!-- ===================================================================
         SECTION 2: CATEGORY TABS
         =================================================================== -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-8 relative z-20">
        <div class="bg-white dark:bg-[#1C1917] border border-[#C6A15B]/30 rounded-2xl p-4 sm:p-5 shadow-[0_12px_36px_-8px_rgba(23,21,19,0.18)] reveal-on-scroll">
            <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-thin" role="tablist" aria-label="Vendor categories">
                <button type="button" wire:click="selectCategory('all')"
                        class="shrink-0 px-4 py-2 rounded-full text-xs font-semibold uppercase tracking-wider border transition-all duration-200 {{ $category === 'all' ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513] border-[#171513] dark:border-[#C6A15B]' : 'border-[#C6A15B]/40 text-stone-600 dark:text-stone-300 hover:bg-[#C6A15B]/10' }}">
                    All Services
                </button>
                @foreach($this->categories as $cat)
                    <button type="button" wire:click="selectCategory('{{ $cat->slug }}')"
                            class="shrink-0 px-4 py-2 rounded-full text-xs font-semibold uppercase tracking-wider border transition-all duration-200 {{ $this->activeParentSlug === $cat->slug ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513] border-[#171513] dark:border-[#C6A15B]' : 'border-[#C6A15B]/40 text-stone-600 dark:text-stone-300 hover:bg-[#C6A15B]/10' }}">
                        {{ $cat->name }} <span class="opacity-60">({{ $cat->tenants_count }})</span>
                    </button>
                @endforeach
            </div>

            <!-- Subcategory drill-down: appears only under a selected top-level category -->
            @if($this->subcategories->isNotEmpty())
                <div class="flex flex-wrap items-center gap-2 mt-3 pb-1 border-b border-[#C6A15B]/15 pb-3"
                     role="tablist" aria-label="Subcategories">
                    <span class="text-[10px] font-semibold uppercase tracking-[0.12em] text-stone-500 dark:text-stone-400 mr-1">
                        Refine:
                    </span>
                    <button type="button" wire:click="selectCategory('{{ $this->activeParentSlug }}')"
                            class="shrink-0 px-3 py-1.5 rounded-full text-[11px] font-semibold border transition-all duration-200 {{ $category === $this->activeParentSlug ? 'bg-[#C6A15B]/15 border-[#C6A15B] text-[#8F7239] dark:text-[#C6A15B]' : 'border-[#C6A15B]/30 text-stone-600 dark:text-stone-300 hover:bg-[#C6A15B]/10' }}">
                        All
                    </button>
                    @foreach($this->subcategories as $sub)
                        <button type="button" wire:click="selectCategory('{{ $sub->slug }}')"
                                class="shrink-0 px-3 py-1.5 rounded-full text-[11px] font-semibold border transition-all duration-200 {{ $category === $sub->slug ? 'bg-[#C6A15B]/15 border-[#C6A15B] text-[#8F7239] dark:text-[#C6A15B]' : 'border-[#C6A15B]/30 text-stone-600 dark:text-stone-300 hover:bg-[#C6A15B]/10' }}">
                            {{ $sub->name }}
                        </button>
                    @endforeach
                </div>
            @endif

            <!-- Filters -->
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-5 gap-3 mt-4 pt-4 border-t border-[#C6A15B]/20">
                <div>
                    <label for="vendor-search" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1">Search</label>
                    <input id="vendor-search" type="search" wire:model.live.debounce.350ms="search" placeholder="Service or vendor…"
                           class="input-luxury w-full text-sm dark:bg-[#171513] dark:text-white dark:border-white/15 rounded-lg">
                </div>
                <div>
                    <label for="vendor-city" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1">City</label>
                    <select id="vendor-city" wire:model.live="city" class="input-luxury w-full text-sm dark:bg-[#171513] dark:text-white dark:border-white/15 rounded-lg">
                        <option value="All">All cities</option>
                        @foreach($this->cities as $availableCity)
                            <option value="{{ $availableCity }}">{{ $availableCity }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="vendor-budget" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1">Budget</label>
                    <select id="vendor-budget" wire:model.live="budget" class="input-luxury w-full text-sm dark:bg-[#171513] dark:text-white dark:border-white/15 rounded-lg">
                        <option value="">Any budget</option>
                        <option value="under-50k">Under PKR 50k</option>
                        <option value="50k-150k">PKR 50k – 150k</option>
                        <option value="150k-300k">PKR 150k – 300k</option>
                        <option value="300k-plus">PKR 300k+</option>
                    </select>
                </div>
                <div>
                    <label for="vendor-rating" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1">Rating</label>
                    <select id="vendor-rating" wire:model.live="rating" class="input-luxury w-full text-sm dark:bg-[#171513] dark:text-white dark:border-white/15 rounded-lg">
                        <option value="">Any rating</option>
                        <option value="4.5">4.5★ &amp; up</option>
                        <option value="4">4★ &amp; up</option>
                        <option value="3">3★ &amp; up</option>
                    </select>
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <label for="vendor-sort" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1">Sort</label>
                    <select id="vendor-sort" wire:model.live="sort" class="input-luxury w-full text-sm dark:bg-[#171513] dark:text-white dark:border-white/15 rounded-lg">
                        <option value="featured">Featured first</option>
                        <option value="rating">Highest rated</option>
                        <option value="name">Name A–Z</option>
                        <option value="newest">Newest</option>
                    </select>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================================================================
         SECTION 3: VENDOR CARDS
         =================================================================== -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
        <div class="flex items-center justify-between mb-6">
            <p class="text-sm text-stone-600 dark:text-stone-300 font-sans">
                <strong class="text-[#171513] dark:text-[#C6A15B]">{{ $this->vendors->total() }}</strong>
                verified {{ $category === 'all'
                    ? 'vendors'
                    : ($this->categories->firstWhere('slug', $category)?->name
                        ?? $this->subcategories->firstWhere('slug', $category)?->name
                        ?? 'vendors') }}
            </p>
            @if($search || $city !== 'All' || $budget || $rating || $category !== 'all')
                <button type="button" wire:click="resetFilters" class="text-xs font-semibold uppercase tracking-wider text-[#8F7239] hover:text-[#C6A15B] transition-colors">Clear all</button>
            @endif
        </div>

        @if($this->vendors->isEmpty())
            <div class="text-center py-20">
                <div class="w-16 h-16 mx-auto rounded-full bg-[#C6A15B]/10 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-[#C6A15B]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                </div>
                <h3 class="font-serif-luxury text-xl mb-2">No vendors match your filters</h3>
                <p class="text-sm text-stone-500 dark:text-stone-400">Try clearing filters or exploring another category.</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                @foreach($this->vendors as $vendor)
                    @php
                        $cover = \App\Support\Media::publicUrl(
                            $vendor->cover_image
                                ?: ($vendor->portfolioMedia->first()?->path
                                    ?: 'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=800&auto=format&fit=crop')
                        );
                    @endphp
                    <article class="group relative bg-white dark:bg-[#1C1917] rounded-2xl overflow-hidden border border-[#C6A15B]/25 hover:border-[#C6A15B]/60 transition-all duration-300 shadow-[0_6px_24px_-6px_rgba(23,21,19,0.12)] hover:shadow-[0_20px_44px_-12px_rgba(23,21,19,0.28)] hover:-translate-y-1">
                        <a href="{{ route('public.vendor-profile', ['slug' => $vendor->slug]) }}" class="block">
                            <div class="relative h-44 overflow-hidden">
                                <img src="{{ $cover }}" alt="{{ $vendor->name }} portfolio"
                                     loading="lazy" decoding="async"
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.06]">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>

                                @if($vendor->is_featured)
                                    <span class="absolute top-3 left-3 px-2.5 py-1 rounded-full bg-[#C6A15B] text-[#171513] text-[10px] font-bold uppercase tracking-wider shadow-lg">Featured</span>
                                @endif
                                @if($vendor->verified_at)
                                    <span class="absolute top-3 right-3 inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-[#171513]/85 text-[#C6A15B] text-[10px] font-bold uppercase tracking-wider backdrop-blur-sm">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.403 12.652a3 3 0 0 0 0-5.304 3 3 0 0 0-3.755-3.751 3 3 0 0 0-5.296 0 3 3 0 0 0-3.751 3.75 3 3 0 0 0 0 5.305 3 3 0 0 0 3.75 3.751 3 3 0 0 0 5.297 0 3 3 0 0 0 3.755-3.751Zm-5.308 1.352a.75.75 0 0 1-1.06 0l-1.5-1.5a.75.75 0 1 1 1.06-1.06l.97.97 2.47-2.47a.75.75 0 1 1 1.06 1.06l-3 3Z" clip-rule="evenodd"/></svg>
                                        Verified
                                    </span>
                                @endif
                            </div>

                            <div class="p-4 space-y-2.5">
                                <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider text-[#8F7239]">
                                    <span class="w-4 h-4 inline-flex items-center justify-center rounded bg-[#C6A15B]/15">{{ $vendor->vendorCategory?->icon ? '◆' : '◆' }}</span>
                                    {{ $vendor->vendorCategory?->name }}
                                </div>

                                <h3 class="font-serif-luxury text-lg leading-snug group-hover:text-[#8F7239] dark:group-hover:text-[#C6A15B] transition-colors line-clamp-1">
                                    {{ $vendor->name }}
                                </h3>

                                <div class="flex items-center gap-3 text-xs text-stone-500 dark:text-stone-400 font-sans">
                                    @if($vendor->rating_avg > 0)
                                        <span class="inline-flex items-center gap-1 text-[#8F7239] dark:text-[#C6A15B] font-semibold">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 1.5l2.6 5.4 5.9.9-4.3 4.1 1 5.9L10 15l-5.2 2.8 1-5.9L1.5 7.8l5.9-.9L10 1.5z"/></svg>
                                            {{ number_format($vendor->rating_avg, 1) }}
                                        </span>
                                        <span>({{ $vendor->rating_count }})</span>
                                    @endif
                                    @if($vendor->city)
                                        <span class="inline-flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                                            {{ $vendor->city }}
                                        </span>
                                    @endif
                                </div>

                                <p class="text-xs text-stone-500 dark:text-stone-400 font-sans leading-relaxed line-clamp-2">
                                    {{ \Illuminate\Support\Str::limit($vendor->about ?? $vendor->vendorCategory?->tagline ?? '', 110) }}
                                </p>

                                <div class="flex items-center justify-between pt-2 border-t border-[#C6A15B]/20">
                                    <span class="text-[11px] text-stone-500 dark:text-stone-400 font-sans">{{ $vendor->services_count }} services</span>
                                    <span class="text-xs font-bold text-[#8F7239] dark:text-[#C6A15B] group-hover:translate-x-0.5 transition-transform">View profile →</span>
                                </div>
                            </div>
                        </a>
                    </article>
                @endforeach
            </div>

            <div class="mt-10">
                {{ $this->vendors->links() }}
            </div>
        @endif
    </section>
</div>
