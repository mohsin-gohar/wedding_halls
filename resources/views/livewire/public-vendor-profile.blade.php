<div class="bg-[#F8F5EF] dark:bg-[#171513] text-[#171513] dark:text-[#F8F5EF] min-h-screen transition-colors">
    <!-- Hero -->
    <section class="relative bg-[#171513] text-white py-16 sm:py-24 overflow-hidden border-b border-[#C6A15B]/30">
        @php
            $cover = $vendor->cover_image ?: ($this->portfolio->first()?->path ?: 'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=2000&auto=format&fit=crop');
        @endphp
        <div class="parallax-bg opacity-60" data-parallax="0.25" style="background-image: url('{{ $cover }}');"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#171513] via-[#171513]/55 to-[#171513]/75"></div>

        <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="text-[11px] font-sans uppercase tracking-wider text-stone-400 mb-4 flex items-center gap-2" aria-label="Breadcrumb">
                <a href="{{ route('public.vendors') }}" class="hover:text-[#C6A15B] transition-colors">Marketplace</a>
                <span>/</span>
                <a href="{{ route('public.vendors', ['category' => $category?->slug]) }}" class="hover:text-[#C6A15B] transition-colors">{{ $category?->name }}</a>
                <span>/</span>
                <span class="text-[#C6A15B]">{{ $vendor->name }}</span>
            </nav>

            <div class="flex flex-col sm:flex-row sm:items-end gap-5">
                <div class="flex-1 space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-3 py-1 rounded-full bg-[#C6A15B]/15 border border-[#C6A15B]/40 text-[#E8DCBE] text-[10px] font-bold uppercase tracking-wider">{{ $category?->name }}</span>
                        @if($vendor->verified_at)
                            <span class="px-3 py-1 rounded-full bg-[#C6A15B] text-[#171513] text-[10px] font-bold uppercase tracking-wider">✓ Verified Business</span>
                        @endif
                        @if($vendor->is_featured)
                            <span class="px-3 py-1 rounded-full bg-white/10 border border-white/20 text-white text-[10px] font-bold uppercase tracking-wider">Featured</span>
                        @endif
                    </div>
                    <h1 class="font-serif-luxury text-4xl sm:text-5xl font-normal">{{ $vendor->name }}</h1>
                    @if($vendor->about)
                        <p class="text-sm sm:text-base text-stone-300 max-w-2xl font-light leading-relaxed">{{ $vendor->about }}</p>
                    @endif
                    <div class="flex flex-wrap items-center gap-4 text-xs text-stone-300 font-sans">
                        @if($vendor->rating_avg > 0)
                            <span class="inline-flex items-center gap-1.5 text-[#C6A15B] font-bold">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 1.5l2.6 5.4 5.9.9-4.3 4.1 1 5.9L10 15l-5.2 2.8 1-5.9L1.5 7.8l5.9-.9L10 1.5z"/></svg>
                                {{ number_format($vendor->rating_avg, 1) }}
                            </span>
                            <span>({{ $vendor->rating_count }} reviews)</span>
                        @endif
                        @if($vendor->city)
                            <span class="inline-flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                                {{ $vendor->city }}@if($vendor->service_areas), {{ implode(', ', $vendor->service_areas) }}@endif
                            </span>
                        @endif
                        <span>{{ $vendor->profile_views }} profile views</span>
                    </div>
                </div>

                <div class="flex flex-col gap-3 shrink-0 w-full sm:w-auto">
                    <a href="{{ route('public.vendor-quote', ['slug' => $vendor->slug]) }}"
                       class="btn-gold px-6 py-3 text-sm font-bold uppercase tracking-wider text-center rounded-xl shadow-[0_10px_30px_-8px_rgba(198,161,91,0.6)]">
                        Request Free Quote
                    </a>
                    @php
                        $whatsapp = $vendor->website_settings['whatsapp_number'] ?? $vendor->phone;
                    @endphp
                    @if($whatsapp)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}" target="_blank" rel="noopener noreferrer"
                           class="px-6 py-3 text-sm font-semibold uppercase tracking-wider text-center rounded-xl border border-white/25 text-white hover:bg-white/10 transition-colors">
                            WhatsApp {{ $vendor->name }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <!-- Services & Packages -->
    @if($vendor->vendorServices->isNotEmpty() || $vendor->vendorPackages->isNotEmpty())
        <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-14 space-y-10">
            @if($vendor->vendorServices->isNotEmpty())
                <div>
                    <h2 class="font-serif-luxury text-2xl sm:text-3xl mb-6">Services &amp; Pricing</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($vendor->vendorServices as $service)
                            <article class="bg-white dark:bg-[#1C1917] rounded-2xl border border-[#C6A15B]/25 p-5 hover:border-[#C6A15B]/60 transition-all duration-300">
                                <div class="flex items-start justify-between gap-3 mb-2">
                                    <h3 class="font-serif-luxury text-lg leading-snug">{{ $service->name }}</h3>
                                    <span class="shrink-0 text-sm font-bold text-[#8F7239] dark:text-[#C6A15B]">{{ $service->priceLabel() }}</span>
                                </div>
                                @if($service->description)
                                    <p class="text-xs text-stone-500 dark:text-stone-400 font-sans leading-relaxed mb-3">{{ $service->description }}</p>
                                @endif
                                @if($service->highlights)
                                    <ul class="space-y-1.5">
                                        @foreach($service->highlights as $highlight)
                                            <li class="flex items-center gap-2 text-xs text-stone-600 dark:text-stone-300 font-sans">
                                                <svg class="w-3.5 h-3.5 text-[#C6A15B] shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
                                                {{ $highlight }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                                <a href="{{ route('public.vendor-quote', ['slug' => $vendor->slug, 'service' => $service->id]) }}"
                                   class="inline-flex items-center gap-1.5 mt-4 text-xs font-bold uppercase tracking-wider text-[#8F7239] dark:text-[#C6A15B] hover:gap-2.5 transition-all">
                                    Get quote for this <span aria-hidden="true">→</span>
                                </a>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($vendor->vendorPackages->isNotEmpty())
                <div>
                    <h2 class="font-serif-luxury text-2xl sm:text-3xl mb-6">Packages</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach($vendor->vendorPackages as $package)
                            <article class="bg-white dark:bg-[#1C1917] rounded-2xl border border-[#C6A15B]/25 overflow-hidden hover:border-[#C6A15B]/60 transition-all duration-300 flex flex-col">
                                @if($package->image)
                                    <img src="{{ $package->image }}" alt="{{ $package->name }}" loading="lazy" class="h-36 w-full object-cover">
                                @endif
                                <div class="p-5 flex-1 flex flex-col">
                                    <h3 class="font-serif-luxury text-lg mb-1">{{ $package->name }}</h3>
                                    <span class="text-sm font-bold text-[#8F7239] dark:text-[#C6A15B] mb-3">{{ $package->priceLabel() }}</span>
                                    @if($package->description)
                                        <p class="text-xs text-stone-500 dark:text-stone-400 font-sans leading-relaxed mb-3 flex-1">{{ $package->description }}</p>
                                    @endif
                                    @if($package->items)
                                        <ul class="space-y-1 mb-4">
                                            @foreach(array_slice($package->items, 0, 5) as $item)
                                                <li class="text-xs text-stone-600 dark:text-stone-300 font-sans flex items-center gap-1.5">
                                                    <span class="text-[#C6A15B]">◆</span>{{ is_string($item) ? $item : ($item['name'] ?? '') }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    <a href="{{ route('public.vendor-quote', ['slug' => $vendor->slug]) }}" class="btn-outline-gold px-4 py-2 text-xs font-bold uppercase tracking-wider rounded-lg text-center mt-auto">Book package</a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    @endif

    <!-- Portfolio -->
    @if($this->portfolio->isNotEmpty())
        <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-14">
            <h2 class="font-serif-luxury text-2xl sm:text-3xl mb-6">Portfolio</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                @foreach($this->portfolio as $media)
                    <figure class="group relative rounded-xl overflow-hidden border border-[#C6A15B]/25 aspect-[4/3]">
                        <img src="{{ $media->url() }}" alt="{{ $media->caption ?? 'Portfolio' }}" loading="lazy" decoding="async"
                             class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                        @if($media->caption)
                            <figcaption class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent px-3 py-2 text-[10px] text-white font-sans opacity-0 group-hover:opacity-100 transition-opacity">
                                {{ $media->caption }}
                            </figcaption>
                        @endif
                    </figure>
                @endforeach
            </div>
        </section>
    @endif

    <!-- Reviews -->
    @if($this->reviews->isNotEmpty())
        <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-14">
            <h2 class="font-serif-luxury text-2xl sm:text-3xl mb-6">Client Reviews</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($this->reviews as $review)
                    <article class="bg-white dark:bg-[#1C1917] rounded-2xl border border-[#C6A15B]/25 p-5">
                        <div class="flex items-center gap-1 mb-2" aria-label="{{ $review->rating }} out of 5 stars">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-[#C6A15B]' : 'text-stone-300 dark:text-stone-600' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M10 1.5l2.6 5.4 5.9.9-4.3 4.1 1 5.9L10 15l-5.2 2.8 1-5.9L1.5 7.8l5.9-.9L10 1.5z"/></svg>
                            @endfor
                        </div>
                        @if($review->title)<h3 class="font-semibold text-sm mb-1">{{ $review->title }}</h3>@endif
                        <p class="text-xs text-stone-500 dark:text-stone-400 font-sans leading-relaxed">{{ $review->body }}</p>
                        <footer class="mt-3 pt-3 border-t border-[#C6A15B]/20 text-[11px] text-stone-400 font-sans flex items-center justify-between">
                            <span class="font-semibold text-stone-600 dark:text-stone-300">{{ $review->user?->name ?? 'Verified client' }}</span>
                            <time datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->format('M d, Y') }}</time>
                        </footer>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <!-- CTA -->
    <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
        <div class="rounded-3xl bg-[#171513] text-white p-8 sm:p-12 text-center border border-[#C6A15B]/30">
            <h2 class="font-serif-luxury text-2xl sm:text-3xl mb-3">Planning a wedding in {{ $vendor->city ?? 'Pakistan' }}?</h2>
            <p class="text-sm text-stone-300 max-w-xl mx-auto mb-6 font-light">Get a free, no-obligation quote from {{ $vendor->name }} — usually within 24 hours.</p>
            <a href="{{ route('public.vendor-quote', ['slug' => $vendor->slug]) }}" class="btn-gold px-8 py-3 text-sm font-bold uppercase tracking-wider rounded-xl inline-block">Request Quote</a>
        </div>
    </section>
</div>
