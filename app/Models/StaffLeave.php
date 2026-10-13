<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffLeave extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'staff_leaves';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'leave_type', // CASUAL, SICK, ANNUAL, EMERGENCY
        'start_date',
        'end_date',
        'days',
        'reason',
        'status', // PENDING, APPROVED, REJECTED
        'action_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_by');
    }
}
