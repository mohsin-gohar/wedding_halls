<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Hall extends Model
{
    use HasFactory, HasUuids;

    /**
     * Override route key name so that the Hall is resolved by its slug
     * (e.g. /venues/executive-crystal-lawn) instead of the raw UUID.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::creating(function (Hall $hall) {
            if (empty($hall->slug) && ! empty($hall->name)) {
                $base = Str::slug($hall->name);
                $slug = $base;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base.'-'.$counter++;
                }
                $hall->slug = $slug;
            }
        });
    }

    protected $fillable = [
        'tenant_id',
        'venue_id',
        'name',
        'slug',
        'min_capacity',
        'max_capacity',
        'base_rent',
        'tagline',
        'services',
        'operating_hours',
        'policies',
        'website_settings',
        'description',
        'cover_image',
        'images',
        'videos',
        'has_air_conditioning',
        'has_generator_backup',
        'has_bridal_room',
        'is_active',
    ];

    protected $casts = [
        'min_capacity' => 'integer',
        'max_capacity' => 'integer',
        'base_rent' => 'decimal:2',
        'services' => 'array',
        'website_settings' => 'array',
        'images' => 'array',
        'videos' => 'array',
        'has_air_conditioning' => 'boolean',
        'has_generator_backup' => 'boolean',
        'has_bridal_room' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function floorPlans(): HasMany
    {
        return $this->hasMany(FloorPlan::class);
    }

    public function hallViews(): HasMany
    {
        return $this->hasMany(HallView::class);
    }

    public function hallWishlists(): HasMany
    {
        return $this->hasMany(HallWishlist::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('status', Review::STATUS_APPROVED);
    }

    public function averageRating(): float
    {
        return Review::averageForHall($this->id);
    }

    public function reviewsCount(): int
    {
        return Review::countForHall($this->id);
    }

    /**
     * Get authoritative WhatsApp / Phone number for this specific hall.
     */
    public function getWhatsappNumber(): ?string
    {
        // 1. Direct hall-level WhatsApp or phone in website_settings
        if (! empty($this->website_settings['whatsapp_number'])) {
            return $this->website_settings['whatsapp_number'];
        }
        if (! empty($this->website_settings['phone'])) {
            return $this->website_settings['phone'];
        }

        // 2. Parent Venue phone
        if (! empty($this->venue?->phone)) {
            return $this->venue->phone;
        }

        // 3. Parent Tenant phone
        if (! empty($this->tenant?->phone)) {
            return $this->tenant->phone;
        }

        // 4. Hall Owner User phone
        $owner = $this->tenant?->users?->whereIn('role', ['ROLE_TENANT_OWNER', 'VENDOR', 'HALL_OWNER'])->first()
            ?? $this->tenant?->users?->first();
        if ($owner && ! empty($owner->phone)) {
            return $owner->phone;
        }

        return null;
    }

    /**
     * Clean WhatsApp number formatted for international wa.me links.
     * E.g. +92 300 7654321 or 03007654321 -> 923007654321
     */
    public function getCleanWhatsappNumber(): ?string
    {
        $phone = $this->getWhatsappNumber();
        if (! $phone) {
            return null;
        }

        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (empty($clean)) {
            return null;
        }

        // Convert domestic Pakistani 03XX... to 923XX...
        if (str_starts_with($clean, '03') && strlen($clean) === 11) {
            $clean = '92'.substr($clean, 1);
        }

        return $clean;
    }

    /**
     * Resolve a high-resolution luxury image for a service item.
     */
    public static function resolveServiceImage(array $service): string
    {
        if (! empty($service['image'])) {
            return $service['image'];
        }

        $haystack = strtolower(
            ($service['title'] ?? '').' '.
            ($service['category'] ?? '').' '.
            ($service['description'] ?? '').' '.
            ($service['icon'] ?? '')
        );

        if (preg_match('/bridal|bride|suite|dressing|makeup|parlor|parlour|room|hospitality/', $haystack)) {
            return 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?q=80&w=800&auto=format&fit=crop';
        }

        if (preg_match('/generator|caterpillar|power|electricity|backup|infrastructure|kva|diesel|bolt/', $haystack)) {
            return 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?q=80&w=800&auto=format&fit=crop';
        }

        if (preg_match('/decor|rose|flower|floral|walkway|stage|aisle|sofa|ambience|canopy|theme/', $haystack)) {
            return 'https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=800&auto=format&fit=crop';
        }

        if (preg_match('/valet|parking|car|chauffeur|driver|transport|logistics|fleet|truck/', $haystack)) {
            return 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?q=80&w=800&auto=format&fit=crop';
        }

        if (preg_match('/drone|camera|cinematography|video|led|screen|p3|wall|broadcast|photo|multimedia/', $haystack)) {
            return 'https://images.unsplash.com/photo-1508614589041-895b88991e3e?q=80&w=800&auto=format&fit=crop';
        }

        if (preg_match('/catering|food|buffet|menu|dish|bawarchi|feast|biryani|qorma|dinner|meal|utensils/', $haystack)) {
            return 'https://images.unsplash.com/photo-1555244162-803834f70033?q=80&w=800&auto=format&fit=crop';
        }

        if (preg_match('/sound|dj|music|audio|speaker|bass|acoustic/', $haystack)) {
            return 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?q=80&w=800&auto=format&fit=crop';
        }

        if (preg_match('/firework|cold fire|sparkle|pyro|entry/', $haystack)) {
            return 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=800&auto=format&fit=crop';
        }

        // Default luxury banquet celebration photography
        return 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=800&auto=format&fit=crop';
    }

    /**
     * Generate direct WhatsApp Click-to-Chat URL for this hall.
     */
    public function getWhatsappUrl(?string $customMessage = null): ?string
    {
        $clean = $this->getCleanWhatsappNumber();
        if (! $clean) {
            return null;
        }

        $message = $customMessage ?? "Hello {$this->name}, I am interested in checking date availability and packages on EventHub.";

        return 'https://wa.me/'.$clean.'?text='.urlencode($message);
    }
}
