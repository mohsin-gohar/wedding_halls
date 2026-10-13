<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'booking_id',
        'amount',
        'payment_type',
        'payment_method',
        'receipt_no',
        'transaction_reference',
        'received_by_user_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function receivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    public static function generateReceiptNumber(): string
    {
        $prefix = 'WH-RCP-' . date('Ymd');
        $random = strtoupper(Str::random(4));
        $count = self::whereDate('created_at', today())->count() + 1;
        return sprintf('%s-%04d-%s', $prefix, $count, $random);
    }
}
