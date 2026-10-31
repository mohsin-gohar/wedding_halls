<div id="venue-concierge-chat-widget" 
     x-data="{ 
         open: @js($isOpen),
         isRecording: false,
         isUploading: false,
         timer: '00:00',
         seconds: 0,
         mediaRecorder: null,
         audioChunks: [],
         interval: null,
         pollInterval: null,

         init() {
             this.$watch('open', (value) => {
                 if (value) {
                     if (!this.pollInterval) {
                         this.pollInterval = setInterval(() => {
                             if (this.open && !document.hidden) {
                                 this.$wire.loadConversation();
                             }
                         }, 8000);
                     }
                 } else if (this.pollInterval) {
                     clearInterval(this.pollInterval);
                     this.pollInterval = null;
                 }
             });

             window.addEventListener('chatOpened', () => { 
                 this.open = true; 
                 setTimeout(scrollVenueChat, 100); 
             });

             window.addEventListener('chatClosed', () => { 
                 this.open = false; 
             });

             window.addEventListener('openVenueChat', () => { 
                 this.open = true;
                 setTimeout(scrollVenueChat, 100); 
             });

             window.addEventListener('toggleChat', () => { 
                 this.open = !this.open;
                 if (this.open) setTimeout(scrollVenueChat, 100);
             });
         },

         async startRecording() {
             if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                 alert('Microphone recording is not supported on this browser or connection.');
                 return;
             }

             try {
                 const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                 this.audioChunks = [];
                 this.mediaRecorder = new MediaRecorder(stream);

                 this.mediaRecorder.ondataavailable = (event) => {
                     if (event.data && event.data.size > 0) {
                         this.audioChunks.push(event.data);
                     }
                 };

                 this.mediaRecorder.onstop = () => {
                     stream.getTracks().forEach(track => track.stop());

                     if (this.audioChunks.length === 0) {
                         this.isRecording = false;
                         return;
                     }

                     this.isUploading = true;
                     const audioBlob = new Blob(this.audioChunks, { type: 'audio/webm' });
                     const audioFile = new File([audioBlob], 'voice-note-' + Date.now() + '.webm', { type: 'audio/webm' });

                     this.$wire.upload('voiceRecording', audioFile, () => {
                         this.$wire.uploadVoice();
                         this.isRecording = false;
                         this.isUploading = false;
                         setTimeout(scrollVenueChat, 120);
                     }, (error) => {
                         alert('Voice upload failed. Please try again.');
                         this.isRecording = false;
                         this.isUploading = false;
                     });
                 };

                 this.mediaRecorder.start();
                 this.isRecording = true;
                 this.seconds = 0;
                 this.timer = '00:00';
                 this.interval = setInterval(() => {
                     this.seconds++;
                     const m = String(Math.floor(this.seconds / 60)).padStart(2, '0');
                     const s = String(this.seconds % 60).padStart(2, '0');
                     this.timer = m + ':' + s;
                 }, 1000);

             } catch (err) {
                 alert('Microphone access denied. Please allow microphone permission in your browser.');
             }
         },

         stopRecording() {
             if (this.mediaRecorder && this.isRecording) {
                 clearInterval(this.interval);
                 this.mediaRecorder.stop();
             }
         },

         cancelRecording() {
             if (this.mediaRecorder && this.isRecording) {
                 clearInterval(this.interval);
                 this.audioChunks = [];
                 if (this.mediaRecorder.stream) {
                     this.mediaRecorder.stream.getTracks().forEach(track => track.stop());
                 }
                 this.isRecording = false;
             }
         }
     }"
     @toggle-chat.window="open = !open; if(open) setTimeout(scrollVenueChat, 100)">

    <!-- =====================================================================
         STATE 1: FLOATING CHAT BUTTON (Pure Chat Icon, Clean & Responsive)
         Always stays mounted in DOM; visible when closed.
         ===================================================================== -->
    <button type="button"
            x-show="!open"
            @click="open = true; setTimeout(scrollVenueChat, 100)"
            aria-label="Ask {{ $hall->name }} Concierge"
            title="Ask {{ $hall->name }} Concierge"
            class="fixed bottom-5 right-5 sm:bottom-6 sm:right-6 z-30 w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-gradient-to-tr from-[#C6A15B] via-[#D8B46B] to-[#F2DE9A] text-[#171513] shadow-[0_12px_32px_rgba(198,161,91,0.6)] hover:shadow-[0_16px_40px_rgba(198,161,91,0.8)] hover:scale-110 active:scale-95 transition-all duration-300 flex items-center justify-center cursor-pointer border-2 border-white/70 dark:border-[#F2DE9A]/50 group">
        
        <!-- Pure Chat Icon -->
        <svg class="w-7 h-7 sm:w-8 sm:h-8 text-[#171513] group-hover:rotate-6 transition-transform" fill="currentColor" viewBox="0 0 24 24">
            <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.17L4 17.17V4h16v12z"/>
            <circle cx="8" cy="10" r="1.3"/>
            <circle cx="12" cy="10" r="1.3"/>
            <circle cx="16" cy="10" r="1.3"/>
        </svg>

        <!-- Live Pulse Indicator Dot -->
        <span class="absolute top-1 right-1 flex h-3.5 w-3.5">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500 border-2 border-white dark:border-[#171513]"></span>
        </span>
    </button>

    <!-- =====================================================================
         STATE 2: CHATBOT POPUP WINDOW (When Opened - Screenshot 1)
         Mobile-Responsive: fixed inset-x-2 bottom-2 on phones, fixed bottom-6 right-6 on desktop.
         min-h-0 + overflow-y-auto ensures inner message scrolling without stretching outer height!
         ===================================================================== -->
    <div x-show="open"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 scale-95"
         class="fixed inset-x-2 bottom-2 sm:inset-x-auto sm:bottom-6 sm:right-6 z-30 w-auto sm:w-[410px] h-[78vh] sm:h-[550px] max-h-[calc(100vh-5.5rem)] bg-white dark:bg-[#1A1714] rounded-2xl shadow-[0_20px_60px_rgba(0,0,0,0.6)] border-2 border-[#C6A15B] flex flex-col overflow-hidden">
        
        <!-- Header (Directly Inspired by Screenshot 1) -->
        <div class="bg-gradient-to-r from-[#C6A15B] via-[#B89241] to-[#9A7836] p-3.5 sm:p-4 text-white flex items-center justify-between shadow-md shrink-0">
            <div class="flex items-center gap-3">
                <!-- Cute Robot Icon Circle -->
                <div class="w-10 h-10 rounded-full bg-white text-[#C6A15B] flex items-center justify-center shadow-md shrink-0">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2a2 2 0 012 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 017 7h1a1 1 0 011 1v3a1 1 0 01-1 1h-1v1a2 2 0 01-2 2H5a2 2 0 01-2-2v-1H2a1 1 0 01-1-1v-3a1 1 0 011-1h1a7 7 0 017-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 012-2M7.5 13A1.5 1.5 0 006 14.5 1.5 1.5 0 007.5 16 1.5 1.5 0 009 14.5 1.5 1.5 0 007.5 13m9 0a1.5 1.5 0 00-1.5 1.5 1.5 1.5 0 001.5 1.5 1.5 1.5 0 001.5-1.5 1.5 1.5 0 00-1.5-1.5z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-bold text-lg leading-tight font-sans text-white">Hello!</h4>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-300 animate-pulse"></span>
                        <p class="text-[11px] text-white/95 font-medium">Ask {{ $hall->name }} Desk (Live)</p>
                    </div>
                </div>
            </div>

            <!-- Action Icons: Refresh & Close (Screenshot 1) -->
            <div class="flex items-center gap-1.5">
                <button type="button"
                        wire:click="resetConversation"
                        title="Reset chat"
                        class="w-8 h-8 rounded-full hover:bg-white/20 text-white flex items-center justify-center transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </button>
                <button type="button"
                        @click="open = false"
                        title="Close chat"
                        class="w-8 h-8 rounded-full hover:bg-white/20 text-white flex items-center justify-center transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Messages Stream Area (min-h-0 prevents container stretch, overflow-y-auto enables smooth scrolling) -->
        <div class="flex-1 min-h-0 overflow-y-auto p-4 space-y-3.5 bg-stone-50/80 dark:bg-[#141210]/90 text-xs" 
             id="venue-chat-stream">
            
            <!-- Initial Welcome Greeting -->
            <div class="flex justify-start">
                <div class="max-w-[85%] rounded-2xl rounded-tl-none p-3.5 bg-[#171513] text-stone-100 border border-[#C6A15B]/35 shadow-md space-y-2">
                    <div class="flex items-center gap-1.5 text-[10px] text-[#C6A15B] font-mono font-bold uppercase tracking-wider">
                        <span>🛎️ {{ $hall->name }} Concierge Desk</span>
                    </div>
                    <p class="leading-relaxed">
                        Assalamu Alaikum! Welcome to <strong>{{ $hall->name }}</strong>. Send us a message or live voice note. Main aap ko rent, catering, aur dates ke baray mein bata sakta hoon, aur hamara venue manager bhi live reply karega!
                    </p>
                </div>
            </div>

            <!-- Conversation Messages -->
            @foreach($messages as $msg)
                <div class="flex {{ $msg['sender_type'] === 'customer' ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[85%] rounded-2xl p-3 shadow-sm space-y-1
                        @if($msg['sender_type'] === 'customer')
                            bg-gradient-to-r from-[#C6A15B] to-[#B38E46] text-[#171513] font-medium rounded-tr-none
                        @elseif($msg['sender_type'] === 'bot')
                            bg-[#171513] text-stone-100 border border-[#C6A15B]/30 rounded-tl-none
                        @else
                            bg-emerald-950 text-emerald-100 border border-emerald-500/40 rounded-tl-none shadow-md
                        @endif
                    ">
                        <div class="flex items-center justify-between gap-3 text-[9px] font-mono opacity-75">
                            <span class="font-bold uppercase tracking-wider">
                                @if($msg['sender_type'] === 'customer')
                                    You (Guest)
                                @elseif($msg['sender_type'] === 'bot')
                                    Concierge Assistant
                                @else
                                    ✓ Venue Manager (Official)
                                @endif
                            </span>
                            <span>{{ $msg['created_at'] }}</span>
                        </div>

                        @if($msg['message_type'] === 'voice')
                            <div class="pt-1">
                                <audio controls class="w-full h-8" style="max-width: 240px;">
                                    <source src="{{ Storage::url($msg['voice_file']) }}" type="audio/webm">
                                    <source src="{{ Storage::url($msg['voice_file']) }}" type="audio/mpeg">
                                    Your browser does not support audio playback.
                                </audio>
                            </div>
                        @else
                            <div class="leading-relaxed whitespace-pre-line text-[11px] sm:text-xs">
                                {!! nl2br(e($msg['message'])) !!}
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

        </div>

        <!-- Footer: Text Input & In-Browser Live Microphone Voice Recorder (No Local File Picker) -->
        <div class="p-3 bg-white dark:bg-[#1A1714] border-t border-[#E7E5E4] dark:border-white/10 shrink-0">
            
            <!-- Live Voice Recording UI State -->
            <div x-show="isRecording" x-cloak class="flex items-center justify-between gap-2 p-2 rounded-xl bg-red-50 dark:bg-red-950/40 border border-red-300 dark:border-red-800">
                <div class="flex items-center gap-2 text-xs font-mono text-red-600 dark:text-red-400 font-bold">
                    <span class="w-3 h-3 rounded-full bg-red-500 animate-ping"></span>
                    <span>Recording... <span x-text="timer">00:00</span></span>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button"
                            @click="cancelRecording()"
                            class="px-2.5 py-1 text-[11px] font-sans font-bold text-stone-600 dark:text-stone-300 hover:text-stone-900 border border-stone-300 dark:border-stone-700 rounded-lg">
                        Cancel
                    </button>
                    <button type="button"
                            @click="stopRecording()"
                            class="px-3 py-1 bg-gradient-to-r from-[#C6A15B] to-[#A88236] text-[#171513] text-[11px] font-sans font-bold uppercase rounded-lg shadow-sm flex items-center gap-1 cursor-pointer">
                        <span>Send Voice &rarr;</span>
                    </button>
                </div>
            </div>

            <!-- Uploading Voice Indicator -->
            <div x-show="isUploading" x-cloak class="p-2 text-center text-xs text-[#C6A15B] font-sans font-bold animate-pulse">
                🎙️ Uploading your voice note to concierge desk...
            </div>

            <!-- Standard Message Form (Shown When Not Recording) -->
            <form x-show="!isRecording && !isUploading" wire:submit.prevent="sendMessage" class="flex items-center gap-2">
                <input type="text"
                       wire:model="newMessage"
                       placeholder="Type your message here..."
                       class="flex-1 px-3.5 py-2.5 rounded-xl border border-stone-300 dark:border-white/20 bg-stone-50 dark:bg-[#171513] text-[#171513] dark:text-white placeholder-stone-400 focus:border-[#C6A15B] focus:ring-1 focus:ring-[#C6A15B] outline-none text-xs transition">

                <!-- Direct Microphone Record Button (Real In-Browser MediaRecorder) -->
                <button type="button"
                        @click="startRecording()"
                        class="p-2.5 rounded-xl border border-stone-200 dark:border-white/15 bg-stone-50 dark:bg-[#171513] text-stone-600 dark:text-stone-300 hover:text-[#C6A15B] hover:border-[#C6A15B] transition shrink-0 cursor-pointer"
                        title="Record live voice note from microphone">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                    </svg>
                </button>

                <!-- Send Button: Paper Airplane Icon (Screenshot 1) -->
                <button type="submit"
                        wire:loading.attr="disabled"
                        class="w-10 h-10 rounded-xl bg-gradient-to-r from-[#C6A15B] to-[#A88236] text-[#171513] hover:brightness-110 flex items-center justify-center shadow-md shadow-[#C6A15B]/30 shrink-0 transition cursor-pointer">
                    <svg class="w-4 h-4 transform rotate-45 -translate-y-0.5 translate-x-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/>
                    </svg>
                </button>
            </form>
        </div>

    </div>

</div>

<script>
    function scrollVenueChat() {
        const stream = document.getElementById('venue-chat-stream');
        if (stream) {
            stream.scrollTo({ top: stream.scrollHeight, behavior: 'smooth' });
        }
    }
    window.addEventListener('chatOpened', () => setTimeout(scrollVenueChat, 100));
    window.addEventListener('messageSent', () => setTimeout(scrollVenueChat, 80));

    document.addEventListener('livewire:navigated', () => {
        setTimeout(scrollVenueChat, 150);
    });
</script>
