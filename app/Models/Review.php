<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'hall_id',
        'booking_id',
        'reviewable_type',
        'reviewable_id',
        'rating',
        'title',
        'body',
        'status',
        'is_verified_booking',
        'vendor_reply',
        'moderated_at',
        'moderated_by',
    ];

    protected $casts = [
        'rating' => 'integer',
        'moderated_at' => 'datetime',
        'is_verified_booking' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hall(): BelongsTo
    {
        return $this->belongsTo(Hall::class);
    }

    /** Polymorphic target: a Hall today, a Tenant (vendor) in the marketplace. */
    public function reviewable()
    {
        return $this->morphTo();
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by')->withTrashed();
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public static function averageForHall(string $hallId): float
    {
        return (float) (self::approved()->where('hall_id', $hallId)->avg('rating') ?? 0);
    }

    public static function countForHall(string $hallId): int
    {
        return self::approved()->where('hall_id', $hallId)->count();
    }
}
