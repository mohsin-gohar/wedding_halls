<?php

namespace App\Services;

use App\Models\Venue;
use App\Models\Tenant;
use App\Models\Package;
use App\Models\Review;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class SearchService
{
    /** * Search venues with filters * * @param Request $request * @return array */
    public function searchVenues(Request $request): array
    {
        return Cache::remember("search_venues_{$request->cache_key}", 300, function () use ($request) {
            $query = Venue::with(['tenant', 'reviews'])
                ->where(function ($q) use ($request) {
                    // City filter
                    if ($city = $request->get('city')) {
                        $q->where('city', 'like', "%{$city%}");
                    }
                    
                    // Status filter
                    if ($status = $request->get('status')) {
                        $q->where('status', $status);
                    }
                    
                    // Capacity filter
                    if ($minCapacity = $request->get('min_capacity')) {
                        $q->where('max_capacity', '>=', $minCapacity);
                    }
                    if ($maxCapacity = $request->get('max_capacity')) {
                        $q->where('max_capacity', '<=', $maxCapacity);
                    }
                    
                    // Budget filter
                    if ($minRent = $request->get('min_rent')) {
                        $q->where('base_rent', '>=', $minRent);
                    }
                    if ($maxRent = $request->get('max_rent')) {
                        $q->where('base_rent', '<=', $maxRent);
                    }
                    
                    // Hall type filter
                    if ($hallType = $request->get('hall_type')) {
                        $q->where('hall_type', $hallType);
                    }
                    
                    // Featured only
                    if ($request->get('featured') === 'true') {
                        $q->where('is_featured', true);
                    }
                })
                ->where('is_active', true);
            
            // Sorting
            $sortBy = $request->get('sort_by', 'sort_order');
            $sortDir = $request->get('sort_dir', 'asc');
            $query->orderBy($sortBy, $sortDir);
            
            // Pagination
            $perPage = $request->get('per_page', 20);
            $page = $request->get('page', 1);
            
            $venues = $query->paginate($perPage, ['*'], 'page', $page);
            
            return [
                'venues' => $venues,
                'filters_applied' => [
                    'city' => $request->get('city'),
                    'status' => $request->get('status'),
                    'min_capacity' => $request->get('min_capacity'),
                    'max_capacity' => $request->get('max_capacity'),
                    'min_rent' => $request->get('min_rent'),
                    'max_rent' => $request->get('max_rent'),
                    'hall_type' => $request->get('hall_type'),
                    'featured' => $request->get('featured'),
                ],
                'total' => $venues->total(),
                'current_page' => $venues->currentPage(),
            ];
        });
    }
    
    /** * Search with autocomplete * * @param string $term * @return array */
    public function autocomplete(string $term): array
    {
        $cacheKey = "search_autocomplete_{$term}";
        
        return Cache::remember($cacheKey, 600, function () use ($term) {
            $results = Venue::where('is_active', true)
                ->where('name', 'like', "%{$term%}")
                ->orWhere('city', 'like', "%{$term%}")
                ->limit(5)
                ->get(['id', 'name', 'city', 'base_rent', 'max_capacity', 'slug'])
                ->map(function ($v) {
                    return [
                        'value' => $v->name . ' (' . $v->city . ')',
                        'label' => $v->name,
                        'city' => $v->city,
                        'rent' => 'PKR ' . number_format($v->base_rent),
                        'capacity' => "Cap: {$v->max_capacity}",
                        'slug' => $v->slug,
                    ];
                })
                ->toArray();
            
            return [
                'results' => $results,
                'term' => $term,
            ];
        });
    }
    
    /** * Get venue filters options * * @return array */
    public function getFilterOptions(): array
    {
        return [
            'cities' => Venue::where('is_active', true)
                ->select('city')
                ->distinct()
                ->pluck('city')
                ->filter()
                ->toArray(),
            
            'hall_types' => Venue::where('is_active', true)
                ->select('hall_type')
                ->distinct()
                ->pluck('hall_type')
                ->filter()
                ->mapWithKeys(function ($type) {
                    $types = [
                        'banquet_ballroom' => 'Banquet Ballroom',
                        'open_air marquee' => 'Open Air Marquee',
                        'garden lawn' => 'Garden Lawn',
                        'rooftop' => 'Rooftop',
                        'private dining room' => 'Private Dining Room',
                    ];
                    return [$type => $types[$type] ?? ucfirst($type)];
                })
                ->toArray(),
            
            'capacity_ranges' => [
                'all' => 'Any Capacity',
                '1-50' => 'Up to 50 guests',
                '51-100' => '51-100 guests',
                '101-200' => '101-200 guests',
                '201-300' => '201-300 guests',
                '300+' => '300+ guests',
            ],
            
            'price_ranges' => [
                'all' => 'Any Price',
                '0-50k' => 'Up to PKR 50,000',
                '50k-100k' => 'PKR 50,001 - 100,000',
                '100k-200k' => 'PKR 100,001 - 200,000',
                '200k-500k' => 'PKR 200,001 - 500,000',
                '500k+' => 'PKR 500,001+',
            ],
        ];
    }
    
    /** * Get available shifts for a specific date and venue * * @param string $venueId * @param string $date * @return array */
    public function getAvailableShifts(string $venueId, string $date): array
    {
        $cacheKey = "shifts_{$venueId}_{$date}";
        
        return Cache::remember($cacheKey, 120, function () use ($venueId, $date) {
            $venue = Venue::find($venueId);
            if (!$venue) {
                return ['error' => 'Venue not found'];
            }
            
            // Get all bookings for this venue on this date
            $bookedShifts = Booking::where('venue_id', $venueId)
                ->where('event_date', $date)
                ->whereIn('status', ['CONFIRMED', 'HOLD'])
                ->pluck('slot_type')
                ->toArray();
            
            $allShifts = ['AFTERNOON', 'EVENING', 'NIGHT'];
            $availableShifts = [];
            
            foreach ($allShifts as $shift) {
                if (!in_array($shift, $bookedShifts)) {
                    $availableShifts[$shift] = [
                        'available' => true,
                        'label' => match($shift) {
                            'AFTERNOON' => 'Lunch (2 PM - 6 PM)',
                            'EVENING' => 'Dinner (6 PM - 10 PM)',
                            'NIGHT' => 'Late Night (10 PM - 2 AM)',
                        },
                        'status' => 'Available',
                    ];
                } else {
                    $availableShifts[$shift] = [
                        'available' => false,
                        'label' => match($shift) {
                            'AFTERNOON' => 'Lunch (2 PM - 6 PM)',
                            'EVENING' => 'Dinner (6 PM - 10 PM)',
                            'NIGHT' => 'Late Night (10 PM - 2 AM)',
                        },
                        'status' => 'Booked',
                    ];
                }
            }
            
            return [
                'venue_name' => $venue->name,
                'date' => $date,
                'shifts' => $availableShifts,
                'total_shifts' => count($allShifts),
                'available_count' => count($availableShifts),
            ];
        });
    }
    
    /** * Get search suggestions * * @param string $term * @return array */
    public function getSuggestions(string $term): array
    {
        $cacheKey = "search_suggestions_{$term}";
        
        return Cache::remember($cacheKey, 300, function () use ($term) {
            $suggestions = [];
            
            // Venue suggestions
            $venues = Venue::where('is_active', true)
                ->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term%}")
                      ->orWhere('city', 'like', "%{$term%}");
                })
                ->take(5)
                ->get();
            
            foreach ($venues as $v) {
                $suggestions[] = [
                    'type' => 'venue',
                    'id' => $v->id,
                    'text' => $v->name . ' (' . $v->city . ')',
                    'action' => "View venue:/{$v->slug}",
                ];
            }
            
            // Package suggestions
            $packages = Package::where('is_active', true)
                ->where('name', 'like', "%{$term%}")
                ->take(3)
                ->get();
            
            foreach ($packages as $p) {
                $suggestions[] = [
                    'type' => 'package',
                    'id' => $p->id,
                    'text' => $p->name . ' - ' . 'PKR ' . number_format($p->price),
                    'action' => "View package",
                ];
            }
            
            return [
                'suggestions' => $suggestions,
                'term' => $term,
            ];
        });
    }
}