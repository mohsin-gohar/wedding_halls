<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory, HasUuids;

    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            if (empty($booking->booking_reference)) {
                $booking->booking_reference = static::generateReference();
            }
        });
    }

    public static function generateReference(): string
    {
        return 'BK-' . strtoupper(now()->format('Ym')) . '-' . strtoupper(substr(uniqid(), -5));
    }

    protected $fillable = [
        'tenant_id',
        'hall_id',
        'package_id',
        'booking_reference',
        'created_by_user_id',
        'customer_id',
        'customer_name',
        'customer_phone',
        'customer_cnic',
        'customer_email',
        'event_type',
        'event_date',
        'slot_type',
        'guest_count',
        'per_head_rate',
        'hall_rent',
        'addons_total',
        'discount_amount',
        'tax_amount',
        'grand_total',
        'advance_paid',
        'balance_due',
        'custom_menu_json',
        'addons_json',
        'status',
        'pipeline_stage',
        'crm_stage',
        'notes',
    ];

    protected $casts = [
        'event_date' => 'date',
        'guest_count' => 'integer',
        'per_head_rate' => 'decimal:2',
        'hall_rent' => 'decimal:2',
        'addons_total' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'advance_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'custom_menu_json' => 'array',
        'addons_json' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function hall(): BelongsTo
    {
        return $this->belongsTo(Hall::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function kitchenKot(): HasOne
    {
        return $this->hasOne(KitchenKot::class);
    }

    public function utilityLog(): HasOne
    {
        return $this->hasOne(UtilityLog::class);
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(GuestRsvp::class);
    }

    public function floorPlan(): HasOne
    {
        return $this->hasOne(FloorPlan::class);
    }

    public function calculateTotal(): float
    {
        $cateringTotal = (float) $this->per_head_rate * (int) $this->guest_count;
        $subtotal = $cateringTotal + (float) $this->hall_rent + (float) $this->addons_total;
        $total = ($subtotal - (float) $this->discount_amount) + (float) $this->tax_amount;
        return max(0, round($total, 2));
    }

    public function updatePaymentBalance(): void
    {
        $paid = (float) $this->payments()->sum('amount');
        $this->advance_paid = $paid;
        $this->balance_due = max(0, (float) $this->grand_total - $paid);
        $this->save();
    }
}
