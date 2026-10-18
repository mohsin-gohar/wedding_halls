<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\GuestRsvp;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WhatsAppRsvpManager extends Component
{
    public ?string $selectedBookingId = null;

    // RSVP Add Modal
    public string $guest_name = '';
    public string $phone = '';

    // Interactive WhatsApp Bot Simulator
    public string $simPhone = '';
    public string $simMessage = 'Attending: 4';
    public ?string $simBotReply = null;

    public function mount()
    {
        $user = Auth::user();
        if (!$user || (!$user->isSuperAdmin() && (!$user->tenant_id || $user->isCustomer()))) {
            abort(403, 'Unauthorized. Tenant staff access required.');
        }

        $query = Booking::whereIn('status', ['CONFIRMED', 'HOLD'])->latest();
        if (!$user->isSuperAdmin()) {
            $query->where('tenant_id', $user->tenant_id);
        }
        $firstBooking = $query->first();

        if ($firstBooking) {
            $this->selectedBookingId = $firstBooking->id;
            $firstRsvp = GuestRsvp::where('booking_id', $firstBooking->id)->first();
            if ($firstRsvp) {
                $this->simPhone = $firstRsvp->phone;
            }
        }
    }

    public function updatedSelectedBookingId($val)
    {
        $firstRsvp = GuestRsvp::where('booking_id', $val)->first();
        if ($firstRsvp) {
            $this->simPhone = $firstRsvp->phone;
        }
    }

    public function addGuest()
    {
        $this->validate([
            'guest_name' => 'required|string|min:3',
            'phone' => 'required|string|min:7',
            'selectedBookingId' => 'required|exists:bookings,id',
        ]);

        $user = Auth::user();
        if (!$user || (!$user->isSuperAdmin() && (!$user->tenant_id || $user->isCustomer()))) {
            abort(403, 'Unauthorized.');
        }

        $query = Booking::query();
        if (!$user->isSuperAdmin()) {
            $query->where('tenant_id', $user->tenant_id);
        }
        $booking = $query->findOrFail($this->selectedBookingId);

        GuestRsvp::create([
            'tenant_id' => $booking->tenant_id,
            'booking_id' => $booking->id,
            'guest_name' => $this->guest_name,
            'phone' => $this->phone,
            'attending_count' => 1,
            'rsvp_status' => 'TENTATIVE',
        ]);

        $this->simPhone = $this->phone;
        $this->guest_name = '';
        $this->phone = '';
    }

    public function simulateWhatsAppReply()
    {
        $cleanedPhone = preg_replace('/[^0-9]/', '', $this->simPhone);
        $last7 = substr($cleanedPhone, -7);

        $user = Auth::user();
        $query = GuestRsvp::where('phone', 'like', "%{$last7}%")->latest();
        if ($user && $user->tenant_id) {
            $query->where('tenant_id', $user->tenant_id);
        }
        $rsvp = $query->first();

        if (!$rsvp) {
            $this->simBotReply = "❌ Number [{$this->simPhone}] not found in guest invitation registry.";
            return;
        }

        $body = trim($this->simMessage);
        $status = 'ATTENDING';
        $count = 1;
        $reply = '';

        if (preg_match('/(decline|no|nahi|cannot|cancel)/i', $body)) {
            $status = 'DECLINED';
            $count = 0;
            $reply = "Thank you {$rsvp->guest_name}. We have updated your status to Declined.";
        } elseif (preg_match('/(?:attending|yes|aunga|haan|persons?|people|guests?|head)[\s:]*(\d+)/i', $body, $matches)) {
            $status = 'ATTENDING';
            $count = (int) $matches[1];
            $reply = "Confirmed! Thank you {$rsvp->guest_name}, {$count} attendee(s) registered on the hall door checklist.";
        } elseif (preg_match('/^(\d+)$/', $body, $matches)) {
            $status = 'ATTENDING';
            $count = (int) $matches[1];
            $reply = "Confirmed! {$count} guest(s) recorded for {$rsvp->guest_name}.";
        } else {
            $reply = "Thank you {$rsvp->guest_name}! Your response has been noted.";
        }

        $rsvp->update([
            'rsvp_status' => $status,
            'attending_count' => $count,
            'last_reply_message' => $body,
        ]);

        $this->simBotReply = "🤖 Bot Response to {$rsvp->guest_name}: \"{$reply}\" (DB Attendance Updated: {$count})";
    }

    public function render()
    {
        $user = Auth::user();
        $query = Booking::whereIn('status', ['CONFIRMED', 'HOLD'])->latest();
        if ($user && $user->tenant_id) {
            $query->where('tenant_id', $user->tenant_id);
        } else {
            $query->whereRaw('1 = 0');
        }
        $bookings = $query->take(15)->get();

        $rsvps = $this->selectedBookingId
            ? GuestRsvp::where('booking_id', $this->selectedBookingId)->latest()->get()
            : collect([]);

        $attendingCount = $rsvps->where('rsvp_status', 'ATTENDING')->sum('attending_count');
        $declinedCount = $rsvps->where('rsvp_status', 'DECLINED')->count();
        $tentativeCount = $rsvps->where('rsvp_status', 'TENTATIVE')->count();

        return view('livewire.whats-app-rsvp-manager', [
            'bookings' => $bookings,
            'rsvps' => $rsvps,
            'attendingCount' => $attendingCount,
            'declinedCount' => $declinedCount,
            'tentativeCount' => $tentativeCount,
        ]);
    }
}
