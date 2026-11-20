<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Append-only idempotency record for inbound provider webhooks.
 */
class ProcessedWebhookMessage extends Model
{
    use HasUuids;

    protected $table = 'processed_webhook_messages';

    protected $fillable = [
        'provider',
        'message_id',
        'payload_hash',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];
}
