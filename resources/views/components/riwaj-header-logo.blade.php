{{-- Riwaj Grand brand lockup — zameen.com style: monogram icon left + wordmark + Urdu tagline.
    Wordmark color (#D29C61) and Urdu tagline color match the official brand sheet (2nd reference image).
    Usage: <x-riwaj-header-logo size="md" />  |  size: sm | md | lg
    Optional: :badge="'Platform Super Admin'" replaces the tagline with a role line. --}}
@props(['size' => 'md', 'badge' => null])

@php
    $sizes = [
        'sm' => ['icon' => 'h-8 w-8 sm:h-9 sm:w-9', 'title' => 'text-[13px] sm:text-base', 'tag' => 'text-[8px] sm:text-[9px]', 'gap' => 'gap-2 sm:gap-2.5'],
        'md' => ['icon' => 'h-10 w-10 sm:h-12 sm:w-12', 'title' => 'text-base sm:text-xl', 'tag' => 'text-[9px] sm:text-[11px]', 'gap' => 'gap-2.5 sm:gap-3'],
        'lg' => ['icon' => 'h-12 w-12 sm:h-16 sm:w-16', 'title' => 'text-xl sm:text-2xl', 'tag' => 'text-[10px] sm:text-xs', 'gap' => 'gap-3'],
    ];
    $s = $sizes[$size] ?? $sizes['md'];
@endphp

<span {{ $attributes->merge(['class' => 'group flex shrink-0 items-center '.$s['gap']]) }}>
    <img src="{{ asset('images/riwaj-grand-mark.png') }}"
         width="512" height="512"
         alt="Riwaj Grand — RG monogram"
         loading="lazy" decoding="async"
         class="{{ $s['icon'] }} shrink-0 object-contain drop-shadow-[0_2px_6px_rgba(0,0,0,0.35)] transition-transform duration-300 group-hover:scale-[1.05]">
    <span class="flex min-w-0 flex-col leading-none">
        <span class="font-cinzel {{ $s['title'] }} font-bold uppercase tracking-[0.14em] text-[#D29C61] transition-colors duration-300 group-hover:text-[#E6B57A]">
            Riwaj&nbsp;Grand
        </span>
        @if($badge)
            <span class="{{ $s['tag'] }} mt-1 font-sans font-semibold uppercase tracking-[0.18em] text-[#D29C61]/80">
                {{ $badge }}
            </span>
        @else
            <span dir="rtl" lang="ur" class="{{ $s['tag'] }} font-urdu mt-1 text-[#C9BFAE]/90">
                روایت کے رنگ، جذبات کے سنگ
            </span>
        @endif
    </span>
</span>
