<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Support\CashOnlyPayments;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Cash-only, manual payment ledger.
 *
 * This service deliberately contains NO online payment gateway code. Every
 * financial mutation is performed by an authenticated, permission-checked
 * staff member, inside a database transaction with a row lock on the booking.
 * Historical rows with legacy method labels are never rewritten or deleted.
 */
class PaymentService
{
    /** Only physical cash may be recorded as a new booking payment. */
    public const NEW_PAYMENT_METHODS = CashOnlyPayments::METHODS;

    public const PAYMENT_TYPES = [
        'TOKEN_ADVANCE',
        'SECOND_INSTALLMENT',
        'FINAL_SETTLEMENT',
        'SECURITY_DEPOSIT',
        'REFUND',
    ];

    /**
     * Low-level insertion used by the manual recording methods and by trusted
     * server-side booking creation. Online/gateway method labels are rejected.
     */
    public function createPayment(
        string $tenantId,
        string $bookingId,
        float $amount,
        string $paymentType,
        string $paymentMethod,
        ?string $transactionReference = null,
        ?string $receivedByUserId = null,
        ?string $notes = null
    ): Payment {
        $paymentType = $paymentType === 'ADVANCE' ? 'TOKEN_ADVANCE' : $paymentType;

        CashOnlyPayments::assert($paymentMethod, 'booking payments');

        if (! in_array($paymentType, self::PAYMENT_TYPES, true)) {
            throw new \InvalidArgumentException('Unsupported payment type.');
        }

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Payment amount must be greater than zero.');
        }

        return Payment::create([
            'tenant_id' => $tenantId,
            'booking_id' => $bookingId,
            'amount' => round($amount, 2),
            'payment_type' => $paymentType,
            'payment_method' => $paymentMethod,
            'receipt_no' => Payment::generateReceiptNumber(),
            'transaction_reference' => $transactionReference,
            'received_by_user_id' => $receivedByUserId,
            'notes' => $notes,
        ]);
    }

    /**
     * Record physically received cash and update the booking ledger atomically.
     * Callers MUST have already performed tenant and permission checks.
     *
     * @throws \InvalidArgumentException|\RuntimeException
     */
    public function recordCashPayment(
        Booking $booking,
        float $amount,
        string $paymentType,
        User $receivedBy,
        ?string $transactionReference = null,
        ?string $notes = null
    ): Payment {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Cash amount must be greater than zero.');
        }

        if (! in_array($paymentType, self::PAYMENT_TYPES, true) || $paymentType === 'REFUND') {
            throw new \InvalidArgumentException('Unsupported cash payment type.');
        }

        if ($receivedBy->isCustomer()) {
            throw new \RuntimeException('Only authorized staff may record cash payments.');
        }

        return DB::transaction(function () use ($booking, $amount, $paymentType, $receivedBy, $transactionReference, $notes): Payment {
            $locked = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $reference = $this->normalizeReference($transactionReference);
            $existing = $this->findByReference($locked, $reference, 'CASH', $paymentType);

            if ($existing) {
                return $existing;
            }

            $balance = round((float) $locked->grand_total - (float) $locked->advance_paid, 2);

            if ($amount > $balance + 0.009) {
                throw new \RuntimeException(
                    'Amount exceeds the outstanding balance of PKR '.number_format(max(0, $balance), 2).'.'
                );
            }

            try {
                $payment = $this->createPayment(
                    $locked->tenant_id,
                    $locked->id,
                    $amount,
                    $paymentType,
                    'CASH',
                    $reference,
                    $receivedBy->id,
                    $notes ?: 'Cash received and physically verified at the venue.'
                );
            } catch (UniqueConstraintViolationException) {
                // A concurrent request won the race for this reference. Return
                // the canonical row rather than double-crediting the booking.
                $canonical = $this->findByReference($locked, $reference, 'CASH', $paymentType);

                if (! $canonical) {
                    throw new \RuntimeException('Duplicate cash reference could not be resolved.');
                }

                return $canonical;
            }

            $locked->advance_paid = round((float) $locked->advance_paid + $amount, 2);
            $locked->balance_due = round(max(0, (float) $locked->grand_total - (float) $locked->advance_paid), 2);
            $locked->save();

            AuditLog::create([
                'tenant_id' => $locked->tenant_id,
                'user_id' => $receivedBy->id,
                'action' => 'payments.record',
                'entity_type' => 'Payment',
                'entity_id' => $payment->id,
                'description' => sprintf(
                    'Recorded %s cash payment of PKR %s for booking %s (receipt %s).',
                    $paymentType,
                    number_format($amount, 2),
                    $locked->booking_reference,
                    $payment->receipt_no
                ),
                'ip_address' => request()?->ip(),
            ]);

            return $payment;
        });
    }

    /**
     * Record an authorized cash refund and reverse the booking ledger.
     * Callers MUST have already performed tenant and permission checks.
     */
    public function recordCashRefund(
        Booking $booking,
        float $amount,
        User $authorizedBy,
        ?string $transactionReference = null,
        ?string $notes = null
    ): Payment {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Refund amount must be greater than zero.');
        }

        if ($authorizedBy->isCustomer()) {
            throw new \RuntimeException('Only authorized staff may record cash refunds.');
        }

        return DB::transaction(function () use ($booking, $amount, $authorizedBy, $transactionReference, $notes): Payment {
            $locked = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $reference = $this->normalizeReference($transactionReference);
            $existing = $this->findByReference($locked, $reference, 'CASH', 'REFUND');

            if ($existing) {
                return $existing;
            }

            if ($amount > (float) $locked->advance_paid + 0.009) {
                throw new \RuntimeException('Refund exceeds the amount received for this booking.');
            }

            try {
                $payment = $this->createPayment(
                    $locked->tenant_id,
                    $locked->id,
                    $amount,
                    'REFUND',
                    'CASH',
                    $reference,
                    $authorizedBy->id,
                    $notes ?: 'Cash refund recorded by authorized staff.'
                );
            } catch (UniqueConstraintViolationException) {
                $canonical = $this->findByReference($locked, $reference, 'CASH', 'REFUND');

                if (! $canonical) {
                    throw new \RuntimeException('Duplicate refund reference could not be resolved.');
                }

                return $canonical;
            }

            $locked->advance_paid = round(max(0, (float) $locked->advance_paid - $amount), 2);
            $locked->balance_due = round(max(0, (float) $locked->grand_total - (float) $locked->advance_paid), 2);
            $locked->save();

            AuditLog::create([
                'tenant_id' => $locked->tenant_id,
                'user_id' => $authorizedBy->id,
                'action' => 'payments.refund',
                'entity_type' => 'Payment',
                'entity_id' => $payment->id,
                'description' => sprintf(
                    'Recorded cash refund of PKR %s for booking %s (receipt %s).',
                    number_format($amount, 2),
                    $locked->booking_reference,
                    $payment->receipt_no
                ),
                'ip_address' => request()?->ip(),
            ]);

            return $payment;
        });
    }

    private function normalizeReference(?string $reference): ?string
    {
        if ($reference === null) {
            return null;
        }

        $reference = trim($reference);

        return $reference === '' ? null : $reference;
    }

    private function findByReference(
        Booking $booking,
        ?string $reference,
        string $paymentMethod = 'CASH',
        ?string $paymentType = null
    ): ?Payment {
        if ($reference === null) {
            return null;
        }

        return Payment::where('tenant_id', $booking->tenant_id)
            ->where('booking_id', $booking->id)
            ->where('payment_method', $paymentMethod)
            ->where('transaction_reference', $reference)
            ->when($paymentType !== null, fn ($query) => $query->where('payment_type', $paymentType))
            ->first();
    }

    public function getPaymentSummary(string $tenantId, string $period = 'MONTHLY'): array
    {
        $query = Payment::where('tenant_id', $tenantId);

        $now = now();
        switch ($period) {
            case 'DAILY':
                $query->whereDate('created_at', $now->toDateString());
                break;
            case 'WEEKLY':
                $query->whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()]);
                break;
            case 'MONTHLY':
            default:
                $query->whereBetween('created_at', [$now->startOfMonth(), $now->endOfMonth()]);
                break;
            case 'YEARLY':
                $query->whereYear('created_at', $now->year);
                break;
        }

        $aggregate = (clone $query)
            ->selectRaw(
                "SUM(CASE WHEN payment_type = 'REFUND' THEN -amount ELSE amount END) as net_total, ".
                "SUM(CASE WHEN payment_type = 'REFUND' THEN 0 ELSE amount END) as inflow_total, ".
                'COUNT(*) as total_count'
            )
            ->first();

        $byMethod = (clone $query)
            ->selectRaw(
                "payment_method, SUM(CASE WHEN payment_type = 'REFUND' THEN -amount ELSE amount END) as total, COUNT(*) as count"
            )
            ->groupBy('payment_method')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->payment_method => ['total' => (float) $row->total, 'count' => (int) $row->count]]);

        return [
            'total_amount' => (float) ($aggregate->net_total ?? 0),
            'cash_inflow' => (float) ($aggregate->inflow_total ?? 0),
            'total_count' => (int) ($aggregate->total_count ?? 0),
            'period' => $period,
            'by_method' => $byMethod,
            'cash_only' => true,
        ];
    }
}
