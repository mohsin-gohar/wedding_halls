<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * API access token (mobile app / partner integration).
 *
 * Only the SHA-256 hash is persisted. The plain token is returned exactly
 * once, at creation time.
 */
class ApiToken extends Model
{
    protected $table = 'api_tokens';

    protected $fillable = [
        'user_id', 'name', 'token_hash', 'prefix', 'abilities',
        'last_used_at', 'expires_at', 'revoked_at', 'last_used_ip',
    ];

    protected $casts = [
        'abilities' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /**
     * Issue a token. Returns [model, plainToken] — the plain value is never
     * stored and cannot be shown again.
     *
     * @return array{0: self, 1: string}
     */
    public static function issue(User $user, string $name, ?array $abilities = null, ?\DateTimeInterface $expiresAt = null): array
    {
        do {
            $plain = Str::random(64);
            $hash = hash('sha256', $plain);
            $exists = static::query()->where('token_hash', $hash)->exists();
        } while ($exists);

        $token = static::create([
            'user_id' => $user->id,
            'name' => $name,
            'token_hash' => $hash,
            'prefix' => substr($plain, 0, 8),
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
        ]);

        return [$token, $plain];
    }

    /**
     * Resolve a plain-text bearer token to its owning user.
     */
    public static function resolve(?string $plain): ?User
    {
        if (! $plain || strlen($plain) < 32) {
            return null;
        }

        $token = static::query()
            ->with('user')
            ->where('token_hash', hash('sha256', $plain))
            ->whereNull('revoked_at')
            ->first();

        if (! $token) {
            return null;
        }

        if ($token->expires_at && $token->expires_at->isPast()) {
            return null;
        }

        $user = $token->user;

        if (! $user || $user->status !== 'ACTIVE') {
            return null;
        }

        $token->forceFill([
            'last_used_at' => now(),
            'last_used_ip' => request()->ip(),
        ])->saveQuietly();

        return $user;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function revoke(): void
    {
        $this->forceFill(['revoked_at' => now()])->save();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }
}
