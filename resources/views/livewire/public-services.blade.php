<div class="bg-[#F8F5EF] dark:bg-[#171513] text-[#171513] dark:text-[#F8F5EF] min-h-screen">

    <!-- ===================================================================
         SECTION 1: HERO — SERVICES EVENTHUB PROVIDES TO HALL OWNERS
         =================================================================== -->
    <section class="parallax-window relative bg-[#171513] text-white py-20 border-b border-[#C6A15B]/30 overflow-hidden">
        <div class="parallax-bg opacity-75" data-parallax="0.25"
             style="background-image: url('https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=2000&auto=format&fit=crop');">
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#171513] via-[#171513]/40 to-[#171513]/65"></div>
        <div class="absolute inset-0 bg-black/20"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 text-center">
            <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full border border-[#C6A15B]/40 bg-[#171513]/90 text-[#C6A15B] text-[11px] font-semibold tracking-[0.16em] uppercase font-sans">
                <span class="w-1.5 h-1.5 rounded-full bg-[#C6A15B]"></span>
                <span>Services We Provide to Wedding Hall Owners</span>
            </div>

            <h1 class="font-serif-luxury text-4xl sm:text-6xl text-white font-normal max-w-4xl mx-auto leading-tight">
                Everything Your Hall Needs to Run Digitally
            </h1>

            <p class="text-sm sm:text-base text-stone-300 max-w-2xl mx-auto font-light leading-relaxed">
                EventHub gives every wedding hall, marquee, and banquet owner a dedicated public website, a private Rent&nbsp;Admin dashboard, an atomic zero double-booking calendar, kitchen KOT slips, staff &amp; payroll tools, expense tracking, and printable legal documents — all in one SaaS platform.
            </p>

            <div class="flex flex-wrap items-center justify-center gap-4 pt-4">
                <a href="{{ route('register') }}" class="btn-gold !h-11 !px-6 text-xs font-bold font-sans tracking-wide rounded-xl">
                    List Your Hall on EventHub &rarr;
                </a>
                <a href="{{ route('public.contact') }}" class="btn-outline-gold !h-11 !px-6 text-xs font-bold font-sans tracking-wide rounded-xl">
                    Talk to Our Onboarding Team
                </a>
            </div>
        </div>
    </section>


    <!-- ===================================================================
         SECTION 2: CORE PLATFORM SERVICES (3 Pillar Cards)
         =================================================================== -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 space-y-10">
        <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-4 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <span class="text-[11px] font-sans uppercase tracking-[0.16em] text-[#8F7239] dark:text-[#C6A15B] font-semibold block">
                    What Every Hall Owner Gets
                </span>
                <h2 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                    Core Platform Services
                </h2>
            </div>
            <p class="text-xs text-stone-500 dark:text-stone-400 font-sans">
                A complete digital operating system for your banquet business
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- 1. Dedicated Public Website -->
            <div class="rounded-2xl p-8 bg-white dark:bg-[#1a1714] border border-[#E7E5E4] dark:border-white/10 space-y-4 shadow-sm hover:border-[#C6A15B]/50 hover:shadow-md transition">
                <div class="w-12 h-12 rounded-xl bg-[#171513] text-[#C6A15B] border border-[#C6A15B]/60 flex items-center justify-center font-cinzel text-xl font-bold">
                    01
                </div>
                <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-medium">Dedicated Public Website</h3>
                <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed font-normal">
                    Every registered hall gets its own shareable website page — with your photo gallery, 4K video tour, capacity &amp; rent details, services catalog, catering menus, live shift calendar, and 24/7 AI concierge chatbot. Share one link with clients and start receiving bookings.
                </p>
                <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10 text-[11px] font-semibold uppercase tracking-wider text-[#8F7239] dark:text-[#C6A15B] font-sans">
                    Free With Every Subscription
                </div>
            </div>

            <!-- 2. Rent Admin Dashboard -->
            <div class="rounded-2xl p-8 bg-white dark:bg-[#1a1714] border border-[#E7E5E4] dark:border-white/10 space-y-4 shadow-sm hover:border-[#C6A15B]/50 hover:shadow-md transition">
                <div class="w-12 h-12 rounded-xl bg-[#171513] text-[#C6A15B] border border-[#C6A15B]/60 flex items-center justify-center font-cinzel text-xl font-bold">
                    02
                </div>
                <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-medium">Private Rent Admin Dashboard</h3>
                <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed font-normal">
                    A fully isolated back-office for your hall only: bookings &amp; CRM pipeline, sales charts, customer records, payments, printable A4 agreements, quotations, invoices and thermal receipts — with strict multi-tenant data privacy.
                </p>
                <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10 text-[11px] font-semibold uppercase tracking-wider text-[#8F7239] dark:text-[#C6A15B] font-sans">
                    Role-Based Staff Access
                </div>
            </div>

            <!-- 3. Atomic Booking Calendar -->
            <div class="rounded-2xl p-8 bg-white dark:bg-[#1a1714] border border-[#E7E5E4] dark:border-white/10 space-y-4 shadow-sm hover:border-[#C6A15B]/50 hover:shadow-md transition">
                <div class="w-12 h-12 rounded-xl bg-[#171513] text-[#C6A15B] border border-[#C6A15B]/60 flex items-center justify-center font-cinzel text-xl font-bold">
                    03
                </div>
                <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-medium">Zero Double-Booking Calendar</h3>
                <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed font-normal">
                    Your hall's 3-shift matrix (Afternoon, Evening, Night) is protected by distributed locks and ACID row-level locking. Once a date-shift is confirmed — online, on phone, or walk-in — no second customer can ever overlap it.
                </p>
                <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10 text-[11px] font-semibold uppercase tracking-wider text-[#8F7239] dark:text-[#C6A15B] font-sans">
                    Concurrency Safe by Design
                </div>
            </div>
        </div>
    </section>


    <!-- ===================================================================
         SECTION 3: DAILY OPERATIONS ERP SUITE
         =================================================================== -->
    <section class="py-16 bg-[#F8F5EF]/50 dark:bg-[#171513]/60 border-t border-b border-[#E7E5E4] dark:border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            <div class="text-center space-y-2 max-w-2xl mx-auto">
                <span class="text-[11px] font-sans uppercase tracking-[0.16em] text-[#8F7239] dark:text-[#C6A15B] font-semibold block">
                    Daily Operations Suite
                </span>
                <h2 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                    Run Every Department From One Panel
                </h2>
                <p class="text-xs text-stone-600 dark:text-stone-300 font-sans">
                    Kitchen, inventory, staff, money, and guest communication — the full ERP modules included free during our launch period.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @if(config('platform.kitchen_module_enabled'))
                <!-- 4. Kitchen KOT Board -->
                <div class="rounded-2xl p-8 bg-white dark:bg-[#1a1714] border border-[#E7E5E4] dark:border-white/10 space-y-4 shadow-sm hover:border-[#C6A15B]/50 hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-xl bg-[#171513] text-[#C6A15B] border border-[#C6A15B]/60 flex items-center justify-center font-cinzel text-xl font-bold">
                        04
                    </div>
                    <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-medium">Kitchen KOT Order Slips</h3>
                    <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed font-normal">
                        Every confirmed booking auto-generates a Kitchen Order Ticket with exact meat, rice, and oil ratios from the guest count. Head chefs track PENDING &rarr; PREPARING &rarr; SERVED status live on the KOT board.
                    </p>
                    <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10 text-[11px] font-semibold uppercase tracking-wider text-[#8F7239] dark:text-[#C6A15B] font-sans">
                        Auto Bawarchi Ratios
                    </div>
                </div>
                @endif

                <!-- 5. Inventory Tracker -->
                <div class="rounded-2xl p-8 bg-white dark:bg-[#1a1714] border border-[#E7E5E4] dark:border-white/10 space-y-4 shadow-sm hover:border-[#C6A15B]/50 hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-xl bg-[#171513] text-[#C6A15B] border border-[#C6A15B]/60 flex items-center justify-center font-cinzel text-xl font-bold">
                        {{ config('platform.kitchen_module_enabled') ? '05' : '04' }}
                    </div>
                    <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-medium">Raw Ingredient Inventory</h3>
                    <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed font-normal">
                        Track mutton, chicken, basmati rice, ghee, and grocery stock with unit costs, low-stock alerts, consumption logs against bookings, and purchase orders for your suppliers.
                    </p>
                    <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10 text-[11px] font-semibold uppercase tracking-wider text-[#8F7239] dark:text-[#C6A15B] font-sans">
                        Stock Depletion Per Event
                    </div>
                </div>

                <!-- 6. Staff, Attendance & Payroll -->
                <div class="rounded-2xl p-8 bg-white dark:bg-[#1a1714] border border-[#E7E5E4] dark:border-white/10 space-y-4 shadow-sm hover:border-[#C6A15B]/50 hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-xl bg-[#171513] text-[#C6A15B] border border-[#C6A15B]/60 flex items-center justify-center font-cinzel text-xl font-bold">
                        {{ config('platform.kitchen_module_enabled') ? '06' : '05' }}
                    </div>
                    <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-medium">Staff, Attendance &amp; Payroll</h3>
                    <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed font-normal">
                        Register waiters, chefs, and security with roles &amp; permissions. Mark daily attendance with overtime, manage leaves, and generate monthly salary slips as printable PDFs.
                    </p>
                    <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10 text-[11px] font-semibold uppercase tracking-wider text-[#8F7239] dark:text-[#C6A15B] font-sans">
                        Printable Salary Slips
                    </div>
                </div>

                <!-- 7. Expense & Fuel Register -->
                <div class="rounded-2xl p-8 bg-white dark:bg-[#1a1714] border border-[#E7E5E4] dark:border-white/10 space-y-4 shadow-sm hover:border-[#C6A15B]/50 hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-xl bg-[#171513] text-[#C6A15B] border border-[#C6A15B]/60 flex items-center justify-center font-cinzel text-xl font-bold">
                        {{ config('platform.kitchen_module_enabled') ? '07' : '06' }}
                    </div>
                    <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-medium">Expense &amp; Generator Fuel Register</h3>
                    <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed font-normal">
                        Log diesel refuels with run-hours and per-litre rates, WAPDA electricity units, decor, salaries, and miscellaneous kharcha — then see category-wise expense reports for your hall.
                    </p>
                    <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10 text-[11px] font-semibold uppercase tracking-wider text-[#8F7239] dark:text-[#C6A15B] font-sans">
                        Per-Event Utility Costing
                    </div>
                </div>

                <!-- 8. WhatsApp RSVP Manager -->
                <div class="rounded-2xl p-8 bg-white dark:bg-[#1a1714] border border-[#E7E5E4] dark:border-white/10 space-y-4 shadow-sm hover:border-[#C6A15B]/50 hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-xl bg-[#171513] text-[#C6A15B] border border-[#C6A15B]/60 flex items-center justify-center font-cinzel text-xl font-bold">
                        {{ config('platform.kitchen_module_enabled') ? '08' : '07' }}
                    </div>
                    <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-medium">WhatsApp RSVP Manager</h3>
                    <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed font-normal">
                        Send wedding invitations over WhatsApp, collect RSVP replies automatically through the Meta Cloud API webhook, and monitor guest headcounts per booking from a single board.
                    </p>
                    <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10 text-[11px] font-semibold uppercase tracking-wider text-[#8F7239] dark:text-[#C6A15B] font-sans">
                        Two-Way Automated Replies
                    </div>
                </div>

                <!-- 9. Seating & Floor Plan Designer -->
                <div class="rounded-2xl p-8 bg-white dark:bg-[#1a1714] border border-[#E7E5E4] dark:border-white/10 space-y-4 shadow-sm hover:border-[#C6A15B]/50 hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-xl bg-[#171513] text-[#C6A15B] border border-[#C6A15B]/60 flex items-center justify-center font-cinzel text-xl font-bold">
                        {{ config('platform.kitchen_module_enabled') ? '09' : '08' }}
                    </div>
                    <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-medium">Seating &amp; Floor Plan Designer</h3>
                    <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed font-normal">
                        Design table layouts for each function — stage, dance floor, VIP sofas, and round tables — so your floor staff knows exactly where every family sits before the Barat walk-in.
                    </p>
                    <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10 text-[11px] font-semibold uppercase tracking-wider text-[#8F7239] dark:text-[#C6A15B] font-sans">
                        Saved Per Booking
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- ===================================================================
         SECTION 4: DOCUMENTS, EXPOSURE & SECURITY
         =================================================================== -->
    <section class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
        <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-4 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <span class="text-[11px] font-sans uppercase tracking-[0.16em] text-[#8F7239] dark:text-[#C6A15B] font-semibold block">
                    Business Growth &amp; Compliance
                </span>
                <h2 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                    Documents, Exposure &amp; Security
                </h2>
            </div>
            <p class="text-xs text-stone-500 dark:text-stone-400 font-sans">
                Everything around the booking — paperwork, marketing, and data protection
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="p-6 rounded-2xl bg-white dark:bg-[#1a1714] border border-[#E7E5E4] dark:border-white/10 space-y-2 shadow-sm">
                <span class="text-xs font-sans font-semibold text-[#8F7239] dark:text-[#C6A15B] uppercase tracking-wider">Printable Documents</span>
                <h4 class="font-serif-luxury text-xl text-[#171513] dark:text-white">A4 Contracts, Invoices &amp; Receipts</h4>
                <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed font-normal">
                    Generate official booking agreements, quotations, tax invoices, thermal payment receipts, chef KOT slips, and staff salary slips — printable or downloadable as PDF, always on your letterhead.
                </p>
            </div>

            <div class="p-6 rounded-2xl bg-white dark:bg-[#1a1714] border border-[#E7E5E4] dark:border-white/10 space-y-2 shadow-sm">
                <span class="text-xs font-sans font-semibold text-[#8F7239] dark:text-[#C6A15B] uppercase tracking-wider">Free Marketplace Exposure</span>
                <h4 class="font-serif-luxury text-xl text-[#171513] dark:text-white">Listed on the Public Marketplace</h4>
                <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed font-normal">
                    Your hall appears in EventHub's public venue discovery, live availability checker, gallery, and search — in front of every couple actively comparing verified banquets in your city.
                </p>
            </div>

            <div class="p-6 rounded-2xl bg-white dark:bg-[#1a1714] border border-[#E7E5E4] dark:border-white/10 space-y-2 shadow-sm">
                <span class="text-xs font-sans font-semibold text-[#8F7239] dark:text-[#C6A15B] uppercase tracking-wider">Multi-Tenant Security</span>
                <h4 class="font-serif-luxury text-xl text-[#171513] dark:text-white">Your Data Stays Yours</h4>
                <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed font-normal">
                    Strict tenant isolation keeps one hall's bookings, payments, and customers invisible to every other hall. Role-based access lets owners decide exactly what managers, accountants, and chefs can see.
                </p>
            </div>
        </div>
    </section>


    <!-- ===================================================================
         SECTION 5: ONBOARDING, SUPPORT & SUBSCRIPTION INCLUDED
         =================================================================== -->
    <section class="py-16 bg-[#F8F5EF] dark:bg-[#171513] border-t border-[#E7E5E4] dark:border-white/10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 text-center">
            <span class="text-[11px] font-sans uppercase tracking-[0.16em] text-[#8F7239] dark:text-[#C6A15B] font-semibold block">
                Onboarding &amp; Support
            </span>
            <h3 class="font-serif-luxury text-3xl text-[#171513] dark:text-white font-normal">
                From Registration to First Online Booking
            </h3>
            <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed max-w-2xl mx-auto">
                Our team verifies your business credentials, builds your hall profile, uploads your gallery, and hands you the Rent Admin login — usually within 48 hours. Every plan starts with a free trial.
            </p>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 text-xs font-sans font-medium text-stone-700 dark:text-stone-300">
                <div class="p-3.5 rounded-xl bg-white dark:bg-[#1a1714] border border-[#E7E5E4] dark:border-white/10 shadow-xs">✓ Free 14-Day Trial</div>
                <div class="p-3.5 rounded-xl bg-white dark:bg-[#1a1714] border border-[#E7E5E4] dark:border-white/10 shadow-xs">✓ Free Gallery Upload</div>
                <div class="p-3.5 rounded-xl bg-white dark:bg-[#1a1714] border border-[#E7E5E4] dark:border-white/10 shadow-xs">✓ WhatsApp Support Desk</div>
                <div class="p-3.5 rounded-xl bg-white dark:bg-[#1a1714] border border-[#E7E5E4] dark:border-white/10 shadow-xs">✓ Cancel Anytime</div>
            </div>
        </div>
    </section>


    <!-- ===================================================================
         SECTION 6: HALL OWNER CTA
         =================================================================== -->
    <section class="py-16 bg-[#171513] text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="space-y-2">
                <span class="text-[11px] font-sans uppercase tracking-widest text-[#C6A15B] block font-semibold">
                    Ready to Go Digital?
                </span>
                <h3 class="font-serif-luxury text-2xl sm:text-3xl font-normal text-white">
                    Put Your Hall on EventHub Today
                </h3>
                <p class="text-xs text-stone-300 font-light max-w-xl">
                    Join the platform where couples check availability, book shifts, and pay advances online — while you manage everything from one dashboard.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-4 shrink-0">
                <a href="{{ route('register') }}" class="btn-gold !h-11 !px-6 text-xs font-bold font-sans tracking-wide rounded-xl">
                    Register Your Hall &rarr;
                </a>
                <a href="{{ route('public.contact') }}" class="btn-outline-gold !h-11 !px-6 text-xs font-bold font-sans tracking-wide rounded-xl">
                    Speak with Sales &rarr;
                </a>
            </div>
        </div>
    </section>

</div>
