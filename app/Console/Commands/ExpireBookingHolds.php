<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireBookingHolds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:expire-holds';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel HOLD bookings whose configured hold_expires_at has passed. Holds without an expiry are left unchanged.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiredCount = 0;

        Booking::query()
            ->where('status', 'HOLD')
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<=', now())
            ->select('id')
            ->chunkById(100, function ($bookings) use (&$expiredCount): void {
                foreach ($bookings as $candidate) {
                    $cancelled = DB::transaction(function () use ($candidate): bool {
                        // Re-check eligibility after acquiring the row lock. A
                        // second scheduler worker must not duplicate the audit.
                        $booking = Booking::query()
                            ->whereKey($candidate->getKey())
                            ->lockForUpdate()
                            ->first();

                        if (
                            ! $booking
                            || $booking->status !== 'HOLD'
                            || ! $booking->hold_expires_at
                            || $booking->hold_expires_at->isFuture()
                        ) {
                            return false;
                        }

                        $stamp = $booking->hold_expires_at->toDateTimeString();
                        $booking->update([
                            'status' => 'CANCELLED',
                            'notes' => trim(
                                ($booking->notes ? $booking->notes."\n" : '')
                                .'Hold expired automatically at '.$stamp.'.'
                            ),
                        ]);

                        AuditLog::log(
                            'BOOKING_HOLD_EXPIRED',
                            "Hold {$booking->booking_reference} for {$booking->customer_name} on "
                                .($booking->event_date?->toDateString() ?? 'unknown date')
                                ." {$booking->slot_type} expired and was cancelled.",
                            $booking->tenant_id,
                            'Booking',
                            (string) $booking->id
                        );

                        return true;
                    });

                    if ($cancelled) {
                        $expiredCount++;
                    }
                }
            }, 'id');

        $this->info('Expired holds cancelled: '.$expiredCount);

        return self::SUCCESS;
    }
}
