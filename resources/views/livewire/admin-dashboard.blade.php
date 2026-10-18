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
                            onclick="const text = `Assalamu Alaikum {{ $provisionedCredentials['owner_name'] }}!\nYour EventHub Rent Admin portal for *{{ $provisionedCredentials['business_name'] }}* is ready.\n\nLogin URL: {{ $provisionedCredentials['login_url'] }}\nEmail: {{ $provisionedCredentials['email'] }}\n\nLive Hall Website: {{ $provisionedCredentials['hall_url'] }}\n\nPlease sign in to customize your hall photos, services catalog, and shifts.`; navigator.clipboard.writeText(text); window.luxuryToast('WhatsApp credentials message copied to clipboard!', 'gold');"
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
        <button wire:click="setTab('vendors')" class="px-4 py-2 transition whitespace-nowrap {{ $activeTab === 'vendors' ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513]' : 'bg-white dark:bg-[#171513] text-stone-700 dark:text-stone-300 border border-[#E7E5E4] dark:border-white/10 hover:bg-[#F8F5EF]' }}">
            Vendors &amp; Subscriptions ({{ $tenants->count() }})
        </button>
        <button wire:click="setTab('tasks')" class="px-4 py-2 transition whitespace-nowrap {{ $activeTab === 'tasks' ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513]' : 'bg-white dark:bg-[#171513] text-stone-700 dark:text-stone-300 border border-[#E7E5E4] dark:border-white/10 hover:bg-[#F8F5EF]' }}">
            📋 Platform Tasks ({{ $pendingTasksCount }})
        </button>
        <button wire:click="setTab('traffic')" class="px-4 py-2 transition whitespace-nowrap {{ $activeTab === 'traffic' ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513]' : 'bg-white dark:bg-[#171513] text-stone-700 dark:text-stone-300 border border-[#E7E5E4] dark:border-white/10 hover:bg-[#F8F5EF]' }}">
            🚦 Traffic &amp; Controls ({{ $activeSessionsCount }} Live)
        </button>
        <button wire:click="setTab('plans')" class="px-4 py-2 transition whitespace-nowrap {{ $activeTab === 'plans' ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513]' : 'bg-white dark:bg-[#171513] text-stone-700 dark:text-stone-300 border border-[#E7E5E4] dark:border-white/10 hover:bg-[#F8F5EF]' }}">
            SaaS Plans ({{ $plans->count() }})
        </button>
        <button wire:click="setTab('payments')" class="px-4 py-2 transition whitespace-nowrap {{ $activeTab === 'payments' ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513]' : 'bg-white dark:bg-[#171513] text-stone-700 dark:text-stone-300 border border-[#E7E5E4] dark:border-white/10 hover:bg-[#F8F5EF]' }}">
            Payment Approvals ({{ $subscriptionPayments->count() }})
        </button>
        <button wire:click="setTab('halls')" class="px-4 py-2 transition whitespace-nowrap {{ $activeTab === 'halls' ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513]' : 'bg-white dark:bg-[#171513] text-stone-700 dark:text-stone-300 border border-[#E7E5E4] dark:border-white/10 hover:bg-[#F8F5EF]' }}">
            Halls Catalog ({{ $halls->count() }})
        </button>
        <button wire:click="setTab('bookings')" class="px-4 py-2 transition whitespace-nowrap {{ $activeTab === 'bookings' ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513]' : 'bg-white dark:bg-[#171513] text-stone-700 dark:text-stone-300 border border-[#E7E5E4] dark:border-white/10 hover:bg-[#F8F5EF]' }}">
            All Bookings ({{ $recentBookings->count() }})
        </button>
        <button wire:click="setTab('users')" class="px-4 py-2 transition whitespace-nowrap {{ $activeTab === 'users' ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513]' : 'bg-white dark:bg-[#171513] text-stone-700 dark:text-stone-300 border border-[#E7E5E4] dark:border-white/10 hover:bg-[#F8F5EF]' }}">
            Users Directory ({{ $totalRegisteredUsers }})
        </button>
        <button wire:click="setTab('audit')" class="px-4 py-2 transition whitespace-nowrap {{ $activeTab === 'audit' ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513]' : 'bg-white dark:bg-[#171513] text-stone-700 dark:text-stone-300 border border-[#E7E5E4] dark:border-white/10 hover:bg-[#F8F5EF]' }}">
            Security Audit Log ({{ $auditLogs->count() }})
        </button>
    </div>

    <!-- ===================================================================
         TAB: PLATFORM TASKS & SUPER ADMIN TO-DO HUB
         =================================================================== -->
    @if($activeTab === 'tasks')
        <div class="space-y-6">
            <!-- Automated Alert Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Alert 1: Pending Approvals -->
                <div class="bg-white dark:bg-[#171513] border {{ $pendingApprovalsCount > 0 ? 'border-amber-500/60' : 'border-[#E7E5E4] dark:border-white/10' }} p-5 shadow-sm space-y-2">
                    <div class="flex items-center justify-between text-xs font-mono uppercase font-bold text-stone-600 dark:text-stone-400">
                        <span>🏛️ Pending Hall Verifications</span>
                        <span class="px-2 py-0.5 rounded font-bold text-[10px] {{ $pendingApprovalsCount > 0 ? 'bg-amber-500/20 text-amber-600 dark:text-amber-400' : 'bg-stone-100 dark:bg-white/5 text-stone-500' }}">
                            {{ $pendingApprovalsCount }} Action Needed
                        </span>
                    </div>
                    <div class="text-2xl font-cinzel font-bold text-[#171513] dark:text-white">
                        {{ $pendingApprovalsCount }} Marquees
                    </div>
                    <p class="text-xs text-stone-500 dark:text-stone-400 font-sans">
                        Halls awaiting Super Admin background verification &amp; commercial KYC.
                    </p>
                    @if($pendingApprovalsCount > 0)
                        <button wire:click="setTab('vendors')" class="text-xs text-[#8F7239] dark:text-[#C6A15B] font-mono font-bold hover:underline block pt-1">
                            Review &amp; Approve Now &rarr;
                        </button>
                    @endif
                </div>

                <!-- Alert 2: Pending Subscription Payments -->
                <div class="bg-white dark:bg-[#171513] border {{ $pendingSubscriptionPaymentsCount > 0 ? 'border-emerald-500/60' : 'border-[#E7E5E4] dark:border-white/10' }} p-5 shadow-sm space-y-2">
                    <div class="flex items-center justify-between text-xs font-mono uppercase font-bold text-stone-600 dark:text-stone-400">
                        <span>💳 Subscription Receipts</span>
                        <span class="px-2 py-0.5 rounded font-bold text-[10px] {{ $pendingSubscriptionPaymentsCount > 0 ? 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-stone-100 dark:bg-white/5 text-stone-500' }}">
                            {{ $pendingSubscriptionPaymentsCount }} Pending
                        </span>
                    </div>
                    <div class="text-2xl font-cinzel font-bold text-[#171513] dark:text-white">
                        {{ $pendingSubscriptionPaymentsCount }} Payments
                    </div>
                    <p class="text-xs text-stone-500 dark:text-stone-400 font-sans">
                        Manual bank transfer receipts submitted by hall owners for tier activation.
                    </p>
                    @if($pendingSubscriptionPaymentsCount > 0)
                        <button wire:click="setTab('payments')" class="text-xs text-[#8F7239] dark:text-[#C6A15B] font-mono font-bold hover:underline block pt-1">
                            Verify Bank Transfers &rarr;
                        </button>
                    @endif
                </div>

                <!-- Alert 3: Task Completion Rate -->
                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-5 shadow-sm space-y-2">
                    <div class="flex items-center justify-between text-xs font-mono uppercase font-bold text-stone-600 dark:text-stone-400">
                        <span>📋 Super Admin Tasks</span>
                        <span class="px-2 py-0.5 rounded font-bold text-[10px] bg-[#C6A15B]/20 text-[#8F7239] dark:text-[#C6A15B]">
                            {{ $pendingTasksCount }} Open / {{ $completedTasksCount }} Done
                        </span>
                    </div>
                    <div class="text-2xl font-cinzel font-bold text-[#171513] dark:text-white">
                        {{ $platformTasks->count() }} Total Tasks
                    </div>
                    <p class="text-xs text-stone-500 dark:text-stone-400 font-sans">
                        Operational to-do items and custom reminders for the platform owner.
                    </p>
                </div>
            </div>

            <!-- Task Creator & Register -->
            <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-6 space-y-6 shadow-sm">
                <div class="pb-4 border-b border-[#E7E5E4] dark:border-white/10">
                    <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-normal">
                        Super Admin Platform To-Do &amp; Task Desk
                    </h3>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-0.5">
                        Assign yourself platform tasks such as vendor phone calls, marketing campaigns, server maintenance, or legal contract audits.
                    </p>
                </div>

                <!-- Add Task Form -->
                <form wire:submit="addTask" class="p-4 bg-[#F8F5EF] dark:bg-white/5 border border-[#E7E5E4] dark:border-white/10 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                        <div class="sm:col-span-5">
                            <label class="block text-[11px] font-mono font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1">
                                Task Title / Action Item *
                            </label>
                            <input type="text" wire:model="newTaskTitle" required placeholder="e.g. Call Margalla Marquee owner for onboarding, Verify SSL renewal..."
                                   class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-xs w-full">
                            @error('newTaskTitle') <span class="text-rose-500 text-xs font-mono">{{ $message }}</span> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-mono font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1">
                                Category
                            </label>
                            <select wire:model="newTaskCategory" class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-xs w-full">
                                <option value="GENERAL">General Platform</option>
                                <option value="VENDOR_ONBOARDING">Vendor Onboarding</option>
                                <option value="TRAFFIC_MONITOR">Traffic &amp; SEO</option>
                                <option value="PAYMENT_REVIEW">Payment Review</option>
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-mono font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1">
                                Priority
                            </label>
                            <select wire:model="newTaskPriority" class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-xs w-full">
                                <option value="HIGH">🔥 High</option>
                                <option value="MEDIUM">⚡ Medium</option>
                                <option value="LOW">🌱 Low</option>
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-mono font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1">
                                Due Date
                            </label>
                            <input type="date" wire:model="newTaskDueDate" 
                                   class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-xs w-full">
                        </div>

                        <div class="sm:col-span-1">
                            <button type="submit" 
                                    class="btn-gold !h-10 !w-full text-xs font-bold uppercase font-mono shadow">
                                + Add
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Tasks List -->
                <div class="space-y-2">
                    @forelse($platformTasks as $task)
                        <div class="p-3.5 border {{ $task->is_completed ? 'bg-stone-50 dark:bg-white/[0.02] border-stone-200 dark:border-white/5 opacity-60' : 'bg-white dark:bg-[#171513] border-[#E7E5E4] dark:border-white/10 hover:border-[#C6A15B]' }} transition flex items-center justify-between gap-4 font-mono text-xs">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <button type="button" wire:click="toggleTask('{{ $task->id }}')" 
                                        class="w-5 h-5 rounded border flex items-center justify-center transition shrink-0 {{ $task->is_completed ? 'bg-emerald-600 border-emerald-600 text-white' : 'border-stone-400 dark:border-white/30 hover:border-[#C6A15B]' }}">
                                    @if($task->is_completed)
                                        ✓
                                    @endif
                                </button>

                                <div class="min-w-0 flex-1">
                                    <div class="font-bold text-sm {{ $task->is_completed ? 'line-through text-stone-500' : 'text-[#171513] dark:text-white' }} truncate">
                                        {{ $task->title }}
                                    </div>
                                    <div class="flex items-center gap-2 text-[10px] text-stone-500 dark:text-stone-400 mt-0.5">
                                        <span>Category: {{ str_replace('_', ' ', $task->category) }}</span>
                                        <span>•</span>
                                        @if($task->due_date)
                                            <span>Due: {{ $task->due_date->format('d M Y') }}</span>
                                            <span>•</span>
                                        @endif
                                        <span>Created: {{ $task->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 shrink-0">
                                @php
                                    $prioBadge = match($task->priority) {
                                        'HIGH' => 'bg-rose-500/15 text-rose-600 border-rose-500/30',
                                        'MEDIUM' => 'bg-amber-500/15 text-amber-600 border-amber-500/30',
                                        default => 'bg-emerald-500/15 text-emerald-600 border-emerald-500/30',
                                    };
                                @endphp
                                <span class="px-2 py-0.5 text-[9px] uppercase font-bold border {{ $prioBadge }}">
                                    {{ $task->priority }}
                                </span>

                                <button wire:click="deleteTask('{{ $task->id }}')" 
                                        onclick="return confirm('Delete this task?')"
                                        class="text-stone-400 hover:text-rose-500 text-base font-bold">
                                    &times;
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-xs text-stone-500 font-sans">
                            No custom tasks created yet. Use the form above to add your first administrative task.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         TAB: WEBSITE TRAFFIC & PLATFORM CONTROL CENTER
         =================================================================== -->
    @if($activeTab === 'traffic')
        <div class="space-y-6">
            <!-- Platform Master Switches & Controls -->
            <div class="bg-white dark:bg-[#171513] border-2 border-[#C6A15B] p-6 shadow-2xl relative space-y-6">
                <div class="w-full h-1 bg-gradient-to-r from-[#C6A15B] via-[#E8DCBE] to-[#C6A15B] absolute top-0 left-0"></div>

                <div class="pb-4 border-b border-[#E7E5E4] dark:border-white/10">
                    <span class="text-[10px] font-mono uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold block">
                        Platform Traffic &amp; System Gates
                    </span>
                    <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-normal mt-0.5">
                        Website Traffic Control &amp; Ingestion Switches
                    </h3>
                    <p class="text-xs text-stone-500 dark:text-stone-400 font-sans mt-0.5">
                        Control public search discovery, vendor onboarding portals, and live platform banners.
                    </p>
                </div>

                <!-- Switches Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Switch 1: Public Marketplace Status -->
                    <div class="p-5 bg-[#F8F5EF] dark:bg-white/5 border border-[#E7E5E4] dark:border-white/10 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-mono font-bold uppercase text-stone-800 dark:text-stone-200">
                                🌐 Public Marketplace Catalog
                            </span>
                            <span class="px-2 py-0.5 text-[9px] font-mono uppercase font-bold border {{ $marketplaceEnabled ? 'bg-emerald-500/15 text-emerald-600 border-emerald-500/30' : 'bg-rose-500/15 text-rose-600 border-rose-500/30' }}">
                                {{ $marketplaceEnabled ? 'ACTIVE / DISCOVERABLE' : 'PAUSED' }}
                            </span>
                        </div>
                        <p class="text-xs text-stone-500 dark:text-stone-400 font-sans">
                            Controls whether guests can browse, search, and view wedding halls on the public marketplace (`/venues`).
                        </p>
                        <button wire:click="toggleMarketplaceStatus" 
                                class="px-4 py-2 text-xs font-mono font-bold uppercase border transition {{ $marketplaceEnabled ? 'bg-stone-200 dark:bg-white/10 text-stone-800 dark:text-stone-200 hover:bg-stone-300' : 'bg-emerald-600 text-white border-emerald-600' }}">
                            {{ $marketplaceEnabled ? 'Pause Marketplace Access' : 'Activate Marketplace Catalog' }}
                        </button>
                    </div>

                    <!-- Switch 2: Vendor Self-Registration -->
                    <div class="p-5 bg-[#F8F5EF] dark:bg-white/5 border border-[#E7E5E4] dark:border-white/10 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-mono font-bold uppercase text-stone-800 dark:text-stone-200">
                                🏛️ Vendor Self-Registration Portal
                            </span>
                            <span class="px-2 py-0.5 text-[9px] font-mono uppercase font-bold border {{ $vendorRegistrationOpen ? 'bg-emerald-500/15 text-emerald-600 border-emerald-500/30' : 'bg-amber-500/15 text-amber-600 border-amber-500/30' }}">
                                {{ $vendorRegistrationOpen ? 'OPEN FOR NEW HALLS' : 'INVITE ONLY' }}
                            </span>
                        </div>
                        <p class="text-xs text-stone-500 dark:text-stone-400 font-sans">
                            Allows or pauses public wedding hall owner registration from `/register`. When paused, only Super Admin can provision new venues.
                        </p>
                        <button wire:click="toggleRegistrationStatus" 
                                class="px-4 py-2 text-xs font-mono font-bold uppercase border transition {{ $vendorRegistrationOpen ? 'bg-stone-200 dark:bg-white/10 text-stone-800 dark:text-stone-200 hover:bg-stone-300' : 'bg-emerald-600 text-white border-emerald-600' }}">
                            {{ $vendorRegistrationOpen ? 'Close Public Registration' : 'Open Public Registration' }}
                        </button>
                    </div>
                </div>

                <!-- Live Platform Announcement Banner -->
                <div class="p-5 bg-[#F8F5EF] dark:bg-white/5 border border-[#E7E5E4] dark:border-white/10 space-y-3">
                    <span class="text-xs font-mono font-bold uppercase text-stone-800 dark:text-stone-200 block">
                        📢 Global Platform Announcement Banner (Public Header)
                    </span>
                    <p class="text-xs text-stone-500 dark:text-stone-400 font-sans">
                        Broadcast system updates, peak wedding season notices, or maintenance schedules across all public pages.
                    </p>
                    <div class="flex gap-2">
                        <input type="text" wire:model="announcementBanner" placeholder="e.g. Wedding Season 2026 Inquiries Open • Guaranteed Shift Locking with Zero Double-Booking"
                               class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-xs flex-1">
                        <button wire:click="saveAnnouncement" class="btn-gold !h-10 !px-5 text-xs font-mono font-bold uppercase shadow">
                            Save Banner
                        </button>
                    </div>
                </div>
            </div>

            <!-- Real-Time Traffic & Visitor Analytics -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="p-5 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-1">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-stone-500 font-bold block">Live Visitors (15m)</span>
                    <div class="text-3xl font-cinzel font-bold text-emerald-600 dark:text-emerald-400">{{ $activeSessionsCount }}</div>
                    <span class="text-[10px] text-stone-400 font-mono">Active browsing sessions</span>
                </div>
                <div class="p-5 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-1">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-stone-500 font-bold block">Today's Page Views</span>
                    <div class="text-3xl font-cinzel font-bold text-[#8F7239] dark:text-[#C6A15B]">{{ $todayVisits }}</div>
                    <span class="text-[10px] text-stone-400 font-mono">Page hits in last 24h</span>
                </div>
                <div class="p-5 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-1">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-stone-500 font-bold block">Unique Visitors</span>
                    <div class="text-3xl font-cinzel font-bold text-[#171513] dark:text-white">{{ $uniqueVisitorsCount }}</div>
                    <span class="text-[10px] text-stone-400 font-mono">Unique IP addresses</span>
                </div>
                <div class="p-5 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 space-y-1">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-stone-500 font-bold block">Total Page Views</span>
                    <div class="text-3xl font-cinzel font-bold text-[#171513] dark:text-white">{{ $totalPageVisits }}</div>
                    <span class="text-[10px] text-stone-400 font-mono">All-time recorded visits</span>
                </div>
            </div>

            <!-- Top Performing Halls by Views -->
            <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-6 space-y-4 shadow-sm">
                <h4 class="text-sm font-mono font-bold uppercase tracking-wider text-[#8F7239] dark:text-[#C6A15B]">
                    Top 10 Most Visited Wedding Marquees &amp; Halls
                </h4>
                <div class="overflow-x-auto border border-[#E7E5E4] dark:border-white/10">
                    <table class="w-full text-xs text-left border-collapse">
                        <thead>
                            <tr class="bg-[#F8F5EF] dark:bg-white/5 border-b border-[#E7E5E4] dark:border-white/10 text-[10px] font-mono uppercase tracking-wider text-stone-600 dark:text-stone-400">
                                <th class="p-3">Hall Name &amp; Venue</th>
                                <th class="p-3">City</th>
                                <th class="p-3 text-right">Showcase Views</th>
                                <th class="p-3 text-right">Wishlists</th>
                                <th class="p-3 text-right">Public Link</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E7E5E4] dark:divide-white/10 font-sans">
                            @foreach($topHallsByViews as $th)
                                <tr class="hover:bg-stone-50 dark:hover:bg-white/5 transition">
                                    <td class="p-3">
                                        <div class="font-bold text-[#171513] dark:text-white">{{ $th->name }}</div>
                                        <span class="text-[10px] text-stone-500 font-mono">{{ $th->venue?->name ?? 'Venue' }}</span>
                                    </td>
                                    <td class="p-3 font-mono text-stone-600 dark:text-stone-400">{{ $th->venue?->city ?? '—' }}</td>
                                    <td class="p-3 text-right font-mono font-bold text-sm text-[#8F7239] dark:text-[#C6A15B]">{{ $th->total_views }}</td>
                                    <td class="p-3 text-right font-mono text-stone-600 dark:text-stone-300">{{ $th->hall_wishlists_count ?? 0 }}</td>
                                    <td class="p-3 text-right">
                                        <a href="{{ route('hall.showcase', $th->slug ?? $th->id) }}" target="_blank" 
                                           class="text-[#8F7239] dark:text-[#C6A15B] hover:underline font-mono text-[11px]">
                                            View Showcase &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         TAB 1: VENDORS & SUBSCRIPTIONS (Full CRUD)
         =================================================================== -->
    @if($activeTab === 'vendors')
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 space-y-6 shadow-sm dark:shadow-xl">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-[#E7E5E4] dark:border-white/10 gap-4">
                <div>
                    <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-normal">
                        Wedding Hall Vendors &amp; SaaS Subscriptions
                    </h3>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-0.5">Register, update credentials, change subscription plans, and manage vendor active states.</p>
                </div>

                <button wire:click="openProvisionModal" class="btn-gold !h-9 !px-4 text-xs font-bold uppercase font-mono shadow-md shrink-0">
                    + Register &amp; Provision Hall
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse border border-[#E7E5E4] dark:border-white/10">
                    <thead>
                        <tr class="bg-[#F8F5EF] dark:bg-white/5 border-b border-[#E7E5E4] dark:border-white/10 text-[10px] uppercase font-bold tracking-wider text-stone-600 dark:text-stone-300 font-mono">
                            <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Business / Address</th>
                            <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Owner Contact</th>
                            <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Assigned SaaS Plan</th>
                            <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Capacity</th>
                            <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Status</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4] dark:divide-white/10">
                        @foreach($tenants as $t)
                            <tr class="hover:bg-[#F8F5EF] dark:hover:bg-white/5 transition">
                                <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10">
                                    <div class="font-bold text-[#171513] dark:text-white text-sm">{{ $t->name }}</div>
                                    <span class="text-[10px] text-stone-500 dark:text-stone-400 font-mono block">{{ $t->city ?? 'Lahore' }}, {{ $t->address }}</span>
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10 font-mono">
                                    <div class="text-stone-800 dark:text-stone-200 font-semibold">{{ $t->email }}</div>
                                    <div class="text-stone-500 dark:text-stone-400 text-[11px]">{{ $t->phone }}</div>
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10">
                                    <div class="font-bold text-[#8F7239] dark:text-[#C6A15B]">
                                        {{ $t->subscriptionPlan->name ?? ($t->subscription_plan ?? 'Starter Marquee') }}
                                    </div>
                                    <span class="text-[10px] font-mono text-stone-500">
                                        Status: <strong class="{{ $t->isSubscriptionActive() ? 'text-[#8F7239]' : 'text-[#171513]' }}">{{ $t->subscription_status }}</strong>
                                    </span>
                                    @if($t->subscription_expires_at)
                                        <div class="text-[10px] text-stone-400 font-mono">Exp: {{ $t->subscription_expires_at->format('d M Y') }}</div>
                                    @endif
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10 font-mono text-stone-600 dark:text-stone-300">
                                    <div>{{ $t->halls_count }} Halls</div>
                                    <div>{{ $t->users_count }} Staff</div>
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10">
                                    @if($t->verification_status === 'APPROVED')
                                        <span class="px-2 py-0.5 bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 text-[10px] font-mono font-bold uppercase">
                                            ✓ Approved
                                        </span>
                                    @elseif($t->verification_status === 'SUSPENDED')
                                        <span class="px-2 py-0.5 bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30 text-[10px] font-mono font-bold uppercase">
                                            ✕ Suspended
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30 text-[10px] font-mono font-bold uppercase">
                                            ⏳ Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="p-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5 font-mono text-[11px]">
                                        <button wire:click="openEditVendorModal('{{ $t->id }}')" 
                                                class="px-2.5 py-1 bg-stone-100 hover:bg-stone-200 text-stone-800 border border-stone-300 dark:bg-white/10 dark:text-stone-200 dark:border-white/20 dark:hover:bg-white/20">
                                            Edit
                                        </button>

                                        @if($t->verification_status !== 'APPROVED')
                                            <button wire:click="approveVendor('{{ $t->id }}')" 
                                                    class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white font-bold">
                                                Approve
                                            </button>
                                        @else
                                            <button wire:click="suspendVendor('{{ $t->id }}')" 
                                                    class="px-2.5 py-1 bg-amber-600 hover:bg-amber-500 text-white">
                                                Suspend
                                            </button>
                                        @endif

                                        <button wire:click="toggleTenantStatus('{{ $t->id }}')" 
                                                class="px-2.5 py-1 {{ $t->is_active ? 'bg-stone-700 dark:bg-stone-600 text-white' : 'bg-emerald-600 text-white' }}">
                                            {{ $t->is_active ? 'Disable' : 'Enable' }}
                                        </button>

                                        <button wire:click="deleteVendor('{{ $t->id }}')" 
                                                onclick="return confirm('Are you sure you want to delete this vendor and all its halls?') || event.stopImmediatePropagation()"
                                                class="px-2 py-1 bg-rose-700 hover:bg-rose-600 text-white">
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
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 space-y-6 shadow-sm dark:shadow-xl">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-[#E7E5E4] dark:border-white/10 gap-4">
                <div>
                    <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-normal">
                        Commercial SaaS Subscription Plans (PKR)
                    </h3>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-0.5">Configure monthly and yearly rates, venue limits, and ERP module privileges.</p>
                </div>

                <button wire:click="openCreatePlanModal" class="btn-gold !h-9 !px-4 text-xs font-bold uppercase font-mono shadow-md">
                    + Add New SaaS Plan
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($plans as $p)
                    <div class="p-6 border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-white/5 flex flex-col justify-between space-y-4 hover:border-[#C6A15B] transition">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between border-b border-[#E7E5E4] dark:border-white/10 pb-2">
                                <span class="text-[10px] font-mono font-bold uppercase text-[#8F7239] dark:text-[#C6A15B]">Tier: {{ $p->slug }}</span>
                                <span class="text-xs font-mono font-semibold text-stone-600 dark:text-stone-300">{{ $p->tenants_count }} Subscribers</span>
                            </div>

                            <h4 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-medium">{{ $p->name }}</h4>
                            
                            <div class="space-y-1">
                                <div class="text-lg font-cinzel font-bold text-[#171513] dark:text-white">
                                    PKR {{ number_format($p->price_monthly) }} <span class="text-xs font-normal text-stone-500 dark:text-stone-400 font-sans">/ month</span>
                                </div>
                                <div class="text-[11px] font-mono text-stone-600 dark:text-stone-300 flex items-center justify-between">
                                    <span>PKR {{ number_format($p->price_weekly ?? 5000) }} / week</span>
                                    <span>PKR {{ number_format($p->price_yearly) }} / year</span>
                                </div>
                            </div>

                            <p class="text-xs text-stone-600 dark:text-stone-300 leading-relaxed font-normal">
                                {{ $p->description }}
                            </p>

                            <div class="pt-3 border-t border-[#E7E5E4] dark:border-white/10 space-y-1.5 text-xs font-mono text-stone-700 dark:text-stone-300">
                                <div>• Max Halls: <strong class="text-[#171513] dark:text-white">{{ $p->max_halls === -1 ? 'Unlimited' : $p->max_halls }}</strong></div>
                                <div>• Staff Accounts: <strong class="text-[#171513] dark:text-white">{{ $p->max_staff === -1 ? 'Unlimited' : $p->max_staff }}</strong></div>
                                <div>• Monthly Bookings: <strong class="text-[#171513] dark:text-white">{{ $p->max_monthly_bookings === -1 ? 'Unlimited' : $p->max_monthly_bookings }}</strong></div>
                                <div>• Kitchen KOT: <strong class="text-[#171513] dark:text-white">{{ $p->has_kitchen_kot ? 'Included' : 'No' }}</strong></div>
                                <div>• WhatsApp Bot: <strong class="text-[#171513] dark:text-white">{{ $p->has_whatsapp_automation ? 'Included' : 'No' }}</strong></div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-[#E7E5E4] dark:border-white/10 flex items-center justify-between gap-2 font-mono text-xs">
                            <button wire:click="openEditPlanModal({{ $p->id }})" class="px-3 py-1.5 bg-[#171513] text-[#C6A15B] border border-[#C6A15B] font-bold hover:bg-[#C6A15B] hover:text-[#171513] transition">
                                Edit Plan
                            </button>
                            <button wire:click="deletePlan({{ $p->id }})" 
                                    onclick="return confirm('Delete this subscription plan?') || event.stopImmediatePropagation()"
                                    class="px-2.5 py-1.5 bg-rose-700 text-white hover:bg-rose-600 transition">
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
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 space-y-6 shadow-sm dark:shadow-xl">
            <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-4">
                <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-normal">
                    Offline Bank Transfer Approvals
                </h3>
                <p class="text-xs text-stone-500 dark:text-stone-400 mt-0.5">Verify vendor direct deposits and extend SaaS subscription periods.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse border border-[#E7E5E4] dark:border-white/10">
                    <thead>
                        <tr class="bg-[#F8F5EF] dark:bg-white/5 border-b border-[#E7E5E4] dark:border-white/10 text-[10px] uppercase font-bold tracking-wider text-stone-600 dark:text-stone-300 font-mono">
                            <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Date</th>
                            <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Vendor Name</th>
                            <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Plan &amp; Cycle</th>
                            <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Amount (PKR)</th>
                            <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Reference / Proof</th>
                            <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Status</th>
                            <th class="p-3 text-right">Verification</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4] dark:divide-white/10">
                        @forelse($subscriptionPayments as $sp)
                            <tr class="hover:bg-[#F8F5EF] dark:hover:bg-white/5 transition font-mono">
                                <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10 text-stone-500 dark:text-stone-400">{{ $sp->created_at->format('d M Y') }}</td>
                                <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10 font-bold text-stone-800 dark:text-stone-200 font-sans">{{ $sp->tenant->name ?? 'N/A' }}</td>
                                <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10 text-stone-700 dark:text-stone-300">{{ $sp->plan->name ?? 'Plan' }} ({{ $sp->billing_cycle }})</td>
                                <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10 font-bold text-[#171513] dark:text-[#C6A15B]">PKR {{ number_format($sp->amount) }}</td>
                                <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10 text-stone-500 dark:text-stone-400">{{ $sp->transaction_reference ?: 'Manual Deposit' }}</td>
                                <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10">
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase {{ $sp->status === 'APPROVED' ? 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400' : ($sp->status === 'REJECTED' ? 'bg-rose-500/20 text-rose-600 dark:text-rose-400' : 'bg-amber-500/20 text-amber-600 dark:text-amber-400') }}">
                                        {{ $sp->status }}
                                    </span>
                                </td>
                                <td class="p-3 text-right">
                                    @if($sp->status === 'PENDING')
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button wire:click="approveSubscriptionPayment('{{ $sp->id }}')" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white font-bold">
                                                Approve
                                            </button>
                                            <button wire:click="rejectSubscriptionPayment('{{ $sp->id }}')" class="px-2.5 py-1 bg-rose-700 hover:bg-rose-600 text-white">
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
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 space-y-6 shadow-sm dark:shadow-xl">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-[#E7E5E4] dark:border-white/10 gap-4">
                <div>
                    <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-normal">
                        Platform Halls &amp; Marquees Directory
                    </h3>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-0.5">Manage public visibility, edit shift pricing, and audit all listed banquet spaces.</p>
                </div>
                <button wire:click="openProvisionModal" class="btn-gold !h-9 !px-4 text-xs font-bold uppercase font-mono shadow-[0_0_20px_rgba(198,161,91,0.4)] hover:shadow-[0_0_30px_rgba(198,161,91,0.55)] transition-all duration-300 group relative overflow-hidden">
                    <span class="relative z-10">+ Register Hall</span>
                    <span class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></span>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse border border-[#E7E5E4] dark:border-white/10">
                    <thead>
                        <tr class="bg-[#F8F5EF] dark:bg-white/5 border-b border-[#E7E5E4] dark:border-white/10 text-[10px] uppercase font-bold tracking-wider text-stone-600 dark:text-stone-300 font-mono">
                            <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Hall / Venue Complex</th>
                            <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">City</th>
                            <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Capacity</th>
                            <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Base Rent</th>
                            <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Status</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4] dark:divide-white/10">
                        @foreach($halls as $hl)
                            <tr class="hover:bg-[#F8F5EF] dark:hover:bg-white/5 transition">
                                <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10">
                                    <div class="font-bold text-[#171513] dark:text-white text-sm">{{ $hl->name }}</div>
                                    <span class="text-[10px] text-stone-500 dark:text-stone-400 font-mono">{{ $hl->tenant->name ?? 'Venue Complex' }}</span>
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10 font-mono text-stone-700 dark:text-stone-300">{{ $hl->venue->city ?? 'Lahore' }}</td>
                                <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10 font-mono text-stone-700 dark:text-stone-300">{{ $hl->min_capacity }} - {{ $hl->max_capacity }} Heads</td>
                                <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10 font-mono font-bold text-[#171513] dark:text-[#C6A15B]">PKR {{ number_format($hl->base_rent) }}</td>
                                <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10">
                                    <span class="px-2 py-0.5 text-[10px] font-mono font-bold uppercase {{ $hl->is_active ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30' : 'bg-stone-200 dark:bg-white/10 text-stone-600 dark:text-stone-400' }}">
                                        {{ $hl->is_active ? '● Published' : '○ Hidden' }}
                                    </span>
                                </td>
                                <td class="p-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5 font-mono text-[11px]">
                                        <a href="{{ route('hall.showcase', $hl->slug) }}" target="_blank" class="px-2.5 py-1 bg-stone-100 hover:bg-stone-200 text-stone-800 border border-stone-300 dark:bg-white/10 dark:text-stone-200 dark:border-white/20 dark:hover:bg-white/20">
                                            View ↗
                                        </a>
                                        <button wire:click="openEditHallModal('{{ $hl->id }}')" class="px-2.5 py-1 bg-stone-800 dark:bg-stone-700 hover:bg-black text-white">
                                            Edit
                                        </button>
                                        <button wire:click="toggleHallStatus('{{ $hl->id }}')" class="px-2.5 py-1 {{ $hl->is_active ? 'bg-amber-600 hover:bg-amber-500 text-white' : 'bg-emerald-600 hover:bg-emerald-500 text-white' }}">
                                            {{ $hl->is_active ? 'Hide' : 'Publish' }}
                                        </button>
                                        <button wire:click="deleteHall('{{ $hl->id }}')" 
                                                onclick="return confirm('Delete hall {{ $hl->name }}?') || event.stopImmediatePropagation()"
                                                class="px-2 py-1 bg-rose-700 hover:bg-rose-600 text-white">
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
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 space-y-6 shadow-sm dark:shadow-xl">
            <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-4 flex items-center justify-between">
                <div>
                    <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-normal">
                        Recent Functions &amp; Bookings Across Platform
                    </h3>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-0.5">Monitor reservations, total turnover, and shift concurrency.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse border border-[#E7E5E4] dark:border-white/10">
                    <thead>
                        <tr class="bg-[#F8F5EF] dark:bg-white/5 border-b border-[#E7E5E4] dark:border-white/10 text-[10px] uppercase font-bold tracking-wider text-stone-600 dark:text-stone-300 font-mono">
                            <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Ref / Date</th>
                            <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Customer</th>
                            <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Hall / Venue</th>
                            <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Shift &amp; Heads</th>
                            <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Grand Total</th>
                            <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Status</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4] dark:divide-white/10 font-mono">
                        @foreach($recentBookings as $b)
                            <tr class="hover:bg-[#F8F5EF] dark:hover:bg-white/5 transition">
                                <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10">
                                    <div class="font-bold text-[#171513] dark:text-white">{{ $b->booking_reference }}</div>
                                    <span class="text-[10px] text-stone-500 dark:text-stone-400">{{ $b->event_date->format('d M Y') }}</span>
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10 font-sans">
                                    <strong class="text-stone-800 dark:text-stone-200">{{ $b->customer_name }}</strong>
                                    <div class="text-[10px] text-stone-400 font-mono">{{ $b->customer_phone }}</div>
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10 font-sans">
                                    <div class="text-[#171513] dark:text-white">{{ $b->hall->name ?? 'Hall' }}</div>
                                    <span class="text-[10px] text-stone-400 font-mono">{{ $b->tenant->name ?? 'Venue' }}</span>
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10 text-stone-700 dark:text-stone-300">
                                    <div>{{ $b->slot_type }}</div>
                                    <span class="text-stone-500 dark:text-stone-400">{{ $b->guest_count }} Guests</span>
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10 font-bold text-[#171513] dark:text-[#C6A15B]">
                                    PKR {{ number_format($b->grand_total) }}
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10">
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase {{ $b->status === 'CONFIRMED' ? 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400' : ($b->status === 'COMPLETED' ? 'bg-blue-500/20 text-blue-600 dark:text-blue-400' : 'bg-amber-500/20 text-amber-600 dark:text-amber-400') }}">
                                        {{ $b->status }}
                                    </span>
                                </td>
                                <td class="p-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5 font-mono text-[10px]">
                                        <a href="{{ route('documents.agreement', $b->id) }}" target="_blank" class="px-2 py-1 bg-stone-100 hover:bg-stone-200 border border-stone-300 text-stone-800 dark:bg-white/10 dark:text-stone-200 dark:border-white/20 dark:hover:bg-white/20">
                                            A4 Doc
                                        </a>
                                        @if($b->status !== 'CONFIRMED')
                                            <button wire:click="updateBookingStatus('{{ $b->id }}', 'CONFIRMED')" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-500 text-white">
                                                Confirm
                                            </button>
                                        @endif
                                        <button wire:click="deleteBooking('{{ $b->id }}')" 
                                                onclick="return confirm('Delete booking {{ $b->booking_reference }}?') || event.stopImmediatePropagation()"
                                                class="px-2 py-1 bg-rose-700 hover:bg-rose-600 text-white">
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
         TAB 7: WEBSITE ANALYTICS (Super Admin Platform Insights)
         =================================================================== -->
    <!-- ===================================================================
         TAB: USERS DIRECTORY (Platform Accounts & Access Control)
         =================================================================== -->
    @if($activeTab === 'users')
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 space-y-6 shadow-sm dark:shadow-xl">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-[#E7E5E4] dark:border-white/10 gap-4">
                <div>
                    <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-normal">
                        Platform User Directory &amp; Access Control
                    </h3>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-0.5">Manage customer accounts, hall vendors, staff permissions, and account status (Active, Pending, Suspended).</p>
                </div>
            </div>

            <!-- User Metric Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm space-y-1">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 font-mono block">Total Accounts</span>
                    <strong class="font-cinzel text-2xl text-[#171513] dark:text-white">{{ $totalRegisteredUsers }}</strong>
                    <span class="text-[11px] text-stone-400 block font-sans">All Users</span>
                </div>
                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm space-y-1">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 font-mono block">Customers</span>
                    <strong class="font-cinzel text-2xl text-[#8F7239] dark:text-[#C6A15B]">{{ $totalCustomers }}</strong>
                    <span class="text-[11px] text-stone-400 block font-sans">Event Organizers</span>
                </div>
                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm space-y-1">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 font-mono block">Hall Vendors</span>
                    <strong class="font-cinzel text-2xl text-[#171513] dark:text-white">{{ $totalVendors }}</strong>
                    <span class="text-[11px] text-stone-400 block font-sans">Marquee Owners</span>
                </div>
                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm space-y-1">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 font-mono block">Hall Staff</span>
                    <strong class="font-cinzel text-2xl text-[#171513] dark:text-white">{{ $totalStaff }}</strong>
                    <span class="text-[11px] text-stone-400 block font-sans">Managers &amp; KOT</span>
                </div>
                <div class="bg-white dark:bg-[#171513] border border-rose-500/50 p-5 shadow-sm space-y-1">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-rose-600 dark:text-rose-400 font-mono block">Suspended</span>
                    <strong class="font-cinzel text-2xl text-rose-600 dark:text-rose-400">{{ $totalSuspendedUsers }}</strong>
                    <span class="text-[11px] text-stone-400 block font-sans">Restricted Access</span>
                </div>
            </div>

            <!-- Search & Filters -->
            <div class="flex flex-col sm:flex-row items-center gap-3 bg-[#F8F5EF] dark:bg-white/5 p-4 border border-[#E7E5E4] dark:border-white/10">
                <div class="w-full sm:w-1/2">
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-stone-600 dark:text-stone-300 font-bold mb-1">Search User Directory</label>
                    <input type="text" wire:model.live.debounce.300ms="userSearch" placeholder="Search by name, email, or phone..."
                           class="w-full bg-white dark:bg-[#171513] text-[#171513] dark:text-white border border-[#E7E5E4] dark:border-white/20 text-xs px-3.5 py-2 font-mono outline-none focus:border-[#C6A15B]">
                </div>
                <div class="w-full sm:w-1/4">
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-stone-600 dark:text-stone-300 font-bold mb-1">Role Filter</label>
                    <select wire:model.live="userRoleFilter" class="w-full bg-white dark:bg-[#171513] text-[#171513] dark:text-white border border-[#E7E5E4] dark:border-white/20 text-xs px-3 py-2 font-mono outline-none focus:border-[#C6A15B]">
                        <option value="ALL">All Roles</option>
                        <option value="ROLE_CUSTOMER">Customer / Client</option>
                        <option value="ROLE_TENANT_OWNER">Hall Owner</option>
                        <option value="ROLE_MANAGER">Hall Manager</option>
                        <option value="ROLE_ACCOUNTANT">Accountant</option>
                        <option value="ROLE_FRONT_DESK">Front Desk</option>
                        <option value="ROLE_CHEF">Chef</option>
                        <option value="ROLE_FLOOR_STAFF">Floor Staff</option>
                        <option value="ROLE_SUPER_ADMIN">Super Admin</option>
                    </select>
                </div>
                <div class="w-full sm:w-1/4">
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-stone-600 dark:text-stone-300 font-bold mb-1">Account Status</label>
                    <select wire:model.live="userStatusFilter" class="w-full bg-white dark:bg-[#171513] text-[#171513] dark:text-white border border-[#E7E5E4] dark:border-white/20 text-xs px-3 py-2 font-mono outline-none focus:border-[#C6A15B]">
                        <option value="ALL">All Statuses</option>
                        <option value="ACTIVE">Active</option>
                        <option value="PENDING">Pending</option>
                        <option value="SUSPENDED">Suspended</option>
                    </select>
                </div>
            </div>

            <!-- Users Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse border border-[#E7E5E4] dark:border-white/10">
                    <thead>
                        <tr class="bg-[#F8F5EF] dark:bg-white/5 border-b border-[#E7E5E4] dark:border-white/10 text-[10px] uppercase font-bold tracking-wider text-stone-600 dark:text-stone-300 font-mono">
                            <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">User Details</th>
                            <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Role</th>
                            <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Business / Venue</th>
                            <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Joined Date</th>
                            <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Last Login</th>
                            <th class="p-3 border-r border-[#E7E5E4] dark:border-white/10">Status</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4] dark:divide-white/10">
                        @forelse($users as $u)
                            <tr class="hover:bg-[#F8F5EF] dark:hover:bg-white/5 transition {{ $u->status === 'SUSPENDED' ? 'bg-red-50/40 dark:bg-red-950/20' : '' }}">
                                <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-[#171513] text-[#C6A15B] border border-[#C6A15B]/40 flex items-center justify-center font-bold text-xs uppercase font-mono shrink-0">
                                            {{ substr($u->name, 0, 2) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-[#171513] dark:text-white text-sm">{{ $u->name }}</div>
                                            <span class="text-[11px] text-stone-500 dark:text-stone-400 font-mono block select-all">{{ $u->email }}</span>
                                            @if($u->phone)
                                                <span class="text-[10px] text-stone-400 dark:text-stone-500 font-mono block">{{ $u->phone }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10">
                                    @if($u->isSuperAdmin())
                                        <span class="px-2 py-0.5 bg-[#171513] text-[#C6A15B] border border-[#C6A15B]/60 text-[10px] font-mono font-bold uppercase">
                                            ★ Super Admin
                                        </span>
                                    @elseif(in_array($u->role, ['VENDOR', 'HALL_OWNER', 'ADMIN']))
                                        <span class="px-2 py-0.5 bg-[#C6A15B]/15 text-[#8F7239] dark:text-[#C6A15B] border border-[#C6A15B]/60 text-[10px] font-mono font-bold uppercase">
                                            Hall Vendor
                                        </span>
                                    @elseif($u->role === 'CUSTOMER')
                                        <span class="px-2 py-0.5 bg-blue-500/15 text-blue-600 dark:text-blue-400 border border-blue-500/30 text-[10px] font-mono font-bold uppercase">
                                            Customer
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 bg-stone-100 dark:bg-white/10 text-stone-700 dark:text-stone-300 border border-stone-200 dark:border-white/20 text-[10px] font-mono font-bold uppercase">
                                            {{ $u->role }}
                                        </span>
                                    @endif
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10 font-mono text-stone-700 dark:text-stone-300">
                                    {{ $u->tenant->name ?? 'Platform Marketplace' }}
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10 font-mono text-stone-500 dark:text-stone-400 text-[11px]">
                                    {{ $u->created_at->format('d M Y') }}
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10 font-mono text-[11px]">
                                    @if($u->last_login_at)
                                        <span class="text-stone-800 dark:text-stone-200 font-semibold">{{ $u->last_login_at->diffForHumans() }}</span>
                                        <div class="text-[9px] text-stone-400 dark:text-stone-500">{{ $u->last_login_at->format('d M H:i') }}</div>
                                    @else
                                        <span class="text-stone-400 dark:text-stone-500 italic">Never</span>
                                    @endif
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] dark:border-white/10">
                                    @if($u->status === 'ACTIVE')
                                        <span class="px-2 py-0.5 bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 text-[10px] font-mono font-bold uppercase">
                                            Active
                                        </span>
                                    @elseif($u->status === 'SUSPENDED')
                                        <span class="px-2 py-0.5 bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30 text-[10px] font-mono font-bold uppercase">
                                            Suspended
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30 text-[10px] font-mono font-bold uppercase">
                                            Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="p-3 text-right space-x-2 font-mono">
                                    @if(!$u->isSuperAdmin() || $u->id !== auth()->id())
                                        @if($u->status === 'SUSPENDED')
                                            <button wire:click="toggleUserStatus({{ $u->id }})" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-bold uppercase transition">
                                                Activate
                                            </button>
                                        @else
                                            <button wire:click="toggleUserStatus({{ $u->id }})" wire:confirm="Are you sure you want to suspend this user account?" class="px-2.5 py-1 bg-rose-700/80 hover:bg-rose-600 text-white text-[10px] font-bold uppercase transition">
                                                Suspend
                                            </button>
                                        @endif
                                        <button wire:click="resetUserPassword({{ $u->id }})" wire:confirm="Reset password for {{ $u->name }} to 'Password123!'?" class="px-2.5 py-1 bg-stone-100 hover:bg-stone-200 text-stone-700 border border-stone-300 dark:bg-white/10 dark:text-stone-200 dark:border-white/20 dark:hover:bg-white/20 text-[10px] font-bold uppercase transition">
                                            Reset Pwd
                                        </button>
                                    @else
                                        <span class="text-[10px] text-stone-400 dark:text-stone-500 italic">Self Account</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-8 text-center text-stone-400 font-sans">
                                    No registered users match your search criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($activeTab === 'analytics')
        <div class="bg-white border border-[#E7E5E4] p-6 space-y-6 shadow-sm">
            <div class="border-b border-[#E7E5E4] pb-4 flex items-center justify-between">
                <div>
                    <h3 class="font-serif text-2xl text-[#171513] font-normal">
                        Platform Website Analytics
                    </h3>
                    <p class="text-xs text-stone-500 mt-0.5">Track visitor traffic, hall views, wishlists, and user engagement across the marketplace.</p>
                </div>
            </div>

            <!-- Analytics Metric Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="bg-[#171513] border border-[#C6A15B] p-5 shadow-xl space-y-1 relative overflow-hidden group">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                        <span class="text-[10px] uppercase font-bold tracking-wider text-[#C6A15B] block font-mono">Live Sessions</span>
                    </div>
                    <strong class="font-cinzel text-2xl text-white block">{{ $activeSessionsCount }}</strong>
                    <span class="text-[10px] text-stone-300 block font-sans">Active (15 min)</span>
                </div>

                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl space-y-1 relative overflow-hidden group hover:border-[#C6A15B] transition">
                    <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
                    <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 block font-mono">Unique Visitors</span>
                    <strong class="font-cinzel text-2xl text-[#171513] dark:text-white">{{ $uniqueVisitorsCount }}</strong>
                    <span class="text-[11px] text-stone-400 block font-sans">Distinct IPs</span>
                </div>

                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl space-y-1 relative overflow-hidden group hover:border-[#C6A15B] transition">
                    <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
                    <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 block font-mono">Total Page Views</span>
                    <strong class="font-cinzel text-2xl text-[#171513] dark:text-white">{{ $totalPageVisits }}</strong>
                    <span class="text-[11px] text-stone-400 block font-sans">Marketplace Hits</span>
                </div>

                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl space-y-1 relative overflow-hidden group hover:border-[#C6A15B] transition">
                    <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
                    <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 block font-mono">Hall Detail Views</span>
                    <strong class="font-cinzel text-2xl text-[#171513] dark:text-white">{{ $totalHallViews }}</strong>
                    <span class="text-[11px] text-stone-400 block font-sans">Hall Page Views</span>
                </div>

                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl space-y-1 relative overflow-hidden group hover:border-[#C6A15B] transition">
                    <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
                    <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 block font-mono">Wishlists Saved</span>
                    <strong class="font-cinzel text-2xl text-[#171513] dark:text-white">{{ $totalWishlists }}</strong>
                    <span class="text-[11px] text-stone-400 block font-sans">Guest Shortlists</span>
                </div>

                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl space-y-1 relative overflow-hidden group hover:border-[#C6A15B] transition">
                    <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
                    <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 block font-mono">Registered Users</span>
                    <strong class="font-cinzel text-2xl text-[#171513] dark:text-white">{{ $totalRegisteredUsers }}</strong>
                    <span class="text-[11px] text-stone-400 block font-sans">Customer Accounts</span>
                </div>

                <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-5 shadow-sm dark:shadow-xl space-y-1 relative overflow-hidden group">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-[#8F7239] dark:text-[#C6A15B] block font-mono">Today's Visits</span>
                    <strong class="font-cinzel text-xl text-[#171513] dark:text-[#C6A15B] block font-bold">{{ $todayVisits }}</strong>
                    <span class="text-[10px] text-stone-500 dark:text-stone-400 block font-mono">Last 24 Hours</span>
                </div>
            </div>

            <!-- Top Halls by Views and Wishlists -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 shadow-sm dark:shadow-xl space-y-6">
                    <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-4">
                        <span class="text-[10px] font-mono uppercase tracking-wider text-[#8F7239] font-bold block">Most Viewed Halls</span>
                        <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold mt-0.5">Top Venues by Traffic</h3>
                    </div>
                    <div class="space-y-4">
                        @forelse($topHallsByViews as $hall)
                            <div class="flex items-center justify-between p-3 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-full bg-[#C6A15B]/20 flex items-center justify-center text-[#8F7239] text-xs font-bold">{{ $loop->iteration }}</span>
                                    <div>
                                        <div class="font-bold text-[#171513] dark:text-white text-sm">{{ $hall->name }}</div>
                                        <span class="text-[10px] text-stone-500 font-mono">{{ $hall->venue->city ?? 'Pakistan' }}</span>
                                    </div>
                                </div>
                                <span class="font-cinzel text-lg text-[#171513] dark:text-[#C6A15B] font-bold">{{ $hall->total_views }} Views</span>
                            </div>
                        @empty
                            <div class="p-8 text-center text-stone-400 text-xs font-mono">No hall views recorded yet.</div>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 shadow-sm dark:shadow-xl space-y-6">
                    <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-4">
                        <span class="text-[10px] font-mono uppercase tracking-wider text-[#8F7239] font-bold block">Most Wishlisted Halls</span>
                        <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold mt-0.5">Guest Favorites</h3>
                    </div>
                    <div class="space-y-4">
                        @forelse($topHallsByWishlists as $hall)
                            <div class="flex items-center justify-between p-3 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-full bg-[#C6A15B]/20 flex items-center justify-center text-[#8F7239] text-xs font-bold">{{ $loop->iteration }}</span>
                                    <div>
                                        <div class="font-bold text-[#171513] dark:text-white text-sm">{{ $hall->name }}</div>
                                        <span class="text-[10px] text-stone-500 font-mono">{{ $hall->venue->city ?? 'Pakistan' }}</span>
                                    </div>
                                </div>
                                <span class="font-cinzel text-lg text-[#171513] dark:text-[#C6A15B] font-bold">{{ $hall->total_wishlists }} Saved</span>
                            </div>
                        @empty
                            <div class="p-8 text-center text-stone-400 text-xs font-mono">No wishlists recorded yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Recent Visitor Activity -->
            <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 shadow-sm dark:shadow-xl">
                <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-4 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono uppercase tracking-wider text-stone-500 dark:text-stone-400 block font-bold">Recent Visitor Activity</span>
                        <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold mt-0.5">Live Traffic Feed</h3>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left border-collapse border border-[#E7E5E4]">
                        <thead>
                            <tr class="bg-[#F8F5EF] border-b border-[#E7E5E4] text-[10px] uppercase font-bold tracking-wider text-stone-600 font-mono">
                                <th class="p-3 border-r border-[#E7E5E4]">Time</th>
                                <th class="p-3 border-r border-[#E7E5E4]">Page / Path</th>
                                <th class="p-3 border-r border-[#E7E5E4]">Visitor</th>
                                <th class="p-3 border-r border-[#E7E5E4]">IP Address</th>
                                <th class="p-3 text-right">Device</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E7E5E4] font-mono">
                            @forelse($recentVisits as $visit)
                                <tr class="hover:bg-[#F8F5EF] transition">
                                    <td class="p-3 border-r border-[#E7E5E4] text-stone-500 text-[11px]">{{ $visit->visited_at->format('d M Y H:i:s') }}</td>
                                    <td class="p-3 border-r border-[#E7E5E4] text-stone-800 font-sans">{{ $visit->path }}</td>
                                    <td class="p-3 border-r border-[#E7E5E4]">
                                        @if($visit->user)
                                            <span class="text-stone-800 font-semibold">{{ $visit->user->name }}</span>
                                            <div class="text-[10px] text-stone-400 font-mono">{{ $visit->user->email }}</div>
                                        @else
                                            <span class="text-stone-500 font-sans">Guest Visitor</span>
                                        @endif
                                    </td>
                                    <td class="p-3 border-r border-[#E7E5E4] text-stone-600 text-[11px]">{{ $visit->ip_address ?? '-' }}</td>
                                    <td class="p-3 text-right text-stone-500 text-[10px] font-mono">
                                        {{ str_contains($visit->user_agent ?? '', 'Mobile') ? 'Mobile' : 'Desktop' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-stone-400 font-sans">No visitor activity recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
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
