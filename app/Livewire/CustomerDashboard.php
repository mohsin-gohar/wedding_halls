<?php

namespace App\Livewire;

use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class CustomerDashboard extends Component
{
    public ?string $selectedBookingId = null;

    public ?Booking $viewingBooking = null;

    public string $cancellationReason = '';

    public ?string $feedbackMessage = null;

    public function mount()
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('customer.login');
        }
    }

    public function viewBooking(string $bookingId)
    {
        $user = Auth::user();
        $this->viewingBooking = Booking::where(function ($q) use ($user) {
            $q->where('customer_id', $user->id)
                ->orWhere('customer_email', $user->email);
        })
            ->with(['hall.venue', 'package', 'payments', 'rsvps'])
            ->findOrFail($bookingId);

        $this->selectedBookingId = $bookingId;
    }

    public function closeBookingModal()
    {
        $this->viewingBooking = null;
        $this->selectedBookingId = null;
        $this->feedbackMessage = null;
    }

    public function requestCancellation(string $bookingId)
    {
        $user = Auth::user();
        $booking = Booking::where(function ($q) use ($user) {
            $q->where('customer_id', $user->id)
                ->orWhere('customer_email', $user->email);
        })
            ->findOrFail($bookingId);

        $booking->update([
            'notes' => ($booking->notes ? $booking->notes.' | ' : '').'Customer requested cancellation: '.($this->cancellationReason ?: 'Personal reasons'),
        ]);

        $this->feedbackMessage = 'Your cancellation/reschedule inquiry has been registered. The venue concierge will contact you.';
        $this->cancellationReason = '';
    }

    public function render()
    {
        $user = Auth::user();
        if (! $user) {
            return view('livewire.customer-dashboard', ['bookings' => collect(), 'totalSpent' => 0, 'totalDue' => 0]);
        }

        $bookings = Booking::where(function ($q) use ($user) {
            $q->where('customer_id', $user->id)
                ->orWhere('customer_email', $user->email);
        })
            ->with(['hall.venue', 'package', 'payments'])
            ->latest()
            ->get();

        $totalSpent = $bookings->sum('advance_paid');
        $totalDue = $bookings->sum('balance_due');

        return view('livewire.customer-dashboard', [
            'bookings' => $bookings,
            'totalSpent' => $totalSpent,
            'totalDue' => $totalDue,
        ]);
    }
}
