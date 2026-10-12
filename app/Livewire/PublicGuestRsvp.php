<?php

namespace App\Livewire;

use App\Models\GuestRsvp;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
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
        $cleanPhone = preg_replace('/[^0-9]/', '', $this->phone);

        $this->foundRsvp = GuestRsvp::with('booking.hall.venue')
            ->where(function ($q) use ($cleanPhone) {
                $q->where('phone', 'like', '%'.$this->phone.'%')
                    ->orWhere('phone', 'like', '%'.$cleanPhone.'%');
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

        $this->foundRsvp->update([
            'rsvp_status' => $this->statusResponse,
            'attending_count' => ($this->statusResponse === 'ATTENDING') ? $this->attendingGuests : 0,
            'last_reply_message' => ($this->statusResponse === 'ATTENDING')
                ? "Online RSVP: Attending with {$this->attendingGuests} guests"
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
