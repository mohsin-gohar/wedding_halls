<?php

namespace App\Services;

use App\Models\Hall;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class SearchService
{
    /**
     * Search halls with filters.
     */
    public function searchHalls(
        string $search = '',
        string $city = 'All',
        int $guests = 0,
        string $date = '',
        string $shift = '',
        int $perPage = 12
    ): array {
        $cacheKey = 'search:halls:'.md5($search.'|'.$city.'|'.$guests.'|'.$date.'|'.$shift.'|'.$perPage);

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($search, $city, $guests, $date, $shift, $perPage) {
            $query = Hall::with(['venue', 'tenant'])
                ->where('is_active', true)
                ->whereHas('tenant', function ($q) {
                    $q->where('is_active', true)
                        ->where('verification_status', 'APPROVED');
                });

            if ($city !== 'All' && $city !== '') {
                $query->whereHas('venue', function ($q) use ($city) {
                    $q->where('city', $city);
                });
            }

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%')
                        ->orWhere('tagline', 'like', '%'.$search.'%');
                });
            }

            if ($guests > 0) {
                $query->where('max_capacity', '>=', $guests);
            }

            if ($date !== '' && $shift !== '') {
                $eventDate = Carbon::parse($date);
                $query->whereDoesntHave('bookings', function ($booking) use ($eventDate, $shift) {
                    $booking->whereIn('status', ['HOLD', 'CONFIRMED'])
                        ->whereDate('event_date', $eventDate->toDateString())
                        ->where(function ($slot) use ($shift) {
                            $slot->where('slot_type', $shift)
                                ->orWhere('slot_type', 'FULL_DAY');
                        });
                });
            }

            $halls = $query->orderBy('name')->paginate($perPage);

            $cities = Venue::where('is_active', true)
                ->distinct('city')
                ->pluck('city');

            return [
                'halls' => $halls,
                'cities' => $cities,
                'total' => $halls->total(),
            ];
        });
    }

    /**
     * Get search suggestions for autocomplete.
     */
    public function getSuggestions(string $query, int $limit = 10): array
    {
        if (trim($query) === '') {
            return [];
        }

        $cacheKey = 'search:suggestions:'.md5($query.'|'.$limit);

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($query, $limit) {
            $halls = Hall::with(['venue', 'tenant'])
                ->where('is_active', true)
                ->whereHas('tenant', function ($q) {
                    $q->where('is_active', true)
                        ->where('verification_status', 'APPROVED');
                })
                ->where('name', 'like', '%'.trim($query).'%')
                ->limit($limit)
                ->get();

            return $halls->map(function ($hall) {
                return [
                    'id' => $hall->id,
                    'name' => $hall->name,
                    'city' => $hall->venue?->city,
                    'slug' => $hall->slug,
                    'image' => $hall->cover_image,
                ];
            })->toArray();
        });
    }

    /**
     * Clear search cache when hall data changes.
     */
    public static function clearCache(): void
    {
        Cache::flush();
    }
}
