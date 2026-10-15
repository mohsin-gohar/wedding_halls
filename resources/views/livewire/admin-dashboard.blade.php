<div class="max-w-7xl mx-auto space-y-8">

    <!-- ===================================================================
         HEADER BANNER (Unified Luxury Obsidian)
         =================================================================== -->
    <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-6 sm:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6 shadow-xl transition-colors duration-200">
        <div class="space-y-1.5">
            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 border border-[#C6A15B]/50 bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] text-[10px] font-mono uppercase tracking-widest font-bold">
                ★ Platform Owner Suite
            </div>
            <h1 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                Super Admin Console
            </h1>
            <p class="text-xs text-stone-600 dark:text-stone-300 font-sans max-w-2xl leading-relaxed">
                Nationwide vendor onboarding, SaaS pricing tiers, offline payment clearances, hall catalogs, and security audit log.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button wire:click="openProvisionModal" class="btn-gold !h-10 !px-5 text-xs font-bold uppercase font-mono shadow-[0_0_20px_rgba(198,161,91,0.4)] hover:shadow-[0_0_30px_rgba(198,161,91,0.55)] transition-all duration-300 group relative overflow-hidden">
                <span class="relative z-10">+ Register &amp; Provision Hall</span>
                <span class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></span>
            </button>
            <button wire:click="openCreatePlanModal" class="px-4 py-2 bg-stone-100 dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/20 hover:border-[#C6A15B] text-[#171513] dark:text-white text-xs font-mono uppercase transition font-bold">
                + New SaaS Plan
            </button>
        </div>
    </div>

    <!-- Feedback Message Alerts -->
    @if($feedbackMessage)
        <div class="p-4 bg-[#171513]/90 border border-[#C6A15B]/60 text-[#C6A15B] text-xs font-semibold shadow-md flex justify-between items-center">
            <span>✓ {{ $feedbackMessage }}</span>
            <button wire:click="$set('feedbackMessage', null)" class="text-[#C6A15B] font-bold text-sm">&times;</button>
        </div>
    @endif

    @if($errorMessage)
        <div class="p-4 bg-[#171513]/90 border border-[#C6A15B]/60 text-[#F8F5EF] text-xs font-semibold shadow-md flex justify-between items-center">
            <span>⚠️ {{ $errorMessage }}</span>
            <button wire:click="$set('errorMessage', null)" class="text-[#F8F5EF] font-bold text-sm">&times;</button>
        </div>
    @endif

    <!-- Handover Credentials Card for Rent Admin -->
    @if($provisionedCredentials)
        <div class="bg-[#171513] border-2 border-[#C6A15B] p-6 text-white space-y-4 shadow-2xl relative">
            <button wire:click="dismissCredentials" class="absolute top-4 right-4 text-white/60 hover:text-white text-lg font-mono">&times;</button>
            
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 bg-[#C6A15B] rounded-full animate-pulse"></span>
                <span class="text-xs font-mono font-bold uppercase tracking-widest text-[#C6A15B]">Hall Account Provisioned — Handover Credentials</span>
            </div>
            
            <p class="text-xs text-stone-300 font-sans">
                Share these login credentials with the hall owner. They will use them to sign in to their private Rent Admin portal and manage their hall's photos, videos, and services catalog.
            </p>

            @if(session('provisioned_password'))
                <div class="mb-4 p-4 bg-[#171513]/90 border border-[#C6A15B]/60 text-[#C6A15B] text-xs font-semibold shadow-md flex justify-between items-center">
                    <span>✓ For security, here is the one-time password for the Rent Admin:</span>
                    <button wire:click="$set('provisioned_password', null)" class="text-[#C6A15B] font-bold text-sm">&times;</button>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 bg-[#171513] p-4 border border-white/10 font-mono text-xs">
                <div>
                    <span class="text-stone-400 text-[10px] uppercase block">Business / Hall</span>
                    <strong class="text-white text-sm">{{ $provisionedCredentials['business_name'] }}</strong>
                    <span class="text-stone-400 text-[11px] block mt-0.5">{{ $provisionedCredentials['owner_name'] }} • {{ $provisionedCredentials['city'] }}</span>
                </div>
                <div>
                    <span class="text-stone-400 text-[10px] uppercase block">Login Email</span>
                    <strong class="text-[#C6A15B] select-all">{{ $provisionedCredentials['email'] }}</strong>
                </div>
                <div>
                    <span class="text-stone-400 text-[10px] uppercase block">Direct Login URL</span>
                    <span class="text-stone-300 text-[11px] truncate block select-all">{{ $provisionedCredentials['login_url'] }}</span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 pt-2">
                    <button type="button" 
                            onclick="const text = `Assalamu Alaikum {{ $provisionedCredentials['owner_name'] }}!\nYour EventHub Rent Admin portal for *{{ $provisionedCredentials['business_name'] }}* is ready.\n\nLogin URL: {{ $provisionedCredentials['login_url'] }}\nEmail: {{ $provisionedCredentials['email'] }}\n\nLive Hall Website: {{ $provisionedCredentials['hall_url'] }}\n\nPlease sign in to customize your hall photos, services catalog, and shifts.`; navigator.clipboard.writeText(text); alert('WhatsApp credentials message copied to clipboard!');"
                            class="btn-gold !h-9 !px-4 text-xs font-bold uppercase font-mono shadow-[0_0_20px_rgba(198,161,91,0.4)] hover:shadow-[0_0_30px_rgba(198,161,91,0.55)] transition-all duration-300 group relative overflow-hidden">
                        <span class="relative z-10">Copy WhatsApp Handover Message</span>
                        <span class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></span>
                    </button>

                    <a href="{{ $provisionedCredentials['hall_url'] }}" target="_blank" class="btn-outline-gold !h-9 !px-4 text-xs font-bold uppercase font-mono shadow-[0_0_15px_rgba(198,161,91,0.25)] hover:shadow-[0_0_25px_rgba(198,161,91,0.4)] transition-all duration-300">
                        View Live Hall Website &rarr;
                    </a>
            </div>
        </div>
    @endif

    <!-- Platform Metric Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl space-y-1 relative overflow-hidden group hover:border-[#C6A15B] transition">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 block font-mono">Total Tenants</span>
            <strong class="font-cinzel text-2xl text-[#171513] dark:text-white">{{ $totalTenants }}</strong>
            <span class="text-[11px] text-stone-400 block font-sans">Registered Venues</span>
        </div>

        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl space-y-1 relative overflow-hidden group hover:border-[#C6A15B] transition">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 block font-mono">Pending Approvals</span>
            <strong class="font-cinzel text-2xl text-[#8F7239] dark:text-[#C6A15B]">{{ $pendingTenants }}</strong>
            <span class="text-[11px] text-stone-400 block font-sans">Awaiting Review</span>
        </div>

        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl space-y-1 relative overflow-hidden group hover:border-[#C6A15B] transition">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 block font-mono">Active Halls</span>
            <strong class="font-cinzel text-2xl text-[#171513] dark:text-white">{{ $totalHalls }}</strong>
            <span class="text-[11px] text-stone-400 block font-sans">Across Pakistan</span>
        </div>

        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl space-y-1 relative overflow-hidden group hover:border-[#C6A15B] transition">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 block font-mono">Total Bookings</span>
            <strong class="font-cinzel text-2xl text-[#171513] dark:text-white">{{ $totalBookings }}</strong>
            <span class="text-[11px] text-stone-400 block font-sans">Confirmed Functions</span>
        </div>

        <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-5 shadow-sm dark:shadow-xl space-y-1 relative overflow-hidden group">
            <span class="text-[10px] uppercase font-bold tracking-wider text-[#8F7239] dark:text-[#C6A15B] block font-mono">Platform GMV</span>
            <strong class="font-cinzel text-xl text-[#171513] dark:text-[#C6A15B] block font-bold">PKR {{ number_format($totalGmv) }}</strong>
            <span class="text-[10px] text-stone-500 dark:text-stone-400 block font-mono">Gross Volume</span>
        </div>
    </div>

    <!-- Navigation Tabs (Zero-Pill Minimalist) -->
    <div class="flex items-center gap-2 border-b border-[#E7E5E4] dark:border-white/10 pb-3 text-xs font-bold uppercase tracking-wider overflow-x-auto font-mono">
        <button wire:click="setTab('vendors')" class="px-4 py-2 transition {{ $activeTab === 'vendors' ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513]' : 'bg-white dark:bg-[#171513] text-stone-700 dark:text-stone-300 border border-[#E7E5E4] dark:border-white/10 hover:bg-[#F8F5EF]' }}">
            Vendors &amp; Subscriptions ({{ $tenants->count() }})
        </button>
        <button wire:click="setTab('plans')" class="px-4 py-2 transition {{ $activeTab === 'plans' ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513]' : 'bg-white dark:bg-[#171513] text-stone-700 dark:text-stone-300 border border-[#E7E5E4] dark:border-white/10 hover:bg-[#F8F5EF]' }}">
            SaaS Plans ({{ $plans->count() }})
        </button>
        <button wire:click="setTab('payments')" class="px-4 py-2 transition {{ $activeTab === 'payments' ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513]' : 'bg-white dark:bg-[#171513] text-stone-700 dark:text-stone-300 border border-[#E7E5E4] dark:border-white/10 hover:bg-[#F8F5EF]' }}">
            Payment Approvals ({{ $subscriptionPayments->count() }})
        </button>
        <button wire:click="setTab('halls')" class="px-4 py-2 transition {{ $activeTab === 'halls' ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513]' : 'bg-white dark:bg-[#171513] text-stone-700 dark:text-stone-300 border border-[#E7E5E4] dark:border-white/10 hover:bg-[#F8F5EF]' }}">
            Halls Catalog ({{ $halls->count() }})
        </button>
        <button wire:click="setTab('bookings')" class="px-4 py-2 transition {{ $activeTab === 'bookings' ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513]' : 'bg-white dark:bg-[#171513] text-stone-700 dark:text-stone-300 border border-[#E7E5E4] dark:border-white/10 hover:bg-[#F8F5EF]' }}">
            All Bookings ({{ $recentBookings->count() }})
        </button>
        <button wire:click="setTab('audit')" class="px-4 py-2 transition {{ $activeTab === 'audit' ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513]' : 'bg-white dark:bg-[#171513] text-stone-700 dark:text-stone-300 border border-[#E7E5E4] dark:border-white/10 hover:bg-[#F8F5EF]' }}">
            Security Audit Log ({{ $auditLogs->count() }})
        </button>
    </div>

    <!-- ===================================================================
         TAB 1: VENDORS & SUBSCRIPTIONS (Full CRUD)
         =================================================================== -->
    @if($activeTab === 'vendors')
        <div class="bg-white border border-[#E7E5E4] p-6 space-y-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-[#E7E5E4] gap-4">
                <div>
                    <h3 class="font-serif text-2xl text-[#171513] font-normal">
                        Wedding Hall Vendors &amp; SaaS Subscriptions
                    </h3>
                    <p class="text-xs text-stone-500 mt-0.5">Register, update credentials, change subscription plans, and manage vendor active states.</p>
                </div>

                <button wire:click="openProvisionModal" class="btn-gold !h-9 !px-4 text-xs font-bold uppercase font-mono shadow-md shrink-0">
                    + Register &amp; Provision Hall
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse border border-[#E7E5E4]">
                    <thead>
                        <tr class="bg-[#F8F5EF] border-b border-[#E7E5E4] text-[10px] uppercase font-bold tracking-wider text-stone-600 font-mono">
                            <th class="p-3 border-r border-[#E7E5E4]">Business / Address</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Owner Contact</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Assigned SaaS Plan</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Capacity</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Status</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4]">
                        @foreach($tenants as $t)
                            <tr class="hover:bg-[#F8F5EF] transition">
                                <td class="p-3 border-r border-[#E7E5E4]">
                                    <div class="font-bold text-[#171513] text-sm">{{ $t->name }}</div>
                                    <span class="text-[10px] text-stone-500 font-mono block">{{ $t->city ?? 'Lahore' }}, {{ $t->address }}</span>
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] font-mono">
                                    <div class="text-stone-800 font-semibold">{{ $t->email }}</div>
                                    <div class="text-stone-500 text-[11px]">{{ $t->phone }}</div>
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4]">
                                    <div class="font-bold text-[#8F7239]">
                                        {{ $t->subscriptionPlan->name ?? ($t->subscription_plan ?? 'Starter Marquee') }}
                                    </div>
                                    <span class="text-[10px] font-mono text-stone-500">
                                        Status: <strong class="{{ $t->isSubscriptionActive() ? 'text-[#8F7239]' : 'text-[#171513]' }}">{{ $t->subscription_status }}</strong>
                                    </span>
                                    @if($t->subscription_expires_at)
                                        <div class="text-[10px] text-stone-400 font-mono">Exp: {{ $t->subscription_expires_at->format('d M Y') }}</div>
                                    @endif
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] font-mono text-stone-600">
                                    <div>{{ $t->halls_count }} Halls</div>
                                    <div>{{ $t->users_count }} Staff</div>
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4]">
                                    @if($t->verification_status === 'APPROVED')
                                        <span class="px-2 py-0.5 bg-[#C6A15B]/15 text-[#8F7239] border border-[#C6A15B]/60 text-[10px] font-mono font-bold uppercase">
                                            ✓ Approved
                                        </span>
                                    @elseif($t->verification_status === 'SUSPENDED')
                                        <span class="px-2 py-0.5 bg-[#171513]/8 text-[#171513] border border-[#C6A15B]/50 text-[10px] font-mono font-bold uppercase">
                                            ✕ Suspended
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 bg-[#C6A15B]/15 text-[#8F7239] border border-[#C6A15B]/60 text-[10px] font-mono font-bold uppercase">
                                            ⏳ Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="p-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5 font-mono text-[11px]">
                                        <button wire:click="openEditVendorModal('{{ $t->id }}')" 
                                                class="px-2.5 py-1 bg-stone-100 hover:bg-stone-200 text-stone-800 border border-stone-300">
                                            Edit
                                        </button>

                                        @if($t->verification_status !== 'APPROVED')
                                            <button wire:click="approveVendor('{{ $t->id }}')" 
                                                    class="px-2.5 py-1 bg-[#C6A15B] hover:bg-[#C6A15B] text-white font-bold">
                                                Approve
                                            </button>
                                        @else
                                            <button wire:click="suspendVendor('{{ $t->id }}')" 
                                                    class="px-2.5 py-1 bg-[#C6A15B] hover:bg-[#C6A15B] text-white">
                                                Suspend
                                            </button>
                                        @endif

                                        <button wire:click="toggleTenantStatus('{{ $t->id }}')" 
                                                class="px-2.5 py-1 {{ $t->is_active ? 'bg-stone-800 text-white' : 'bg-[#C6A15B] text-white' }}">
                                            {{ $t->is_active ? 'Disable' : 'Enable' }}
                                        </button>

                                        <button wire:click="deleteVendor('{{ $t->id }}')" 
                                                onclick="return confirm('Are you sure you want to delete this vendor and all its halls?') || event.stopImmediatePropagation()"
                                                class="px-2 py-1 bg-[#171513] hover:bg-[#171513] text-white">
                                            Del
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         TAB 2: SAAS PRICING PLANS (Full CRUD)
         =================================================================== -->
    @if($activeTab === 'plans')
        <div class="bg-white border border-[#E7E5E4] p-6 space-y-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-[#E7E5E4] gap-4">
                <div>
                    <h3 class="font-serif text-2xl text-[#171513] font-normal">
                        Commercial SaaS Subscription Plans (PKR)
                    </h3>
                    <p class="text-xs text-stone-500 mt-0.5">Configure monthly and yearly rates, venue limits, and ERP module privileges.</p>
                </div>

                <button wire:click="openCreatePlanModal" class="btn-gold !h-9 !px-4 text-xs font-bold uppercase font-mono shadow-md">
                    + Add New SaaS Plan
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($plans as $p)
                    <div class="p-6 border border-[#E7E5E4] bg-[#F8F5EF] flex flex-col justify-between space-y-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between border-b border-[#E7E5E4] pb-2">
                                <span class="text-[10px] font-mono font-bold uppercase text-[#8F7239]">Tier: {{ $p->slug }}</span>
                                <span class="text-xs font-mono font-semibold text-stone-600">{{ $p->tenants_count }} Subscribers</span>
                            </div>

                            <h4 class="font-serif text-2xl text-[#171513] font-medium">{{ $p->name }}</h4>
                            
                            <div class="space-y-1">
                                <div class="text-lg font-cinzel font-bold text-[#171513]">
                                    PKR {{ number_format($p->price_monthly) }} <span class="text-xs font-normal text-stone-500 font-sans">/ month</span>
                                </div>
                                <div class="text-[11px] font-mono text-stone-600 flex items-center justify-between">
                                    <span>PKR {{ number_format($p->price_weekly ?? 5000) }} / week</span>
                                    <span>PKR {{ number_format($p->price_yearly) }} / year</span>
                                </div>
                            </div>

                            <p class="text-xs text-stone-600 leading-relaxed font-normal">
                                {{ $p->description }}
                            </p>

                            <div class="pt-3 border-t border-[#E7E5E4] space-y-1.5 text-xs font-mono text-stone-700">
                                <div>• Max Halls: <strong>{{ $p->max_halls === -1 ? 'Unlimited' : $p->max_halls }}</strong></div>
                                <div>• Staff Accounts: <strong>{{ $p->max_staff === -1 ? 'Unlimited' : $p->max_staff }}</strong></div>
                                <div>• Monthly Bookings: <strong>{{ $p->max_monthly_bookings === -1 ? 'Unlimited' : $p->max_monthly_bookings }}</strong></div>
                                <div>• Kitchen KOT: <strong>{{ $p->has_kitchen_kot ? 'Included' : 'No' }}</strong></div>
                                <div>• WhatsApp Bot: <strong>{{ $p->has_whatsapp_automation ? 'Included' : 'No' }}</strong></div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-[#E7E5E4] flex items-center justify-between gap-2 font-mono text-xs">
                            <button wire:click="openEditPlanModal({{ $p->id }})" class="px-3 py-1.5 bg-[#171513] text-[#C6A15B] border border-[#C6A15B] font-bold hover:bg-[#C6A15B] hover:text-[#171513] transition">
                                Edit Plan
                            </button>
                            <button wire:click="deletePlan({{ $p->id }})" 
                                    onclick="return confirm('Delete this subscription plan?') || event.stopImmediatePropagation()"
                                    class="px-2.5 py-1.5 bg-[#171513] text-white hover:bg-[#171513] transition">
                                Delete
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- ===================================================================
         TAB 3: SUBSCRIPTION PAYMENT CLEARANCE
         =================================================================== -->
    @if($activeTab === 'payments')
        <div class="bg-white border border-[#E7E5E4] p-6 space-y-6 shadow-sm">
            <div class="border-b border-[#E7E5E4] pb-4">
                <h3 class="font-serif text-2xl text-[#171513] font-normal">
                    Offline Bank Transfer Approvals
                </h3>
                <p class="text-xs text-stone-500 mt-0.5">Verify vendor direct deposits and extend SaaS subscription periods.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse border border-[#E7E5E4]">
                    <thead>
                        <tr class="bg-[#F8F5EF] border-b border-[#E7E5E4] text-[10px] uppercase font-bold tracking-wider text-stone-600 font-mono">
                            <th class="p-3 border-r border-[#E7E5E4]">Date</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Vendor Name</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Plan &amp; Cycle</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Amount (PKR)</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Reference / Proof</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Status</th>
                            <th class="p-3 text-right">Verification</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4]">
                        @forelse($subscriptionPayments as $sp)
                            <tr class="hover:bg-[#F8F5EF] transition font-mono">
                                <td class="p-3 border-r border-[#E7E5E4] text-stone-500">{{ $sp->created_at->format('d M Y') }}</td>
                                <td class="p-3 border-r border-[#E7E5E4] font-bold text-stone-800 font-sans">{{ $sp->tenant->name ?? 'N/A' }}</td>
                                <td class="p-3 border-r border-[#E7E5E4]">{{ $sp->plan->name ?? 'Plan' }} ({{ $sp->billing_cycle }})</td>
                                <td class="p-3 border-r border-[#E7E5E4] font-bold text-[#171513]">PKR {{ number_format($sp->amount) }}</td>
                                <td class="p-3 border-r border-[#E7E5E4] text-stone-500">{{ $sp->transaction_reference ?: 'Manual Deposit' }}</td>
                                <td class="p-3 border-r border-[#E7E5E4]">
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase {{ $sp->status === 'APPROVED' ? 'bg-[#C6A15B]/20 text-[#8F7239]' : ($sp->status === 'REJECTED' ? 'bg-[#171513]/10 text-[#171513]' : 'bg-[#C6A15B]/20 text-[#8F7239]') }}">
                                        {{ $sp->status }}
                                    </span>
                                </td>
                                <td class="p-3 text-right">
                                    @if($sp->status === 'PENDING')
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button wire:click="approveSubscriptionPayment('{{ $sp->id }}')" class="px-2.5 py-1 bg-[#C6A15B] text-white hover:bg-[#C6A15B]">
                                                Approve
                                            </button>
                                            <button wire:click="rejectSubscriptionPayment('{{ $sp->id }}')" class="px-2.5 py-1 bg-[#171513] text-white hover:bg-[#171513]">
                                                Reject
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-stone-400 text-[10px]">Processed</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-8 text-center text-stone-400 font-sans">
                                    No offline subscription payment receipts submitted yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         TAB 4: HALLS CATALOG (Full CRUD)
         =================================================================== -->
    @if($activeTab === 'halls')
        <div class="bg-white border border-[#E7E5E4] p-6 space-y-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-[#E7E5E4] gap-4">
                <div>
                    <h3 class="font-serif text-2xl text-[#171513] font-normal">
                        Platform Halls &amp; Marquees Directory
                    </h3>
                    <p class="text-xs text-stone-500 mt-0.5">Manage public visibility, edit shift pricing, and audit all listed banquet spaces.</p>
                </div>
                  <button wire:click="openProvisionModal" class="btn-gold !h-9 !px-4 text-xs font-bold uppercase font-mono shadow-[0_0_20px_rgba(198,161,91,0.4)] hover:shadow-[0_0_30px_rgba(198,161,91,0.55)] transition-all duration-300 group relative overflow-hidden">
                      <span class="relative z-10">+ Add Hall</span>
                      <span class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></span>
                  </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse border border-[#E7E5E4]">
                    <thead>
                        <tr class="bg-[#F8F5EF] border-b border-[#E7E5E4] text-[10px] uppercase font-bold tracking-wider text-stone-600 font-mono">
                            <th class="p-3 border-r border-[#E7E5E4]">Hall / Venue Complex</th>
                            <th class="p-3 border-r border-[#E7E5E4]">City</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Capacity</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Base Rent</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Status</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4]">
                        @foreach($halls as $hl)
                            <tr class="hover:bg-[#F8F5EF] transition">
                                <td class="p-3 border-r border-[#E7E5E4]">
                                    <div class="font-bold text-[#171513] text-sm">{{ $hl->name }}</div>
                                    <span class="text-[10px] text-stone-500 font-mono">{{ $hl->tenant->name ?? 'Venue Complex' }}</span>
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] font-mono">{{ $hl->venue->city ?? 'Lahore' }}</td>
                                <td class="p-3 border-r border-[#E7E5E4] font-mono">{{ $hl->min_capacity }} - {{ $hl->max_capacity }} Heads</td>
                                <td class="p-3 border-r border-[#E7E5E4] font-mono font-bold text-[#171513]">PKR {{ number_format($hl->base_rent) }}</td>
                                <td class="p-3 border-r border-[#E7E5E4]">
                                    <span class="px-2 py-0.5 text-[10px] font-mono font-bold uppercase {{ $hl->is_active ? 'bg-[#C6A15B]/15 text-[#8F7239] border border-[#C6A15B]/60' : 'bg-stone-200 text-stone-700' }}">
                                        {{ $hl->is_active ? '● Published' : '○ Hidden' }}
                                    </span>
                                </td>
                                <td class="p-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5 font-mono text-[11px]">
                                        <a href="{{ route('hall.showcase', $hl->slug) }}" target="_blank" class="px-2 py-1 bg-stone-100 hover:bg-stone-200 text-stone-800 border border-stone-300">
                                            View ↗
                                        </a>
                                        <button wire:click="openEditHallModal('{{ $hl->id }}')" class="px-2.5 py-1 bg-stone-800 hover:bg-black text-white">
                                            Edit
                                        </button>
                                        <button wire:click="toggleHallStatus('{{ $hl->id }}')" class="px-2.5 py-1 {{ $hl->is_active ? 'bg-[#C6A15B] text-white' : 'bg-[#C6A15B] text-white' }}">
                                            {{ $hl->is_active ? 'Hide' : 'Publish' }}
                                        </button>
                                        <button wire:click="deleteHall('{{ $hl->id }}')" 
                                                onclick="return confirm('Delete hall {{ $hl->name }}?') || event.stopImmediatePropagation()"
                                                class="px-2 py-1 bg-[#171513] text-white hover:bg-[#171513]">
                                            Del
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         TAB 5: ALL PLATFORM BOOKINGS (CRUD & Status)
         =================================================================== -->
    @if($activeTab === 'bookings')
        <div class="bg-white border border-[#E7E5E4] p-6 space-y-6 shadow-sm">
            <div class="border-b border-[#E7E5E4] pb-4 flex items-center justify-between">
                <div>
                    <h3 class="font-serif text-2xl text-[#171513] font-normal">
                        Recent Functions &amp; Bookings Across Platform
                    </h3>
                    <p class="text-xs text-stone-500 mt-0.5">Monitor reservations, total turnover, and shift concurrency.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse border border-[#E7E5E4]">
                    <thead>
                        <tr class="bg-[#F8F5EF] border-b border-[#E7E5E4] text-[10px] uppercase font-bold tracking-wider text-stone-600 font-mono">
                            <th class="p-3 border-r border-[#E7E5E4]">Ref / Date</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Customer</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Hall / Venue</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Shift &amp; Heads</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Grand Total</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Status</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4] font-mono">
                        @foreach($recentBookings as $b)
                            <tr class="hover:bg-[#F8F5EF] transition">
                                <td class="p-3 border-r border-[#E7E5E4]">
                                    <div class="font-bold text-[#171513]">{{ $b->booking_reference }}</div>
                                    <span class="text-[10px] text-stone-500">{{ $b->event_date->format('d M Y') }}</span>
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] font-sans">
                                    <strong class="text-stone-800">{{ $b->customer_name }}</strong>
                                    <div class="text-[10px] text-stone-400 font-mono">{{ $b->customer_phone }}</div>
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] font-sans">
                                    <div>{{ $b->hall->name ?? 'Hall' }}</div>
                                    <span class="text-[10px] text-stone-400 font-mono">{{ $b->tenant->name ?? 'Venue' }}</span>
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4]">
                                    <div>{{ $b->slot_type }}</div>
                                    <span class="text-stone-500">{{ $b->guest_count }} Guests</span>
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] font-bold text-[#171513]">
                                    PKR {{ number_format($b->grand_total) }}
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4]">
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase {{ $b->status === 'CONFIRMED' ? 'bg-[#C6A15B]/20 text-[#8F7239]' : ($b->status === 'COMPLETED' ? 'bg-[#C6A15B]/15 text-[#8F7239]' : 'bg-[#C6A15B]/20 text-[#8F7239]') }}">
                                        {{ $b->status }}
                                    </span>
                                </td>
                                <td class="p-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5 font-mono text-[10px]">
                                        <a href="{{ route('documents.agreement', $b->id) }}" target="_blank" class="px-2 py-1 bg-stone-100 hover:bg-stone-200 border border-stone-300 text-stone-800">
                                            A4 Doc
                                        </a>
                                        @if($b->status !== 'CONFIRMED')
                                            <button wire:click="updateBookingStatus('{{ $b->id }}', 'CONFIRMED')" class="px-2 py-1 bg-[#C6A15B] text-white">
                                                Confirm
                                            </button>
                                        @endif
                                        <button wire:click="deleteBooking('{{ $b->id }}')" 
                                                onclick="return confirm('Delete booking {{ $b->booking_reference }}?') || event.stopImmediatePropagation()"
                                                class="px-2 py-1 bg-[#171513] text-white">
                                            Del
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         TAB 6: SECURITY AUDIT LOG
         =================================================================== -->
    @if($activeTab === 'audit')
        <div class="bg-white border border-[#E7E5E4] p-6 space-y-6 shadow-sm">
            <div class="border-b border-[#E7E5E4] pb-4">
                <h3 class="font-serif text-2xl text-[#171513] font-normal">
                    System Security &amp; Concurrency Audit Trail
                </h3>
                <p class="text-xs text-stone-500 mt-0.5">Immutable record of tenant registrations, payment approvals, and administrative events.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse border border-[#E7E5E4]">
                    <thead>
                        <tr class="bg-[#F8F5EF] border-b border-[#E7E5E4] text-[10px] uppercase font-bold tracking-wider text-stone-600 font-mono">
                            <th class="p-3 border-r border-[#E7E5E4]">Timestamp</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Action</th>
                            <th class="p-3 border-r border-[#E7E5E4]">User / IP</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Tenant</th>
                            <th class="p-3">Audit Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4] font-mono">
                        @forelse($auditLogs as $log)
                            <tr class="hover:bg-[#F8F5EF] transition">
                                <td class="p-3 border-r border-[#E7E5E4] text-stone-500 text-[11px]">
                                    {{ $log->created_at->format('d M Y H:i:s') }}
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4]">
                                    <span class="px-2 py-0.5 bg-stone-100 border border-stone-300 text-stone-800 font-bold text-[10px]">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] text-stone-700">
                                    <div>{{ $log->user->name ?? 'System' }}</div>
                                    <div class="text-[10px] text-stone-400">IP: {{ $log->ip_address ?? '127.0.0.1' }}</div>
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] text-[#171513] font-sans font-semibold">
                                    {{ $log->tenant->name ?? 'Platform Global' }}
                                </td>
                                <td class="p-3 text-stone-700 font-sans text-xs">
                                    {{ $log->description }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-stone-400 font-sans">
                                    No audit records logged yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         MODAL 1: REGISTER & PROVISION NEW HALL (Super Admin Onboarding)
         =================================================================== -->
    @if($showProvisionModal)
        <div class="fixed inset-0 z-50 bg-black/85 flex items-center justify-center p-4 overflow-y-auto">
            <div class="bg-[#171513] border-2 border-[#C6A15B] max-w-2xl w-full p-6 sm:p-8 space-y-6 shadow-2xl text-white my-8">
                <div class="border-b border-white/10 pb-4 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono uppercase tracking-widest text-[#C6A15B] block font-bold">Platform Owner Tool</span>
                        <h3 class="font-serif text-2xl text-white font-normal mt-0.5">Register &amp; Provision New Wedding Hall</h3>
                    </div>
                    <button wire:click="closeProvisionModal" class="text-white/60 hover:text-white text-2xl font-mono leading-none">&times;</button>
                </div>

                <div class="space-y-4 font-sans text-xs">
                    <p class="text-stone-300">
                        Enter the hall details and set the email and password for the Rent Admin. They will use these exact credentials to log in and manage their hall's images, videos, and services catalog.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">
                                Hall / Business Name *
                            </label>
                            <input type="text" wire:model="newBusinessName" placeholder="e.g. Shehnai Royal Palace"
                                   class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B]">
                            @error('newBusinessName') <span class="text-[#F8F5EF] text-[10px] font-mono mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">
                                City *
                            </label>
                            <select wire:model="newCity" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B]">
                                <option value="Lahore">Lahore</option>
                                <option value="Karachi">Karachi</option>
                                <option value="Islamabad">Islamabad</option>
                                <option value="Rawalpindi">Rawalpindi</option>
                                <option value="Faisalabad">Faisalabad</option>
                                <option value="Multan">Multan</option>
                                <option value="Peshawar">Peshawar</option>
                                <option value="Sialkot">Sialkot</option>
                                <option value="Gujranwala">Gujranwala</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">
                                Physical Address / Area
                            </label>
                            <input type="text" wire:model="newAddress" placeholder="e.g. Main Canal Road, Near Mall of Lahore"
                                   class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B]">
                        </div>

                        <div>
                            <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">
                                Tagline
                            </label>
                            <input type="text" wire:model="newTagline" placeholder="e.g. The Pinnacle of Royal Banqueting"
                                   class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B]">
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">
                                Min Capacity *
                            </label>
                            <input type="number" wire:model="newMinCapacity" step="25"
                                   class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>

                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">
                                Max Capacity *
                            </label>
                            <input type="number" wire:model="newMaxCapacity" step="50"
                                   class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>

                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">
                                Base Shift Rent (PKR) *
                            </label>
                            <input type="number" wire:model="newBaseRent" step="5000"
                                   class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                    </div>

                    <!-- Rent Admin Credentials Section -->
                    <div class="p-4 bg-[#171513] border border-[#C6A15B]/50 space-y-3">
                        <span class="text-[10px] font-mono uppercase tracking-widest text-[#C6A15B] block font-bold">
                            Rent Admin Login Credentials (To Hand Over)
                        </span>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-mono uppercase tracking-wider text-stone-300 mb-1">
                                    Rent Admin Full Name *
                                </label>
                                <input type="text" wire:model="newOwnerName" placeholder="e.g. Malik Jahangir"
                                       class="w-full bg-[#171513] border border-white/15 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                                @error('newOwnerName') <span class="text-[#F8F5EF] text-[10px] font-mono mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-[10px] font-mono uppercase tracking-wider text-stone-300 mb-1">
                                    WhatsApp / Phone *
                                </label>
                                <input type="text" wire:model="newOwnerPhone" placeholder="e.g. +92 300 1234567"
                                       class="w-full bg-[#171513] border border-white/15 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                                @error('newOwnerPhone') <span class="text-[#F8F5EF] text-[10px] font-mono mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-mono uppercase tracking-wider text-stone-300 mb-1">
                                    Login Email *
                                </label>
                                <input type="email" wire:model="newOwnerEmail" placeholder="e.g. owner@shehnaipalace.pk"
                                       class="w-full bg-[#171513] border border-white/15 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                                @error('newOwnerEmail') <span class="text-[#F8F5EF] text-[10px] font-mono mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-[10px] font-mono uppercase tracking-wider text-stone-300 mb-1">
                                    Password to Set *
                                </label>
                                <input type="text" wire:model="newOwnerPassword" placeholder="e.g. Shehnai2026!"
                                       class="w-full bg-[#171513] border border-white/15 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B] font-mono">
                                @error('newOwnerPassword') <span class="text-[#F8F5EF] text-[10px] font-mono mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-stone-300 mb-1">
                                Initial SaaS Subscription Tier
                            </label>
                            <select wire:model="newPlanId" class="w-full bg-[#171513] border border-white/15 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                                @foreach($plans as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }} — PKR {{ number_format($p->price_monthly) }}/mo</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="border-t border-white/10 pt-4 flex items-center justify-end gap-3">
                    <button wire:click="closeProvisionModal" class="px-4 py-2 border border-white/20 text-white text-xs font-mono uppercase hover:bg-white/10 transition">
                        Cancel
                    </button>
                    <button wire:click="provisionHallAccount" class="btn-gold !h-9 !px-5 text-xs font-bold uppercase font-mono shadow-[0_0_20px_rgba(198,161,91,0.4)] hover:shadow-[0_0_30px_rgba(198,161,91,0.55)] transition-all duration-300 group relative overflow-hidden">
                        <span class="relative z-10">Provision Hall &amp; Generate Credentials &rarr;</span>
                        <span class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         MODAL 2: EDIT VENDOR DETAILS
         =================================================================== -->
    @if($showEditVendorModal)
        <div class="fixed inset-0 z-50 bg-black/85 flex items-center justify-center p-4 overflow-y-auto">
            <div class="bg-[#171513] border-2 border-[#C6A15B] max-w-lg w-full p-6 sm:p-8 space-y-6 shadow-2xl text-white my-8">
                <div class="border-b border-white/10 pb-4 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono uppercase tracking-widest text-[#C6A15B] block font-bold">Vendor Record Edit</span>
                        <h3 class="font-serif text-2xl text-white font-normal mt-0.5">Edit Business Details</h3>
                    </div>
                    <button wire:click="closeEditVendorModal" class="text-white/60 hover:text-white text-2xl font-mono leading-none">&times;</button>
                </div>

                <div class="space-y-4 font-sans text-xs">
                    <div>
                        <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Business / Venue Name *</label>
                        <input type="text" wire:model="editVendorName" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B]">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">City</label>
                            <input type="text" wire:model="editVendorCity" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B]">
                        </div>
                        <div>
                            <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Phone / WhatsApp</label>
                            <input type="text" wire:model="editVendorPhone" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B]">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Login / Notification Email</label>
                        <input type="email" wire:model="editVendorEmail" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B]">
                    </div>

                    <div>
                        <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Assigned Plan</label>
                        <select wire:model="editVendorPlanId" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B]">
                            @foreach($plans as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Subscription Expiry Date</label>
                        <input type="date" wire:model="editVendorExpiresAt" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B]">
                    </div>
                </div>

                <div class="border-t border-white/10 pt-4 flex items-center justify-end gap-3 font-mono">
                    <button wire:click="closeEditVendorModal" class="px-4 py-2 border border-white/20 text-white text-xs uppercase">Cancel</button>
                    <button wire:click="updateVendor" class="btn-gold !h-9 !px-5 text-xs font-bold uppercase shadow-[0_0_20px_rgba(198,161,91,0.4)] hover:shadow-[0_0_30px_rgba(198,161,91,0.55)] transition-all duration-300 group relative overflow-hidden">
                        <span class="relative z-10">Save Changes &rarr;</span>
                        <span class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         MODAL 3: CREATE / EDIT SAAS SUBSCRIPTION PLAN
         =================================================================== -->
    @if($showPlanModal)
        <div class="fixed inset-0 z-50 bg-black/85 flex items-center justify-center p-4 overflow-y-auto">
            <div class="bg-[#171513] border-2 border-[#C6A15B] max-w-lg w-full p-6 sm:p-8 space-y-6 shadow-2xl text-white my-8">
                <div class="border-b border-white/10 pb-4 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono uppercase tracking-widest text-[#C6A15B] block font-bold">SaaS Tier Engine</span>
                        <h3 class="font-serif text-2xl text-white font-normal mt-0.5">
                            {{ $isEditingPlan ? 'Edit SaaS Plan' : 'Create New SaaS Plan' }}
                        </h3>
                    </div>
                    <button wire:click="closePlanModal" class="text-white/60 hover:text-white text-2xl font-mono leading-none">&times;</button>
                </div>

                <div class="space-y-4 font-sans text-xs">
                    <div>
                        <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Plan Name *</label>
                        <input type="text" wire:model="planName" placeholder="e.g. Royal Marquee Pro" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B]">
                        @error('planName') <span class="text-[#F8F5EF] text-[10px] font-mono mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Weekly (PKR)</label>
                            <input type="number" wire:model="planPriceWeekly" step="500" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-2.5 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Monthly (PKR) *</label>
                            <input type="number" wire:model="planPriceMonthly" step="1000" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-2.5 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Yearly (PKR) *</label>
                            <input type="number" wire:model="planPriceYearly" step="5000" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-2.5 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Max Halls (-1 unlim)</label>
                            <input type="number" wire:model="planMaxHalls" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Max Staff (-1 unlim)</label>
                            <input type="number" wire:model="planMaxStaff" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Max Bookings/Mo</label>
                            <input type="number" wire:model="planMaxBookings" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Plan Description</label>
                        <textarea wire:model="planDescription" rows="2" class="w-full bg-[#171513] border border-white/20 text-white text-xs p-2.5 outline-none focus:border-[#C6A15B]"></textarea>
                    </div>

                    <!-- Features Switches -->
                    <div class="pt-2 border-t border-white/10 grid grid-cols-3 gap-3">
                        <label class="flex items-center gap-2 cursor-pointer font-mono text-[11px]">
                            <input type="checkbox" wire:model="planHasKot" class="accent-[#C6A15B] w-4 h-4">
                            <span>Kitchen KOT</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer font-mono text-[11px]">
                            <input type="checkbox" wire:model="planHasWhatsapp" class="accent-[#C6A15B] w-4 h-4">
                            <span>WhatsApp Bot</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer font-mono text-[11px]">
                            <input type="checkbox" wire:model="planHasAccounting" class="accent-[#C6A15B] w-4 h-4">
                            <span>Accounting</span>
                        </label>
                    </div>
                </div>

                <div class="border-t border-white/10 pt-4 flex items-center justify-end gap-3 font-mono">
                    <button wire:click="closePlanModal" class="px-4 py-2 border border-white/20 text-white text-xs uppercase">Cancel</button>
                    <button wire:click="savePlan" class="btn-gold !h-9 !px-5 text-xs font-bold uppercase shadow-[0_0_20px_rgba(198,161,91,0.4)] hover:shadow-[0_0_30px_rgba(198,161,91,0.55)] transition-all duration-300 group relative overflow-hidden">
                        <span class="relative z-10">Save Plan &rarr;</span>
                        <span class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         MODAL 4: EDIT PLATFORM HALL DETAILS
         =================================================================== -->
    @if($showEditHallModal)
        <div class="fixed inset-0 z-50 bg-black/85 flex items-center justify-center p-4 overflow-y-auto">
            <div class="bg-[#171513] border-2 border-[#C6A15B] max-w-md w-full p-6 sm:p-8 space-y-6 shadow-2xl text-white my-8">
                <div class="border-b border-white/10 pb-4 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono uppercase tracking-widest text-[#C6A15B] block font-bold">Hall Registry Edit</span>
                        <h3 class="font-serif text-2xl text-white font-normal mt-0.5">Edit Banquet Hall</h3>
                    </div>
                    <button wire:click="closeEditHallModal" class="text-white/60 hover:text-white text-2xl font-mono leading-none">&times;</button>
                </div>

                <div class="space-y-4 font-sans text-xs">
                    <div>
                        <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Hall Name *</label>
                        <input type="text" wire:model="editHallName" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B]">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Min Capacity</label>
                            <input type="number" wire:model="editHallMinCapacity" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                        <div>
                            <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Max Capacity</label>
                            <input type="number" wire:model="editHallMaxCapacity" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-mono uppercase tracking-wider text-[#C6A15B] mb-1 font-semibold">Base Shift Rent (PKR)</label>
                        <input type="number" wire:model="editHallBaseRent" step="5000" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3.5 py-2.5 outline-none focus:border-[#C6A15B] font-mono">
                    </div>

                    <div class="pt-2">
                        <label class="flex items-center gap-2 cursor-pointer font-mono text-xs">
                            <input type="checkbox" wire:model="editHallIsActive" class="accent-[#C6A15B] w-4 h-4">
                            <span>Published on Public Marketplace</span>
                        </label>
                    </div>
                </div>

                <div class="border-t border-white/10 pt-4 flex items-center justify-end gap-3 font-mono">
                    <button wire:click="closeEditHallModal" class="px-4 py-2 border border-white/20 text-white text-xs uppercase">Cancel</button>
                    <button wire:click="updateHall" class="btn-gold !h-9 !px-5 text-xs font-bold uppercase shadow-[0_0_20px_rgba(198,161,91,0.4)] hover:shadow-[0_0_30px_rgba(198,161,91,0.55)] transition-all duration-300 group relative overflow-hidden">
                        <span class="relative z-10">Update Hall &rarr;</span>
                        <span class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
