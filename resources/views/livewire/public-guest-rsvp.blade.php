<div class="py-12 bg-[#F8F5EF] dark:bg-[#171513]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">

        <!-- Page Header -->
        <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-8 space-y-3 text-center max-w-3xl mx-auto">
            <div class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.2em] text-[#8F7239] dark:text-[#C6A15B]">
                <span>Guest Services</span>
                <span>•</span>
                <span>Attendance Registry</span>
            </div>
            <h1 class="font-serif-luxury text-4xl sm:text-5xl text-[#171513] dark:text-white font-normal">
                Wedding Card Online RSVP
            </h1>
            <p class="text-stone-600 dark:text-stone-300 text-sm font-normal leading-relaxed">
                Enter the mobile phone or WhatsApp number printed on your wedding card invitation to confirm your family attendance with the event host.
            </p>
        </div>

        <!-- Phone Lookup Card -->
        <div class="max-w-md mx-auto bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-6 sm:p-8 shadow-sm dark:shadow-xl space-y-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-200 mb-2">Mobile / WhatsApp Number</label>
                <div class="flex gap-2">
                    <input type="text" 
                           wire:model="phone" 
                           placeholder="e.g. +92 300 1234567" 
                           class="flex-1 input-luxury text-sm">
                    <button type="button" 
                            wire:click="searchInvitation" 
                            class="btn-gold !h-[42px] text-xs font-bold uppercase">
                        Find Invite
                    </button>
                </div>
            </div>

            @if($feedbackMessage && !$foundRsvp)
                <div class="p-3 bg-[#171513]/8 border border-[#C6A15B]/40 text-xs text-[#171513]">
                    {{ $feedbackMessage }}
                </div>
            @endif
        </div>

        <!-- Found Invitation Card -->
        @if($foundRsvp)
            <div class="max-w-2xl mx-auto bg-white dark:bg-[#171513] border-2 border-[#C6A15B] p-8 sm:p-10 shadow-lg dark:shadow-xl space-y-8">
                <!-- Invitation Header -->
                <div class="text-center space-y-3 border-b border-[#E7E5E4] pb-6">
                    <span class="text-[10px] uppercase font-bold tracking-[0.25em] text-[#8F7239] block">HONORED GUEST INVITATION</span>
                    <h2 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] font-normal">
                        {{ $foundRsvp->booking->customer_name }}’s Family
                    </h2>
                    <p class="text-xs text-stone-600">
                        Cordially invites <strong class="text-[#171513]">{{ $foundRsvp->guest_name }}</strong> &amp; Family to grace the auspicious ceremony of:
                    </p>
                    <span class="inline-block px-3 py-1 text-xs font-bold uppercase tracking-wider bg-[#F8F5EF] dark:bg-white/5 border border-[#E7E5E4] dark:border-white/10 text-[#171513] dark:text-white">
                        {{ $foundRsvp->booking->event_type }}
                    </span>
                </div>

                <!-- Event Details Grid -->
                <div class="grid grid-cols-2 gap-4 text-xs">
                    <div class="p-3 bg-[#F8F5EF] dark:bg-white/5 border border-[#E7E5E4] dark:border-white/10">
                        <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 block">Date &amp; Shift</span>
                        <strong class="text-stone-900 dark:text-stone-100 block mt-1 text-sm">{{ $foundRsvp->booking->event_date->format('l, d M Y') }}</strong>
                        <span class="text-stone-500 text-[11px]">{{ $foundRsvp->booking->slot_type }} Shift</span>
                    </div>

                    <div class="p-3 bg-[#F8F5EF] dark:bg-white/5 border border-[#E7E5E4] dark:border-white/10">
                        <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 block">Venue &amp; Hall</span>
                        <strong class="text-stone-900 dark:text-stone-100 block mt-1 text-sm">{{ $foundRsvp->booking->hall->name ?? 'Ballroom' }}</strong>
                        <span class="text-stone-500 text-[11px]">{{ $foundRsvp->booking->hall->venue->address ?? 'Gulberg III, Lahore' }}</span>
                    </div>
                </div>

                <!-- Response Form -->
                @if(!$isSubmitted)
                    <div class="space-y-6 pt-4 border-t border-[#E7E5E4]">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-200 mb-2">Will you be attending?</label>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="border p-3 flex items-center gap-3 cursor-pointer transition {{ $statusResponse === 'ATTENDING' ? 'border-[#171513] dark:border-white bg-[#F8F5EF] dark:bg-white/10' : 'border-[#E7E5E4]' }}">
                                    <input type="radio" wire:model.live="statusResponse" value="ATTENDING" class="accent-[#C6A15B]">
                                    <span class="text-xs font-bold text-stone-900 dark:text-stone-100">Yes, We Are Attending</span>
                                </label>
                                <label class="border p-3 flex items-center gap-3 cursor-pointer transition {{ $statusResponse === 'DECLINED' ? 'border-[#171513] dark:border-white bg-[#F8F5EF] dark:bg-white/10' : 'border-[#E7E5E4]' }}">
                                    <input type="radio" wire:model.live="statusResponse" value="DECLINED" class="accent-[#C6A15B]">
                                    <span class="text-xs font-bold text-stone-900 dark:text-stone-100">Regretfully Decline</span>
                                </label>
                            </div>
                        </div>

                        @if($statusResponse === 'ATTENDING')
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-200 mb-2">
                                    Number of Family Members / Attendees: <strong>{{ $attendingGuests }}</strong>
                                </label>
                                <input type="number" wire:model="attendingGuests" min="1" max="20" class="input-luxury max-w-xs text-sm">
                            </div>
                        @endif

                        <button type="button" wire:click="submitRsvp" class="btn-gold !w-full !h-11 text-xs font-bold uppercase tracking-wider">
                            Confirm RSVP Response &rarr;
                        </button>
                    </div>
                @else
                    <div class="p-4 bg-[#C6A15B]/15 border border-[#C6A15B]/60 text-center space-y-2">
                        <span class="text-[#8F7239] text-sm font-bold block">✓ RSVP Recorded Successfully</span>
                        <p class="text-xs text-[#8F7239]">{{ $feedbackMessage }}</p>
                    </div>
                @endif
            </div>
        @endif

    </div>
</div>
