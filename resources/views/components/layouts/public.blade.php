<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#F8F5EF] text-[#171513] antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'VenueCloud — Luxury Wedding Venues & Banquet Marketplace' }}</title>
    <meta name="description" content="Discover and reserve Pakistan's premier luxury wedding ballrooms, royal marquees, and banquet complexes. Concurrency-safe shift reservations, authentic Mughlai catering, and transparent pricing in PKR.">

    <!-- Google Fonts: Playfair Display, Cinzel, Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles & Livewire -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <!-- Theme State Initializer (Default: Dark Luxury Palace) -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('venuecloud_theme');
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
                  localStorage.setItem('venuecloud_theme', 'dark'); 
              } else { 
                  document.documentElement.classList.remove('dark'); 
                  localStorage.setItem('venuecloud_theme', 'light'); 
              } 
          } 
      }">

    <!-- Main Navigation Bar (Luxury Obsidian & Champagne Gold, Zero-Pill) -->
    <header class="sticky top-0 z-40 bg-[#171513]/95 backdrop-blur-lg border-b border-[#C6A15B]/40 transition-all duration-300 text-white shadow-[0_10px_40px_-15px_rgba(198,161,91,0.45)]" id="site-header">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="h-20 flex items-center justify-between gap-6">
                
                <!-- Brand Identity -->
                <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 border border-[#C6A15B] flex items-center justify-center bg-[#171513] text-[#C6A15B] transition group-hover:bg-gradient-to-br group-hover:from-[#C6A15B] group-hover:to-[#8F7239] group-hover:text-[#171513] shadow-[0_0_20px_-8px_rgba(198,161,91,0.4)] group-hover:shadow-[0_0_30px_rgba(198,161,91,0.6)]">
                        <span class="font-cinzel text-lg font-bold">V</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-cinzel text-xl font-bold tracking-[0.16em] text-white group-hover:text-[#C6A15B] transition">
                            VENUECLOUD
                        </span>
                        <span class="text-[9px] tracking-[0.26em] text-[#C6A15B]/80 uppercase -mt-0.5 font-medium font-mono">
                            Luxury Banquets &amp; Marquees
                        </span>
                    </div>
                </a>

                <!-- Desktop Navigation Links -->
                <nav class="hidden lg:flex items-center space-x-7 text-[13px] font-semibold tracking-[0.03em] uppercase font-mono">
                    <a href="{{ route('home') }}" class="py-2 transition {{ request()->routeIs('home') ? 'border-b-2 border-[#C6A15B] text-[#C6A15B]' : 'text-stone-300 hover:text-[#C6A15B]' }}">
                        Home
                    </a>
                    <a href="{{ route('public.venues') }}" class="py-2 transition {{ request()->routeIs('public.venues*') ? 'border-b-2 border-[#C6A15B] text-[#C6A15B]' : 'text-stone-300 hover:text-[#C6A15B]' }}">
                        Explore Venues
                    </a>
                    <a href="{{ route('public.packages') }}" class="py-2 transition {{ request()->routeIs('public.packages*') ? 'border-b-2 border-[#C6A15B] text-[#C6A15B]' : 'text-stone-300 hover:text-[#C6A15B]' }}">
                        Catering &amp; Packages
                    </a>
                    <a href="{{ route('public.gallery') }}" class="py-2 transition {{ request()->routeIs('public.gallery*') ? 'border-b-2 border-[#C6A15B] text-[#C6A15B]' : 'text-stone-300 hover:text-[#C6A15B]' }}">
                        Gallery
                    </a>
                    <a href="{{ route('public.services') }}" class="py-2 transition {{ request()->routeIs('public.services*') ? 'border-b-2 border-[#C6A15B] text-[#C6A15B]' : 'text-stone-300 hover:text-[#C6A15B]' }}">
                        Services
                    </a>
                    <a href="{{ route('public.availability') }}" class="py-2 transition {{ request()->routeIs('public.availability*') ? 'border-b-2 border-[#C6A15B] text-[#C6A15B]' : 'text-stone-300 hover:text-[#C6A15B]' }}">
                        Availability
                    </a>
                    <a href="{{ route('public.about') }}" class="py-2 transition {{ request()->routeIs('public.about*') ? 'border-b-2 border-[#C6A15B] text-[#C6A15B]' : 'text-stone-300 hover:text-[#C6A15B]' }}">
                        About
                    </a>
                    <a href="{{ route('public.contact') }}" class="py-2 transition {{ request()->routeIs('public.contact*') ? 'border-b-2 border-[#C6A15B] text-[#C6A15B]' : 'text-stone-300 hover:text-[#C6A15B]' }}">
                        Contact
                    </a>
                </nav>

                <!-- Actions: Zero-Pill Buttons -->
                <div class="flex items-center gap-3">
                    <!-- Theme Toggle Button -->
                    <button type="button" 
                            @click="toggleTheme()" 
                            class="h-9 px-2.5 border border-[#C6A15B]/40 bg-[#171513] text-[#C6A15B] hover:bg-[#C6A15B] hover:text-[#171513] transition flex items-center gap-1.5 text-xs font-mono font-semibold cursor-pointer"
                            title="Toggle Light / Dark Mode">
                        <span x-show="darkMode">☀️ Light</span>
                        <span x-show="!darkMode">🌙 Dark</span>
                    </button>

                    @guest
                        <a href="{{ route('public.contact') }}" class="btn-outline-gold !h-9 !text-xs !text-[#F8F5EF] !border-[#C6A15B]/60 hover:!text-[#171513] hover:!border-[#C6A15B] hover:!bg-[#C6A15B] hidden md:inline-flex transition-all duration-300">
                            Request Quote
                        </a>
                        <a href="{{ route('login') }}" class="btn-outline-gold !h-9 !text-xs !text-[#F8F5EF] !border-[#C6A15B]/60 hover:!text-[#171513] hover:!border-[#C6A15B] hover:!bg-[#C6A15B] hidden sm:inline-flex transition-all duration-300">
                            Sign In
                        </a>
                        <a href="{{ route('register') }}" class="btn-gold !h-9 !text-xs shadow-[0_0_20px_rgba(198,161,91,0.4)] hover:shadow-[0_0_30px_rgba(198,161,91,0.5)] transition-all duration-300">
                            List Your Hall &rarr;
                        </a>
                    @else
                        @if(auth()->user()->isSuperAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="btn-gold !h-9 !text-xs shadow-[0_0_15px_$1198, 161, 91,0.3)]">
                                Super Admin &rarr;
                            </a>
                        @elseif(auth()->user()->isTenantOwner() || auth()->user()->isFrontDesk() || auth()->user()->isChef())
                            <a href="{{ route('dashboard') }}" class="btn-gold !h-9 !text-xs shadow-[0_0_15px_$1198, 161, 91,0.3)]">
                                Vendor Console &rarr;
                            </a>
                        @else
                            <a href="{{ route('customer.dashboard') }}" class="btn-gold !h-9 !text-xs shadow-[0_0_15px_$1198, 161, 91,0.3)]">
                                My Bookings &rarr;
                            </a>
                        @endif

                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" title="Sign Out" class="h-9 px-3 border border-white/20 bg-[#171513] text-stone-300 hover:text-[#171513] hover:border-[#C6A15B]/60 text-xs font-semibold transition">
                                Exit
                            </button>
                        </form>
                    @endguest

                    <!-- Mobile Menu Hamburger -->
                    <button type="button" @click="mobileNav = !mobileNav" class="lg:hidden w-10 h-10 border border-[#C6A15B]/40 bg-[#171513] flex items-center justify-center text-white" aria-label="Toggle Navigation">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16m-7 6h7"/>
                        </svg>
                    </button>
                </div>

            </div>
        </div>

        <!-- Mobile Drawer (Luxury Obsidian Sheet) -->
        <div x-show="mobileNav" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="lg:hidden bg-[#171513] border-b border-[#C6A15B]/30 px-6 py-6 space-y-4 shadow-2xl text-white"
             style="display: none;"
             @click.outside="mobileNav = false">
            <div class="text-[10px] uppercase font-bold tracking-[0.2em] text-[#C6A15B] font-mono">Navigation</div>
            <div class="grid grid-cols-2 gap-3 text-sm font-semibold text-stone-300 font-mono">
                <a href="{{ route('home') }}" class="py-1 hover:text-[#C6A15B]">Home</a>
                <a href="{{ route('public.venues') }}" class="py-1 hover:text-[#C6A15B]">Explore Venues</a>
                <a href="{{ route('public.packages') }}" class="py-1 hover:text-[#C6A15B]">Packages &amp; Menus</a>
                <a href="{{ route('public.gallery') }}" class="py-1 hover:text-[#C6A15B]">Photo &amp; Video</a>
                <a href="{{ route('public.services') }}" class="py-1 hover:text-[#C6A15B]">Bespoke Services</a>
                <a href="{{ route('public.availability') }}" class="py-1 hover:text-[#C6A15B]">Availability</a>
                <a href="{{ route('public.about') }}" class="py-1 hover:text-[#C6A15B]">About VenueCloud</a>
                <a href="{{ route('public.contact') }}" class="py-1 hover:text-[#C6A15B]">Contact &amp; Office</a>
            </div>

            <div class="pt-4 border-t border-white/10 flex flex-col gap-2">
                @guest
                            <a href="{{ route('login') }}" class="btn-outline-gold !w-full !justify-center !text-[#F8F5EF] !border-[#C6A15B]/60 hover:!text-[#171513] hover:!border-[#C6A15B] hover:!bg-[#C6A15B] transition-all duration-300">Sign In (Vendor / Customer)</a>
                            <a href="{{ route('register') }}" class="btn-gold !w-full !justify-center shadow-[0_0_20px_rgba(198,161,91,0.4)] hover:shadow-[0_0_30px_rgba(198,161,91,0.5)] transition-all duration-300">Register Wedding Hall</a>
                @else
                    <a href="{{ route('dashboard') }}" class="btn-gold !w-full !justify-center">Go to Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-center py-2 text-xs font-semibold text-[#171513]">Sign Out</button>
                    </form>
                @endguest
            </div>
        </div>
    </header>

    <!-- Page Content Slot -->
    <main class="flex-1">
        {{ $slot }}
    </main>

    <!-- Luxury Obsidian Dark Footer -->
    <footer class="bg-[#171513] text-[#F8F5EF] pt-20 pb-12 border-t border-[#C6A15B]/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16">
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-12 pb-12 border-b border-white/10">
                <!-- Brand Info -->
                <div class="lg:col-span-2 space-y-5">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 border border-[#C6A15B] flex items-center justify-center bg-stone-900 text-[#C6A15B]">
                            <span class="font-cinzel text-base font-bold">V</span>
                        </div>
                        <span class="font-cinzel text-2xl font-bold tracking-[0.16em] text-white">VENUECLOUD</span>
                    </div>
                    <p class="text-sm text-stone-400 font-light leading-relaxed max-w-sm">
                        Pakistan's premier commercial marketplace and management SaaS for luxury wedding banquets, royal marquees, and convention centres. Dedicated to authentic hospitality, zero double-booking reliability, and transparent pricing.
                    </p>
                    <div class="pt-2 text-xs text-stone-400 space-y-1">
                        <p><strong class="text-white">Headquarters:</strong> Plot 42, Main Boulevard, Gulberg III, Lahore, Pakistan</p>
                        <p><strong class="text-white">Direct Phone:</strong> +92 42 35789000 • +92 300 8472910</p>
                        <p><strong class="text-white">Inquiries:</strong> inquiries@venuecloud.pk</p>
                    </div>
                </div>

                <!-- Explore Links -->
                <div class="space-y-4">
                    <h4 class="font-cinzel text-xs font-bold uppercase tracking-[0.15em] text-[#C6A15B]">Marketplace</h4>
                    <ul class="space-y-2.5 text-xs text-stone-200 font-normal">
                        <li><a href="{{ route('public.venues') }}" class="hover:text-white transition">Explore Venues</a></li>
                        <li><a href="{{ route('public.packages') }}" class="hover:text-white transition">Wedding Packages</a></li>
                        <li><a href="{{ route('public.packages') }}" class="hover:text-white transition">Catering & Bawarchi</a></li>
                        <li><a href="{{ route('public.gallery') }}" class="hover:text-white transition">Photo & Video Gallery</a></li>
                        <li><a href="{{ route('public.availability') }}" class="hover:text-white transition">Live Shift Availability</a></li>
                        <li><a href="{{ route('public.services') }}" class="hover:text-white transition">Event Services</a></li>
                    </ul>
                </div>

                <!-- Venue Owners / SaaS -->
                <div class="space-y-4">
                    <h4 class="font-cinzel text-xs font-bold uppercase tracking-[0.15em] text-[#C6A15B]">Venue Owners</h4>
                    <ul class="space-y-2.5 text-xs text-stone-200 font-normal">
                        <li><a href="{{ route('register') }}" class="hover:text-white transition">List Your Venue</a></li>
                        <li><a href="{{ route('login') }}" class="hover:text-white transition">Vendor Portal Login</a></li>
                        <li><a href="{{ route('dashboard') }}" class="hover:text-white transition">Shift Matrix & Calendar</a></li>
                        <li><a href="{{ route('public.about') }}" class="hover:text-white transition">Why VenueCloud SaaS</a></li>
                        <li><a href="{{ route('public.faqs') }}" class="hover:text-white transition">Vendor FAQs</a></li>
                    </ul>
                </div>

                <!-- Legal & Compliance -->
                <div class="space-y-4">
                    <h4 class="font-cinzel text-xs font-bold uppercase tracking-[0.15em] text-[#C6A15B]">Trust & Policies</h4>
                    <ul class="space-y-2.5 text-xs text-stone-200 font-normal">
                        <li><a href="{{ route('public.policies.terms') }}" class="hover:text-white transition">Terms of Service</a></li>
                        <li><a href="{{ route('public.policies.privacy') }}" class="hover:text-white transition">Privacy Policy</a></li>
                        <li><a href="{{ route('public.policies.cancellation') }}" class="hover:text-white transition">Cancellation & Refund Policy</a></li>
                        <li><a href="{{ route('public.faqs') }}" class="hover:text-white transition">Booking Security FAQ</a></li>
                        <li><a href="{{ route('public.contact') }}" class="hover:text-white transition">Customer Support</a></li>
                    </ul>
                </div>

            </div>

            <!-- Bottom Disclaimer & Copyright -->
            <div class="flex flex-col md:flex-row items-center justify-between text-xs text-stone-500 gap-4">
                <span>&copy; {{ date('Y') }} VenueCloud Technologies (Pvt) Ltd. All rights reserved. Registered under SECP Pakistan.</span>
                <div class="flex items-center gap-6">
                    <span class="text-stone-400">Currency: <strong>Pakistani Rupee (PKR)</strong></span>
                    <span class="text-stone-400">Tax Compliance: <strong>PRA / SRB / FBR</strong></span>
                </div>
            </div>

        </div>
    </footer>

    <!-- Interactive Walk-in Booking Modal Component (Accessible across public site) -->
    @livewire('walk-in-booking-modal')

    @livewireScripts
</body>
</html>
