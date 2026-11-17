<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrection extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'attendance_id',
        'user_id',
        'date',
        'old_status',
        'new_status',
        'old_check_in',
        'new_check_in',
        'old_check_out',
        'new_check_out',
        'old_overtime',
        'new_overtime',
        'reason',
        'actor_id',
    ];

    protected $casts = [
        'date' => 'date',
        'old_overtime' => 'decimal:2',
        'new_overtime' => 'decimal:2',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(StaffAttendance::class, 'attendance_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
