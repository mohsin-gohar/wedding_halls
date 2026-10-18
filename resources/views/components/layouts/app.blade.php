<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#F8F5EF] text-[#171513] antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'EventHub — Vendor SaaS Management Suite' }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/eventhub-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/eventhub-logo.png') }}">

    <!-- Google Fonts: Cinzel, Playfair Display, Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles & Livewire -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <!-- Theme State Initializer (Default: Dark Luxury Palace) -->
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
<body class="min-h-full flex flex-col font-sans bg-[#F8F5EF] dark:bg-[#171513] text-[#171513] dark:text-[#F8F5EF] selection:bg-[#C6A15B] selection:text-[#171513] overflow-x-hidden transition-colors duration-200" 
      x-data="{ 
          sidebarOpen: false, 
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

    <!-- Top Command Strip -->
    <div class="bg-[#171513] text-white text-[11px] font-mono py-1.5 px-4 border-b border-[#C6A15B]/30 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="inline-block w-2 h-2 rounded-full bg-[#C6A15B] animate-pulse"></span>
            <span class="text-stone-300">
                TENANT SUITE: <strong class="text-white">{{ auth()->user()->tenant->name ?? 'EventHub Host' }}</strong>
            </span>
            <span class="text-stone-600 hidden sm:inline">|</span>
            <span class="text-stone-400 hidden sm:inline">LOCK: ACID &amp; DISTRIBUTED MUTEX</span>
        </div>
        <div class="flex items-center gap-4 text-stone-400 text-[11px]">
            <button type="button" @click="toggleTheme()" class="hover:text-[#C6A15B] transition flex items-center gap-1.5 font-mono cursor-pointer" title="Toggle Light / Dark Mode">
                <span x-show="darkMode">☀️ Light Mode</span>
                <span x-show="!darkMode">🌙 Dark Mode</span>
            </button>
            <span>•</span>
            <span>CURRENCY: <strong>PKR</strong></span>
            <span>•</span>
            <a href="{{ route('home') }}" target="_blank" class="text-[#C6A15B] hover:underline flex items-center gap-1">
                <span>Public Marketplace ↗</span>
            </a>
        </div>
    </div>

    <!-- Subscription Status Banners -->
    @if(auth()->user()->tenant && auth()->user()->tenant->subscription_status === 'EXPIRED')
        <div class="bg-[#171513] border-b border-[#C6A15B]/60 text-[#F8F5EF] px-4 py-3 text-xs text-center font-medium shadow-md">
            🔒 <strong>Subscription Notice:</strong> Your EventHub SaaS subscription expired on {{ auth()->user()->tenant->subscription_expires_at?->format('d M Y') ?? 'recently' }}. All your halls, bookings, client records, and finances are 100% safe and preserved. Please submit your renewal payment to unlock operations.
        </div>
    @elseif(auth()->user()->tenant && auth()->user()->tenant->subscription_status === 'GRACE_PERIOD')
        <div class="bg-[#24211E] border-b border-[#A98746]/50 text-[#F8F5EF] px-4 py-2.5 text-xs text-center font-medium">
            ⏳ <strong>Grace Period Active:</strong> Your subscription has ended. You have a 3-day grace period to submit renewal before operations lock.
        </div>
    @elseif(auth()->user()->tenant && auth()->user()->tenant->verification_status === 'PENDING')
        <div class="bg-[#C6A15B]/20 border-b border-[#C6A15B]/60 text-[#8F7239] px-4 py-2.5 text-xs text-center font-medium">
            ⚠️ <strong>Verification Notice:</strong> Your venue business credentials are under review by EventHub administrators. You can configure halls, photos, and menus now; public listings will become visible upon verification.
        </div>
    @endif

    <div class="flex-1 flex">
        
        <!-- Desktop Luxury Obsidian Sidebar (Strict Zero-Pill) -->
        <aside class="hidden lg:flex lg:flex-col lg:w-64 bg-gradient-to-b from-[#F8F5EF] to-[#E7E5E4] dark:from-[#15130F] dark:to-[#0F0D0A] text-[#171513] dark:text-white border-r border-[#E7E5E4] dark:border-[#C6A15B]/25 justify-between shrink-0 shadow-[8px_0_40px_-12px_rgba(0,0,0,0.85)]">
            <div>
                <!-- Brand Badge -->
                    <div class="p-6 border-b border-[#E7E5E4] dark:border-[#C6A15B]/20 flex items-center justify-between bg-gradient-to-r from-transparent to-[#171513]/5 dark:to-[#C6A15B]/5">
                        <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                            <img src="{{ asset('images/eventhub-logo.png') }}" alt="EventHub Logo" class="h-10 w-auto object-contain rounded border border-[#C6A15B] bg-[#171513] shadow-[0_0_18px_rgba(198,161,91,0.25)]">
                            <div>
                                <span class="font-cinzel text-lg font-bold tracking-[0.15em] text-[#171513] dark:text-white block group-hover:text-[#C6A15B] transition">EventHub</span>
                                @if(auth()->user()->isSuperAdmin())
                                    <span class="text-[9px] uppercase tracking-[0.18em] text-[#C6A15B] font-bold block -mt-1 font-mono">Platform Super Admin</span>
                                @else
                                    <span class="text-[9px] uppercase tracking-[0.18em] text-[#C6A15B] font-medium block -mt-1 font-mono">Rent Admin Suite</span>
                                @endif
                            </div>
                        </a>
                    </div>

                <!-- Navigation Menu -->
                <nav class="p-4 space-y-1 text-xs font-semibold uppercase tracking-wider">
                    @if(auth()->user()->isSuperAdmin())
                        <div class="space-y-4">
                            <!-- Section: Overview & Operations -->
                            <div>
                                <div class="text-[10px] text-[#C6A15B] font-bold px-3 py-1 uppercase tracking-[0.2em] font-mono">Platform Governance</div>
                                <div class="mt-1 space-y-1">
                                    <a href="{{ route('admin.dashboard', ['tab' => 'vendors']) }}" 
                                       @click="$dispatch('set-admin-tab', { tab: 'vendors' })"
                                       class="flex items-center justify-between px-3 py-2 border text-xs transition {{ (request('tab') === 'vendors' || (!request('tab') && request()->routeIs('admin.dashboard'))) ? 'bg-[#C6A15B]/20 text-[#171513] dark:text-[#C6A15B] border-[#C6A15B]' : 'border-transparent text-stone-600 dark:text-stone-300 hover:bg-black/5 dark:hover:bg-white/5 hover:text-[#C6A15B]' }}">
                                        <div class="flex items-center gap-2.5">
                                            <span>🏢</span>
                                            <span>Vendors &amp; Subscriptions</span>
                                        </div>
                                        <span class="text-[10px] font-mono font-bold px-1.5 py-0.5 rounded bg-black/10 dark:bg-white/10 text-stone-600 dark:text-stone-300">
                                            {{ \App\Models\Tenant::count() }}
                                        </span>
                                    </a>

                                    <a href="{{ route('admin.dashboard', ['tab' => 'halls']) }}" 
                                       @click="$dispatch('set-admin-tab', { tab: 'halls' })"
                                       class="flex items-center justify-between px-3 py-2 border text-xs transition {{ request('tab') === 'halls' ? 'bg-[#C6A15B]/20 text-[#171513] dark:text-[#C6A15B] border-[#C6A15B]' : 'border-transparent text-stone-600 dark:text-stone-300 hover:bg-black/5 dark:hover:bg-white/5 hover:text-[#C6A15B]' }}">
                                        <div class="flex items-center gap-2.5">
                                            <span>🏛️</span>
                                            <span>Halls Catalog</span>
                                        </div>
                                        <span class="text-[10px] font-mono font-bold px-1.5 py-0.5 rounded bg-black/10 dark:bg-white/10 text-stone-600 dark:text-stone-300">
                                            {{ \App\Models\Hall::count() }}
                                        </span>
                                    </a>

                                    <a href="{{ route('admin.dashboard', ['tab' => 'bookings']) }}" 
                                       @click="$dispatch('set-admin-tab', { tab: 'bookings' })"
                                       class="flex items-center justify-between px-3 py-2 border text-xs transition {{ request('tab') === 'bookings' ? 'bg-[#C6A15B]/20 text-[#171513] dark:text-[#C6A15B] border-[#C6A15B]' : 'border-transparent text-stone-600 dark:text-stone-300 hover:bg-black/5 dark:hover:bg-white/5 hover:text-[#C6A15B]' }}">
                                        <div class="flex items-center gap-2.5">
                                            <span>📅</span>
                                            <span>All Bookings</span>
                                        </div>
                                        <span class="text-[10px] font-mono font-bold px-1.5 py-0.5 rounded bg-black/10 dark:bg-white/10 text-stone-600 dark:text-stone-300">
                                            {{ \App\Models\Booking::count() }}
                                        </span>
                                    </a>

                                    <a href="{{ route('admin.dashboard', ['tab' => 'users']) }}" 
                                       @click="$dispatch('set-admin-tab', { tab: 'users' })"
                                       class="flex items-center justify-between px-3 py-2 border text-xs transition {{ request('tab') === 'users' ? 'bg-[#C6A15B]/20 text-[#171513] dark:text-[#C6A15B] border-[#C6A15B]' : 'border-transparent text-stone-600 dark:text-stone-300 hover:bg-black/5 dark:hover:bg-white/5 hover:text-[#C6A15B]' }}">
                                        <div class="flex items-center gap-2.5">
                                            <span>👥</span>
                                            <span>Users &amp; Staff</span>
                                        </div>
                                        <span class="text-[10px] font-mono font-bold px-1.5 py-0.5 rounded bg-black/10 dark:bg-white/10 text-stone-600 dark:text-stone-300">
                                            {{ \App\Models\User::count() }}
                                        </span>
                                    </a>
                                </div>
                            </div>

                            <!-- Section: Commercial SaaS & Pricing -->
                            <div>
                                <div class="text-[10px] text-[#C6A15B] font-bold px-3 py-1 uppercase tracking-[0.2em] font-mono">Commercial &amp; SaaS</div>
                                <div class="mt-1 space-y-1">
                                    <a href="{{ route('admin.dashboard', ['tab' => 'plans']) }}" 
                                       @click="$dispatch('set-admin-tab', { tab: 'plans' })"
                                       class="flex items-center justify-between px-3 py-2 border text-xs transition {{ request('tab') === 'plans' ? 'bg-[#C6A15B]/20 text-[#171513] dark:text-[#C6A15B] border-[#C6A15B]' : 'border-transparent text-stone-600 dark:text-stone-300 hover:bg-black/5 dark:hover:bg-white/5 hover:text-[#C6A15B]' }}">
                                        <div class="flex items-center gap-2.5">
                                            <span>💎</span>
                                            <span>SaaS Plans &amp; Pricing</span>
                                        </div>
                                        <span class="text-[10px] font-mono font-bold px-1.5 py-0.5 rounded bg-black/10 dark:bg-white/10 text-stone-600 dark:text-stone-300">
                                            {{ \App\Models\SubscriptionPlan::count() }}
                                        </span>
                                    </a>

                                    <a href="{{ route('admin.dashboard', ['tab' => 'payments']) }}" 
                                       @click="$dispatch('set-admin-tab', { tab: 'payments' })"
                                       class="flex items-center justify-between px-3 py-2 border text-xs transition {{ request('tab') === 'payments' ? 'bg-[#C6A15B]/20 text-[#171513] dark:text-[#C6A15B] border-[#C6A15B]' : 'border-transparent text-stone-600 dark:text-stone-300 hover:bg-black/5 dark:hover:bg-white/5 hover:text-[#C6A15B]' }}">
                                        <div class="flex items-center gap-2.5">
                                            <span>💳</span>
                                            <span>Payment Approvals</span>
                                        </div>
                                        @php $pendingSubs = \App\Models\SubscriptionPayment::where('status', 'PENDING')->count(); @endphp
                                        @if($pendingSubs > 0)
                                            <span class="text-[10px] font-mono font-bold px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-500">
                                                {{ $pendingSubs }} new
                                            </span>
                                        @else
                                            <span class="text-[10px] font-mono text-stone-500">0</span>
                                        @endif
                                    </a>
                                </div>
                            </div>

                            <!-- Section: Operations & Security -->
                            <div>
                                <div class="text-[10px] text-[#C6A15B] font-bold px-3 py-1 uppercase tracking-[0.2em] font-mono">Operations &amp; Security</div>
                                <div class="mt-1 space-y-1">
                                    <a href="{{ route('admin.dashboard', ['tab' => 'tasks']) }}" 
                                       @click="$dispatch('set-admin-tab', { tab: 'tasks' })"
                                       class="flex items-center justify-between px-3 py-2 border text-xs transition {{ request('tab') === 'tasks' ? 'bg-[#C6A15B]/20 text-[#171513] dark:text-[#C6A15B] border-[#C6A15B]' : 'border-transparent text-stone-600 dark:text-stone-300 hover:bg-black/5 dark:hover:bg-white/5 hover:text-[#C6A15B]' }}">
                                        <div class="flex items-center gap-2.5">
                                            <span>📋</span>
                                            <span>Platform Tasks</span>
                                        </div>
                                        @php $openTasks = \App\Models\PlatformTask::where('is_completed', false)->count(); @endphp
                                        @if($openTasks > 0)
                                            <span class="text-[10px] font-mono font-bold px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-500">
                                                {{ $openTasks }} open
                                            </span>
                                        @endif
                                    </a>

                                    <a href="{{ route('admin.dashboard', ['tab' => 'traffic']) }}" 
                                       @click="$dispatch('set-admin-tab', { tab: 'traffic' })"
                                       class="flex items-center justify-between px-3 py-2 border text-xs transition {{ request('tab') === 'traffic' ? 'bg-[#C6A15B]/20 text-[#171513] dark:text-[#C6A15B] border-[#C6A15B]' : 'border-transparent text-stone-600 dark:text-stone-300 hover:bg-black/5 dark:hover:bg-white/5 hover:text-[#C6A15B]' }}">
                                        <div class="flex items-center gap-2.5">
                                            <span>🚦</span>
                                            <span>Traffic &amp; Controls</span>
                                        </div>
                                        <span class="inline-flex items-center gap-1 text-[10px] font-mono text-emerald-500 font-bold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Live
                                        </span>
                                    </a>

                                    <a href="{{ route('admin.dashboard', ['tab' => 'analytics']) }}" 
                                       @click="$dispatch('set-admin-tab', { tab: 'analytics' })"
                                       class="flex items-center justify-between px-3 py-2 border text-xs transition {{ request('tab') === 'analytics' ? 'bg-[#C6A15B]/20 text-[#171513] dark:text-[#C6A15B] border-[#C6A15B]' : 'border-transparent text-stone-600 dark:text-stone-300 hover:bg-black/5 dark:hover:bg-white/5 hover:text-[#C6A15B]' }}">
                                        <div class="flex items-center gap-2.5">
                                            <span>📊</span>
                                            <span>Visitor Analytics</span>
                                        </div>
                                    </a>

                                    <a href="{{ route('admin.dashboard', ['tab' => 'audit']) }}" 
                                       @click="$dispatch('set-admin-tab', { tab: 'audit' })"
                                       class="flex items-center justify-between px-3 py-2 border text-xs transition {{ request('tab') === 'audit' ? 'bg-[#C6A15B]/20 text-[#171513] dark:text-[#C6A15B] border-[#C6A15B]' : 'border-transparent text-stone-600 dark:text-stone-300 hover:bg-black/5 dark:hover:bg-white/5 hover:text-[#C6A15B]' }}">
                                        <div class="flex items-center gap-2.5">
                                            <span>🛡️</span>
                                            <span>Security Audit Log</span>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif

                    @unless(auth()->user()->isSuperAdmin())
                    <div class="text-[10px] text-[#C6A15B]/80 font-bold px-3 py-2 uppercase tracking-[0.2em] font-mono mt-2">Banquet Operations</div>
                    
                    @if(auth()->user()->isTenantOwner() || auth()->user()->isManager() || auth()->user()->isAccountant())
                    <a href="{{ route('dashboard') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 border transition-all duration-300 {{ request()->routeIs('dashboard') ? 'bg-[#C6A15B]/15 text-[#171513] border-[#C6A15B]/50 shadow-[0_0_20px_-8px_rgba(198,161,91,0.45)] dark:bg-[#C6A15B]/25 dark:text-white dark:border-[#C6A15B]/50' : 'text-stone-600 dark:text-stone-300 border-transparent hover:bg-black/5 dark:hover:bg-white/5 hover:text-[#171513] dark:hover:text-[#C6A15B]' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span>Analytics &amp; Revenue</span>
                    </a>
                    @endif

                    @if(auth()->user()->isTenantOwner() || auth()->user()->isManager() || auth()->user()->isFrontDesk() || auth()->user()->role === 'ROLE_FLOOR_STAFF')
                    <a href="{{ route('calendar') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 border transition-all duration-300 {{ request()->routeIs('calendar') ? 'bg-[#C6A15B]/15 text-[#171513] border-[#C6A15B]/50 shadow-[0_0_20px_-8px_rgba(198,161,91,0.45)] dark:bg-[#C6A15B]/25 dark:text-white dark:border-[#C6A15B]/50' : 'text-stone-600 dark:text-stone-300 border-transparent hover:bg-black/5 dark:hover:bg-white/5 hover:text-[#171513] dark:hover:text-[#C6A15B]' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>Shift Matrix &amp; Calendar</span>
                    </a>
                    @endif

                    @if(auth()->user()->isTenantOwner() || auth()->user()->isManager())
                    <a href="{{ route('hall.media') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 border transition-all duration-300 {{ request()->routeIs('hall.media') ? 'bg-[#C6A15B]/15 text-[#171513] border-[#C6A15B]/50 shadow-[0_0_20px_-8px_rgba(198,161,91,0.45)] dark:bg-[#C6A15B]/25 dark:text-white dark:border-[#C6A15B]/50' : 'text-stone-600 dark:text-stone-300 border-transparent hover:bg-black/5 dark:hover:bg-white/5 hover:text-[#171513] dark:hover:text-[#C6A15B]' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>Hall Website &amp; Identity</span>
                    </a>
                    @endif

                    <div class="text-[10px] text-[#C6A15B]/80 font-bold px-3 pt-4 pb-2 uppercase tracking-[0.2em] font-mono">Business ERP &amp; Workforce</div>

                    @if(auth()->user()->isTenantOwner() || auth()->user()->isManager() || auth()->user()->isAccountant())
                    <a href="{{ route('expenses') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 border transition-all duration-300 {{ request()->routeIs('expenses') ? 'bg-[#C6A15B]/15 text-[#171513] border-[#C6A15B]/50 shadow-[0_0_20px_-8px_rgba(198,161,91,0.45)] dark:bg-[#C6A15B]/25 dark:text-white dark:border-[#C6A15B]/50' : 'text-stone-600 dark:text-stone-300 border-transparent hover:bg-black/5 dark:hover:bg-white/5 hover:text-[#171513] dark:hover:text-[#C6A15B]' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Expenses &amp; Net P&amp;L</span>
                    </a>
                    @endif

                    @if(auth()->user()->isTenantOwner() || auth()->user()->isManager())
                    <a href="{{ route('staff') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 border transition-all duration-300 {{ request()->routeIs('staff') ? 'bg-[#C6A15B]/15 text-[#171513] border-[#C6A15B]/50 shadow-[0_0_20px_-8px_rgba(198,161,91,0.45)] dark:bg-[#C6A15B]/25 dark:text-white dark:border-[#C6A15B]/50' : 'text-stone-600 dark:text-stone-300 border-transparent hover:bg-black/5 dark:hover:bg-white/5 hover:text-[#171513] dark:hover:text-[#C6A15B]' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span>Team &amp; Staff Roles</span>
                    </a>
                    @endif

                    @if(auth()->user()->isTenantOwner() || auth()->user()->isManager() || auth()->user()->isAccountant())
                    <a href="{{ route('staff.attendance') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 border transition-all duration-300 {{ request()->routeIs('staff.attendance') ? 'bg-[#C6A15B]/15 text-[#171513] border-[#C6A15B]/50 shadow-[0_0_20px_-8px_rgba(198,161,91,0.45)] dark:bg-[#C6A15B]/25 dark:text-white dark:border-[#C6A15B]/50' : 'text-stone-600 dark:text-stone-300 border-transparent hover:bg-black/5 dark:hover:bg-white/5 hover:text-[#171513] dark:hover:text-[#C6A15B]' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        <span>Staff Attendance Roster</span>
                    </a>

                    <a href="{{ route('staff.payroll') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 border transition {{ request()->routeIs('staff.payroll') ? 'bg-[#C6A15B]/15 text-[#171513] border-[#C6A15B]/40 dark:bg-[#C6A15B]/25 dark:text-white dark:border-[#C6A15B]/40' : 'text-stone-600 dark:text-stone-300 border-transparent hover:bg-black/5 dark:hover:bg-white/5 hover:text-[#171513] dark:hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span>Payroll &amp; Salary Slips</span>
                    </a>
                    @endif

                    @if(auth()->user()->isTenantOwner() || auth()->user()->isManager() || auth()->user()->isChef() || auth()->user()->role === 'ROLE_FLOOR_STAFF')
                    <div class="text-[10px] text-[#C6A15B]/80 font-bold px-3 pt-4 pb-2 uppercase tracking-[0.2em] font-mono">Kitchen &amp; Logistics</div>
                    @endif

                    @if(auth()->user()->isTenantOwner() || auth()->user()->isManager() || auth()->user()->isChef())
                    <a href="{{ route('inventory') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 border transition-all duration-300 {{ request()->routeIs('inventory') ? 'bg-[#C6A15B]/15 text-[#171513] border-[#C6A15B]/50 shadow-[0_0_20px_-8px_rgba(198,161,91,0.45)] dark:bg-[#C6A15B]/25 dark:text-white dark:border-[#C6A15B]/50' : 'text-stone-600 dark:text-stone-300 border-transparent hover:bg-black/5 dark:hover:bg-white/5 hover:text-[#171513] dark:hover:text-[#C6A15B]' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <span>Inventory &amp; Supplies</span>
                    </a>
                    @endif

                    @if(auth()->user()->isTenantOwner() || auth()->user()->isManager() || auth()->user()->isChef())
                    <a href="{{ route('packages.manager') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 border transition {{ request()->routeIs('packages.manager') ? 'bg-[#C6A15B]/15 text-[#171513] border-[#C6A15B]/40 dark:bg-[#C6A15B]/25 dark:text-white dark:border-[#C6A15B]/40' : 'text-stone-600 dark:text-stone-300 border-transparent hover:bg-black/5 dark:hover:bg-white/5 hover:text-[#171513] dark:hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        <span>Catering &amp; Menus</span>
                    </a>
                    @endif

                    @if(auth()->user()->isTenantOwner() || auth()->user()->isManager() || auth()->user()->isChef() || auth()->user()->role === 'ROLE_FLOOR_STAFF')
                    <a href="{{ route('kitchen.board') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 border transition {{ request()->routeIs('kitchen.board') ? 'bg-[#C6A15B]/15 text-[#171513] border-[#C6A15B]/40 dark:bg-[#C6A15B]/25 dark:text-white dark:border-[#C6A15B]/40' : 'text-stone-600 dark:text-stone-300 border-transparent hover:bg-black/5 dark:hover:bg-white/5 hover:text-[#171513] dark:hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        <span>Chef Kitchen KOTs</span>
                    </a>
                    @endif

                    @if(auth()->user()->isTenantOwner() || auth()->user()->isManager())
                    <a href="{{ route('utility.fuel') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 border transition {{ request()->routeIs('utility.fuel') ? 'bg-[#C6A15B]/15 text-[#171513] border-[#C6A15B]/40 dark:bg-[#C6A15B]/25 dark:text-white dark:border-[#C6A15B]/40' : 'text-stone-600 dark:text-stone-300 border-transparent hover:bg-black/5 dark:hover:bg-white/5 hover:text-[#171513] dark:hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>Generator Fuel Tracker</span>
                    </a>
                    @endif

                    @if(auth()->user()->isTenantOwner() || auth()->user()->isManager() || auth()->user()->role === 'ROLE_FLOOR_STAFF')
                    <a href="{{ route('seating.planner') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 border transition {{ request()->routeIs('seating.planner') ? 'bg-[#C6A15B]/15 text-[#171513] border-[#C6A15B]/40 dark:bg-[#C6A15B]/25 dark:text-white dark:border-[#C6A15B]/40' : 'text-stone-600 dark:text-stone-300 border-transparent hover:bg-black/5 dark:hover:bg-white/5 hover:text-[#171513] dark:hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                        <span>Floor Plan Designer</span>
                    </a>
                    @endif

                    @if(auth()->user()->isTenantOwner() || auth()->user()->isManager() || auth()->user()->isFrontDesk())
                    <a href="{{ route('whatsapp.rsvp') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 border transition {{ request()->routeIs('whatsapp.rsvp') ? 'bg-[#C6A15B]/15 text-[#171513] border-[#C6A15B]/40 dark:bg-[#C6A15B]/25 dark:text-white dark:border-[#C6A15B]/40' : 'text-stone-600 dark:text-stone-300 border-transparent hover:bg-black/5 dark:hover:bg-white/5 hover:text-[#171513] dark:hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        <span>WhatsApp RSVP Bot</span>
                    </a>
                    @endif

                    @php
                        $userHall = \App\Models\Hall::where('tenant_id', auth()->user()->tenant_id)->first();
                    @endphp
                    @if($userHall && (auth()->user()->isTenantOwner() || auth()->user()->isManager()))
                        <div class="pt-6 px-1">
                            <a href="{{ route('hall.showcase', $userHall->slug) }}" target="_blank"
                               class="w-full py-2 px-3 bg-[#171513] border border-[#C6A15B] text-[#C6A15B] text-[11px] font-mono uppercase tracking-wider hover:bg-[#C6A15B] hover:text-[#171513] transition flex items-center justify-center gap-1.5 font-bold shadow">
                                <span>🌐 Live Hall Page ↗</span>
                            </a>
                        </div>
                    @endif
                </nav>
                    @endunless
            </div>

            <!-- Sidebar Footer User Account -->
            <div class="p-4 border-t border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#171513]">
                <div class="flex items-center justify-between text-xs">
                    <div>
                        <strong class="text-[#171513] dark:text-white block font-semibold">{{ auth()->user()->name }}</strong>
                        <span class="text-[10px] text-[#C6A15B] uppercase font-mono">{{ auth()->user()->role }}</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-[#171513] dark:text-[#C6A15B] hover:text-[#171513] dark:hover:text-[#F8F5EF] text-xs font-semibold underline">
                            Sign Out
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            
            <!-- Top App Bar -->
            <header class="bg-white dark:bg-[#171513] border-b border-[#E7E5E4] dark:border-white/10 px-4 sm:px-8 py-3.5 flex items-center justify-between transition-colors duration-200">
                <div class="flex items-center gap-3">
                    <!-- Mobile Hamburger -->
                    <button type="button" @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 text-stone-600 dark:text-stone-300 border border-[#E7E5E4] dark:border-white/10">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
                    </button>

                    <!-- Context Heading -->
                    <div class="flex items-center gap-2 text-xs font-semibold text-stone-500 dark:text-stone-400 uppercase tracking-wider">
                        <span>Management</span>
                        <span>/</span>
                        <span class="text-[#171513] dark:text-[#C6A15B] font-bold">
                            @if(request()->routeIs('dashboard')) Executive Analytics &amp; Revenue
                            @elseif(request()->routeIs('calendar')) Shift Concurrency Matrix
                            @elseif(request()->routeIs('hall.media')) Hall Media &amp; Information
                            @elseif(request()->routeIs('kitchen.board')) Chef KOT Orders
                            @elseif(request()->routeIs('packages.manager')) Catering Packages &amp; Menus
                            @elseif(request()->routeIs('utility.fuel')) Fuel Run-Hours Tracker
                            @elseif(request()->routeIs('seating.planner')) Banquet Seating Designer
                            @elseif(request()->routeIs('whatsapp.rsvp')) WhatsApp RSVP Automation
                            @else Vendor Console
                            @endif
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Theme Toggle Button -->
                    <button type="button" 
                            @click="toggleTheme()" 
                            class="h-9 px-3 border border-[#E7E5E4] dark:border-white/20 bg-[#F8F5EF] dark:bg-[#171513] text-[#171513] dark:text-[#C6A15B] hover:border-[#C6A15B] transition flex items-center gap-1.5 text-xs font-mono font-semibold cursor-pointer"
                            title="Toggle Light / Dark Mode">
                        <span x-show="darkMode" class="flex items-center gap-1.5">
                            <span>☀️</span>
                            <span class="hidden sm:inline">Light</span>
                        </span>
                        <span x-show="!darkMode" class="flex items-center gap-1.5">
                            <span>🌙</span>
                            <span class="hidden sm:inline">Dark</span>
                        </span>
                    </button>

                    <!-- ERP Quick Action Dropdown -->
                    @if(auth()->user()->isTenantOwner() || auth()->user()->isManager() || auth()->user()->isFrontDesk() || auth()->user()->isAccountant())
                        <div class="relative" x-data="{ quickMenuOpen: false }">
                            <button type="button" 
                                    @click="quickMenuOpen = !quickMenuOpen" 
                                    class="h-9 px-3 bg-[#F8F5EF] dark:bg-[#1f1c18] border border-[#E7E5E4] dark:border-[#C6A15B]/50 hover:border-[#C6A15B] text-[#171513] dark:text-[#C6A15B] text-xs font-mono font-bold uppercase tracking-wider transition flex items-center gap-1.5 cursor-pointer">
                                <span>⚡</span>
                                <span class="hidden sm:inline">ERP Quick Actions</span>
                                <svg class="w-3.5 h-3.5 transition-transform" :class="quickMenuOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>

                            <div x-show="quickMenuOpen" 
                                 @click.outside="quickMenuOpen = false" 
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 class="absolute right-0 mt-2 w-56 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/50 shadow-2xl py-2 z-50 text-xs font-sans"
                                 style="display: none;">
                                <div class="px-3 py-1.5 text-[9px] uppercase tracking-widest font-mono text-[#8F7239] dark:text-[#C6A15B] font-bold border-b border-[#E7E5E4] dark:border-white/10">
                                    Quick ERP Commands
                                </div>
                                <button type="button" 
                                        onclick="Livewire.dispatch('openWalkInModal')" 
                                        @click="quickMenuOpen = false"
                                        class="w-full text-left px-3 py-2 text-stone-700 dark:text-stone-200 hover:bg-[#C6A15B]/15 hover:text-[#C6A15B] flex items-center gap-2">
                                    <span>📅</span>
                                    <span>New Event Reservation</span>
                                </button>
                                <a href="{{ route('dashboard') }}" 
                                   @click="quickMenuOpen = false"
                                   class="w-full text-left px-3 py-2 text-stone-700 dark:text-stone-200 hover:bg-[#C6A15B]/15 hover:text-[#C6A15B] flex items-center gap-2">
                                    <span>⛽</span>
                                    <span>Log Daily Kharcha / Fuel</span>
                                </a>
                                <a href="{{ route('staff.attendance') }}" 
                                   @click="quickMenuOpen = false"
                                   class="w-full text-left px-3 py-2 text-stone-700 dark:text-stone-200 hover:bg-[#C6A15B]/15 hover:text-[#C6A15B] flex items-center gap-2">
                                    <span>🕒</span>
                                    <span>Staff Attendance Roster</span>
                                </a>
                                <a href="{{ route('staff.payroll') }}" 
                                   @click="quickMenuOpen = false"
                                   class="w-full text-left px-3 py-2 text-stone-700 dark:text-stone-200 hover:bg-[#C6A15B]/15 hover:text-[#C6A15B] flex items-center gap-2">
                                    <span>💰</span>
                                    <span>Generate Payroll Slip</span>
                                </a>
                                <a href="{{ route('inventory') }}" 
                                   @click="quickMenuOpen = false"
                                   class="w-full text-left px-3 py-2 text-stone-700 dark:text-stone-200 hover:bg-[#C6A15B]/15 hover:text-[#C6A15B] flex items-center gap-2">
                                    <span>📦</span>
                                    <span>Add Stock / Inventory</span>
                                </a>
                                <a href="{{ route('kitchen.board') }}" 
                                   @click="quickMenuOpen = false"
                                   class="w-full text-left px-3 py-2 text-stone-700 dark:text-stone-200 hover:bg-[#C6A15B]/15 hover:text-[#C6A15B] flex items-center gap-2">
                                    <span>🍳</span>
                                    <span>Live Kitchen KOTs</span>
                                </a>
                                <a href="{{ route('seating.planner') }}" 
                                   @click="quickMenuOpen = false"
                                   class="w-full text-left px-3 py-2 text-stone-700 dark:text-stone-200 hover:bg-[#C6A15B]/15 hover:text-[#C6A15B] flex items-center gap-2">
                                    <span>🪑</span>
                                    <span>Floor Seating Designer</span>
                                </a>
                            </div>
                        </div>
                    @endif

                    @if(auth()->user()->isTenantOwner() || auth()->user()->isManager() || auth()->user()->isFrontDesk())
                        <button onclick="Livewire.dispatch('openWalkInModal')" class="btn-gold !h-9 !px-4 text-xs font-bold uppercase shadow-[0_0_20px_rgba(198,161,91,0.4)] hover:shadow-[0_0_30px_rgba(198,161,91,0.55)] transition-all duration-300 group relative overflow-hidden">
                            <span class="relative z-10">+ Walk-In Reservation</span>
                            <span class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></span>
                        </button>
                    @endif
                    <a href="{{ route('home') }}" target="_blank" class="btn-secondary !h-9 !px-3 text-xs hidden sm:inline-flex">
                        Marketplace ↗
                    </a>
                </div>
            </header>

            <!-- Mobile Navigation Drawer -->
            <div x-show="sidebarOpen" 
                 class="lg:hidden bg-[#171513] text-white p-6 space-y-4 border-b border-[#C6A15B]/30" 
                 style="display: none;" 
                 @click.outside="sidebarOpen = false">
                <div class="text-[10px] uppercase font-bold tracking-widest text-[#C6A15B]">{{ auth()->user()->isSuperAdmin() ? 'Platform Administration' : 'Management Menu' }}</div>
                <div class="grid grid-cols-2 gap-2 text-xs font-semibold text-stone-300">
                    @if(auth()->user()->isSuperAdmin())
                        <a href="{{ route('admin.dashboard', ['tab' => 'vendors']) }}" @click="sidebarOpen = false; $dispatch('set-admin-tab', { tab: 'vendors' })" class="py-2 hover:text-[#C6A15B]">🏢 Vendors</a>
                        <a href="{{ route('admin.dashboard', ['tab' => 'halls']) }}" @click="sidebarOpen = false; $dispatch('set-admin-tab', { tab: 'halls' })" class="py-2 hover:text-[#C6A15B]">🏛️ Halls</a>
                        <a href="{{ route('admin.dashboard', ['tab' => 'plans']) }}" @click="sidebarOpen = false; $dispatch('set-admin-tab', { tab: 'plans' })" class="py-2 hover:text-[#C6A15B]">💎 SaaS Plans</a>
                        <a href="{{ route('admin.dashboard', ['tab' => 'payments']) }}" @click="sidebarOpen = false; $dispatch('set-admin-tab', { tab: 'payments' })" class="py-2 hover:text-[#C6A15B]">💳 Payments</a>
                        <a href="{{ route('admin.dashboard', ['tab' => 'bookings']) }}" @click="sidebarOpen = false; $dispatch('set-admin-tab', { tab: 'bookings' })" class="py-2 hover:text-[#C6A15B]">📅 Bookings</a>
                        <a href="{{ route('admin.dashboard', ['tab' => 'users']) }}" @click="sidebarOpen = false; $dispatch('set-admin-tab', { tab: 'users' })" class="py-2 hover:text-[#C6A15B]">👥 Users</a>
                        <a href="{{ route('admin.dashboard', ['tab' => 'tasks']) }}" @click="sidebarOpen = false; $dispatch('set-admin-tab', { tab: 'tasks' })" class="py-2 hover:text-[#C6A15B]">📋 Tasks</a>
                        <a href="{{ route('admin.dashboard', ['tab' => 'traffic']) }}" @click="sidebarOpen = false; $dispatch('set-admin-tab', { tab: 'traffic' })" class="py-2 hover:text-[#C6A15B]">🚦 Traffic</a>
                    @else
                        @if(auth()->user()->isTenantOwner() || auth()->user()->isManager() || auth()->user()->isAccountant())
                            <a href="{{ route('dashboard') }}" class="py-2 hover:text-[#C6A15B]">Analytics</a>
                        @endif
                        @if(auth()->user()->isTenantOwner() || auth()->user()->isManager() || auth()->user()->isFrontDesk() || auth()->user()->role === 'ROLE_FLOOR_STAFF')
                            <a href="{{ route('calendar') }}" class="py-2 hover:text-[#C6A15B]">Shift Matrix</a>
                        @endif
                        @if(auth()->user()->isTenantOwner() || auth()->user()->isManager() || auth()->user()->isAccountant())
                            <a href="{{ route('expenses') }}" class="py-2 hover:text-[#C6A15B]">Expenses &amp; P&amp;L</a>
                            <a href="{{ route('staff.attendance') }}" class="py-2 hover:text-[#C6A15B]">Attendance</a>
                            <a href="{{ route('staff.payroll') }}" class="py-2 hover:text-[#C6A15B]">Payroll</a>
                        @endif
                        @if(auth()->user()->isTenantOwner() || auth()->user()->isManager())
                            <a href="{{ route('staff') }}" class="py-2 hover:text-[#C6A15B]">Team &amp; Staff</a>
                            <a href="{{ route('hall.media') }}" class="py-2 hover:text-[#C6A15B]">Website &amp; Halls</a>
                            <a href="{{ route('utility.fuel') }}" class="py-2 hover:text-[#C6A15B]">Generator Fuel</a>
                        @endif
                        @if(auth()->user()->isTenantOwner() || auth()->user()->isManager() || auth()->user()->isChef())
                            <a href="{{ route('inventory') }}" class="py-2 hover:text-[#C6A15B]">Inventory</a>
                            <a href="{{ route('packages.manager') }}" class="py-2 hover:text-[#C6A15B]">Catering Menus</a>
                        @endif
                        @if(auth()->user()->isTenantOwner() || auth()->user()->isManager() || auth()->user()->isChef() || auth()->user()->role === 'ROLE_FLOOR_STAFF')
                            <a href="{{ route('kitchen.board') }}" class="py-2 hover:text-[#C6A15B]">Kitchen KOT</a>
                        @endif
                        @if(auth()->user()->isTenantOwner() || auth()->user()->isManager() || auth()->user()->isFrontDesk())
                            <a href="{{ route('whatsapp.rsvp') }}" class="py-2 hover:text-[#C6A15B]">WhatsApp RSVP</a>
                        @endif
                        @if(auth()->user()->isTenantOwner() || auth()->user()->isManager() || auth()->user()->role === 'ROLE_FLOOR_STAFF')
                            <a href="{{ route('seating.planner') }}" class="py-2 hover:text-[#C6A15B]">Floor Seating</a>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Content Slot -->
            <main class="app-page-content flex-1 p-4 sm:p-8 bg-[#F8F5EF] dark:bg-[#171513] transition-colors duration-200">
                {{ $slot }}
            </main>

            <!-- Footer -->
            <footer class="bg-white dark:bg-[#171513] border-t border-[#E7E5E4] dark:border-white/10 py-4 px-8 text-xs text-stone-500 dark:text-stone-400 flex flex-col sm:flex-row items-center justify-between gap-2 transition-colors duration-200">
                <span>&copy; {{ date('Y') }} EventHub Enterprise Hospitality Architecture. All rights reserved.</span>
                <span class="font-mono text-[11px] text-stone-400 dark:text-stone-500">Strict Tenant Scoping Active</span>
            </footer>

        </div>

    </div>

    <!-- Interactive Walk-in Booking Modal Component -->
    @livewire('walk-in-booking-modal')

    <!-- Universal Royal Luxury Alert & Notification Dialog -->
    <x-luxury-alert-dialog />

    @livewireScripts
</body>
</html>
