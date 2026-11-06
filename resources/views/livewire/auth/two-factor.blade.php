<div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="flex justify-center">
            <div class="w-16 h-16 bg-[#C6A15B] rounded-2xl flex items-center justify-center text-[#171513] font-bold text-2xl shadow-lg">
                2FA
            </div>
        </div>

        @if ($setupMode)
            <h2 class="mt-6 text-center text-3xl font-extrabold text-[#171513] tracking-tight sm:text-4xl">
                Set Up 2FA
            </h2>
            <p class="mt-2 text-center text-stone-600 dark:text-stone-400 text-sm">
                Scan the QR code with Google Authenticator, Authy, or any TOTP app.
            </p>

            <div class="mt-8">
                @if ($errorMessage)
                    <div class="mb-4 p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-500 text-sm font-medium">
                        {{ $errorMessage }}
                    </div>
                @endif

                @if ($feedbackMessage)
                    <div class="mb-4 p-4 bg-[#C6A15B]/10 border border-[#C6A15B]/20 rounded-xl text-[#C6A15B] text-sm font-medium">
                        {{ $feedbackMessage }}
                    </div>
                @endif

                <div class="bg-white dark:bg-[#171513] p-6 rounded-xl border border-[#E7E5E4] dark:border-white/10">
                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-stone-500 dark:text-stone-400 uppercase tracking-wider mb-2">
                            Recovery Codes (Save These!)
                        </label>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($recoveryCodes as $code)
                                <span class="px-2 py-1 rounded bg-[#F8F5EF] dark:bg-[#1f1c18] border border-[#E7E5E4] dark:border-white/10 text-xs font-mono text-[#171513] dark:text-white">
                                    {{ $code }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label for="code" class="block text-sm font-medium text-[#171513] dark:text-white mb-2">
                            Verification Code
                        </label>
                        <input type="text" id="code" wire:model="code" placeholder="123456" maxlength="6"
                            class="w-full px-4 py-3 rounded-xl border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#1f1c18] text-[#171513] dark:text-white text-center text-xl font-mono focus:ring-2 focus:ring-[#C6A15B] focus:border-transparent outline-none transition-all"
                            autocomplete="one-time-code" required>
                    </div>

                    <button wire:click="enableTwoFactor"
                        class="w-full mt-4 tbtn tbtn-gold py-3.5 rounded-xl text-sm font-semibold uppercase tracking-wide shadow-lg hover:shadow-[#C6A15B]/30 transition-all">
                        Enable Two-Factor Authentication
                    </button>
                </div>
            </div>
        @else
            <h2 class="mt-6 text-center text-3xl font-extrabold text-[#171513] tracking-tight sm:text-4xl">
                Two-Factor Verification
            </h2>
            <p class="mt-2 text-center text-stone-600 dark:text-stone-400 text-sm">
                Enter the 6-digit code from your authenticator app.
            </p>

            <div class="mt-8">
                @if ($errorMessage)
                    <div class="mb-4 p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-500 text-sm font-medium">
                        {{ $errorMessage }}
                    </div>
                @endif

                <div class="bg-white dark:bg-[#171513] p-6 rounded-xl border border-[#E7E5E4] dark:border-white/10">
                    <div>
                        <label for="code" class="block text-sm font-medium text-[#171513] dark:text-white mb-2">
                            Verification Code
                        </label>
                        <input type="text" id="code" wire:model="code" placeholder="123456" maxlength="6"
                            class="w-full px-4 py-3 rounded-xl border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#1f1c18] text-[#171513] dark:text-white text-center text-xl font-mono focus:ring-2 focus:ring-[#C6A15B] focus:border-transparent outline-none transition-all"
                            autocomplete="one-time-code" required>
                    </div>

                    <button wire:click="verifyTwoFactor"
                        class="w-full mt-4 tbtn tbtn-gold py-3.5 rounded-xl text-sm font-semibold uppercase tracking-wide shadow-lg hover:shadow-[#C6A15B]/30 transition-all">
                        Verify & Continue
                    </button>

                    <p class="mt-4 text-center text-xs text-stone-500 dark:text-stone-400">
                        Lost your device?
                        <a href="{{ route('login') }}" class="font-semibold text-[#C6A15B] hover:underline">Use recovery code</a>
                    </p>
                </div>
            </div>
        @endif
    </div>
</div>