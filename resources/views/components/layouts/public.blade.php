<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#F8F5EF] dark:bg-[#171513] text-[#171513] dark:text-[#F8F5EF] antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $currentRoute = request()->route();
        $canonicalUrl = url()->current();

        // Use the named route with only its route parameters so query-string
        // filters never produce duplicate canonical/OG URLs.
        if ($currentRoute && $currentRoute->getName() && ! str_starts_with($currentRoute->getName(), 'livewire.')) {
            try {
                $canonicalUrl = route($currentRoute->getName(), $currentRoute->parameters());
            } catch (\Throwable $e) {
                $canonicalUrl = url()->current();
            }
        }
    @endphp

    <title>{{ $title ?? 'Riwaj Grand — Riwayat bhi, Jiddat bhi | Luxury Wedding Banquets & Marquees' }}</title>
    <meta name="description" content="{{ $metaDescription ?? 'Discover and reserve Pakistan\'s premier luxury wedding ballrooms, royal marquees, and banquet complexes. Concurrency-safe shift reservations, authentic Mughlai catering, and transparent pricing in PKR.' }}">
    <meta name="theme-color" content="#171513">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">

    <!-- OpenGraph & Social Sharing Meta -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Riwaj Grand">
    <meta property="og:title" content="{{ $title ?? 'Riwaj Grand — Riwayat bhi, Jiddat bhi' }}">
    <meta property="og:description" content="{{ $metaDescription ?? 'Discover and reserve Pakistan\'s premier luxury wedding ballrooms, royal marquees, and banquet complexes with guaranteed single-host shift exclusivity.' }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $ogImage ?? asset('images/riwaj-grand-logo.png') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? 'Riwaj Grand — Riwayat bhi, Jiddat bhi' }}">
    <meta name="twitter:description" content="{{ $metaDescription ?? 'Discover and reserve Pakistan\'s premier luxury wedding ballrooms, royal marquees, and banquet complexes.' }}">
    <meta name="twitter:image" content="{{ $ogImage ?? asset('images/riwaj-grand-logo.png') }}">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/riwaj-grand-mark.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/riwaj-grand-mark-192.png') }}">

    <!-- Google Fonts: Preconnect & Asynchronous Loading (High Performance & Zero Render-Blocking) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Cormorant+Garamond:ital,wght@0,500;0,600;1,400&family=Playfair+Display:ital,wght@0,500;0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Noto+Nastaliq+Urdu:wght@400&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Cormorant+Garamond:ital,wght@0,500;0,600;1,400&family=Playfair+Display:ital,wght@0,500;0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Noto+Nastaliq+Urdu:wght@400&display=swap" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Cormorant+Garamond:ital,wght@0,500;0,600;1,400&family=Playfair+Display:ital,wght@0,500;0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Noto+Nastaliq+Urdu:wght@400&display=swap">
    </noscript>

    <!-- Styles & Livewire -->
    <!-- Defensive Baseline Styles (Prevent Unstyled Image Blowout & Layout Shift) -->
    <style>
        img { max-width: 100%; height: auto; }
        .site-header img, header img, aside img { max-height: 48px; width: auto; }
    </style>

    <!-- Styles & Livewire -->
    @vite(['resources/js/app.js'])
    @livewireStyles

    <!-- Theme State Initializer (Default: Dark Luxury Palace) -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('RiwajGrand_theme') || localStorage.getItem('EventHub_theme');
            if (savedTheme === 'light') {
                document.documentElement.classList.remove('dark');
            } else {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>
<body class="min-h-full flex flex-col font-sans bg-[#F8F5EF] dark:bg-[#171513] text-[#171513] dark:text-[#F8F5EF] selection:bg-[#C6A15B] selection:text-[#171513] overflow-x-hidden transition-colors duration-200" 
      x-data="{ 
          mobileNav: false, 
          darkMode: document.documentElement.classList.contains('dark'), 
          toggleTheme() { 
              this.darkMode = !this.darkMode; 
              if (this.darkMode) { 
                  document.documentElement.classList.add('dark'); 
                  localStorage.setItem('RiwajGrand_theme', 'dark'); 
              } else { 
                  document.documentElement.classList.remove('dark'); 
                  localStorage.setItem('RiwajGrand_theme', 'light'); 
              } 
          } 
      }">

    <!-- Accessibility Skip Link (WCAG 2.1 AA) -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[100] focus:px-4 focus:py-2 focus:rounded-lg focus:bg-[#C6A15B] focus:text-[#171513] focus:font-semibold focus:text-xs focus:shadow-lg">
        Skip to main content
    </a>

    <!-- Main Navigation Bar (Luxury Obsidian & Champagne Gold) -->
    <header id="site-header" class="site-header sticky top-0 z-40 bg-[#171513]/95 backdrop-blur-xl border-b border-[#C6A15B]/25 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between gap-3 sm:gap-5 lg:h-[4.5rem]">
                
                <!-- Brand Identity (Riwaj Grand lockup) -->
                <a href="{{ route('home') }}" wire:navigate.hover aria-label="Riwaj Grand — Home">
                    <x-riwaj-header-logo size="md" />
                </a>

                <!-- Desktop Navigation Links -->
                @php
                    $primaryNavLinks = [
                        ['label' => 'Venues', 'route' => 'public.venues', 'patterns' => ['public.venues*', 'public.venue.details*', 'hall.showcase', 'marketplace']],
                        ['label' => 'Marketplace', 'route' => 'public.vendors', 'patterns' => ['public.vendors*', 'public.vendor-profile*', 'public.vendor-quote*']],
                        ['label' => 'Services', 'route' => 'public.services', 'patterns' => ['public.services*']],
                        ['label' => 'About', 'route' => 'public.about', 'patterns' => ['public.about*']],
                        ['label' => 'Contact', 'route' => 'public.contact', 'patterns' => ['public.contact*']],
                    ];
                    $mobileNavLinks = [
                        ['label' => 'Home', 'route' => 'home', 'patterns' => ['home']],
                        ...$primaryNavLinks,
                    ];
                @endphp
                <nav class="hidden shrink-0 items-center gap-6 xl:flex xl:gap-8" aria-label="Primary">
                    @foreach ($primaryNavLinks as $link)
                        <a href="{{ route($link['route']) }}" wire:navigate.hover class="nav-link {{ request()->routeIs(...$link['patterns']) ? 'is-active' : '' }}">{{ $link['label'] }}</a>
                    @endforeach
                </nav>

                <!-- Actions -->
                <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                    <!-- Theme Toggle (icon-only) -->
                    <button type="button"
                            @click="toggleTheme()"
                            class="flex h-9 w-9 items-center justify-center rounded-lg border border-[#C6A15B]/30 bg-[#171513] text-[#C6A15B] transition-colors duration-200 hover:border-[#C6A15B] hover:bg-[#C6A15B] hover:text-[#171513] cursor-pointer"
                            title="Toggle Light / Dark Mode"
                            aria-label="Toggle Light / Dark Mode">
                        <svg x-show="darkMode" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1.5M12 19.5V21M4.22 4.22l1.06 1.06M18.72 18.72l1.06 1.06M3 12h1.5M19.5 12H21M4.22 19.78l1.06-1.06M18.72 5.28l1.06-1.06M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <svg x-show="!darkMode" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                        </svg>
                    </button>

                    @guest
                        <a href="{{ route('login') }}" wire:navigate.hover class="hidden h-9 items-center px-2 text-[12px] font-semibold uppercase tracking-[0.12em] text-stone-300 transition-colors duration-300 hover:text-[#C6A15B] sm:inline-flex">
                            Sign In
                        </a>
                    @else
                        @if(auth()->user()->isSuperAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="btn-gold !h-9 !px-3 !py-1 !text-[11px] inline-flex items-center gap-1.5 font-bold whitespace-nowrap">
                                <span>Platform Console</span>
                            </a>
                        @elseif(auth()->user()->isCustomer())
                            <a href="{{ route('customer.dashboard') }}" class="btn-gold !h-9 !px-3 !py-1 !text-[11px] inline-flex items-center gap-1.5 font-bold whitespace-nowrap">
                                <span>My Bookings</span>
                            </a>
                        @else
                            <a href="{{ route('dashboard') }}" class="btn-gold !h-9 !px-3 !py-1 !text-[11px] inline-flex items-center gap-1.5 font-bold whitespace-nowrap">
                                <span>Hall Dashboard</span>
                            </a>
                        @endif

                        <form method="POST" action="{{ route('logout') }}" class="hidden sm:inline">
                            @csrf
                            <button type="submit" title="Sign Out" class="h-9 px-3 rounded-lg border border-white/20 bg-[#171513] text-stone-300 hover:text-[#F2DE9A] hover:border-[#C6A15B]/60 text-xs font-semibold transition-colors duration-300 cursor-pointer">
                                Exit
                            </button>
                        </form>
                    @endguest

                    <!-- Mobile Menu Hamburger -->
                    <button type="button" @click="mobileNav = !mobileNav" class="xl:hidden flex h-9 w-9 items-center justify-center rounded-lg border border-[#C6A15B]/30 bg-[#171513] text-white transition-colors duration-300 hover:border-[#C6A15B] hover:text-[#C6A15B] cursor-pointer" aria-label="Toggle Navigation" :aria-expanded="mobileNav ? 'true' : 'false'">
                        <svg x-show="!mobileNav" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M4 7h16M4 12h16M4 17h16"/>
                        </svg>
                        <svg x-show="mobileNav" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M6 6l12 12M18 6L6 18"/>
                        </svg>
                    </button>
                </div>

            </div>
        </div>

        <!-- Mobile Drawer -->
        <div x-show="mobileNav"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="xl:hidden bg-[#171513] border-b border-[#C6A15B]/30 px-5 py-5 space-y-5 shadow-2xl text-white"
             style="display: none;"
             @click.outside="mobileNav = false">
            <div class="text-[10px] uppercase font-bold tracking-[0.2em] text-[#C6A15B] font-sans">Navigation</div>
            <nav class="flex flex-col" aria-label="Mobile">
                @foreach ($mobileNavLinks as $mobileLink)
                    @php $mobileActive = request()->routeIs(...$mobileLink['patterns']); @endphp
                    <a href="{{ route($mobileLink['route']) }}"
                       wire:navigate.hover
                       class="flex items-center justify-between border-b border-white/[0.07] py-3 text-[15px] font-medium transition-colors duration-200 {{ $mobileActive ? 'text-[#C6A15B]' : 'text-stone-200 hover:text-[#C6A15B]' }}">
                        <span>{{ $mobileLink['label'] }}</span>
                        <svg class="h-4 w-4 {{ $mobileActive ? 'text-[#C6A15B]' : 'text-[#C6A15B]/50' }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                @endforeach
            </nav>

            <div class="flex flex-col gap-2.5">
                @guest
                    <a href="{{ route('login') }}" wire:navigate.hover class="btn-outline-gold !w-full !justify-center !text-[#F8F5EF] !border-[#C6A15B]/60 hover:!text-[#171513] hover:!border-[#C6A15B] hover:!bg-[#C6A15B] transition-all duration-300">Sign In</a>
                    <a href="{{ route('register') }}" wire:navigate.hover class="btn-gold !w-full !justify-center">List Your Wedding Hall</a>
                @else
                    @if(auth()->user()->isSuperAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="btn-gold !w-full !justify-center">Platform Console</a>
                    @elseif(auth()->user()->isCustomer())
                        <a href="{{ route('customer.dashboard') }}" class="btn-gold !w-full !justify-center">My Bookings</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="btn-gold !w-full !justify-center">Hall Dashboard</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-center py-2 text-xs font-semibold text-[#F8F5EF] hover:text-[#C6A15B]">Sign Out</button>
                    </form>
                @endguest
            </div>
        </div>
    </header>

    <!-- Page Content Slot -->
    <main id="main-content" class="public-page-content flex-1">
        {{ $slot }}
    </main>

    <!-- Luxury Obsidian Dark Footer -->
    <footer class="bg-[#171513] text-[#F8F5EF] pt-20 pb-12 border-t border-[#C6A15B]/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16">
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-12 pb-12 border-b border-white/10">
                <!-- Brand Info -->
                <div class="lg:col-span-2 space-y-5">
                    <a href="{{ route('home') }}" aria-label="Riwaj Grand — Home">
                        <x-riwaj-header-logo size="lg" />
                    </a>
                    <p class="text-sm text-stone-400 font-light leading-relaxed max-w-sm">
                        Pakistan's premier commercial marketplace and management SaaS for luxury wedding banquets, royal marquees, and convention centres. Dedicated to authentic hospitality, zero double-booking reliability, and transparent pricing.
                    </p>
                    <div class="pt-2 text-xs text-stone-400 space-y-1">
                        <p><strong class="text-white">Headquarters:</strong> Plot 42, Main Boulevard, Gulberg III, Lahore, Pakistan</p>
                        <p><strong class="text-white">Direct Phone:</strong> +92 42 35789000 (UAN Helpline)</p>
                        <p><strong class="text-white">Inquiries:</strong> inquiries@riwajgrand.pk</p>
                    </div>
                </div>

                <!-- Explore Links -->
                <div class="space-y-4">
                    <h4 class="font-cinzel text-xs font-bold uppercase tracking-[0.15em] text-[#C6A15B]">Marketplace</h4>
                    <ul class="space-y-2.5 text-xs text-stone-200 font-normal">
                        <li><a href="{{ route('public.venues') }}" wire:navigate.hover class="hover:text-white transition">Explore Venues</a></li>
                        <li><a href="{{ route('public.vendors') }}" wire:navigate.hover class="hover:text-white transition">Vendor Marketplace</a></li>
                        <li><a href="{{ route('public.packages') }}" wire:navigate.hover class="hover:text-white transition">Wedding Packages</a></li>
                        <li><a href="{{ route('public.packages') }}" wire:navigate.hover class="hover:text-white transition">Catering & Bawarchi</a></li>
                        <li><a href="{{ route('public.gallery') }}" wire:navigate.hover class="hover:text-white transition">Photo & Video Gallery</a></li>
                        <li><a href="{{ route('public.availability') }}" wire:navigate.hover class="hover:text-white transition">Live Shift Availability</a></li>
                        <li><a href="{{ route('public.services') }}" wire:navigate.hover class="hover:text-white transition">Event Services</a></li>
                    </ul>
                </div>

                <!-- Venue Owners / SaaS -->
                <div class="space-y-4">
                    <h4 class="font-cinzel text-xs font-bold uppercase tracking-[0.15em] text-[#C6A15B]">Venue Owners</h4>
                    <ul class="space-y-2.5 text-xs text-stone-200 font-normal">
                        <li><a href="{{ route('register') }}" wire:navigate.hover class="hover:text-white transition">List Your Venue</a></li>
                        <li><a href="{{ route('login') }}" wire:navigate.hover class="hover:text-white transition">Vendor Portal Login</a></li>
                        <li><a href="{{ route('dashboard') }}" class="hover:text-white transition">Shift Matrix & Calendar</a></li>
                        <li><a href="{{ route('public.about') }}" wire:navigate.hover class="hover:text-white transition">Why Riwaj Grand SaaS</a></li>
                        <li><a href="{{ route('public.faqs') }}" wire:navigate.hover class="hover:text-white transition">Vendor FAQs</a></li>
                    </ul>
                </div>

                <!-- Legal & Compliance -->
                <div class="space-y-4">
                    <h4 class="font-cinzel text-xs font-bold uppercase tracking-[0.15em] text-[#C6A15B]">Trust & Policies</h4>
                    <ul class="space-y-2.5 text-xs text-stone-200 font-normal">
                        <li><a href="{{ route('public.policies.terms') }}" wire:navigate.hover class="hover:text-white transition">Terms of Service</a></li>
                        <li><a href="{{ route('public.policies.privacy') }}" wire:navigate.hover class="hover:text-white transition">Privacy Policy</a></li>
                        <li><a href="{{ route('public.policies.cancellation') }}" wire:navigate.hover class="hover:text-white transition">Cancellation & Refund Policy</a></li>
                        <li><a href="{{ route('public.faqs') }}" wire:navigate.hover class="hover:text-white transition">Booking Security FAQ</a></li>
                        <li><a href="{{ route('public.contact') }}" wire:navigate.hover class="hover:text-white transition">Customer Support</a></li>
                    </ul>
                </div>

            </div>

            <!-- Bottom Disclaimer & Copyright -->
            <div class="flex flex-col md:flex-row items-center justify-between text-xs text-stone-500 gap-4">
                <span>&copy; {{ date('Y') }} Riwaj Grand Technologies (Pvt) Ltd. All rights reserved. Registered under SECP Pakistan.</span>
                <div class="flex items-center gap-6">
                    <span class="text-stone-400">Currency: <strong>Pakistani Rupee (PKR)</strong></span>
                    <span class="text-stone-400">Tax Compliance: <strong>PRA / SRB / FBR</strong></span>
                </div>
            </div>

        </div>
    </footer>

    <!-- GDPR / PDPA Cookie & Privacy Consent Bar (hidden on auth screens so login controls stay clickable) -->
    @unless(request()->routeIs('login', 'register', 'customer.login', 'customer.register'))
    <div x-data="{ showCookieBar: !localStorage.getItem('RiwajGrand_cookie_consent') && !localStorage.getItem('EventHub_cookie_consent') }"
         x-show="showCookieBar"
         x-cloak
         role="region"
         aria-label="Cookie consent notice"
         class="fixed bottom-4 left-4 right-28 sm:right-auto sm:max-w-sm z-30 bg-[#171513]/95 backdrop-blur-md border border-[#C6A15B]/35 rounded-2xl p-4 text-xs text-stone-300 shadow-2xl">
        <div class="flex items-start justify-between gap-4">
            <p class="leading-relaxed">
                We use essential cookies to guarantee secure shift reservations and personalize your venue search experience. Read our <a href="{{ route('public.policies.privacy') }}" class="text-[#C6A15B] underline hover:text-white">Privacy Policy</a>.
            </p>
            <button type="button"
                    @click="localStorage.setItem('RiwajGrand_cookie_consent', 'accepted'); showCookieBar = false"
                    aria-label="Accept cookies"
                    class="btn-gold !h-8 !px-3.5 text-[11px] font-semibold rounded-lg shrink-0">
                Accept
            </button>
        </div>
    </div>
    @endunless

    @unless(request()->routeIs('login', 'register', 'customer.login', 'customer.register'))
    <livewire:walk-in-booking-modal />
    @endunless

    <!-- Platform AI Public Concierge Chatbot (On General Public Pages; Venue Showcase has its own Venue Manager Desk) -->
    @unless(request()->routeIs('login', 'register', 'customer.login', 'customer.register', 'hall.showcase', 'public.venue.details'))
    <livewire:public-chat-widget />
    @endunless

    @livewireScripts
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            });
        }
    </script>
</body>
</html>

