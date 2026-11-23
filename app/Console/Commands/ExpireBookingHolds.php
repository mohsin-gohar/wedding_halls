<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use Carbon\Carbon;

class ExpireBookingHolds extends Command
{
    /** The name and signature of the console command. */
    protected $signature = 'bookings:expire-holds';

    /** The console command description. */
    protected $description = 'Expire expired booking holds and availability';

    /** Execute the console command. */
    public function handle()
    {
        $this->info('Expiring booking holds...');

        $now = Carbon::now();

        // Find bookings with hold_expires_at in the past that are still CONFIRMED or HOLD
        $bookings = Booking::where('hold_expires_at', '<', $now)
            ->whereIn('status', ['CONFIRMED', 'HOLD'])
            ->get();

        $expired = 0;

        foreach ($bookings as $booking) {
            // Release the hold - set status back to HOLD or appropriate state
            $booking->status = 'HOLD';
            $booking->hold_expires_at = null;
            $booking->save();

            $expired++;
        }

        $this->info("Expired {$expired} booking holds.");

        return Command::SUCCESS;
    }
}