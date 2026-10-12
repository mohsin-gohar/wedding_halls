<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hall extends Model
{
    use BelongsToTenant, HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'venue_id',
        'name',
        'min_capacity',
        'max_capacity',
        'base_rent',
        'tagline',
        'services',
        'operating_hours',
        'policies',
        'website_settings',
        'description',
        'cover_image',
        'images',
        'videos',
        'has_air_conditioning',
        'has_generator_backup',
        'has_bridal_room',
        'is_active',
    ];

    protected $casts = [
        'min_capacity' => 'integer',
        'max_capacity' => 'integer',
        'base_rent' => 'decimal:2',
        'services' => 'array',
        'website_settings' => 'array',
        'images' => 'array',
        'videos' => 'array',
        'has_air_conditioning' => 'boolean',
        'has_generator_backup' => 'boolean',
        'has_bridal_room' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function floorPlans(): HasMany
    {
        return $this->hasMany(FloorPlan::class);
    }
}
