<?php

namespace App\Support;

/**
 * Single source of truth for new payment-method writes.
 *
 * Historical rows may still carry legacy labels (BANK_TRANSFER, JAZZCASH,
 * STRIPE, …) so reports remain truthful. This class only governs NEW records:
 * physical cash is the sole accepted method at every validation and service
 * boundary.
 */
final class CashOnlyPayments
{
    public const CASH = 'CASH';

    /** @var array<int, string> */
    public const METHODS = [self::CASH];

    public static function validationRule(bool $required = true): string
    {
        return ($required ? 'required|' : 'nullable|').'in:'.implode(',', self::METHODS);
    }

    /**
     * @throws \InvalidArgumentException when a non-cash method is supplied
     */
    public static function assert(mixed $method, string $context = 'payment'): string
    {
        if (! is_string($method) || ! in_array($method, self::METHODS, true)) {
            throw new \InvalidArgumentException('Only cash is accepted for new '.$context.' records.');
        }

        return $method;
    }
}
