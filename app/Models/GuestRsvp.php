<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestRsvp extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'booking_id',
        'guest_name',
        'phone',
        'attending_count',
        'rsvp_status',
        'last_reply_message',
    ];

    protected $casts = [
        'attending_count' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
