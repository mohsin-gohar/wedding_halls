<div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="flex justify-center">
            <div class="w-16 h-16 bg-[#C6A15B] rounded-2xl flex items-center justify-center text-[#171513] font-bold text-2xl shadow-lg">
                EH
            </div>
        </div>
        @if ($resetComplete)
            <h2 class="mt-6 text-center text-3xl font-extrabold text-[#171513] tracking-tight sm:text-4xl">
                Password Reset ✓
            </h2>
            <p class="mt-2 text-center text-stone-600 dark:text-stone-400 text-sm">
                Your password has been changed successfully. Redirecting you to your dashboard...
            </p>
        @else
            <h2 class="mt-6 text-center text-3xl font-extrabold text-[#171513] tracking-tight sm:text-4xl">
                Set New Password
            </h2>
            <p class="mt-2 text-center text-stone-600 dark:text-stone-400 text-sm">
                Choose a strong password for your EventHub account.
            </p>
        @endif
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white dark:bg-[#171513] py-8 px-4 shadow-2xl sm:rounded-3xl sm:p-10 border border-[#E7E5E4] dark:border-white/10">
            @if ($resetComplete)
                <div class="mb-6 p-4 bg-[#C6A15B]/10 border border-[#C6A15B]/20 rounded-xl text-[#C6A15B] text-sm font-medium flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    {{ $feedbackMessage }}
                </div>
            @else
                @if ($errorMessage)
                    <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-500 text-sm font-medium">
                        {{ $errorMessage }}
                    </div>
                @endif

                <form wire:submit.prevent="resetPassword" class="space-y-6">
                    <input type="hidden" wire:model="token">
                    <input type="hidden" wire:model="email">

                    <div>
                        <label for="password" class="block text-sm font-medium text-[#171513] dark:text-white mb-2">
                            New Password
                        </label>
                        <input type="password" id="password" wire:model="password" placeholder="••••••••"
                            class="w-full px-4 py-3 rounded-xl border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#1f1c18] text-[#171513] dark:text-white focus:ring-2 focus:ring-[#C6A15B] focus:border-transparent outline-none transition-all"
                            autocomplete="new-password" required>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-[#171513] dark:text-white mb-2">
                            Confirm Password
                        </label>
                        <input type="password" id="password_confirmation" wire:model="password_confirmation" placeholder="••••••••"
                            class="w-full px-4 py-3 rounded-xl border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#1f1c18] text-[#171513] dark:text-white focus:ring-2 focus:ring-[#C6A15B] focus:border-transparent outline-none transition-all"
                            autocomplete="new-password" required>
                    </div>

                    @error('password')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror

                    <button type="submit"
                        class="w-full tbtn tbtn-gold py-3.5 rounded-xl text-sm font-semibold uppercase tracking-wide shadow-lg hover:shadow-[#C6A15B]/30 transition-all">
                        Reset Password
                    </button>
                </form>

                <p class="mt-6 text-center text-xs text-stone-500 dark:text-stone-400">
                    Don't have a reset link?
                    <a href="{{ route('password.forgot') }}" class="font-semibold text-[#C6A15B] hover:underline">Request one</a>
                </p>
            @endif
        </div>
    </div>
</div>