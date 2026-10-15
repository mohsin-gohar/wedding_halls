<div class="py-12 bg-[#F8F5EF] dark:bg-[#171513] text-[#171513] dark:text-[#F8F5EF] transition-colors">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-14">

        <!-- Page Header -->
        <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-8 space-y-3">
            <div class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.2em] text-[#8F7239] dark:text-[#C6A15B]">
                <span>Concierge &amp; Direct Support</span>
                <span>•</span>
                <span>Immediate Assistance</span>
            </div>
            <h1 class="font-serif-luxury text-4xl sm:text-5xl text-[#171513] dark:text-white font-normal">
                Contact EventHub
            </h1>
            <p class="text-stone-600 dark:text-stone-300 text-sm max-w-2xl font-normal leading-relaxed">
                Whether you are planning a high-profile wedding celebration or require enterprise hall management SaaS solutions, our dedicated team is at your service.
            </p>
        </div>

        <!-- Contact Form & Direct Details Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
            
            <!-- Left: Contact & WhatsApp Form (7 cols) -->
            <div class="lg:col-span-7 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-8 sm:p-10 shadow-sm space-y-6">
                <div>
                    <span class="text-[10px] uppercase font-bold tracking-[0.15em] text-[#8F7239] dark:text-[#C6A15B] block font-mono">Direct Inquiry Form</span>
                    <h2 class="font-serif-luxury text-2xl text-[#171513] dark:text-white font-normal">Send an Official Message</h2>
                </div>

                @if($isSubmitted)
                    <div class="p-6 bg-[#C6A15B]/15 dark:bg-[#171513]/85 border border-[#C6A15B]/60 dark:border-[#C6A15B]/50 text-[#8F7239] dark:text-[#E8DCBE] space-y-2">
                        <strong class="font-cinzel text-lg block">✓ Inquiry Transmitted Successfully</strong>
                        <p class="text-xs leading-relaxed">
                            Thank you, {{ $name }}. Your wedding requirements have been forwarded to our senior hospitality concierge. We will contact you within 2 business hours via phone ({{ $phone }}) or email.
                        </p>
                    </div>
                @else
                    <form wire:submit="submitInquiry" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1 font-mono">Your Full Name *</label>
                                <input type="text" wire:model="name" class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm">
                                @error('name') <span class="text-xs text-rose-600 dark:text-rose-400 font-mono">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1 font-mono">Email Address *</label>
                                <input type="email" wire:model="email" class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm">
                                @error('email') <span class="text-xs text-rose-600 dark:text-rose-400 font-mono">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1 font-mono">Phone / WhatsApp *</label>
                                <input type="text" wire:model="phone" placeholder="+92 300 1234567" class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm">
                                @error('phone') <span class="text-xs text-rose-600 dark:text-rose-400 font-mono">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1 font-mono">City</label>
                                <select wire:model="city" class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm">
                                    <option value="Lahore">Lahore</option>
                                    <option value="Islamabad">Islamabad</option>
                                    <option value="Karachi">Karachi</option>
                                    <option value="Rawalpindi">Rawalpindi</option>
                                    <option value="Faisalabad">Faisalabad</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1 font-mono">Tentative Event Date</label>
                                <input type="date" wire:model="eventDate" class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300 mb-1 font-mono">Event Details &amp; Requirements *</label>
                            <textarea wire:model="message" rows="4" placeholder="Mention guest count, preferred halls, catering preferences, or hall owner inquiry..." class="input-luxury dark:bg-[#171513] dark:text-white dark:border-white/20 text-sm"></textarea>
                            @error('message') <span class="text-xs text-rose-600 dark:text-rose-400 font-mono">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit" class="btn-gold !w-full !h-11 text-xs font-bold uppercase tracking-wider font-mono shadow-md">
                            Transmit Inquiry &rarr;
                        </button>
                    </form>
                @endif
            </div>

            <!-- Right: Direct WhatsApp & Office Locations (5 cols) -->
            <div class="lg:col-span-5 space-y-6">
                <!-- Direct WhatsApp Box -->
                <div class="bg-[#171513] text-white p-8 border border-[#C6A15B]/40 shadow-xl space-y-4">
                    <span class="text-[10px] uppercase font-bold tracking-[0.2em] text-[#C6A15B] block font-mono">Instant VIP Response</span>
                    <h3 class="font-serif-luxury text-2xl font-normal text-white">WhatsApp Direct Concierge</h3>
                    <p class="text-xs text-stone-300 font-light leading-relaxed">
                        For immediate shift checks, walk-in appointment scheduling, or urgent inquiries, message our 24/7 dedicated concierge desk.
                    </p>
                    <a href="https://wa.me/923008472910?text=Hello%20EventHub%20Concierge,%20I%20have%20an%20inquiry%20regarding%20wedding%20venue%20booking" 
                       target="_blank" 
                       class="btn-gold !w-full !h-11 text-xs font-bold uppercase flex items-center justify-center gap-2 font-mono shadow-md">
                        <span>Message on WhatsApp (+92 300 8472910)</span>
                    </a>
                </div>

                <!-- Office Locations -->
                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-6 space-y-4">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-stone-800 dark:text-white border-b border-[#E7E5E4] dark:border-white/10 pb-2 font-mono">
                        Regional Offices in Pakistan
                    </h4>

                    <div class="space-y-4 text-xs text-stone-600 dark:text-stone-300">
                        <div>
                            <strong class="text-stone-900 dark:text-[#C6A15B] block font-semibold">Lahore Flagship (Headquarters):</strong>
                            <span>Plot 42, Main Boulevard, Gulberg III, Lahore, Punjab</span>
                            <span class="block text-stone-500 dark:text-stone-400 text-[11px] font-mono">Phone: +92 42 35789000</span>
                        </div>

                        <div>
                            <strong class="text-stone-900 dark:text-[#C6A15B] block font-semibold">Islamabad Regional Office:</strong>
                            <span>Office 402, Beverly Centre, Blue Area, Islamabad</span>
                            <span class="block text-stone-500 dark:text-stone-400 text-[11px] font-mono">Phone: +92 51 2800112</span>
                        </div>

                        <div>
                            <strong class="text-stone-900 dark:text-[#C6A15B] block font-semibold">Karachi Corporate Desk:</strong>
                            <span>Executive Tower, Clifton Block 4, Karachi, Sindh</span>
                            <span class="block text-stone-500 dark:text-stone-400 text-[11px] font-mono">Phone: +92 21 35899000</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>
