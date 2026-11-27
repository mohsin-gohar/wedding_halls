<?php

namespace App\Livewire;

use App\Models\GuestRsvp;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.public', ['title' => 'RSVP Your Invitation — Riwaj Grand Pakistan'])]
class PublicGuestRsvp extends Component
{
    public string $phone = '';

    #[Locked]
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

        if (strlen($this->normalizePhone($this->phone)) < 10) {
            $this->feedbackMessage = 'Please enter a valid complete mobile number (minimum 10 digits) to locate your invitation.';

            return;
        }

        $invitation = $this->resolveInvitation();

        if (! $invitation) {
            $this->feedbackMessage = 'No active wedding invitation found with this phone number. Please verify with the event host.';

            return;
        }

        if ($invitation instanceof GuestRsvp) {
            $this->foundRsvp = $invitation;
            $this->attendingGuests = max(1, $invitation->attending_count);

            return;
        }

        // More than one guest shares this number; require the host to clarify.
        $this->feedbackMessage = 'More than one invitation matches this number. Please contact the event host.';
    }

    /**
     * Resolve an invitation by EXACT, normalized full phone number. Partial
     * suffix matching allowed cross-event/cross-tenant takeover by guessing a
     * number's last digits; ambiguous matches are now rejected outright.
     *
     * @return GuestRsvp|string|null GuestRsvp on unique match, 'ambiguous' on collision, null otherwise
     */
    private function resolveInvitation(): GuestRsvp|string|null
    {
        $canonical = $this->normalizePhone($this->phone);

        if (strlen($canonical) < 10) {
            return null;
        }

        $needle = substr($canonical, -8);

        $matches = GuestRsvp::with('booking.hall.venue')
            ->where('phone', 'like', "%{$needle}")
            ->limit(50)
            ->get()
            ->filter(fn (GuestRsvp $rsvp): bool => $this->normalizePhone($rsvp->phone) === $canonical)
            ->values();

        // A capped candidate set is not proof of uniqueness. Fail closed if
        // the suffix bucket reaches the cap rather than trusting a partial scan.
        if ($matches->count() >= 50) {
            return 'ambiguous';
        }

        return match ($matches->count()) {
            0 => null,
            1 => $matches->first(),
            default => 'ambiguous',
        };
    }

    /** Normalize local (03XX) and international (+923XX) numbers to one form. */
    private function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D/', '', (string) $phone) ?? '';

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        } elseif (strlen($digits) === 12 && str_starts_with($digits, '92')) {
            $digits = substr($digits, 2);
        }

        return $digits;
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

        // Re-resolve from the phone on every submit. Livewire rehydrates model
        // properties by ID, so the bound RSVP must be re-verified against the
        // entered number to stop ID tampering from changing another guest's reply.
        $verified = $this->resolveInvitation();

        if (! $verified instanceof GuestRsvp || $verified->id !== $this->foundRsvp->id) {
            $this->foundRsvp = null;
            $this->feedbackMessage = 'Your invitation could not be verified. Please search again.';

            return;
        }

        $count = ($this->statusResponse === 'ATTENDING') ? (int) $this->attendingGuests : 0;

        $verified->update([
            'rsvp_status' => $this->statusResponse,
            'attending_count' => $count,
            'last_reply_message' => ($this->statusResponse === 'ATTENDING')
                ? "Online RSVP: Attending with {$count} guest(s)"
                : 'Online RSVP: Regret, cannot attend',
        ]);

        $this->foundRsvp = $verified;
        $this->isSubmitted = true;
        $this->feedbackMessage = 'Your RSVP has been recorded successfully! We look forward to welcoming you.';
    }

    public function render()
    {
        return view('livewire.public-guest-rsvp');
    }
}
