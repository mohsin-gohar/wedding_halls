<div id="eventhub-public-ai-chatbot"
     x-data="{
         open: false,
         init() {
             window.addEventListener('publicChatOpened', () => {
                 this.open = true;
                 setTimeout(scrollPublicChat, 100);
             });
             window.addEventListener('publicChatUpdated', () => {
                 setTimeout(scrollPublicChat, 80);
             });
         },
         openChat() {
             this.open = true;
             setTimeout(scrollPublicChat, 100);
         },
         closeChat() {
             this.open = false;
         }
     }"
     class="font-sans">

    <!-- =====================================================================
         STATE 1: FLOATING CHAT BUTTON (When Closed - Pure Elegant Chat Bubble)
         z-30 ensures it never collides or creeps above the sticky navbar (z-40)
         ===================================================================== -->
    <button type="button"
            x-show="!open"
            @click="openChat()"
            aria-label="Open Riwaj Grand AI Chatbot"
            title="Riwaj Grand AI Chatbot"
            class="fixed bottom-5 right-5 sm:bottom-6 sm:right-6 z-30 w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-gradient-to-tr from-[#C6A15B] via-[#D8B46B] to-[#F2DE9A] text-[#171513] shadow-[0_12px_32px_rgba(198,161,91,0.6)] hover:shadow-[0_16px_40px_rgba(198,161,91,0.8)] hover:scale-110 active:scale-95 transition-all duration-300 flex items-center justify-center cursor-pointer border-2 border-white/70 dark:border-[#F2DE9A]/50 group">
        
        <!-- Friendly Chatbot Robot Icon -->
        <svg class="w-7 h-7 sm:w-8 sm:h-8 text-[#171513] group-hover:rotate-6 transition-transform" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2a2 2 0 012 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 017 7h1a1 1 0 011 1v3a1 1 0 01-1 1h-1v1a2 2 0 01-2 2H5a2 2 0 01-2-2v-1H2a1 1 0 01-1-1v-3a1 1 0 011-1h1a7 7 0 017-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 012-2M7.5 13A1.5 1.5 0 006 14.5 1.5 1.5 0 007.5 16 1.5 1.5 0 009 14.5 1.5 1.5 0 007.5 13m9 0a1.5 1.5 0 00-1.5 1.5 1.5 1.5 0 001.5 1.5 1.5 1.5 0 001.5-1.5 1.5 1.5 0 00-1.5-1.5z"/>
        </svg>

        <!-- Pulsing Active Status Indicator -->
        <span class="absolute top-1 right-1 flex h-3.5 w-3.5">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500 border-2 border-white dark:border-[#171513]"></span>
        </span>
    </button>

    <!-- =====================================================================
         STATE 2: CHATBOT POPUP WINDOW (Matching Reference Screenshot)
         z-30 + max-h-[calc(100vh-5.5rem)] strictly prevents it from ever climbing over or covering the navbar!
         ===================================================================== -->
    <div x-show="open"
         x-cloak
         style="display: none;"
         class="fixed inset-x-3 bottom-3 sm:inset-x-auto sm:bottom-6 sm:right-6 z-30 w-auto sm:w-[410px] h-[78vh] sm:h-[550px] max-h-[calc(100vh-5.5rem)] bg-white dark:bg-[#1A1714] rounded-2xl shadow-[0_20px_60px_rgba(0,0,0,0.5)] border-2 border-[#C6A15B] flex flex-col overflow-hidden">
        
        <!-- Header (Directly Inspired by Reference Screenshot) -->
        <div class="bg-gradient-to-r from-[#C6A15B] via-[#B89241] to-[#9A7836] p-3.5 sm:p-4 text-white flex items-center justify-between shadow-md shrink-0">
            <div class="flex items-center gap-3">
                <!-- White Circular Robot Icon Avatar -->
                <div class="w-10 h-10 rounded-full bg-white text-[#C6A15B] flex items-center justify-center shadow-md shrink-0">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2a2 2 0 012 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 017 7h1a1 1 0 011 1v3a1 1 0 01-1 1h-1v1a2 2 0 01-2 2H5a2 2 0 01-2-2v-1H2a1 1 0 01-1-1v-3a1 1 0 011-1h1a7 7 0 017-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 012-2M7.5 13A1.5 1.5 0 006 14.5 1.5 1.5 0 007.5 16 1.5 1.5 0 009 14.5 1.5 1.5 0 007.5 13m9 0a1.5 1.5 0 00-1.5 1.5 1.5 1.5 0 001.5 1.5 1.5 1.5 0 001.5-1.5 1.5 1.5 0 00-1.5-1.5z"/>
                    </svg>
                </div>
                <div>
                    <!-- "Hello!" replaced by "Riwaj Grand Chatbot" -->
                    <h4 class="font-bold text-base sm:text-lg leading-tight text-white tracking-wide">Riwaj Grand Chatbot</h4>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-300 animate-pulse"></span>
                        <p class="text-[11px] text-white/95 font-medium">Ask Riwaj Grand AI • Always Online</p>
                    </div>
                </div>
            </div>

            <!-- Action Icons: Refresh & Close (Screenshot Top Right) -->
            <div class="flex items-center gap-1">
                <button type="button"
                        wire:click="resetConversation"
                        title="Reset conversation"
                        class="w-8 h-8 rounded-full hover:bg-white/20 text-white flex items-center justify-center transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </button>
                <button type="button"
                        @click="closeChat()"
                        title="Close chatbot"
                        class="w-8 h-8 rounded-full hover:bg-white/20 text-white flex items-center justify-center transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Messages Stream Area -->
        <div class="flex-1 min-h-0 overflow-y-auto p-4 space-y-3.5 bg-stone-50/90 dark:bg-[#141210]/95 text-xs" 
             id="eventhub-public-chat-stream">
            
            <!-- Quick Suggestion Action Chips -->
            <div class="pb-1">
                <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500 dark:text-stone-400 block mb-2 font-mono">Suggested Inquiries:</span>
                <div class="flex flex-wrap gap-1.5">
                    <button type="button" 
                            wire:click="askQuickQuestion('Top verified venues in Lahore')"
                            class="px-2.5 py-1 rounded-full bg-white dark:bg-[#1f1b17] border border-[#C6A15B]/40 hover:border-[#C6A15B] text-[11px] text-[#171513] dark:text-stone-200 transition shadow-xs cursor-pointer">
                        🏛️ Top Venues
                    </button>
                    <button type="button" 
                            wire:click="askQuickQuestion('Shift timings aur single-host exclusivity kya hai?')"
                            class="px-2.5 py-1 rounded-full bg-white dark:bg-[#1f1b17] border border-[#C6A15B]/40 hover:border-[#C6A15B] text-[11px] text-[#171513] dark:text-stone-200 transition shadow-xs cursor-pointer">
                        📅 Shift Timings
                    </button>
                    <button type="button" 
                            wire:click="askQuickQuestion('Catering packages aur per head rate kya hai?')"
                            class="px-2.5 py-1 rounded-full bg-white dark:bg-[#1f1b17] border border-[#C6A15B]/40 hover:border-[#C6A15B] text-[11px] text-[#171513] dark:text-stone-200 transition shadow-xs cursor-pointer">
                        🍽️ Catering Menu
                    </button>
                    <button type="button" 
                            wire:click="askQuickQuestion('Apna wedding hall kaise register karein?')"
                            class="px-2.5 py-1 rounded-full bg-white dark:bg-[#1f1b17] border border-[#C6A15B]/40 hover:border-[#C6A15B] text-[11px] text-[#171513] dark:text-stone-200 transition shadow-xs cursor-pointer">
                        📝 Register Hall
                    </button>
                </div>
            </div>

            <!-- Messages Conversation -->
            @foreach($messages as $msg)
                <div class="flex {{ $msg['sender'] === 'user' ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[85%] rounded-2xl p-3.5 shadow-sm space-y-1.5
                        @if($msg['sender'] === 'user')
                            bg-gradient-to-r from-[#C6A15B] to-[#B38E46] text-[#171513] font-medium rounded-tr-none
                        @else
                            bg-white dark:bg-[#1c1917] text-[#171513] dark:text-stone-100 border border-[#C6A15B]/30 rounded-tl-none shadow-md
                        @endif
                    ">
                        <div class="flex items-center justify-between gap-3 text-[9px] font-mono opacity-75">
                            <span class="font-bold uppercase tracking-wider">
                                @if($msg['sender'] === 'user')
                                    You (Guest)
                                @else
                                    🤖 Riwaj Grand AI Concierge
                                @endif
                            </span>
                            <span>{{ $msg['time'] }}</span>
                        </div>

                        <div class="leading-relaxed whitespace-pre-line text-[11px] sm:text-xs">
                            {!! nl2br(e($msg['message'])) !!}
                        </div>
                    </div>
                </div>
            @endforeach

        </div>

        <!-- Footer: Text Input Form (Matching Reference Screenshot) -->
        <div class="p-3 bg-white dark:bg-[#1A1714] border-t border-[#E7E5E4] dark:border-white/10 shrink-0">
            <form wire:submit.prevent="sendMessage" class="flex items-center gap-2">
                <input type="text"
                       wire:model="newMessage"
                       placeholder="Type your message here..."
                       class="flex-1 px-3.5 py-2.5 rounded-xl border border-stone-300 dark:border-white/20 bg-stone-50 dark:bg-[#171513] text-[#171513] dark:text-white placeholder-stone-400 focus:border-[#C6A15B] focus:ring-1 focus:ring-[#C6A15B] outline-none text-xs transition">

                <!-- Orange/Gold Paper Airplane Send Button (Matching Screenshot) -->
                <button type="submit"
                        wire:loading.attr="disabled"
                        class="w-10 h-10 rounded-xl bg-gradient-to-r from-[#C6A15B] to-[#A88236] text-[#171513] hover:brightness-110 flex items-center justify-center shadow-md shadow-[#C6A15B]/30 shrink-0 transition cursor-pointer"
                        title="Send message">
                    <svg class="w-4 h-4 transform rotate-45 -translate-y-0.5 translate-x-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/>
                    </svg>
                </button>
            </form>
        </div>

    </div>

</div>

<script>
    function scrollPublicChat() {
        const stream = document.getElementById('eventhub-public-chat-stream');
        if (stream) {
            stream.scrollTo({ top: stream.scrollHeight, behavior: 'smooth' });
        }
    }
</script>
