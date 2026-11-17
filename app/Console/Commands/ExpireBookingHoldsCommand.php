<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Booking;
use Illuminate\Console\Command;

class ExpireBookingHoldsCommand extends Command
{
    protected $signature = 'bookings:expire-holds';

    protected $description = 'Cancel HOLD bookings whose configured hold_expires_at has passed. Holds without an expiry are left unchanged.';

    public function handle(): int
    {
        $expired = Booking::query()
            ->where('status', 'HOLD')
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<=', now())
            ->get();

        foreach ($expired as $booking) {
            $stamp = $booking->hold_expires_at?->toDateTimeString() ?? now()->toDateTimeString();
            $booking->update([
                'status' => 'CANCELLED',
                'notes' => trim(($booking->notes ? $booking->notes."\n" : '').'Hold expired automatically at '.$stamp.'.'),
            ]);

            AuditLog::log(
                'BOOKING_HOLD_EXPIRED',
                "Hold {$booking->booking_reference} for {$booking->customer_name} on {$booking->event_date?->toDateString()} {$booking->slot_type} expired and was cancelled.",
                $booking->tenant_id,
                'Booking',
                $booking->id
            );
        }

        $this->info('Expired holds cancelled: '.$expired->count());

        return self::SUCCESS;
    }
}
