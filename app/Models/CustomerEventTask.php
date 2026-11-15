<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerEventTask extends Model
{
    use HasUuids;

    protected $table = 'customer_event_tasks';

    protected $fillable = ['customer_event_id', 'title', 'due_date', 'status', 'sort'];

    protected $casts = [
        'due_date' => 'date',
        'sort' => 'integer',
    ];

    public const STATUSES = ['PENDING', 'IN_PROGRESS', 'DONE'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(CustomerEvent::class, 'customer_event_id');
    }
}
