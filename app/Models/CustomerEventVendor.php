<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerEventVendor extends Model
{
    use HasUuids;

    protected $table = 'customer_event_vendors';

    protected $fillable = ['customer_event_id', 'tenant_id', 'service_booking_id', 'status', 'note'];

    protected $casts = [];

    public const STATUSES = ['SHORTLISTED', 'REQUESTED', 'BOOKED', 'DROPPED'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(CustomerEvent::class, 'customer_event_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(ServiceBooking::class, 'service_booking_id');
    }
}
