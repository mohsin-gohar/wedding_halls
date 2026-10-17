<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HallWishlist extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'hall_id',
        'tenant_id',
        'user_id',
        'session_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function hall(): BelongsTo
    {
        return $this->belongsTo(Hall::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function isWishlisted(string $hallId, ?string $userId = null, ?string $sessionId = null): bool
    {
        $query = self::where('hall_id', $hallId);
        if ($userId) {
            return $query->where('user_id', $userId)->exists();
        }
        if ($sessionId) {
            return $query->where('session_id', $sessionId)->exists();
        }
        return false;
    }

    public static function toggle(string $hallId, string $tenantId, ?string $userId = null, ?string $sessionId = null): bool
    {
        $query = self::where('hall_id', $hallId);
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId);
        }

        $existing = $query->first();
        if ($existing) {
            $existing->delete();
            return false;
        }

        self::create([
            'hall_id' => $hallId,
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'session_id' => $sessionId,
        ]);

        return true;
    }

    public static function countWishlists(string $hallId): int
    {
        return self::where('hall_id', $hallId)->count();
    }
}
