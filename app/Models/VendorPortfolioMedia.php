<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPortfolioMedia extends Model
{
    use HasUuids;

    protected $table = 'vendor_portfolio_media';

    protected $fillable = [
        'tenant_id', 'type', 'path', 'thumbnail_path', 'caption', 'category', 'sort',
    ];

    protected $casts = [
        'sort' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeImages($query)
    {
        return $query->where('type', 'IMAGE')->orderBy('sort');
    }

    public function scopeVideos($query)
    {
        return $query->where('type', 'VIDEO')->orderBy('sort');
    }

    public function url(): string
    {
        return asset('storage/'.$this->path);
    }

    public function thumbUrl(): string
    {
        return asset('storage/'.($this->thumbnail_path ?: $this->path));
    }
}
