@php
    // Brand tokens — fall back to the Riwaj Grand house style.
    $brandPrimary = $brand?->primaryColor ?? '#171513';
    $brandSecondary = $brand?->secondaryColor ?? '#24211E';
    $brandAccent = $brand?->accentColor ?? '#C6A15B';
    $brandName = $brand?->name ?? ($vendor?->name ?? 'Riwaj Grand');
@endphp

<div style="--brand-primary: {{ $brandPrimary }}; --brand-secondary: {{ $brandSecondary }}; --brand-accent: {{ $brandAccent }};">
    <!-- Hero -->
    <section class="relative overflow-hidden" style="background: {{ $brandPrimary }};">
        @if($brand?->coverImage)
            <div class="absolute inset-0 opacity-30" style="background-image: url('{{ $brandCover = $brand->coverImage }}'); background-size: cover; background-position: center;"></div>
        @endif
        <div class="absolute inset-0" style="background: linear-gradient(to bottom, rgba(0,0,0,0.55), rgba(0,0,0,0.85));"></div>

        <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-28 text-center">
            @if($brand?->logo)
                <img src="{{ $brand->logo }}" alt="{{ $brandName }}" class="h-16 w-auto mx-auto mb-6 object-contain">
            @endif

            <span class="inline-flex items-center gap-2 px-3 py-1 border text-[10px] font-mono uppercase tracking-[0.2em] font-bold"
                  style="border-color: {{ $brandAccent }}; color: {{ $brandAccent }};">
                {{ $vendor?->vendorCategory?->name ?? 'Verified Vendor' }}
            </span>

            <h1 class="font-serif-luxury text-4xl sm:text-6xl text-white mt-5 font-normal">
                {{ $brandName }}
            </h1>

            @if($brand?->tagline)
                <p class="mt-4 text-sm sm:text-base text-stone-200 font-sans max-w-2xl mx-auto">{{ $brand->tagline }}</p>
            @endif

            <div class="flex flex-wrap items-center justify-center gap-4 mt-8">
                <a href="{{ route('public.vendor-quote', ['slug' => $vendor?->slug]) }}"
                   class="btn-gold !h-11 !px-7 text-xs font-bold uppercase font-mono shadow-lg">
                    Request a Quotation
                </a>
                @if($brand?->phone)
                    <a href="tel:{{ $brand->phone }}" class="btn-outline-gold !h-11 !px-7 text-xs font-bold uppercase font-mono">
                        {{ $brand->phone }}
                    </a>
                @endif
            </div>
        </div>
    </section>

    <!-- About -->
    @if($vendor?->about)
        <section class="py-16" style="background: {{ $brandSecondary }};">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="font-serif-luxury text-3xl text-white text-center">About Us</h2>
                <p class="mt-6 text-sm text-stone-300 font-sans leading-relaxed text-center">{{ $vendor->about }}</p>
            </div>
        </section>
    @endif

    <!-- Services -->
    @if($this->services->isNotEmpty())
        <section class="py-16 bg-[#F8F5EF] dark:bg-[#171513]">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="font-serif-luxury text-3xl text-[#171513] dark:text-white text-center">Our Services</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-10">
                    @foreach($this->services as $service)
                        <article class="bg-white dark:bg-[#1C1917] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6">
                            <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white">{{ $service->name }}</h3>
                            @if($service->description)
                                <p class="text-xs text-stone-600 dark:text-stone-300 font-sans mt-2 leading-relaxed">{{ $service->description }}</p>
                            @endif
                            @if($service->price_from)
                                <p class="font-serif-luxury text-lg mt-4" style="color: {{ $brandAccent }};">
                                    PKR {{ number_format((float) $service->price_from) }}
                                    @if($service->price_to && (float) $service->price_to > (float) $service->price_from)
                                        <span class="text-xs font-sans text-stone-500">— {{ number_format((float) $service->price_to) }}</span>
                                    @endif
                                    @if($service->unit)
                                        <span class="text-[11px] font-sans text-stone-500">/ {{ $service->unit }}</span>
                                    @endif
                                </p>
                            @endif
                            @if($service->highlights)
                                <ul class="mt-4 space-y-1">
                                    @foreach($service->highlights as $highlight)
                                        <li class="text-[11px] text-stone-600 dark:text-stone-300 font-sans">• {{ $highlight }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- Portfolio -->
    @if($this->portfolio->isNotEmpty())
        <section class="py-16" style="background: {{ $brandPrimary }};">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="font-serif-luxury text-3xl text-white text-center">Our Work</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-10">
                    @foreach($this->portfolio as $media)
                        <figure class="overflow-hidden border" style="border-color: {{ $brandAccent }}55;">
                            @if($media->type === 'VIDEO')
                                <video src="{{ $media->path }}" class="w-full h-40 object-cover" muted></video>
                            @else
                                <img src="{{ $media->path }}" alt="{{ $media->caption ?? $brandName }}" class="w-full h-40 object-cover" loading="lazy">
                            @endif
                            @if($media->caption)
                                <figcaption class="text-[10px] text-stone-300 font-sans px-3 py-2" style="background: {{ $brandSecondary }};">{{ $media->caption }}</figcaption>
                            @endif
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- Locations -->
    @if($this->locations->isNotEmpty())
        <section class="py-16 bg-[#F8F5EF] dark:bg-[#171513]">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="font-serif-luxury text-3xl text-[#171513] dark:text-white text-center">Our Locations</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mt-10">
                    @foreach($this->locations as $location)
                        <div class="bg-white dark:bg-[#1C1917] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5">
                            <h3 class="font-serif-luxury text-lg text-[#171513] dark:text-white">{{ $location->name }}</h3>
                            <p class="text-xs text-stone-600 dark:text-stone-300 font-sans mt-1">{{ $location->fullAddress() }}</p>
                            @if($location->phone)
                                <p class="text-xs text-stone-500 dark:text-stone-400 font-sans mt-2">{{ $location->phone }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- Reviews -->
    @if($this->reviews->isNotEmpty())
        <section class="py-16" style="background: {{ $brandSecondary }};">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="font-serif-luxury text-3xl text-white text-center">What Our Clients Say</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mt-10">
                    @foreach($this->reviews as $review)
                        <blockquote class="p-5 border" style="border-color: {{ $brandAccent }}55; background: {{ $brandPrimary }};">
                            <p class="text-sm text-stone-200 font-sans italic leading-relaxed">“{{ $review->body }}”</p>
                            <footer class="mt-4 text-[11px] font-mono uppercase tracking-wider" style="color: {{ $brandAccent }};">
                                {{ $review->user?->name ?? 'Verified client' }}
                            </footer>
                        </blockquote>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- CTA -->
    <section class="py-16 text-center" style="background: {{ $brandPrimary }}; border-top: 1px solid {{ $brandAccent }}55;">
        <h2 class="font-serif-luxury text-3xl text-white">Let's plan something unforgettable</h2>
        <p class="text-sm text-stone-300 font-sans mt-3 max-w-xl mx-auto">
            Tell us about your event and receive a tailored quotation within 24 hours.
        </p>
        <a href="{{ route('public.vendor-quote', ['slug' => $vendor?->slug]) }}"
           class="btn-gold !h-11 !px-8 text-xs font-bold uppercase font-mono mt-7 inline-block">
            Request a Quotation
        </a>
    </section>
</div>
