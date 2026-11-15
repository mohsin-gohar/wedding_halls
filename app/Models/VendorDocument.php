<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorDocument extends Model
{
    use HasUuids;

    protected $table = 'vendor_documents';

    protected $fillable = [
        'tenant_id', 'doc_type', 'file_path', 'original_name',
        'status', 'reviewed_by', 'review_notes', 'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public const DOC_TYPES = ['CNIC', 'NTN', 'LICENSE', 'BUSINESS_REG', 'OTHER'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }
}
