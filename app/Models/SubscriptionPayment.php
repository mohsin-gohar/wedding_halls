<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'subscription_plan_id',
        'billing_cycle',
        'amount',
        'payment_method',
        'transaction_reference',
        'proof_attachment',
        'status',
        'notes',
        'approved_by_user_id',
        'approved_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'approved_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
