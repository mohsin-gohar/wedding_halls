<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CarFleet extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'model',
        'color',
        'hourly_rate',
        'capacity',
        'chauffeur_name',
        'is_available',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'capacity' => 'integer',
        'is_available' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function bookings(): MorphMany
    {
        return $this->morphMany(ServiceBooking::class, 'bookable');
    }

    public function isAvailable(): bool
    {
        return $this->is_available;
    }

    public function availabilityStatus(): string
    {
        return $this->is_available ? 'Available' : 'Unavailable';
    }

    public function hourlyCostForHours(int $hours): float
    {
        return (float) $this->hourly_rate * max(1, $hours);
    }
}
