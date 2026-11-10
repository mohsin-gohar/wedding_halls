<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Routing\Middleware\ValidateSession;

class TwoFactorAuth extends ValidateSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request, Closure $next)
    {
        // If user is not authenticated, skip 2FA check
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Skip 2FA for super admin
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if 2FA is enabled for this user
        $twoFactorEnabled = !empty($user->two_factor_secret);

        // If 2FA not enabled, continue normally
        if (!$twoFactorEnabled) {
            return $next($request);
        }

        // If user is already verified via 2FA session, continue
        if ($request->hasValidSignature() || $request->session()->get('2fa_verified')) {
            return $next($request);
        }

        // If this is a 2FA verification POST request
        if ($request->method() === 'POST' && $request->has('code')) {
            // Verify the 2FA code
            $expectedCode = $this->generateTotpCode($user->two_factor_secret);

            if ($request->input('code') === $expectedCode) {
                // Mark session as verified
                $request->session()->put('2fa_verified', true);

                // Send success notification
                // app/Services/NotificationService::send('2fa_verified', ...);

                return $next($request);
            }

            // Invalid code - show error
            return back()
                ->withInput()
                ->withErrors(['code' => 'Invalid 2FA code. Please try again.']);
        }

        // Show 2FA verification form
        return view('auth.two-factor-verification', [
            'user' => $user,
            'title' => 'Two-Factor Authentication',
        ]);
    }

    /**
     * Generate TOTP code from secret.
     * @param string $secret
     * @return string
     */
    private function generateTotpCode(string $secret): string
    {
        // Use the standard TOTP algorithm (RFC 6236)
        // Time step: 30 seconds
        // Digits: 6
        // Algorithm: SHA-1

        $time = time();
        $step = 30;
        $digits = 6;
        $key = base32ToBinary($secret);

        $counter = intval(floor($time / $step));
        $keyBin = base32ToBinary($secret);

        // HMAC-SHA1
        $hmac = hexdec(
            bin2hex(
                hash_hmac(
                    'sha1',
                    pack('N*', $counter >> 24, $counter >> 16 & 0xff, $counter >> 8 & 0xff, $counter & 0xff),
                    $keyBin
                )
            )
        );

        // Dynamic truncation
        $offset = $hmac & 0xf;
        $code = ($hmac >> 4) & 0x7fffffff;

        $code = ($code >> $offset) % pow(10, $digits);

        return sprintf('%06d', $code);
    }

    /**
     * Convert base32 to binary.
     * @param string $base32
     * @return string
     */
    private function base32ToBinary(string $base32): string
    {
        $binary = '';
        $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32 = strtoupper($base32);

        for ($i = 0; $i < strlen($base32); $i++) {
            $char = $base32[$i];
            if (($pos = strpos($base32Chars, $char)) !== false) {
                $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
            }
        }

        // Remove leading zeros in groups of 8
        $result = '';
        for ($i = 0; $i < strlen($binary); $i += 8) {
            $chunk = substr($binary, $i, 8);
            $result .= chr(bindec($chunk));
        }

        return $result;
    }
}