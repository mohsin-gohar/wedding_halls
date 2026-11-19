<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorCategory extends Model
{
    protected $table = 'vendor_categories';

    protected $fillable = [
        'parent_id', 'name', 'slug', 'tagline', 'icon', 'image', 'hero_color',
        'booking_mode', 'features', 'description', 'seo_title', 'seo_description',
        'is_active', 'sort',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'features' => 'array',
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];

    /** Parent category (null for top-level). */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** Direct subcategories. */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort')->orderBy('name');
    }

    /** All descendant subcategories (recursive, one level eager-load helper). */
    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    /** Tenants (vendors) in this category. */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(VendorService::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /** Valid booking flows — drives the category-specific booking UI. */
    public const BOOKING_MODES = [
        'SHIFT_VENUE',   // Halls: shift-based slot exclusivity (existing flow)
        'DATE_EVENT',    // Full-day event booking (caterers, planners, decorators)
        'HOURLY',        // Hourly blocks (photographers, transport)
        'APPOINTMENT',   // Time-slot appointments (makeup, consultations)
        'PER_HEAD',      // Per-head catering/banquet pricing
        'PROJECT',       // Custom project quotation (decor, planning)
        'CONSULTATION',  // Consultation-based (planners)
        'TRANSPORT',     // Vehicle/fleet booking
    ];

    public function isVenueCategory(): bool
    {
        return $this->booking_mode === 'SHIFT_VENUE';
    }

    public function isTopLevel(): bool
    {
        return $this->parent_id === null;
    }
}
