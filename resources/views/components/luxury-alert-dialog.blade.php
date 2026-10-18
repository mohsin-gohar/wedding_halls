<div x-data="{
        dialogOpen: false,
        title: 'EventHub Royal Notification',
        message: '',
        type: 'gold',
        confirmText: 'Understood',
        showCancel: false,
        cancelText: 'Cancel',
        callback: null,
        toasts: [],

        openDialog(detail) {
            this.title = detail.title || 'EventHub Notice';
            this.message = detail.message || '';
            this.type = detail.type || 'gold';
            this.confirmText = detail.confirmText || 'Understood';
            this.showCancel = !!detail.showCancel;
            this.cancelText = detail.cancelText || 'Cancel';
            this.callback = typeof detail.onConfirm === 'function' ? detail.onConfirm : null;
            this.dialogOpen = true;
        },

        confirmAction() {
            this.dialogOpen = false;
            if (this.callback) {
                this.callback();
                this.callback = null;
            }
        },

        addToast(msg, type = 'gold') {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, message: msg, type });
            setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }, 4000);
        }
    }"
    x-init="
        window.luxuryAlert = (title, message, options = {}) => {
            openDialog({ title, message, ...options });
        };
        window.luxuryToast = (message, type = 'gold') => {
            addToast(message, type);
        };
        // Universal interceptor: upgrade any native browser alert() to luxury modal
        window.alert = (msg) => {
            openDialog({
                title: 'Royal Notification',
                message: String(msg),
                type: 'gold',
                confirmText: 'Acknowledge'
            });
        };
    "
    @luxury-alert.window="openDialog($event.detail)"
    @luxury-toast.window="addToast($event.detail.message, $event.detail.type || 'gold')"
    class="relative z-[9999]">

    <!-- GLOBAL LUXURY MODAL DIALOG -->
    <template x-teleport="body">
        <div x-cloak 
             x-show="dialogOpen" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/85 backdrop-blur-md"
             @keydown.escape.window="dialogOpen = false"
             role="dialog"
             aria-modal="true">

            <div x-show="dialogOpen"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-3"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-3"
                 class="relative w-full max-w-lg bg-[#171513] border-2 border-[#C6A15B] rounded-2xl shadow-[0_25px_80px_rgba(0,0,0,0.95),0_0_50px_rgba(198,161,91,0.3)] text-white overflow-hidden p-6 sm:p-8 space-y-6"
                 @click.outside="dialogOpen = false">
                
                <!-- Decorative Corner Accents -->
                <div class="absolute top-0 left-0 w-8 h-8 border-t-2 border-l-2 border-[#DFBF7A] pointer-events-none"></div>
                <div class="absolute top-0 right-0 w-8 h-8 border-t-2 border-r-2 border-[#DFBF7A] pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 w-8 h-8 border-b-2 border-l-2 border-[#DFBF7A] pointer-events-none"></div>
                <div class="absolute bottom-0 right-0 w-8 h-8 border-b-2 border-r-2 border-[#DFBF7A] pointer-events-none"></div>

                <!-- Gold Crest Emblem -->
                <div class="relative mx-auto w-16 h-16 rounded-full bg-gradient-to-b from-[#DFBF7A]/25 via-[#C6A15B]/10 to-transparent border-2 border-[#C6A15B] flex items-center justify-center shadow-[0_0_25px_rgba(198,161,91,0.35)]">
                    <template x-if="type === 'gold' || type === 'info'">
                        <svg class="w-8 h-8 text-[#DFBF7A]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </template>
                    <template x-if="type === 'success'">
                        <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </template>
                    <template x-if="type === 'warning' || type === 'error'">
                        <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </template>
                    <span class="absolute -bottom-2 px-2 py-0.5 bg-[#C6A15B] text-[#171513] text-[8px] font-mono font-bold tracking-widest uppercase rounded shadow">
                        EVENTHUB
                    </span>
                </div>

                <!-- Modal Text Content -->
                <div class="text-center space-y-2">
                    <span class="text-[9px] font-mono uppercase tracking-[0.25em] text-[#C6A15B] font-bold block" x-text="type === 'error' ? 'Notice' : 'Luxury Verification'"></span>
                    <h3 class="font-serif-luxury text-2xl text-white font-medium" x-text="title"></h3>
                    <p class="text-sm font-sans text-stone-300 leading-relaxed whitespace-pre-line" x-text="message"></p>
                </div>

                <!-- Modal Actions -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
                    <button type="button" 
                            @click="confirmAction()"
                            class="btn-gold !w-full sm:!w-auto !min-w-[140px] !h-11 font-mono uppercase tracking-wider text-xs font-bold rounded-xl cursor-pointer shadow-lg hover:shadow-[#C6A15B]/30 transition"
                            x-text="confirmText">
                    </button>
                    <button type="button" 
                            x-show="showCancel"
                            @click="dialogOpen = false"
                            class="w-full sm:w-auto px-5 h-11 border border-[#C6A15B]/40 hover:border-[#C6A15B] text-stone-300 hover:text-white text-xs font-mono uppercase tracking-wider rounded-xl transition cursor-pointer"
                            x-text="cancelText">
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- LUXURY FLOATING TOASTS NOTIFICATION TRAY -->
    <template x-teleport="body">
        <div class="fixed bottom-6 right-6 z-[9998] flex flex-col gap-3 max-w-sm w-full pointer-events-none px-4 sm:px-0">
            <template x-for="toast in toasts" :key="toast.id">
                <div x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-3 scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                     x-transition:leave-end="opacity-0 translate-y-3 scale-95"
                     class="pointer-events-auto bg-[#171513] border border-[#C6A15B] text-white p-4 rounded-xl shadow-[0_10px_35px_rgba(0,0,0,0.8),0_0_20px_rgba(198,161,91,0.25)] flex items-start gap-3">
                    <div class="w-7 h-7 rounded-full bg-[#C6A15B]/20 border border-[#C6A15B] flex items-center justify-center text-[#DFBF7A] shrink-0 text-xs">
                        ✨
                    </div>
                    <div class="flex-1 text-xs text-stone-200 font-sans leading-snug" x-text="toast.message"></div>
                </div>
            </template>
        </div>
    </template>
</div>

