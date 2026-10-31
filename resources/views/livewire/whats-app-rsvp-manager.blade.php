<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    <!-- ===================================================================
         EXECUTIVE HEADER BANNER (Dual-Mode Luxury)
         =================================================================== -->
    <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-6 sm:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6 shadow-xl transition-colors duration-200">
        <div class="space-y-1.5">
            <div class="flex items-center gap-2 text-xs font-mono text-[#8F7239] dark:text-[#C6A15B] uppercase tracking-widest font-bold">
                <span>Vendor Portal</span>
                <span class="text-stone-400 dark:text-stone-600">/</span>
                <span>Guest Communications</span>
                <span class="text-stone-400 dark:text-stone-600">•</span>
                <span class="text-[#8F7239] dark:text-[#C6A15B] font-mono">Meta Cloud 2-Way Webhook Active</span>
            </div>
            <h1 class="font-serif-luxury text-2xl sm:text-4xl text-[#171513] dark:text-white font-bold tracking-tight">
                WhatsApp Smart RSVP Automation
            </h1>
            <p class="text-xs text-stone-600 dark:text-stone-300 font-sans max-w-2xl leading-relaxed">
                Automated guest attendance confirmation webhook with natural text parser, door headcount checklists, and instant status synchronizations.
            </p>
        </div>

        <div>
            <label class="block text-[10px] uppercase font-mono tracking-wider text-[#8F7239] dark:text-[#C6A15B] mb-1 font-bold">Active Function</label>
            <select wire:model.live="selectedBookingId" class="bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/50 text-[#171513] dark:text-stone-200 text-xs px-3.5 py-2 font-mono focus:border-[#C6A15B] outline-none">
                @foreach($bookings as $b)
                    <option value="{{ $b->id }}">{{ $b->customer_name }} ({{ $b->event_type }} — {{ optional($b->event_date)->format('d M Y') }})</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- ===================================================================
         RSVP HEADCOUNT MATRIX (Dual-Mode Metric Panels)
         =================================================================== -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 font-mono">
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl text-center space-y-1 relative overflow-hidden group hover:border-[#C6A15B] transition duration-300">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <span class="text-[10px] uppercase tracking-wider text-[#8F7239] dark:text-[#C6A15B] font-bold block">Confirmed Attending</span>
            <span class="font-cinzel text-4xl text-[#8F7239] dark:text-[#C6A15B] font-bold block">{{ $attendingCount }}</span>
            <span class="text-[11px] text-stone-500 dark:text-stone-400 block font-sans">Guests confirmed via WhatsApp</span>
        </div>
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl text-center space-y-1 relative overflow-hidden group hover:border-[#C6A15B] transition duration-300">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <span class="text-[10px] uppercase tracking-wider text-[#171513] dark:text-[#F8F5EF] font-bold block">Regret / Declined</span>
            <span class="font-cinzel text-4xl text-[#171513] dark:text-[#F8F5EF] font-bold block">{{ $declinedCount }}</span>
            <span class="text-[11px] text-stone-500 dark:text-stone-400 block font-sans">Cannot attend function</span>
        </div>
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl text-center space-y-1 relative overflow-hidden group hover:border-[#C6A15B] transition duration-300">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <span class="text-[10px] uppercase tracking-wider text-[#8F7239] dark:text-[#C6A15B] font-bold block">Pending / Tentative</span>
            <span class="font-cinzel text-4xl text-[#8F7239] dark:text-[#C6A15B] font-bold block">{{ $tentativeCount }}</span>
            <span class="text-[11px] text-stone-500 dark:text-stone-400 block font-sans">Awaiting guest response</span>
        </div>
    </div>

    <!-- ===================================================================
         SIMULATOR & REGISTRY GRID
         =================================================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Interactive WhatsApp Test Simulator -->
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-6 space-y-5 shadow-sm dark:shadow-xl transition-colors">
            <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-3">
                <span class="text-[10px] tracking-wider text-[#8F7239] dark:text-[#C6A15B] uppercase font-mono font-bold block">Diagnostic Sandbox</span>
                <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold mt-0.5">Webhook Simulator</h3>
                <p class="text-xs text-stone-500 dark:text-stone-400 font-sans">Test incoming guest responses and view database parsing.</p>
            </div>

            <div class="space-y-3 font-mono">
                <div>
                    <label class="block text-[11px] uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1 font-bold font-sans">Guest Mobile Phone</label>
                    <input type="text" wire:model="simPhone" placeholder="03001234567" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 text-[#171513] dark:text-white px-3 py-2 text-xs focus:border-[#C6A15B] outline-none font-bold">
                </div>

                <div>
                    <label class="block text-[11px] uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1 font-bold font-sans">Incoming Message Text</label>
                    <input type="text" wire:model="simMessage" placeholder="e.g. Attending: 4 or Regret" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 text-[#171513] dark:text-white px-3 py-2 text-xs focus:border-[#C6A15B] outline-none">
                </div>

                <button wire:click="simulateWhatsAppReply" class="w-full py-2 bg-[#F8F5EF] dark:bg-[#171513] border border-[#C6A15B]/60 text-[#8F7239] dark:text-[#C6A15B] hover:bg-[#C6A15B]/15 dark:hover:bg-[#171513]/85 text-xs font-bold uppercase tracking-wider transition">
                    Simulate Incoming Webhook &rarr;
                </button>
            </div>

            @if($simBotReply)
                <div class="p-3.5 bg-[#C6A15B]/15 dark:bg-[#171513]/85 border border-[#C6A15B]/60 dark:border-[#C6A15B]/50 text-xs font-mono font-bold text-[#8F7239] dark:text-[#C6A15B] shadow-xs">
                    {{ $simBotReply }}
                </div>
            @endif

            <!-- Add New Guest -->
            <div class="border-t border-[#E7E5E4] dark:border-white/10 pt-5 space-y-3">
                <span class="text-[11px] uppercase tracking-wider text-[#171513] dark:text-white font-bold block font-sans">Add Guest to Invitation List</span>
                <input type="text" wire:model="guest_name" placeholder="Guest Full Name" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 text-[#171513] dark:text-white px-3 py-2 text-xs font-sans focus:border-[#C6A15B] outline-none">
                <input type="text" wire:model="phone" placeholder="Guest WhatsApp Number" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 text-[#171513] dark:text-white px-3 py-2 text-xs font-mono focus:border-[#C6A15B] outline-none">
                <button wire:click="addGuest" class="w-full btn-gold !h-[36px] text-xs font-mono font-bold uppercase tracking-wider shadow">
                    + Add to RSVP Registry
                </button>
            </div>
        </div>

        <!-- RSVP Registry Table -->
        <div class="lg:col-span-2 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 shadow-sm dark:shadow-xl overflow-hidden transition-colors">
            <div class="p-6 border-b border-[#E7E5E4] dark:border-white/10">
                <span class="text-[10px] tracking-wider text-[#8F7239] dark:text-[#C6A15B] uppercase font-mono font-bold block">Guest Attendance Registry</span>
                <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold mt-0.5">Door Verification &amp; WhatsApp Log</h3>
            </div>
            <div class="table-wrapper-enterprise" tabindex="0" role="region" aria-label="Guest Attendance Registry"><table class="table-enterprise">
                    <thead>
                        <tr class="bg-[#F8F5EF] dark:bg-[#171513] border-b border-[#E7E5E4] dark:border-white/10 text-[10px] uppercase font-bold tracking-wider text-stone-600 dark:text-[#C6A15B] font-mono">
                            <th>Guest Name</th>
                            <th>WhatsApp</th>
                            <th class="text-center">Heads</th>
                            <th>Status</th>
                            <th>Last Message</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4] dark:divide-white/10 text-stone-800 dark:text-stone-200">
                        @forelse($rsvps as $rsvp)
                            <tr class="hover:bg-[#F8F5EF] dark:hover:bg-[#171513]/70 transition">
                                <td class="font-sans font-bold text-[#171513] dark:text-white">{{ $rsvp->guest_name }}</td>
                                <td class="font-mono text-stone-600 dark:text-stone-400 text-xs">{{ $rsvp->phone }}</td>
                                <td class="text-center font-bold text-[#171513] dark:text-white font-mono">{{ $rsvp->attending_count }}</td>
                                <td>
                                    <span class="badge-clean
                                        {{ $rsvp->rsvp_status === 'ATTENDING' ? 'badge-confirmed' : 
                                          ($rsvp->rsvp_status === 'DECLINED' ? 'badge-cancelled' : 'badge-hold') }}">
                                        {{ $rsvp->rsvp_status }}
                                    </span>
                                </td>
                                <td class="text-stone-500 dark:text-stone-400 italic truncate max-w-xs text-xs font-mono">
                                    {{ $rsvp->last_reply_message ?? 'Awaiting Reply' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-stone-400 font-mono text-xs">No guests registered on this invitation list yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
