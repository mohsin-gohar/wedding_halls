<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class VendorService extends Model
{
    use HasUuids;

    protected $table = 'vendor_services';

    protected $fillable = [
        'tenant_id', 'vendor_category_id', 'name', 'description',
        'pricing_type', 'price_from', 'price_to', 'unit',
        'duration_minutes', 'highlights', 'is_active', 'sort',
    ];

    protected $casts = [
        'highlights' => 'array',
        'is_active' => 'boolean',
        'sort' => 'integer',
        'duration_minutes' => 'integer',
        'price_from' => 'decimal:2',
        'price_to' => 'decimal:2',
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
        if ($this->price_from && $this->price_to && (float) $this->price_from !== (float) $this->price_to) {
            return 'PKR '.number_format((float) $this->price_from).' – '.number_format((float) $this->price_to);
        }

        $price = $this->price_from ?? $this->price_to;

        return $price ? 'PKR '.number_format((float) $price).($this->unit ? ' '.$this->unit : '') : 'On request';
    }
}
