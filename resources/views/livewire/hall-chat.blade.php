<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    <!-- Page Header & Luxury Breadcrumbs -->
    <div class="border-b border-[#E7E5E4] dark:border-white/10 pb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="space-y-2">
            <div class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.2em] text-[#8F7239] dark:text-[#C6A15B]">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                </span>
                <span>24/7 Virtual Concierge</span>
                <span>•</span>
                <span>Real-Time Inquiries</span>
            </div>
            <h1 class="font-serif-luxury text-2xl sm:text-3xl lg:text-4xl text-[#171513] dark:text-white font-normal">
                {{ $hall->name }} Concierge
            </h1>
            <p class="text-stone-600 dark:text-stone-300 text-xs sm:text-sm font-normal leading-relaxed">
                Instant answers regarding hall rent, catering menus, shift timings, and direct messages to the venue manager.
            </p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <a href="{{ route('hall.showcase', $hall->slug) }}" 
               class="px-4 py-2.5 rounded-xl border border-[#E7E5E4] dark:border-white/15 bg-white dark:bg-[#1a1714] text-[#171513] dark:text-white hover:border-[#C6A15B] text-xs font-semibold font-sans transition flex items-center gap-2">
                <span>&larr;</span>
                <span>Back to Venue</span>
            </a>
            <button onclick="Livewire.dispatch('openWalkInModal', { hall_id: '{{ $hall->id }}' })"
                    class="btn-gold !h-10 !px-5 text-xs font-bold font-sans tracking-wide">
                Reserve Shift &rarr;
            </button>
        </div>
    </div>

    @if($isHallOwner)
        <!-- ===================================================================
             HALL OWNER & DESK VIEW: Manage Incoming Guest Inquiries
             =================================================================== -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Left Column: Customer Conversations List -->
            <div class="bg-white dark:bg-[#1a1714] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-2xl p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-[#E7E5E4] dark:border-white/10">
                    <h3 class="font-serif-luxury text-lg text-[#171513] dark:text-white font-bold">
                        Guest Inquiries
                    </h3>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-[#C6A15B]/20 text-[#8F7239] dark:text-[#C6A15B] font-bold">
                        {{ count($chatParticipants) }} active
                    </span>
                </div>

                @if(count($chatParticipants) > 0)
                    <div class="space-y-2.5 max-h-[550px] overflow-y-auto pr-1">
                        @foreach($chatParticipants as $participant)
                            <button
                                wire:click="setSelectedCustomer('{{ $participant['ip_address'] }}', '{{ $participant['session_id'] }}')"
                                class="w-full text-left p-3.5 rounded-xl border transition-all duration-200 {{ $selectedCustomerIp === $participant['ip_address'] && $selectedCustomerSession === $participant['session_id'] ? 'bg-[#C6A15B]/15 border-[#C6A15B] shadow-sm' : 'border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#171513]/50 hover:border-[#C6A15B]/60 hover:bg-white dark:hover:bg-[#171513]' }}"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-mono font-bold text-[#171513] dark:text-white truncate">
                                        Client #{{ substr(md5($participant['session_id']), 0, 6) }}
                                    </span>
                                    <span class="text-[9px] px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold">
                                        {{ $participant['message_count'] }} msgs
                                    </span>
                                </div>
                                <div class="text-[11px] text-stone-500 dark:text-stone-400 truncate font-mono">
                                    IP: {{ $participant['ip_address'] }}
                                </div>
                                <div class="text-[10px] text-stone-400 dark:text-stone-500 mt-1 flex items-center justify-between">
                                    <span>{{ $participant['last_message_at'] ?? 'Recent' }}</span>
                                    <span class="text-[#C6A15B] font-semibold text-[10px]">Open &rarr;</span>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12 text-stone-400 space-y-2">
                        <div class="w-10 h-10 rounded-full bg-stone-100 dark:bg-white/5 flex items-center justify-center mx-auto text-lg text-stone-400">
                            ✉
                        </div>
                        <p class="text-xs">No guest messages yet.</p>
                        <p class="text-[10px] text-stone-500">Customer inquiries from the public showcase will appear here.</p>
                    </div>
                @endif
            </div>

            <!-- Right Column: Conversation Stream & Reply Console -->
            <div class="md:col-span-2 bg-white dark:bg-[#1a1714] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-2xl shadow-sm flex flex-col h-[650px] overflow-hidden">
                @if($selectedCustomerIp && $selectedCustomerSession)
                    <!-- Active Chat Header -->
                    <div class="p-4 border-b border-[#E7E5E4] dark:border-white/10 flex items-center justify-between bg-[#F8F5EF] dark:bg-[#171513]/50">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-[#C6A15B]/20 text-[#8F7239] dark:text-[#C6A15B] flex items-center justify-center font-bold text-xs">
                                👤
                            </div>
                            <div>
                                <h4 class="font-serif-luxury text-sm font-bold text-[#171513] dark:text-white">
                                    Guest Client Conversation
                                </h4>
                                <p class="text-[10px] text-stone-500 dark:text-stone-400 font-mono">
                                    Session: {{ substr($selectedCustomerSession, 0, 12) }}... • {{ $selectedCustomerIp }}
                                </p>
                            </div>
                        </div>
                        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-500 font-semibold">
                            Live Stream
                        </span>
                    </div>

                    <!-- Messages Window -->
                    <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-stone-50/50 dark:bg-[#141210]/50" id="chat-messages-scroll">
                        @foreach($messages as $msg)
                            <div class="flex {{ $msg['sender_type'] === 'hall_owner' ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[85%] rounded-2xl p-3.5 shadow-sm space-y-1.5
                                    @if($msg['sender_type'] === 'hall_owner')
                                        bg-[#C6A15B] text-[#171513] font-medium rounded-tr-none
                                    @elseif($msg['sender_type'] === 'bot')
                                        bg-[#171513] text-stone-200 border border-[#C6A15B]/30 rounded-tl-none
                                    @else
                                        bg-white dark:bg-[#1f1b17] text-[#171513] dark:text-white border border-[#E7E5E4] dark:border-white/10 rounded-tl-none
                                    @endif
                                ">
                                    <div class="flex items-center justify-between gap-3 text-[10px] opacity-75 font-mono">
                                        <span class="font-bold">
                                            @if($msg['sender_type'] === 'hall_owner')
                                                ★ You (Venue Manager)
                                            @elseif($msg['sender_type'] === 'bot')
                                                🤖 Virtual AI Concierge
                                            @else
                                                👤 Customer
                                            @endif
                                        </span>
                                        <span>{{ $msg['created_at'] }}</span>
                                    </div>

                                    @if($msg['message_type'] === 'voice')
                                        <div class="pt-1">
                                            <audio controls class="w-full h-8" style="max-width: 260px;">
                                                <source src="{{ Storage::url($msg['voice_file']) }}" type="audio/mpeg">
                                                Your browser does not support audio playback.
                                            </audio>
                                        </div>
                                    @else
                                        <div class="text-xs sm:text-sm leading-relaxed whitespace-pre-line">
                                            {!! nl2br(e($msg['message'])) !!}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Reply Form -->
                    <div class="p-3.5 border-t border-[#E7E5E4] dark:border-white/10 bg-white dark:bg-[#1a1714]">
                        <form wire:submit.prevent="sendMessage" class="flex gap-2">
                            <input type="text"
                                   wire:model="newMessage"
                                   placeholder="Reply directly to this client as venue manager..."
                                   class="flex-1 px-4 py-2.5 rounded-xl border border-[#E7E5E4] dark:border-white/15 bg-[#F8F5EF] dark:bg-[#171513] text-[#171513] dark:text-white focus:border-[#C6A15B] outline-none text-xs sm:text-sm transition">
                            <button type="submit"
                                    class="btn-gold !h-10 !px-5 text-xs font-bold rounded-xl whitespace-nowrap">
                                Send Reply
                            </button>
                        </form>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center h-full text-center p-8 text-stone-400 space-y-3">
                        <div class="w-14 h-14 rounded-full bg-[#C6A15B]/15 text-[#C6A15B] flex items-center justify-center text-2xl">
                            💬
                        </div>
                        <h4 class="font-serif-luxury text-lg text-[#171513] dark:text-white font-normal">
                            Select a Guest Conversation
                        </h4>
                        <p class="text-xs text-stone-500 max-w-sm">
                            Click on any client session on the left panel to review inquiry history or send a direct reply.
                        </p>
                    </div>
                @endif
            </div>
        </div>

    @else
        <!-- ===================================================================
             PUBLIC & CUSTOMER VIEW: Luxury AI Concierge Chatbot
             =================================================================== -->
        <div class="bg-white dark:bg-[#1a1714] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-2xl shadow-xl flex flex-col h-[650px] overflow-hidden">
            
            <!-- Chat Window Top Bar -->
            <div class="p-4 sm:p-5 border-b border-[#E7E5E4] dark:border-white/10 flex items-center justify-between bg-gradient-to-r from-[#F8F5EF] to-white dark:from-[#171513] dark:to-[#1a1714]">
                <div class="flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-full bg-[#171513] border border-[#C6A15B]/50 flex items-center justify-center text-[#C6A15B] shadow-md shadow-[#C6A15B]/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-serif-luxury text-base sm:text-lg text-[#171513] dark:text-white font-bold leading-tight">
                            {{ $hall->name }} AI Concierge
                        </h3>
                        <p class="text-[11px] text-stone-500 dark:text-stone-400 font-sans flex items-center gap-1.5 mt-0.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Online • Instant Automated Intelligence &amp; Live Desk</span>
                        </p>
                    </div>
                </div>

                <div class="hidden sm:flex items-center gap-2">
                    <span class="text-[10px] font-mono px-2.5 py-1 rounded-full bg-[#C6A15B]/15 text-[#8F7239] dark:text-[#C6A15B] font-bold uppercase tracking-wider">
                        24/7 Virtual Assistant
                    </span>
                </div>
            </div>

            <!-- Messages Stream Area -->
            <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4 bg-stone-50/60 dark:bg-[#141210]/60" id="chat-stream">
                
                <!-- Initial Welcome Card from AI Concierge -->
                <div class="flex justify-start">
                    <div class="max-w-[88%] sm:max-w-[80%] rounded-2xl rounded-tl-none p-4 sm:p-5 bg-[#171513] text-[#F8F5EF] border border-[#C6A15B]/35 shadow-lg space-y-3">
                        <div class="flex items-center gap-2 text-[10px] text-[#C6A15B] font-mono font-bold uppercase tracking-widest">
                            <span>✨ Welcome to {{ $hall->name }}</span>
                        </div>
                        <p class="text-xs sm:text-sm leading-relaxed text-stone-200">
                            Assalamu Alaikum! I am your <strong>24/7 AI Concierge</strong>. I can provide instant, verified information about hall rental rates, royal catering menus, seating capacity, or shift availability.
                        </p>
                        <p class="text-[11px] text-stone-400 leading-relaxed">
                            Select a quick question below or type your inquiry in Urdu or English:
                        </p>
                    </div>
                </div>

                <!-- Live Conversation Messages -->
                @foreach($messages as $msg)
                    <div class="flex {{ $msg['sender_type'] === 'customer' ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[88%] sm:max-w-[80%] rounded-2xl p-4 shadow-sm space-y-1.5
                            @if($msg['sender_type'] === 'customer')
                                bg-[#C6A15B] text-[#171513] font-medium rounded-tr-none shadow-md shadow-[#C6A15B]/20
                            @elseif($msg['sender_type'] === 'bot')
                                bg-[#171513] text-stone-200 border border-[#C6A15B]/35 rounded-tl-none shadow-lg
                            @else
                                bg-emerald-950/80 text-emerald-100 border border-emerald-500/40 rounded-tl-none shadow-lg
                            @endif
                        ">
                            <div class="flex items-center justify-between gap-4 text-[10px] font-mono opacity-80 pb-1 border-b border-black/10 dark:border-white/10">
                                <span class="font-bold uppercase tracking-wider">
                                    @if($msg['sender_type'] === 'customer')
                                        You
                                    @elseif($msg['sender_type'] === 'bot')
                                        ✨ AI Concierge Bot
                                    @else
                                        🏛️ Venue Manager
                                    @endif
                                </span>
                                <span>{{ $msg['created_at'] }}</span>
                            </div>

                            @if($msg['message_type'] === 'voice')
                                <div class="pt-1.5">
                                    <audio controls class="w-full h-8" style="max-width: 260px;">
                                        <source src="{{ Storage::url($msg['voice_file']) }}" type="audio/mpeg">
                                        Your browser does not support audio playback.
                                    </audio>
                                </div>
                            @else
                                <div class="text-xs sm:text-sm leading-relaxed whitespace-pre-line">
                                    {!! nl2br(e($msg['message'])) !!}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

            </div>

            <!-- Quick Action Suggestion Chips -->
            <div class="px-4 py-2 bg-stone-100 dark:bg-[#171513] border-t border-[#E7E5E4] dark:border-white/10 overflow-x-auto flex items-center gap-2 text-xs no-scrollbar">
                <span class="text-[10px] uppercase font-mono tracking-wider font-bold text-stone-400 shrink-0">
                    Quick Inquiries:
                </span>
                <button type="button"
                        wire:click="askQuickQuestion('What is the hall rent and guest capacity?')"
                        class="px-3 py-1.5 rounded-full border border-[#C6A15B]/40 hover:border-[#C6A15B] bg-white dark:bg-[#1a1714] text-[#171513] dark:text-[#F8F5EF] text-[11px] font-medium whitespace-nowrap transition hover:-translate-y-0.5 shadow-sm">
                    🏛️ Hall Rent &amp; Capacity
                </button>
                <button type="button"
                        wire:click="askQuickQuestion('What royal catering packages and menus do you offer?')"
                        class="px-3 py-1.5 rounded-full border border-[#C6A15B]/40 hover:border-[#C6A15B] bg-white dark:bg-[#1a1714] text-[#171513] dark:text-[#F8F5EF] text-[11px] font-medium whitespace-nowrap transition hover:-translate-y-0.5 shadow-sm">
                    🍽️ Catering Packages
                </button>
                <button type="button"
                        wire:click="askQuickQuestion('What are your shift timings and how can I check open dates?')"
                        class="px-3 py-1.5 rounded-full border border-[#C6A15B]/40 hover:border-[#C6A15B] bg-white dark:bg-[#1a1714] text-[#171513] dark:text-[#F8F5EF] text-[11px] font-medium whitespace-nowrap transition hover:-translate-y-0.5 shadow-sm">
                    📅 Shift Timings &amp; Booking
                </button>
                <button type="button"
                        wire:click="askQuickQuestion('Do you have 100% generator backup and a bridal suite?')"
                        class="px-3 py-1.5 rounded-full border border-[#C6A15B]/40 hover:border-[#C6A15B] bg-white dark:bg-[#1a1714] text-[#171513] dark:text-[#F8F5EF] text-[11px] font-medium whitespace-nowrap transition hover:-translate-y-0.5 shadow-sm">
                    ⚡ Generator &amp; Facilities
                </button>
                <button type="button"
                        wire:click="askQuickQuestion('I want to speak with the venue manager')"
                        class="px-3 py-1.5 rounded-full border border-[#C6A15B]/40 hover:border-[#C6A15B] bg-white dark:bg-[#1a1714] text-[#171513] dark:text-[#F8F5EF] text-[11px] font-medium whitespace-nowrap transition hover:-translate-y-0.5 shadow-sm">
                    📞 Request Call-back
                </button>
            </div>

            <!-- Message Input Console -->
            <div class="p-3.5 sm:p-4 border-t border-[#E7E5E4] dark:border-white/10 bg-white dark:bg-[#1a1714]">
                <form wire:submit.prevent="sendMessage" class="flex items-center gap-2.5">
                    <div class="relative flex-1">
                        <input type="text"
                               wire:model="newMessage"
                               placeholder="Ask about rent, menus, open dates, or leave your phone number..."
                               class="w-full pl-4 pr-10 py-3 rounded-xl border border-[#E7E5E4] dark:border-white/15 bg-[#F8F5EF] dark:bg-[#171513] text-[#171513] dark:text-white placeholder-stone-400 focus:border-[#C6A15B] focus:ring-1 focus:ring-[#C6A15B] outline-none text-xs sm:text-sm transition">
                    </div>

                    <!-- Voice Note Upload -->
                    <label class="cursor-pointer p-2.5 sm:p-3 rounded-xl border border-[#E7E5E4] dark:border-white/15 bg-[#F8F5EF] dark:bg-[#171513] text-stone-500 hover:text-[#C6A15B] hover:border-[#C6A15B] transition shrink-0"
                           title="Upload Voice Message">
                        <input type="file" wire:model="voiceRecording" accept="audio/*" class="hidden">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                        </svg>
                    </label>

                    <!-- Send Button -->
                    <button type="submit"
                            wire:loading.attr="disabled"
                            class="btn-gold !h-11 !px-5 sm:!px-6 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 shrink-0">
                        <span wire:loading.remove>Send</span>
                        <span wire:loading class="animate-spin text-xs">↻</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                </form>

                <div class="flex items-center justify-between text-[10px] text-stone-400 dark:text-stone-500 mt-2 px-1">
                    <span>💡 Powered by EventHub Automated Concierge • 24/7 Availability</span>
                    <span>Messages are delivered directly to {{ $hall->name }} front desk</span>
                </div>
            </div>

        </div>
    @endif

</div>

<script>
    function scrollChatToBottom() {
        const stream = document.getElementById('chat-stream');
        if (stream) {
            stream.scrollTop = stream.scrollHeight;
        }
        const managerStream = document.getElementById('chat-messages-scroll');
        if (managerStream) {
            managerStream.scrollTop = managerStream.scrollHeight;
        }
    }

    document.addEventListener('DOMContentLoaded', scrollChatToBottom);
    window.addEventListener('livewire-navigated', scrollChatToBottom);
    window.addEventListener('messageSent', () => {
        setTimeout(scrollChatToBottom, 80);
    });
</script>
