<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitchenKot extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'booking_id',
        'kot_number',
        'guest_count',
        'raw_meat_kg',
        'raw_rice_kg',
        'raw_oil_liters',
        'ingredient_details',
        'dishes_summary',
        'preparation_status',
        'custom_instructions',
        'chef_notes',
    ];

    protected $casts = [
        'guest_count' => 'integer',
        'raw_meat_kg' => 'decimal:2',
        'raw_rice_kg' => 'decimal:2',
        'raw_oil_liters' => 'decimal:2',
        'ingredient_details' => 'array',
        'dishes_summary' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public static function generateKotNumber(): string
    {
        $prefix = 'KOT-'.date('ymd');
        $count = self::whereDate('created_at', today())->count() + 1;

        return sprintf('%s-%03d', $prefix, $count);
    }
}
