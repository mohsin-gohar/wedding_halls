<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ServiceBooking extends Model
{
    use HasUuids;

    protected $table = 'service_bookings';

    protected $fillable = [
        'tenant_id', 'vendor_category_id', 'bookable_type', 'bookable_id',
        'customer_id', 'rfq_bid_id', 'customer_event_id',
        'booking_reference', 'event_name', 'event_date', 'start_time', 'end_time',
        'hours', 'guest_count', 'requirements', 'requirements_json',
        'quoted_amount', 'discount_amount', 'advance_amount', 'paid_amount',
        'commission_amount', 'net_vendor_amount',
        'status', 'payment_status', 'deliverables_json', 'delivery_status',
        'cancellation_reason', 'cancellation_policy',
        'confirmed_at', 'completed_at', 'cancelled_at',
    ];

    protected $casts = [
        'event_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'hours' => 'decimal:2',
        'guest_count' => 'integer',
        'requirements_json' => 'array',
        'deliverables_json' => 'array',
        'quoted_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'advance_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'net_vendor_amount' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public const STATUSES = [
        'PENDING_VENDOR', 'QUOTE_SENT', 'ACCEPTED', 'CONFIRMED', 'IN_PROGRESS',
        'COMPLETED', 'CANCELLED', 'DECLINED',
    ];

    public const PAYMENT_STATUSES = ['UNPAID', 'ADVANCE_PAID', 'PARTIAL', 'PAID', 'REFUNDED'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function vendorCategory(): BelongsTo
    {
        return $this->belongsTo(VendorCategory::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function rfqBid(): BelongsTo
    {
        return $this->belongsTo(RFQBid::class);
    }

    public function customerEvent(): BelongsTo
    {
        return $this->belongsTo(CustomerEvent::class);
    }

    public function bookable(): MorphTo
    {
        return $this->morphTo();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ServicePayment::class);
    }

    public function verifiedPayments(): HasMany
    {
        return $this->hasMany(ServicePayment::class)->where('status', 'VERIFIED');
    }

    /** Human-friendly reference for UI/logs. */
    public function displayReference(): string
    {
        return $this->booking_reference;
    }

    public function scopeForTenant(Builder $query, ?string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForCustomer(Builder $query, int $userId): Builder
    {
        return $query->where('customer_id', $userId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['ACCEPTED', 'CONFIRMED', 'IN_PROGRESS']);
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, ['PENDING_VENDOR', 'QUOTE_SENT', 'ACCEPTED', 'CONFIRMED'], true);
    }

    public function balanceDue(): float
    {
        return max(0, (float) $this->quoted_amount - (float) $this->discount_amount - (float) $this->paid_amount);
    }

    /** Generate a unique human-readable reference (RG-SB-XXXXXX). */
    public static function generateReference(): string
    {
        do {
            $reference = 'RG-SB-'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        } while (static::query()->where('booking_reference', $reference)->exists());

        return $reference;
    }
}
