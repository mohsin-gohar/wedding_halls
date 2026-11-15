<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class VendorPackage extends Model
{
    use HasUuids;

    protected $table = 'vendor_packages';

    protected $fillable = [
        'tenant_id', 'vendor_category_id', 'name', 'description', 'items',
        'pricing_type', 'price', 'price_from', 'unit', 'image', 'is_active', 'sort',
    ];

    protected $casts = [
        'items' => 'array',
        'is_active' => 'boolean',
        'sort' => 'integer',
        'price' => 'decimal:2',
        'price_from' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function vendorCategory(): BelongsTo
    {
        return $this->belongsTo(VendorCategory::class);
    }

    public function bookings(): MorphMany
    {
        return $this->morphMany(ServiceBooking::class, 'bookable');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort');
    }

    public function priceLabel(): string
    {
        if ($this->price) {
            return 'PKR '.number_format((float) $this->price);
        }

        return $this->price_from
            ? 'PKR '.number_format((float) $this->price_from).($this->unit ? ' '.$this->unit : '')
            : 'On request';
    }
}
