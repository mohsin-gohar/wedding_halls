<div x-data="{
        dialogOpen: false,
        title: 'Riwaj Grand Royal Notification',
        message: '',
        type: 'gold',
        confirmText: 'Understood',
        showCancel: false,
        cancelText: 'Cancel',
        callback: null,
        toasts: [],

        openDialog(detail) {
            this.title = detail.title || 'Riwaj Grand Notice';
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
             class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md"
             @keydown.escape.window="dialogOpen = false"
             role="dialog"
             aria-modal="true"
             aria-labelledby="luxury-dialog-title">

            <div x-show="dialogOpen"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-3"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-3"
                 class="relative w-full max-w-lg bg-[#171513] border border-[#C6A15B]/50 rounded-2xl shadow-[0_24px_64px_rgba(0,0,0,0.85)] text-white overflow-hidden p-6 sm:p-8 space-y-6"
                 @click.outside="dialogOpen = false">

                <!-- Gold Crest Emblem -->
                <div class="relative mx-auto w-14 h-14 rounded-full bg-[#C6A15B]/15 border border-[#C6A15B]/50 flex items-center justify-center">
                    <template x-if="type === 'gold' || type === 'info'">
                        <svg class="w-7 h-7 text-[#C6A15B]" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </template>
                    <template x-if="type === 'success'">
                        <svg class="w-7 h-7 text-emerald-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </template>
                    <template x-if="type === 'warning' || type === 'error'">
                        <svg class="w-7 h-7 text-amber-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </template>
                    <span class="absolute -bottom-2 px-2 py-0.5 bg-[#C6A15B] text-[#171513] text-[8px] font-sans font-bold tracking-widest uppercase rounded shadow-sm">
                        RIWAJ GRAND
                    </span>
                </div>

                <!-- Modal Text Content -->
                <div class="text-center space-y-2">
                    <span class="text-[10px] font-sans uppercase tracking-[0.2em] text-[#C6A15B] font-semibold block" x-text="type === 'error' ? 'Notice' : 'Concierge Verification'"></span>
                    <h3 id="luxury-dialog-title" class="font-serif-luxury text-2xl text-white font-medium" x-text="title"></h3>
                    <p class="text-sm font-sans text-stone-300 leading-relaxed whitespace-pre-line" x-text="message"></p>
                </div>

                <!-- Modal Actions -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
                    <button type="button" 
                            @click="confirmAction()"
                            class="btn-gold !w-full sm:!w-auto !min-w-[140px] !h-11 font-sans uppercase tracking-wider text-xs font-semibold rounded-xl cursor-pointer shadow-sm transition"
                            x-text="confirmText">
                    </button>
                    <button type="button" 
                            x-show="showCancel"
                            @click="dialogOpen = false"
                            class="w-full sm:w-auto px-5 h-11 border border-[#C6A15B]/40 hover:border-[#C6A15B] text-stone-300 hover:text-white text-xs font-sans uppercase tracking-wider rounded-xl transition cursor-pointer"
                            x-text="cancelText">
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- LUXURY FLOATING TOASTS NOTIFICATION TRAY -->
    <template x-teleport="body">
        <div class="fixed bottom-6 right-6 z-[9998] flex flex-col gap-3 max-w-sm w-full pointer-events-none px-4 sm:px-0" role="status" aria-live="polite">
            <template x-for="toast in toasts" :key="toast.id">
                <div x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-3 scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                     x-transition:leave-end="opacity-0 translate-y-3 scale-95"
                     class="pointer-events-auto bg-[#171513] border border-[#C6A15B]/50 text-white p-4 rounded-xl shadow-[0_12px_32px_rgba(0,0,0,0.65)] flex items-start gap-3">
                    <div class="w-7 h-7 rounded-full bg-[#C6A15B]/15 border border-[#C6A15B]/40 flex items-center justify-center text-[#C6A15B] shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div class="flex-1 text-xs text-stone-200 font-sans leading-snug" x-text="toast.message"></div>
                </div>
            </template>
        </div>
    </template>
</div>

