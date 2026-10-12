<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAttendance extends Model
{
    use BelongsToTenant, HasFactory, HasUuids;

    protected $table = 'staff_attendances';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'date',
        'status', // PRESENT, ABSENT, HALF_DAY, ON_LEAVE, LATE
        'check_in',
        'check_out',
        'overtime_hours',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'overtime_hours' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
