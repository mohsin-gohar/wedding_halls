<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Hall;
use App\Models\Package;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class PublicHallDetails extends Component
{
    public Hall $hall;
    public string $activePhoto = '';

    public function mount(string $id)
    {
        // 1. Strict Visibility: Ensure hall exists, is active, and belongs to an active, approved vendor
        $this->hall = Hall::with(['venue', 'tenant'])->findOrFail($id);

        if (!$this->hall->is_active) {
            abort(404, 'This venue space is currently inactive.');
        }

        if (!$this->hall->tenant || !$this->hall->tenant->is_active || $this->hall->tenant->verification_status !== 'APPROVED') {
            abort(404, 'Venue is pending verification or currently unavailable.');
        }

        $images = is_array($this->hall->images) ? $this->hall->images : [];
        $this->activePhoto = !empty($this->hall->cover_image) 
            ? $this->hall->cover_image 
            : ($images[0] ?? 'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=1200&auto=format&fit=crop');
    }

    public function setPhoto(string $url)
    {
        // Validate that the requested photo is actually part of this hall's gallery or cover image
        $images = is_array($this->hall->images) ? $this->hall->images : [];
        if ($url === $this->hall->cover_image || in_array($url, $images)) {
            $this->activePhoto = $url;
        }
    }

    public function render()
    {
        // Fetch upcoming 14 days availability for this specific hall
        $today = Carbon::today();
        $days = [];
        for ($i = 0; $i < 14; $i++) {
            $days[] = $today->copy()->addDays($i);
        }

        $startDate = $days[0]->format('Y-m-d');
        $endDate = $days[count($days) - 1]->format('Y-m-d');

        // Note: Only select fields needed for availability display to prevent leaking customer private info
        $bookings = Booking::where('hall_id', $this->hall->id)
            ->whereBetween('event_date', [$startDate, $endDate])
            ->whereIn('status', ['CONFIRMED', 'HOLD'])
            ->select(['id', 'hall_id', 'event_date', 'slot_type', 'status', 'event_type'])
            ->get()
            ->keyBy(function ($b) {
                return $b->event_date->format('Y-m-d') . '_' . $b->slot_type;
            });

        // Ensure packages belong to this specific tenant or are platform standard offerings
        $packages = Package::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('tenant_id')
                  ->orWhere('tenant_id', $this->hall->tenant_id);
            })
            ->get();

        return view('livewire.public-hall-details', [
            'days' => $days,
            'bookings' => $bookings,
            'packages' => $packages,
            'images' => is_array($this->hall->images) ? $this->hall->images : [],
            'videos' => is_array($this->hall->videos) ? $this->hall->videos : [],
        ]);
    }
}
