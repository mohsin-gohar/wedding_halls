<?php

namespace App\Livewire;

use App\Models\GuestRsvp;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public', ['title' => 'RSVP Your Invitation — EventHub Pakistan'])]
class PublicGuestRsvp extends Component
{
    public string $phone = '';

    public ?GuestRsvp $foundRsvp = null;

    public int $attendingGuests = 2;

    public string $statusResponse = 'ATTENDING'; // 'ATTENDING' or 'DECLINED'

    public ?string $feedbackMessage = null;

    public bool $isSubmitted = false;

    public function searchInvitation()
    {
        $this->feedbackMessage = null;
        $this->foundRsvp = null;

        $throttleKey = 'rsvp-search:'.request()->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $this->feedbackMessage = 'Too many lookup attempts. Please wait a minute and try again.';

            return;
        }
        RateLimiter::hit($throttleKey, 60);

        $cleanPhone = preg_replace('/[^0-9]/', '', $this->phone);

        if (strlen($cleanPhone) < 10) {
            $this->feedbackMessage = 'Please enter a valid complete mobile number (minimum 10 digits) to locate your invitation.';

            return;
        }

        $last10 = substr($cleanPhone, -10);

        $this->foundRsvp = GuestRsvp::with('booking.hall.venue')
            ->where(function ($q) use ($last10, $cleanPhone) {
                $q->where('phone', 'like', "%{$last10}")
                    ->orWhere('phone', $cleanPhone);
            })
            ->first();

        if (! $this->foundRsvp) {
            $this->feedbackMessage = 'No active wedding invitation found with this phone number. Please verify with the event host.';
        } else {
            $this->attendingGuests = max(1, $this->foundRsvp->attending_count);
        }
    }

    public function submitRsvp()
    {
        if (! $this->foundRsvp) {
            return;
        }

        $throttleKey = 'rsvp-submit:'.request()->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $this->feedbackMessage = 'Too many RSVP updates. Please wait a minute and try again.';

            return;
        }
        RateLimiter::hit($throttleKey, 60);

        $this->validate([
            'statusResponse' => 'required|in:ATTENDING,DECLINED',
            'attendingGuests' => 'required|integer|min:1|max:20',
        ]);

        $count = ($this->statusResponse === 'ATTENDING') ? (int) $this->attendingGuests : 0;

        $this->foundRsvp->update([
            'rsvp_status' => $this->statusResponse,
            'attending_count' => $count,
            'last_reply_message' => ($this->statusResponse === 'ATTENDING')
                ? "Online RSVP: Attending with {$count} guest(s)"
                : 'Online RSVP: Regret, cannot attend',
        ]);

        $this->isSubmitted = true;
        $this->feedbackMessage = 'Your RSVP has been recorded successfully! We look forward to welcoming you.';
    }

    public function render()
    {
        return view('livewire.public-guest-rsvp');
    }
}
