<div class="py-12 bg-[#F8F5EF] dark:bg-[#171513] text-[#171513] dark:text-[#F8F5EF] min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">

        <!-- Header -->
        <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-8 space-y-3">
            <div class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.2em] text-[#8F7239]">
                <span>Legal &amp; Compliance</span>
                <span>•</span>
                <span>Governed Under Laws of Pakistan</span>
            </div>
            <h1 class="font-serif-luxury text-4xl sm:text-5xl text-[#171513] dark:text-white font-normal">
                @if($section === 'privacy')
                    Privacy &amp; Data Protection Policy
                @elseif($section === 'cancellation')
                    Cancellation, Rescheduling &amp; Refund Policy
                @else
                    Platform Terms of Service
                @endif
            </h1>
            <p class="text-stone-600 dark:text-stone-400 text-xs font-mono uppercase tracking-wider">
                Last updated: October 2026 • Effective for all Riwaj Grand customers and hall operators
            </p>
        </div>

        <!-- Section Navigation Tabs -->
        <div class="flex items-center gap-3 border-b border-[#E7E5E4] dark:border-white/10 pb-4 text-xs font-bold uppercase tracking-wider">
            <a href="{{ route('public.policies.terms') }}" class="px-3.5 py-1.5 transition {{ $section === 'terms' ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513]' : 'bg-white dark:bg-[#171513] text-stone-700 dark:text-stone-300 border border-[#E7E5E4] dark:border-white/10 hover:bg-[#F8F5EF] dark:hover:bg-white/10' }}">
                Terms of Service
            </a>
            <a href="{{ route('public.policies.privacy') }}" class="px-3.5 py-1.5 transition {{ $section === 'privacy' ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513]' : 'bg-white dark:bg-[#171513] text-stone-700 dark:text-stone-300 border border-[#E7E5E4] dark:border-white/10 hover:bg-[#F8F5EF] dark:hover:bg-white/10' }}">
                Privacy Policy
            </a>
            <a href="{{ route('public.policies.cancellation') }}" class="px-3.5 py-1.5 transition {{ $section === 'cancellation' ? 'bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513]' : 'bg-white dark:bg-[#171513] text-stone-700 dark:text-stone-300 border border-[#E7E5E4] dark:border-white/10 hover:bg-[#F8F5EF] dark:hover:bg-white/10' }}">
                Cancellation &amp; Refund Policy
            </a>
        </div>

        <!-- Content Body (Clean Editorial Zero-Pill Paper) -->
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-8 sm:p-12 shadow-sm text-xs sm:text-sm text-stone-700 dark:text-stone-300 space-y-6 leading-relaxed">
            @if($section === 'privacy')
                <div class="space-y-4">
                    <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold">1. Information Collection and Privacy</h3>
                    <p>
                        Riwaj Grand Technologies (Pvt) Ltd ("Riwaj Grand" — "Riwayat bhi, Jiddat bhi") respects the privacy of our customers and partner wedding venues. We collect personal contact details (name, CNIC number, phone number, email address) solely for the purpose of booking confirmation, legal contract generation, guest RSVP checklists, and payment ledger maintenance.
                    </p>
                    <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold">2. CNIC &amp; Guest Privacy</h3>
                    <p>
                        Under Pakistani municipal banqueting regulations, the CNIC of the primary booking host is required for security records and local law enforcement compliance. We do not sell, rent, or disclose this data to third-party marketing brokers.
                    </p>
                    <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold">3. Multi-Tenant Data Segregation</h3>
                    <p>
                        All vendor financial data, customer identities, and contract agreements are isolated via strict database tenant boundaries. Venue operators can only inspect their own booked events and cannot access competitor data.
                    </p>
                    <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold">4. Free Registration Data &amp; Public Showcase Notice</h3>
                    <p>
                        Hall profiles, hall capacity, addresses, photos, and shift pricing submitted during free vendor registration are used strictly to showcase your venue on Riwaj Grand's public discovery marketplace once approved by administration. No hidden listing fees or rent are charged for public listing.
                    </p>
                </div>
            @elseif($section === 'cancellation')
                <div class="space-y-4">
                    <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold">1. Token Advance &amp; Deposit Terms</h3>
                    <p>
                        Initial token advances (usually 20% to 30% of base rental) secure the shift exclusively on the venue calendar. Once confirmed, this slot is blocked against all other inquiring parties.
                    </p>
                    <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold">2. Cancellation Notice Schedule</h3>
                    <p>
                        &bull; <strong>Notice given 45+ days prior:</strong> 70% of advance deposit is refundable or fully creditable to an alternative available date within 6 calendar months.
                        <br>&bull; <strong>Notice given 15 to 44 days prior:</strong> 40% of advance deposit is refundable.
                        <br>&bull; <strong>Notice given under 14 days prior:</strong> Token advance is forfeited to cover reserved culinary ingredients and blocked marquee opportunity costs.
                    </p>
                    <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold">3. Force Majeure &amp; Weather Contingencies</h3>
                    <p>
                        In the event of unpredicted natural disasters, governmental curbs, or extreme weather conditions affecting open-sky lawns, Riwaj Grand venues provide complimentary shift rescheduling to indoor banquet halls or subsequent open dates without cancellation penalties.
                    </p>
                </div>
            @else
                <div class="space-y-4">
                    <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold">1. 100% Free Launch Onboarding &amp; Zero Platform Rent</h3>
                    <p>
                        During this launch phase across Pakistan, <strong>Riwaj Grand is completely 100% free of charge for banquet, marquee, and wedding hall owners</strong>. We charge <strong>zero platform rent, zero monthly subscription fees, and zero listing charges</strong>. Any venue owner can sign up and utilize the complete management suite without any payment obligation.
                    </p>
                    <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold">2. Admin Verification &amp; Instant Live Publication</h3>
                    <p>
                        When a wedding hall completes free registration, its credentials undergo rapid verification by our administrative moderation team. <strong>The moment the Super Admin approves the venue, the hall profile, high-resolution photo galleries, availability calendar, and instant booking inquiry channels go LIVE immediately on the public website and nationwide marketplace</strong>, making it directly accessible to thousands of wedding couples and families across Pakistan.
                    </p>
                    <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold">3. Agreement to Platform Terms</h3>
                    <p>
                        By using the Riwaj Grand marketplace or SaaS platform, clients and venue operators agree to adhere to these Terms of Service. All monetary amounts are quoted authoritatively in Pakistani Rupees (PKR) and are subject to applicable provincial sales tax on services (PRA in Punjab, SRB in Sindh, ICT in Islamabad).
                    </p>
                    <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold">4. Concurrency &amp; Booking Validity</h3>
                    <p>
                        A booking reservation is only officially confirmed once the token advance has been recorded and an official Riwaj Grand receipt number is issued. Online holds expire automatically after the designated window if unconfirmed.
                    </p>
                    <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold">5. Operating Shift Compliance</h3>
                    <p>
                        Events must conform to municipal operating regulations, including Punjabi Banquets One-Dish legislation and standard municipal closing times. Hall managers hold authority to enforce these requirements.
                    </p>
                </div>
            @endif
        </div>

    </div>
</div>
