<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VenueShift extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'venue_id',
        'event_date',
        'shift_definition_id',
        'min_capacity',
        'max_capacity',
        'base_rent',
        'commission_rate',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'base_rent' => 'decimal:2',
        'commission_rate' => 'decimal:2',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function shiftDefinition(): BelongsTo
    {
        return $this->belongsTo(ShiftDefinition::class);
    }

    public function isAvailable(): bool
    {
        return $this->is_active;
    }

    public function statusLabel(): string
    {
        return $this->is_active ? 'Available' : 'Maintenance';
    }
}
