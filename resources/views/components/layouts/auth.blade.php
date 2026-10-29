<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#F8F5EF] dark:bg-[#171513] text-[#171513] dark:text-[#F8F5EF] antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'EventHub — Secure Sign In' }}</title>
    <meta name="description" content="Secure access to EventHub luxury banqueting and wedding management.">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/eventhub-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/eventhub-logo.png') }}">

    <!-- Google Fonts: Preconnect & Asynchronous Loading (High Performance & Zero Render-Blocking) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Playfair+Display:ital,wght@0,500;0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Playfair+Display:ital,wght@0,500;0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Playfair+Display:ital,wght@0,500;0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">
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

    <!-- Theme State Initializer (Default: Dark) -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('EventHub_theme');
            if (savedTheme === 'light') {
                document.documentElement.classList.remove('dark');
            } else {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>
<body class="min-h-full flex flex-col font-sans bg-[#F8F5EF] dark:bg-[#0E0D0C] text-[#171513] dark:text-[#F8F5EF] selection:bg-[#C6A15B] selection:text-[#171513] transition-colors duration-200"
      x-data="{ 
          darkMode: document.documentElement.classList.contains('dark'), 
          toggleTheme() { 
              this.darkMode = !this.darkMode; 
              if (this.darkMode) { 
                  document.documentElement.classList.add('dark'); 
                  localStorage.setItem('EventHub_theme', 'dark'); 
              } else { 
                  document.documentElement.classList.remove('dark'); 
                  localStorage.setItem('EventHub_theme', 'light'); 
              } 
          } 
      }">

    <!-- Minimal Auth Header: Back to Home + Brand Mark + Theme Toggle -->
    <header class="w-full py-3.5 px-4 sm:px-8 flex items-center justify-between border-b border-[#E7E5E4] dark:border-white/10 bg-white/70 dark:bg-[#171513]/70 backdrop-blur-md sticky top-0 z-50">
        <!-- Back Button -->
        <a href="{{ route('home') }}" 
           class="inline-flex items-center gap-2 text-xs font-sans uppercase tracking-wider text-stone-600 dark:text-stone-300 hover:text-[#8F7239] dark:hover:text-[#C6A15B] transition-colors group">
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-[#E7E5E4] dark:border-white/15 bg-stone-50 dark:bg-white/5 group-hover:border-[#C6A15B]/60 transition-colors">
                &larr;
            </span>
            <span class="font-bold">Back to Home</span>
        </a>

        <!-- Center Brand Logo -->
        <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
            <img src="{{ asset('images/eventhub-logo.png') }}" width="36" height="36" alt="EventHub Logo" class="h-9 w-9 object-contain rounded-lg border border-[#C6A15B]/50 bg-[#171513] shadow-sm">
            <span class="font-cinzel text-sm sm:text-base font-bold tracking-[0.16em] text-[#171513] dark:text-white group-hover:text-[#C6A15B] transition-colors">
                EventHub
            </span>
        </a>

        <!-- Dark / Light Theme Toggle -->
        <button type="button" 
                @click="toggleTheme()" 
                class="flex h-8 w-8 items-center justify-center rounded-lg border border-[#E7E5E4] dark:border-white/15 bg-stone-50 dark:bg-white/5 text-stone-600 dark:text-stone-300 hover:text-[#8F7239] dark:hover:text-[#C6A15B] transition-colors cursor-pointer"
                aria-label="Toggle color theme"
                title="Toggle Theme">
            <svg x-show="!darkMode" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
            <svg x-show="darkMode" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
        </button>
    </header>

    <!-- Main Content Slot (Perfect Vertical Centering Without Clipping) -->
    <main class="auth-page-content flex-1 flex flex-col justify-center py-6 sm:py-10 px-4 sm:px-6 min-h-[calc(100vh-4.5rem)]">
        <div class="w-full flex justify-center my-auto">
            {{ $slot }}
        </div>
    </main>

    @livewireScripts
</body>
</html>
