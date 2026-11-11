<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DispatchLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'recipient_type',
        'recipient_id',
        'channel',
        'template',
        'status',
        'payload',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'tenant_id' => 'uuid',
        'user_id' => 'uuid',
        'recipient_type' => 'string',
        'recipient_id' => 'string',
        'channel' => 'string',
        'template' => 'string',
        'status' => 'string',
        'sent_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function channelLabel(): string
    {
        return match($this->channel) {
            'WHATSAPP' => 'WhatsApp',
            'SMS' => 'SMS',
            'EMAIL' => 'Email',
            default => $this->channel,
        };
    }

    public function statusLabel(): string
    {
        return match($this->status) {
            'PENDING' => 'Pending',
            'SENT' => 'Sent',
            'DELIVERED' => 'Delivered',
            'FAILED' => 'Failed',
            default => $this->status,
        };
    }

    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }

    public function isSent(): bool
    {
        return $this->status === 'SENT';
    }

    public function isDelivered(): bool
    {
        return $this->status === 'DELIVERED';
    }

    public function isFailed(): bool
    {
        return $this->status === 'FAILED';
    }
}