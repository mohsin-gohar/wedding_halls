<?php

namespace App\Providers;

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
