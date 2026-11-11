<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftDefinition extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'start_time',
        'end_time',
        'multiplier',
        'is_active',
    ];

    protected $casts = [
        'start_time' => 'time',
        'end_time' => 'time',
        'multiplier' => 'decimal:2',
    ];

    public function tenantVenueShifts(): HasMany
    {
        return $this->hasManyThrough(
            VenueShift::class,
            Venue::class,
            'id',          // Foreign key on venue_shifts
            'id',          // Foreign key on venues
            'id',          // Local key on shift_definitions
            'venue_id'     // Local key on venues
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function lunch(): self
    {
        return $this->where('slug', 'lunch');
    }

    public function dinner(): self
    {
        return $this->where('slug', 'dinner');
    }

    public function lateNight(): self
    {
        return $this->where('slug', 'late_night');
    }

    public function fullDay(): self
    {
        return $this->where('slug', 'full_day');
    }
}
