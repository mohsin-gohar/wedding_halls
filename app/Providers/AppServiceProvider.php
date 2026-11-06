<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
