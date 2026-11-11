<?php

namespace App\Livewire;

use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public', ['title' => 'Customer Dashboard â€” Riwaj Grand Pakistan'])]
class CustomerDashboard extends Component
{
    public ?string $selectedBookingId = null;

    public ?Booking $viewingBooking = null;

    public string $cancellationReason = '';

    public ?string $feedbackMessage = null;

    public string $activeTab = 'bookings';

    public string $profileName = '';

    public string $profilePhone = '';

    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPassword_confirmation = '';

    public ?string $profileFeedback = null;

    public function mount()
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('customer.login');
        }

        if (! $user->isCustomer()) {
            if ($user->isSuperAdmin()) {
                return redirect()->route('admin.dashboard');
            }

            return redirect()->route('dashboard');
        }

        $this->profileName = $user->name ?? '';
        $this->profilePhone = $user->phone ?? '';

        // Claim orphan walk-in bookings that already store this customer's email
        // so future access is by customer_id only (prevents email-registration IDOR).
        Booking::query()
            ->whereNull('customer_id')
            ->whereRaw('LOWER(customer_email) = ?', [strtolower($user->email)])
            ->update(['customer_id' => $user->id]);
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['bookings', 'profile']) ? $tab : 'bookings';
        $this->profileFeedback = null;
    }

    public function updateProfile(): void
    {
        $this->validate([
            'profileName' => 'required|string|min:3|max:100',
            'profilePhone' => 'required|string|min:10|max:25',
        ]);

        $user = Auth::user();
        $user->update([
            'name' => $this->profileName,
            'phone' => $this->profilePhone,
        ]);

        $this->profileFeedback = 'Profile details updated successfully!';
    }

    public function updatePassword(): void
    {
        $this->validate([
            'currentPassword' => 'required|string',
            'newPassword' => 'required|string|min:6|confirmed',
        ]);

        $user = Auth::user();

        if (! Hash::check($this->currentPassword, $user->password)) {
            $this->addError('currentPassword', 'The provided current password does not match.');

            return;
        }

        $user->update([
            'password' => Hash::make($this->newPassword),
        ]);

        $this->currentPassword = '';
        $this->newPassword = '';
        $this->newPassword_confirmation = '';
        $this->profileFeedback = 'Password changed successfully!';
    }

    public function viewBooking(string $bookingId)
    {
        $user = Auth::user();
        $this->viewingBooking = Booking::where('customer_id', $user->id)
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
        $booking = Booking::where('customer_id', $user->id)
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

        $bookings = Booking::where('customer_id', $user->id)
            ->with(['hall.venue', 'package', 'payments'])
            ->latest()
            ->get();

        $totalSpent = $bookings->sum('advance_paid');
        $totalDue = $bookings->sum('balance_due');
        $totalGuests = $bookings->sum('guest_count');
        $upcomingBooking = $bookings->where('event_date', '>=', now()->toDateString())->sortBy('event_date')->first();

        return view('livewire.customer-dashboard', [
            'bookings' => $bookings,
            'totalSpent' => $totalSpent,
            'totalDue' => $totalDue,
            'totalGuests' => $totalGuests,
            'upcomingBooking' => $upcomingBooking,
        ]);
    }
}
