<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformTask extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'title',
        'category',
        'priority',
        'due_date',
        'is_completed',
        'completed_at',
        'created_by_user_id',
        'notes',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'due_date' => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id')->withTrashed();
    }
}
