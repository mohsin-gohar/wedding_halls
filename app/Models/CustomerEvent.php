<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerEvent extends Model
{
    use HasUuids;

    protected $table = 'customer_events';

    protected $fillable = [
        'user_id', 'name', 'event_type', 'event_date', 'city', 'venue_text',
        'guest_count', 'estimated_budget', 'notes', 'status',
    ];

    protected $casts = [
        'event_date' => 'date',
        'guest_count' => 'integer',
        'estimated_budget' => 'decimal:2',
    ];

    public const EVENT_TYPES = ['MEHNDI', 'BARAAT', 'WALIMA', 'ENGAGEMENT', 'BIRTHDAY', 'CORPORATE', 'WEDDING', 'OTHER'];

    public const STATUSES = ['PLANNING', 'BOOKED', 'COMPLETED', 'CANCELLED'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(CustomerEventVendor::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(CustomerEventTask::class)->orderBy('sort');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(ServiceBooking::class);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /** Total verified spend across bookings linked to this event. */
    public function paidTotal(): float
    {
        return (float) $this->bookings()->sum('paid_amount');
    }
}
