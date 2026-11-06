<div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="flex justify-center">
            <div class="w-16 h-16 bg-[#C6A15B] rounded-2xl flex items-center justify-center text-[#171513] font-bold text-2xl shadow-lg">
                EH
            </div>
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-[#171513] tracking-tight sm:text-4xl">
            Reset Your Password
        </h2>
        <p class="mt-2 text-center text-stone-600 dark:text-stone-400 text-sm">
            Enter your email below and we'll send you a secure reset link.
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white dark:bg-[#171513] py-8 px-4 shadow-2xl sm:rounded-3xl sm:p-10 border border-[#E7E5E4] dark:border-white/10">
            @if ($resetLinkSent)
                <div class="mb-6 p-4 bg-[#C6A15B]/10 border border-[#C6A15B]/20 rounded-xl text-[#C6A15B] text-sm font-medium flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    {{ $feedbackMessage }}
                </div>
                <div class="text-center">
                    <a href="{{ route('login') }}" class="tbtn tbtn-outline text-sm w-full">Back to Sign In</a>
                </div>
            @else
                @if ($errorMessage)
                    <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-500 text-sm font-medium">
                        {{ $errorMessage }}
                    </div>
                @endif

                <form wire:submit.prevent="sendResetLink" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-[#171513] dark:text-white mb-2">
                            Email Address
                        </label>
                        <input type="email" id="email" wire:model="email" placeholder="you@example.com"
                            class="w-full px-4 py-3 rounded-xl border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#1f1c18] text-[#171513] dark:text-white focus:ring-2 focus:ring-[#C6A15B] focus:border-transparent outline-none transition-all"
                            autocomplete="email" required>
                        @error('email')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                        class="w-full tbtn tbtn-gold py-3.5 rounded-xl text-sm font-semibold uppercase tracking-wide shadow-lg hover:shadow-[#C6A15B]/30 transition-all">
                        Send Reset Link
                    </button>
                </form>

                <p class="mt-6 text-center text-xs text-stone-500 dark:text-stone-400">
                    Already have a reset link?
                    <a href="{{ route('password.reset') }}" class="font-semibold text-[#C6A15B] hover:underline">Use it here</a>
                </p>
                <p class="mt-2 text-center text-xs text-stone-500 dark:text-stone-400">
                    Remember your password?
                    <a href="{{ route('login') }}" class="font-semibold text-[#C6A15B] hover:underline">Sign in</a>
                </p>
            @endif
        </div>
    </div>
</div>