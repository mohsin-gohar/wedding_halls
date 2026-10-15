<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hall extends Model
{
    use HasFactory, HasUuids;

    /**
     * Override route key name so that the Hall is resolved by its slug
     * (e.g. /venues/executive-crystal-lawn) instead of the raw UUID.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::creating(function (Hall $hall) {
            if (empty($hall->slug) && !empty($hall->name)) {
                $base = \Illuminate\Support\Str::slug($hall->name);
                $slug = $base;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $counter++;
                }
                $hall->slug = $slug;
            }
        });
    }

    protected $fillable = [
        'tenant_id',
        'venue_id',
        'name',
        'slug',
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
