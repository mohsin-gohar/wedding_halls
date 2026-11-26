<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\GuestRsvp;
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
        if (! $user || (! $user->isSuperAdmin() && (! $user->tenant_id || $user->isCustomer()))) {
            abort(403, 'Unauthorized. Tenant staff access required.');
        }

        $query = Booking::whereIn('status', ['CONFIRMED', 'HOLD'])->latest();
        if (! $user->isSuperAdmin()) {
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
        $user = Auth::user();
        $query = GuestRsvp::where('booking_id', $val);
        if ($user && $user->tenant_id && ! $user->isSuperAdmin()) {
            $query->where('tenant_id', $user->tenant_id);
        }
        $firstRsvp = $query->first();
        if ($firstRsvp) {
            $this->simPhone = $firstRsvp->phone;
        } else {
            $this->simPhone = '';
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
        if (! $user || (! $user->isSuperAdmin() && (! $user->tenant_id || $user->isCustomer()))) {
            abort(403, 'Unauthorized.');
        }

        $query = Booking::query();
        if (! $user->isSuperAdmin()) {
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
        $user = Auth::user();
        $canonical = $this->normalizePhone($this->simPhone);

        if (strlen($canonical) < 10) {
            $this->simBotReply = '❌ Enter a complete mobile number to locate the invitation.';

            return;
        }

        // Candidate narrowing only; exact full-number filtering below is the
        // authority even when stored numbers contain spaces or dashes.
        $query = GuestRsvp::where('phone', 'like', '%'.substr($canonical, -7));
        if ($user && $user->tenant_id && ! $user->isSuperAdmin()) {
            $query->where('tenant_id', $user->tenant_id);
        }
        if ($this->selectedBookingId) {
            $query->where('booking_id', $this->selectedBookingId);
        }

        $matches = $query->limit(50)->get()
            ->filter(fn (GuestRsvp $rsvp): bool => $this->normalizePhone($rsvp->phone) === $canonical)
            ->values();

        if ($matches->count() !== 1) {
            $this->simBotReply = "❌ Number [{$this->simPhone}] did not match exactly one invitation in this event.";

            return;
        }

        $rsvp = $matches->first();

        if (! $rsvp) {
            $this->simBotReply = "❌ Number [{$this->simPhone}] not found in guest invitation registry.";

            return;
        }

        $body = trim($this->simMessage);
        $status = null;
        $count = null;
        $reply = 'Please reply YES, YES followed by your guest count, or NO to decline.';

        if (preg_match('/(?:\\bdecline\\b|\\bno\\b|\\bnahi\\b|\\bcannot\\b|\\bcancel\\b)/i', $body)) {
            $status = 'DECLINED';
            $count = 0;
            $reply = "Thank you {$rsvp->guest_name}. We have updated your status to Declined.";
        } elseif (preg_match('/(?:\\battending\\b|\\byes\\b|\\baunga\\b|\\bhaan\\b|\\bpersons?\\b|\\bpeople\\b|\\bguests?\\b|\\bhead\\b)[\\s:]*(\\d+)?/i', $body, $matches)
            || preg_match('/^(\\d+)$/', $body, $matches)) {
            $status = 'ATTENDING';
            $count = isset($matches[1]) && $matches[1] !== ''
                ? max(1, min(20, (int) $matches[1]))
                : 1;
            $reply = "Confirmed! Thank you {$rsvp->guest_name}, {$count} attendee(s) registered on the hall door checklist.";
        } else {
            $this->simBotReply = "🤖 Bot Response to {$rsvp->guest_name}: \"{$reply}\" (RSVP unchanged)";

            return;
        }

        $rsvp->update([
            'rsvp_status' => $status,
            'attending_count' => $count,
            'last_reply_message' => $body,
        ]);

        $this->simBotReply = "🤖 Bot Response to {$rsvp->guest_name}: \"{$reply}\" (DB Attendance Updated: {$count})";
    }

    /** Normalize local (03XX) and international (+923XX) numbers to one form. */
    private function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D/', '', (string) $phone) ?? '';

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return substr($digits, 1);
        }
        if (strlen($digits) === 12 && str_starts_with($digits, '92')) {
            return substr($digits, 2);
        }

        return $digits;
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

        $rsvpsQuery = GuestRsvp::query();
        if ($user && $user->tenant_id && ! $user->isSuperAdmin()) {
            $rsvpsQuery->where('tenant_id', $user->tenant_id);
        } elseif (! $user || ! $user->isSuperAdmin()) {
            $rsvpsQuery->whereRaw('1 = 0');
        }

        $rsvps = $this->selectedBookingId
            ? (clone $rsvpsQuery)->where('booking_id', $this->selectedBookingId)->latest()->get()
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
