<?php

namespace App\Models;

use App\Concerns\GuardsCashOnlyMethod;
use App\Support\CashOnlyPayments;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePayment extends Model
{
    use GuardsCashOnlyMethod, HasUuids;

    protected $table = 'service_payments';

    protected $fillable = [
        'tenant_id', 'service_booking_id', 'customer_id', 'amount',
        'method', 'gateway', 'gateway_ref', 'reference', 'proof_path',
        'status', 'kind', 'verified_by', 'verified_at', 'notes', 'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'verified_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /** Methods accepted for NEW manual records. Historical labels are preserved separately. */
    public const METHODS = CashOnlyPayments::METHODS;

    /** Legacy labels retained only so historical records remain readable. */
    public const LEGACY_METHODS = ['JAZZCASH', 'EASYPAISA', 'CARD', 'WALLET', 'ONLINE', 'CREDIT_CARD'];

    public const STATUSES = ['PENDING', 'VERIFIED', 'REJECTED', 'REFUNDED'];

    public const KINDS = ['ADVANCE', 'INSTALLMENT', 'FULL', 'REFUND'];

    protected function cashOnlyMethodField(): string
    {
        return 'method';
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(ServiceBooking::class, 'service_booking_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /** Safe invoice document stream (authorization checked in controller). */
    public function proofUrl(): ?string
    {
        return $this->proof_path ? route('documents.vendor-payment-proof', ['id' => $this->id]) : null;
    }
}
