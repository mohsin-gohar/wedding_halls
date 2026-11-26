<?php

namespace App\Models;

use App\Concerns\GuardsCashOnlyMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use GuardsCashOnlyMethod, HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'hall_id',
        'payroll_id',
        'category',
        'title',
        'amount',
        'expense_date',
        'payee',
        'payment_method',
        'receipt_number',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'amount' => 'float',
        'expense_date' => 'date',
        'is_voided' => 'boolean',
        'voided_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // All financial reports and operational totals exclude voided entries
        // unless an explicit audit-history query opts in.
        static::addGlobalScope('not_voided', function (Builder $query): void {
            $query->where('is_voided', false);
        });
    }

    public function scopeWithVoided(Builder $query): Builder
    {
        return $query->withoutGlobalScope('not_voided');
    }

    protected function cashOnlyMethodField(): string
    {
        return 'payment_method';
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function hall(): BelongsTo
    {
        return $this->belongsTo(Hall::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id')->withTrashed();
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by_user_id')->withTrashed();
    }
}
