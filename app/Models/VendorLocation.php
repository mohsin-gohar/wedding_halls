<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A physical location of a vendor business.
 *
 * A tenant (business) may operate several branches — a photography studio in
 * Gulberg and a second in DHA, a catering kitchen plus a banquet branch, and
 * so on. Locations are tenant-scoped and never leak across vendors.
 */
class VendorLocation extends Model
{
    use HasUuids;

    protected $table = 'vendor_locations';

    protected $fillable = [
        'tenant_id', 'name', 'slug', 'branch_type', 'address', 'area',
        'city', 'province', 'postal_code', 'latitude', 'longitude',
        'phone', 'email', 'service_areas', 'is_primary', 'is_active', 'notes',
    ];

    protected $casts = [
        'service_areas' => 'array',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public const BRANCH_TYPES = ['HEAD_OFFICE', 'BRANCH', 'STUDIO', 'WAREHOUSE', 'MOBILE'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeForTenant(Builder $query, ?string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function fullAddress(): string
    {
        return collect([$this->address, $this->area, $this->city])
            ->filter()
            ->implode(', ');
    }

    public static function generateSlug(string $name, string $tenantId): string
    {
        $base = Str::slug($name) ?: 'location';

        do {
            $slug = $base.'-'.strtolower(substr(bin2hex(random_bytes(2)), 0, 4));
        } while (static::query()->where('tenant_id', $tenantId)->where('slug', $slug)->exists());

        return $slug;
    }
}
