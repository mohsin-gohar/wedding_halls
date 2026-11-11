<div class="py-12 bg-[#F8F5EF] dark:bg-[#171513] text-[#171513] dark:text-[#F8F5EF] min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">

        <!-- Page Header -->
        <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-8 space-y-3">
            <div class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.2em] text-[#8F7239]">
                <span>Knowledge Base</span>
                <span>•</span>
                <span>Policies &amp; Booking Guidelines</span>
            </div>
            <h1 class="font-serif-luxury text-4xl sm:text-5xl text-[#171513] dark:text-white font-normal">
                Frequently Asked Questions
            </h1>
            <p class="text-stone-600 dark:text-stone-300 text-sm font-normal leading-relaxed">
                Everything you need to know about wedding hall reservations, catering formulas, shift hours, and payment terms in Pakistan.
            </p>
        </div>

        <!-- FAQs Accordions (Zero-Pill Minimalist) -->
        <div class="space-y-4">
            <!-- FAQ 1 -->
            <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 rounded-2xl p-6 sm:p-7 shadow-sm space-y-2">
                <h3 class="font-serif-luxury text-xl sm:text-2xl text-[#171513] dark:text-white font-medium" style="text-wrap: balance;">
                    1. How does Riwaj Grand guarantee zero double-bookings?
                </h3>
                <p class="text-xs sm:text-sm text-stone-600 dark:text-stone-300 leading-relaxed font-normal" style="text-wrap: pretty;">
                    When you initiate a booking, Riwaj Grand creates a distributed cache mutex lock in memory and locks the database row. If another user attempts to book the same hall on the same date and shift, our system immediately rejects the conflict. Confirmed reservations are atomically committed to our ACID database.
                </p>
            </div>

            <!-- FAQ 2 -->
            <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 rounded-2xl p-6 sm:p-7 shadow-sm space-y-2">
                <h3 class="font-serif-luxury text-xl sm:text-2xl text-[#171513] dark:text-white font-medium" style="text-wrap: balance;">
                    2. What are the standard event shift hours in Pakistan?
                </h3>
                <p class="text-xs sm:text-sm text-stone-600 dark:text-stone-300 leading-relaxed font-normal" style="text-wrap: pretty;">
                    Our platform supports three primary operating shifts:
                    <br>&bull; <strong>Afternoon Lunch:</strong> 12:00 PM to 4:00 PM (Ideal for Nikkah and lunch receptions).
                    <br>&bull; <strong>Evening Dinner:</strong> 6:30 PM to 10:30 PM (Compliant with provincial Punjab/Sindh 10:00 PM banqueting curfew guidelines).
                    <br>&bull; <strong>Late Night:</strong> 11:00 PM to 3:00 AM (Reserved for private indoor Mehndi, Sangeet, and Qawwali functions).
                </p>
            </div>

            <!-- FAQ 3 -->
            <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 rounded-2xl p-6 sm:p-7 shadow-sm space-y-2">
                <h3 class="font-serif-luxury text-xl sm:text-2xl text-[#171513] dark:text-white font-medium" style="text-wrap: balance;">
                    3. What are the deposit requirements and payment schedules?
                </h3>
                <p class="text-xs sm:text-sm text-stone-600 dark:text-stone-300 leading-relaxed font-normal" style="text-wrap: pretty;">
                    A minimum token advance of PKR 50,000 to PKR 200,000 (depending on the venue's tier) is required upon reservation. A 50% second installment is payable 15 days before the event, and the final remaining balance must be cleared 48 hours prior to function commencement. Official thermal tax receipts and legal contracts are generated automatically.
                </p>
            </div>

            <!-- FAQ 4 -->
            <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 rounded-2xl p-6 sm:p-7 shadow-sm space-y-2">
                <h3 class="font-serif-luxury text-xl sm:text-2xl text-[#171513] dark:text-white font-medium" style="text-wrap: balance;">
                    4. Are electrical generator and diesel costs included in the rental price?
                </h3>
                <p class="text-xs sm:text-sm text-stone-600 dark:text-stone-300 leading-relaxed font-normal" style="text-wrap: pretty;">
                    Most marquee venues provide base generator backup during WAPDA load-shedding as part of the package. Some premium venues log exact generator run-hours and diesel consumption (e.g. at PKR 280/liter) transparently on your invoice. Check individual hall specifications on the venue details page.
                </p>
            </div>

            <!-- FAQ 5 -->
            <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 rounded-2xl p-6 sm:p-7 shadow-sm space-y-2">
                <h3 class="font-serif-luxury text-xl sm:text-2xl text-[#171513] dark:text-white font-medium" style="text-wrap: balance;">
                    5. How can wedding hall and marquee owners register their property?
                </h3>
                <p class="text-xs sm:text-sm text-stone-600 dark:text-stone-300 leading-relaxed font-normal" style="text-wrap: pretty;">
                    Banquet and marquee owners can sign up directly via our <a href="{{ route('register') }}" class="text-[#8F7239] dark:text-[#C6A15B] font-bold hover:underline">Free Venue Registration</a> form in under 2 minutes. Fill in your basic venue details, capacity, and city to begin.
                </p>
            </div>

            <!-- FAQ 6 -->
            <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 rounded-2xl p-6 sm:p-7 shadow-sm space-y-2">
                <h3 class="font-serif-luxury text-xl sm:text-2xl text-[#171513] dark:text-white font-medium" style="text-wrap: balance;">
                    6. Is listing my hall on Riwaj Grand free? When does it go live?
                </h3>
                <p class="text-xs sm:text-sm text-stone-600 dark:text-stone-300 leading-relaxed font-normal" style="text-wrap: pretty;">
                    Yes! During our current nationwide launch phase across Pakistan, registering and listing your hall on Riwaj Grand is <strong>100% free of cost</strong> — with zero monthly rent, zero subscription fees, and zero listing commission ("Riwayat bhi, Jiddat bhi"). Once you submit your registration, our administration team reviews your details. <strong>The moment your account is approved by admin, your hall profile and availability calendar go LIVE on our public marketplace immediately</strong> for couples to explore, check available dates, and submit booking inquiries.
                </p>
            </div>
        </div>

    </div>
</div>
