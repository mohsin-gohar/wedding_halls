<?php

namespace App\Models;

use App\Support\PlatformMetrics;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory, HasUuids;

    protected static function booted(): void
    {
        // Audit history is append-only: financial accountability requires that
        // entries can never be rewritten or erased through the application.
        static::updating(static function (): void {
            throw new \LogicException('Audit log entries are append-only and cannot be modified.');
        });

        static::deleting(static function (): void {
            throw new \LogicException('Audit log entries are append-only and cannot be deleted.');
        });

        // Any privileged mutation that is audited must also invalidate the
        // cached Super Admin counters immediately.
        static::created(static function (): void {
            PlatformMetrics::flush();
        });
    }

    protected $fillable = [
        'tenant_id',
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'description',
        'ip_address',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class->withTrashed();
    }

    public static function log(string $action, string $description, ?string $tenantId = null, ?string $entityType = null, ?string $entityId = null): self
    {
        return self::create([
            'tenant_id' => $tenantId ?? (auth()->check() ? auth()->user()->tenant_id : null),
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'ip_address' => request()->ip(),
        ]);
    }
}
