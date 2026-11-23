<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as Kernel;
use Illuminate\Contracts\Console\Kernel as KernelContract;

class Kernel extends Kernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\CheckSubscriptionExpiry::class,
        \App\Console\Commands\ExpireBookingHolds::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Expiry booking holds (hourly)
        $schedule->command('bookings:expire-holds')->hourly();

        // Check subscription expiry (daily)
        $schedule->command('subscriptions:check-expiry')->daily();

        // Additional scheduled tasks can be added here

        // Run at midnight with proper timezone
        // $schedule->command('something')->at('01:00');
    }

    /**
     * Get the closure-based commands for the application.
     *
     * @return array
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require __DIR__ . '/routes.php';

        return;
    }
}