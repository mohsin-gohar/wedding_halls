<?php

namespace App\Exceptions;

use Exception;

class SlotAlreadyBookedException extends Exception
{
    protected string $hallId;
    protected string $eventDate;
    protected string $slotType;

    public function __construct(
        string $message = "The selected hall slot is already booked for this date.",
        int $code = 409,
        ?string $hallId = null,
        ?string $eventDate = null,
        ?string $slotType = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->hallId = $hallId ?? '';
        $this->eventDate = $eventDate ?? '';
        $this->slotType = $slotType ?? '';
    }

    public function getHallId(): string
    {
        return $this->hallId;
    }

    public function getEventDate(): string
    {
        return $this->eventDate;
    }

    public function getSlotType(): string
    {
        return $this->slotType;
    }
}
