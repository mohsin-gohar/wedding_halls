<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorCategory extends Model
{
    protected $table = 'vendor_categories';

    protected $fillable = [
        'name', 'slug', 'tagline', 'icon', 'hero_color',
        'booking_mode', 'features', 'description', 'is_active', 'sort',
    ];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];

    /** Tenants (vendors) in this category. */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(VendorService::class);
    }

    /** Valid booking flows — drives the category-specific booking UI. */
    public const BOOKING_MODES = [
        'SHIFT_VENUE',   // Halls: shift-based slot exclusivity (existing flow)
        'DATE_EVENT',    // Full-day event booking (caterers, planners, decorators)
        'HOURLY',        // Hourly sessions (photographers, transport)
        'APPOINTMENT',   // Slot appointments (makeup artists, salons)
        'PER_HEAD',      // Guest-count pricing (caterers, food stations)
        'PROJECT',       // Project/contract based (decoration, planning)
        'CONSULTATION',  // Consultation-first (planners, destination weddings)
        'TRANSPORT',     // Vehicle reservations
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort');
    }

    public static function slugToId(string $slug): ?int
    {
        return static::query()->where('slug', $slug)->value('id');
    }
}
