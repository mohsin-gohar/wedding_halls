<?php

namespace App\Providers;

use App\Models\ApiToken;
use App\Models\Booking;
use App\Models\RFQ;
use App\Models\RFQBid;
use App\Models\ServiceBooking;
use App\Models\Tenant;
use App\Models\VendorDocument;
use App\Models\VendorPayout;
use App\Models\VendorService;
use App\Policies\BookingPolicy;
use App\Policies\RFQBidPolicy;
use App\Policies\RFQPolicy;
use App\Policies\ServiceBookingPolicy;
use App\Policies\VendorDocumentPolicy;
use App\Policies\VendorPayoutPolicy;
use App\Policies\VendorPolicy;
use App\Policies\VendorServicePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Named rate limiters for public, read-heavy pages. These sit in front
        // of Livewire's initial page render; Livewire action requests are
        // additionally protected by per-component guards where needed.
        RateLimiter::for('public-pages', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
        RateLimiter::for('public-availability', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        // API throttle: keyed by token prefix when present, else IP.
        RateLimiter::for('api', function (Request $request) {
            $token = $request->bearerToken();

            return Limit::perMinute(60)->by($token ? substr(hash('sha256', $token), 0, 32) : $request->ip());
        });

        // ── Stateless API guard (bearer tokens) ──────────────────────────────
        // Tokens are hashed at rest; the plain value never touches the DB.
        Auth::viaRequest('api-token', function (Request $request) {
            return ApiToken::resolve($request->bearerToken());
        });

        // Reload Protection: Limits rapid reloads to 5 per minute per IP + path
        RateLimiter::for('reload-protection', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip().'|'.$request->path())
                ->response(function (Request $request, array $headers) {
                    $retryAfter = (int) ($headers['Retry-After'] ?? 60);

                    return response()->view('errors.429', [
                        'retryAfter' => $retryAfter,
                        'message' => 'Aap website ko bar bar reload nahi kar saktay. Baraye meherbani thori der intezar karein.',
                    ], 429, $headers);
                });
        });

        // ── Authorization Policies (enterprise-grade server-side enforcement) ──
        Gate::policy(Tenant::class, VendorPolicy::class);
        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(ServiceBooking::class, ServiceBookingPolicy::class);
        Gate::policy(VendorService::class, VendorServicePolicy::class);
        Gate::policy(VendorDocument::class, VendorDocumentPolicy::class);
        Gate::policy(RFQ::class, RFQPolicy::class);
        Gate::policy(RFQBid::class, RFQBidPolicy::class);
        Gate::policy(VendorPayout::class, VendorPayoutPolicy::class);

        if (! $this->app->runningInConsole() && is_file($hotFile = public_path('hot')) && is_file(public_path('build/manifest.json'))) {
            $hotUrl = trim((string) @file_get_contents($hotFile));
            $parsed = parse_url($hotUrl);
            $host = $parsed['host'] ?? '127.0.0.1';
            $port = (int) ($parsed['port'] ?? 5173);

            $socket = @fsockopen($host, $port, $errno, $errstr, 0.15);
            if ($socket) {
                fclose($socket);
            } else {
                @unlink($hotFile);
            }
        }
    }
}
