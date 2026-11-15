<div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="flex justify-center">
            <div class="w-16 h-16 bg-[#C6A15B] rounded-2xl flex items-center justify-center text-[#171513] font-bold text-2xl shadow-lg">
                2FA
            </div>
        </div>

        @if ($setupMode)
            <h2 class="mt-6 text-center text-3xl font-extrabold text-[#171513] dark:text-white tracking-tight sm:text-4xl">
                Set Up 2FA
            </h2>
            <p class="mt-2 text-center text-stone-600 dark:text-stone-400 text-sm">
                Scan the QR code with Google Authenticator, Authy, or any TOTP app.
            </p>

            <div class="mt-8">
                @if ($errorMessage)
                    <div class="mb-4 p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-500 text-sm font-medium" role="alert">
                        {{ $errorMessage }}
                    </div>
                @endif

                @if ($feedbackMessage)
                    <div class="mb-4 p-4 bg-[#C6A15B]/10 border border-[#C6A15B]/20 rounded-xl text-[#C6A15B] text-sm font-medium">
                        {{ $feedbackMessage }}
                    </div>
                @endif

                <div class="bg-white dark:bg-[#171513] p-6 rounded-xl border border-[#E7E5E4] dark:border-white/10 shadow-sm">
                    <!-- QR code -->
                    <div class="flex flex-col items-center gap-3 mb-6">
                        @if ($qrCodeDataUri !== '')
                            <div class="p-3 bg-white border border-[#E7E5E4] rounded-xl">
                                <img src="{{ $qrCodeDataUri }}" alt="Two-factor authentication QR code" width="200" height="200">
                            </div>
                            <p class="text-[11px] text-stone-500 dark:text-stone-400 text-center">
                                Scan this with your authenticator app
                            </p>
                        @endif

                        <div class="w-full">
                            <label for="manual-secret" class="block text-[11px] font-semibold text-stone-500 dark:text-stone-400 uppercase tracking-wider mb-2">
                                Can't scan? Enter this key manually
                            </label>
                            <input id="manual-secret" type="text" readonly value="{{ $secret }}"
                                onclick="this.select()"
                                class="w-full px-4 py-3 rounded-xl border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#1f1c18] text-[#171513] dark:text-white text-center text-sm font-mono tracking-widest focus:ring-2 focus:ring-[#C6A15B] focus:border-transparent outline-none transition-all">
                            <p class="text-[11px] text-stone-500 dark:text-stone-400 mt-2 text-center">
                                Keep this key private — anyone with it can generate your codes.
                            </p>
                        </div>
                    </div>

                    <!-- Recovery codes -->
                    <div class="mb-5">
                        <label class="block text-xs font-semibold text-stone-500 dark:text-stone-400 uppercase tracking-wider mb-2">
                            Recovery Codes (Save These!)
                        </label>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($recoveryCodes as $recoveryCode)
                                <span class="px-2 py-1 rounded bg-[#F8F5EF] dark:bg-[#1f1c18] border border-[#E7E5E4] dark:border-white/10 text-xs font-mono text-[#171513] dark:text-white">
                                    {{ $recoveryCode }}
                                </span>
                            @endforeach
                        </div>
                        <p class="text-[11px] text-stone-500 dark:text-stone-400 mt-2">
                            Each code works once if you lose your device. They are shown only now.
                        </p>
                    </div>

                    <div>
                        <label for="code" class="block text-sm font-medium text-[#171513] dark:text-white mb-2">
                            Verification Code
                        </label>
                        <input type="text" id="code" wire:model="code" placeholder="123456" maxlength="6"
                            class="w-full px-4 py-3 rounded-xl border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#1f1c18] text-[#171513] dark:text-white text-center text-xl font-mono focus:ring-2 focus:ring-[#C6A15B] focus:border-transparent outline-none transition-all"
                            autocomplete="one-time-code" required>
                        @error('code')
                            <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <button wire:click="enableTwoFactor"
                        class="w-full mt-4 tbtn tbtn-gold py-3.5 rounded-xl text-sm font-semibold uppercase tracking-wide shadow-lg hover:shadow-[#C6A15B]/30 transition-all">
                        Enable Two-Factor Authentication
                    </button>
                </div>
            </div>
        @elseif ($isEnabled && session('2fa_verified'))
            <h2 class="mt-6 text-center text-3xl font-extrabold text-[#171513] dark:text-white tracking-tight sm:text-4xl">
                Two-Factor Authentication
            </h2>
            <p class="mt-2 text-center text-stone-600 dark:text-stone-400 text-sm">
                Your account is protected with an authenticator app.
            </p>

            <div class="mt-8">
                @if ($errorMessage)
                    <div class="mb-4 p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-500 text-sm font-medium" role="alert">
                        {{ $errorMessage }}
                    </div>
                @endif

                @if ($feedbackMessage)
                    <div class="mb-4 p-4 bg-[#C6A15B]/10 border border-[#C6A15B]/20 rounded-xl text-[#C6A15B] text-sm font-medium">
                        {{ $feedbackMessage }}
                    </div>
                @endif

                <div class="bg-white dark:bg-[#171513] p-6 rounded-xl border border-[#E7E5E4] dark:border-white/10 shadow-sm">
                    <div class="flex items-center gap-3 pb-5 mb-5 border-b border-[#E7E5E4] dark:border-white/10">
                        <span class="w-10 h-10 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-lg font-bold">&check;</span>
                        <div>
                            <p class="text-sm font-semibold text-[#171513] dark:text-white">Two-factor is active</p>
                            <p class="text-[11px] text-stone-500 dark:text-stone-400">
                                Confirmed {{ auth()->user()->two_factor_confirmed_at?->format('M d, Y') ?? 'recently' }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <label for="disable-password" class="block text-sm font-medium text-[#171513] dark:text-white mb-2">
                            Confirm your password to disable
                        </label>
                        <input type="password" id="disable-password" wire:model="password"
                            autocomplete="current-password"
                            class="w-full px-4 py-3 rounded-xl border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#1f1c18] text-[#171513] dark:text-white text-sm focus:ring-2 focus:ring-[#C6A15B] focus:border-transparent outline-none transition-all">
                        @error('password')
                            <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <button wire:click="disableTwoFactor" wire:confirm="Disable two-factor authentication for your account?"
                        class="w-full mt-4 tbtn tbtn-outline py-3.5 rounded-xl text-sm font-semibold uppercase tracking-wide transition-all">
                        Disable Two-Factor
                    </button>
                </div>
            </div>
        @else
            <h2 class="mt-6 text-center text-3xl font-extrabold text-[#171513] dark:text-white tracking-tight sm:text-4xl">
                Two-Factor Verification
            </h2>
            <p class="mt-2 text-center text-stone-600 dark:text-stone-400 text-sm">
                Enter the 6-digit code from your authenticator app — or one of your recovery codes.
            </p>

            <div class="mt-8">
                @if ($errorMessage)
                    <div class="mb-4 p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-500 text-sm font-medium" role="alert">
                        {{ $errorMessage }}
                    </div>
                @endif

                @if ($feedbackMessage)
                    <div class="mb-4 p-4 bg-[#C6A15B]/10 border border-[#C6A15B]/20 rounded-xl text-[#C6A15B] text-sm font-medium">
                        {{ $feedbackMessage }}
                    </div>
                @endif

                <div class="bg-white dark:bg-[#171513] p-6 rounded-xl border border-[#E7E5E4] dark:border-white/10 shadow-sm">
                    <div>
                        <label for="code" class="block text-sm font-medium text-[#171513] dark:text-white mb-2">
                            Verification Code
                        </label>
                        <input type="text" id="code" wire:model="code" placeholder="123456 or ABCD-1234" maxlength="10"
                            class="w-full px-4 py-3 rounded-xl border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#1f1c18] text-[#171513] dark:text-white text-center text-xl font-mono focus:ring-2 focus:ring-[#C6A15B] focus:border-transparent outline-none transition-all"
                            autocomplete="one-time-code" required>
                        @error('code')
                            <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <button wire:click="verifyTwoFactor"
                        class="w-full mt-4 tbtn tbtn-gold py-3.5 rounded-xl text-sm font-semibold uppercase tracking-wide shadow-lg hover:shadow-[#C6A15B]/30 transition-all">
                        Verify &amp; Continue
                    </button>

                    <p class="mt-4 text-center text-xs text-stone-500 dark:text-stone-400">
                        Lost your device? Type one of your recovery codes above — each works once.
                    </p>
                </div>
            </div>
        @endif
    </div>
</div>
