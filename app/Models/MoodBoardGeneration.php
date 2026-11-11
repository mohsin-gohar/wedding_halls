<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoodBoardGeneration extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'prompt',
        'status',
        'image_url',
        'vendor_matches',
        'color_scheme',
    ];

    protected $casts = [
        'tenant_id' => 'uuid',
        'user_id' => 'uuid',
        'prompt' => 'string',
        'status' => 'string',
        'image_url' => 'string',
        'vendor_matches' => 'array',
        'color_scheme' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'PENDING' => 'Pending',
            'GENERATING' => 'Generating',
            'COMPLETED' => 'Completed',
            'FAILED' => 'Failed',
            default => $this->status,
        };
    }

    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }

    public function isGenerating(): bool
    {
        return $this->status === 'GENERATING';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'COMPLETED';
    }

    public function isFailed(): bool
    {
        return $this->status === 'FAILED';
    }

    public function vendorMatchCount(): int
    {
        return count($this->vendor_matches ?? []);
    }
}
