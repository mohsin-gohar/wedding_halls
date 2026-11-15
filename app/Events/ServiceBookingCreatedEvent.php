<?php

namespace App\Events;

use App\Models\ServiceBooking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceBookingCreatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ServiceBooking $booking;

    public function __construct(ServiceBooking $booking)
    {
        $this->booking = $booking;
    }
}
