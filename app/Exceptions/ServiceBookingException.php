<?php

namespace App\Exceptions;

use Exception;

/**
 * Domain exception for the multi-vendor service-booking lifecycle
 * (RFQ award → ServiceBooking → confirmation → payments → payouts).
 */
class ServiceBookingException extends Exception
{
    public static function notFound(string $message = 'The service booking could not be found.'): self
    {
        return new self($message, 404);
    }

    public static function notAwardable(string $message = 'This quotation is not eligible for award.'): self
    {
        return new self($message, 409);
    }

    public static function invalidState(string $message = 'The service booking cannot transition to the requested state.'): self
    {
        return new self($message, 409);
    }
}
