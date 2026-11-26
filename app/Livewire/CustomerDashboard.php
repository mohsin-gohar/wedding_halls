<?php

namespace App\Livewire;

use App\Exceptions\ServiceBookingException;
use App\Models\Booking;
use App\Models\RFQ;
use App\Models\RFQBid;
use App\Models\ServiceBooking;
use App\Models\User;
use App\Services\ServiceBookingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public', ['title' => 'Customer Dashboard — Riwaj Grand Pakistan'])]
class CustomerDashboard extends Component
{
    public ?string $selectedBookingId = null;

    public ?Booking $viewingBooking = null;

    public string $cancellationReason = '';

    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

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

        // Ownership is granted only through an explicit staff action that sets
        // bookings.customer_id. Matching an email string here would let anyone
        // register a known customer's address and take over their bookings.
    }

    private function assertCustomer(): User
    {
        $user = Auth::user();

        abort_unless($user && $user->isCustomer(), 403, 'Customer account required.');

        return $user;
    }

    public function switchTab(string $tab): void
    {
        $this->assertCustomer();

        $this->activeTab = in_array($tab, ['bookings', 'quotes', 'profile']) ? $tab : 'bookings';
        $this->profileFeedback = null;
        $this->errorMessage = null;
    }

    public function updateProfile(): void
    {
        $user = $this->assertCustomer();

        $this->validate([
            'profileName' => 'required|string|min:3|max:100',
            'profilePhone' => 'required|string|min:10|max:25',
        ]);

        $user->update([
            'name' => $this->profileName,
            'phone' => $this->profilePhone,
        ]);

        $this->profileFeedback = 'Profile details updated successfully!';
    }

    public function updatePassword(): void
    {
        $user = $this->assertCustomer();

        $this->validate([
            'currentPassword' => 'required|string',
            'newPassword' => 'required|string|min:6|confirmed',
        ]);

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
        $user = $this->assertCustomer();
        $this->viewingBooking = Booking::where('customer_id', $user->id)
            ->with(['hall.venue', 'package', 'payments', 'rsvps'])
            ->findOrFail($bookingId);

        $this->selectedBookingId = $bookingId;
    }

    public function closeBookingModal()
    {
        $this->assertCustomer();

        $this->viewingBooking = null;
        $this->selectedBookingId = null;
        $this->feedbackMessage = null;
    }

    public function requestCancellation(string $bookingId)
    {
        $user = $this->assertCustomer();

        $this->validate([
            'cancellationReason' => ['nullable', 'string', 'max:1000'],
        ]);
        $booking = Booking::where('customer_id', $user->id)
            ->findOrFail($bookingId);

        $booking->update([
            'notes' => ($booking->notes ? $booking->notes.' | ' : '').'Customer requested cancellation: '.($this->cancellationReason ?: 'Personal reasons'),
        ]);

        $this->feedbackMessage = 'Your cancellation/reschedule inquiry has been registered. The venue concierge will contact you.';
        $this->cancellationReason = '';
    }

    /**
     * Customer accepts one of the quotations received on their own RFQ.
     *
     * Ownership is enforced here (not just in the service): the bid must
     * belong to an RFQ whose client_id is the signed-in customer, so a
     * tampered bid ID from someone else's RFQ can never be awarded.
     * The service itself re-checks OPEN/PENDING state under a row lock,
     * so a concurrent double-award is impossible.
     */
    public function acceptQuote(int $rfqBidId): void
    {
        $user = $this->assertCustomer();

        $bid = RFQBid::query()->with('rfq')->find($rfqBidId);

        if (! $bid || ! $bid->rfq || (int) $bid->rfq->client_id !== (int) $user->id) {
            abort(404, 'Quotation not found.');
        }

        try {
            app(ServiceBookingService::class)
                ->acceptQuote($bid->id, $user->id);

            $this->feedbackMessage = 'Quotation accepted — your service booking has been created.';
        } catch (ServiceBookingException $e) {
            $this->feedbackMessage = null;
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        $user = Auth::user();

        // mount() redirects non-customers. Keep the component render itself
        // data-free as a second boundary if it is instantiated outside that
        // route; every action independently enforces the customer role.
        if (! $user || ! $user->isCustomer()) {
            return view('livewire.customer-dashboard', [
                'bookings' => collect(),
                'rfqs' => collect(),
                'serviceBookings' => collect(),
                'totalSpent' => 0,
                'totalDue' => 0,
                'totalGuests' => 0,
                'upcomingBooking' => null,
            ]);
        }

        $bookings = Booking::where('customer_id', $user->id)
            ->with(['hall.venue', 'package', 'payments'])
            ->latest()
            ->get();

        // Marketplace side: the customer's own RFQs (with vendor quote
        // responses) and any ServiceBookings created from awarded quotes.
        // Both are strictly scoped to the signed-in customer — there is no
        // request parameter that could widen either query.
        $rfqs = RFQ::where('client_id', $user->id)
            ->with(['vendorCategory', 'targets.tenant', 'bids.vendor.tenant'])
            ->latest()
            ->get();

        $serviceBookings = ServiceBooking::where('customer_id', $user->id)
            ->with(['tenant', 'vendorCategory'])
            ->latest()
            ->get();

        $totalSpent = $bookings->sum('advance_paid');
        $totalDue = $bookings->sum('balance_due');
        $totalGuests = $bookings->sum('guest_count');
        $upcomingBooking = $bookings->where('event_date', '>=', now()->toDateString())->sortBy('event_date')->first();

        return view('livewire.customer-dashboard', [
            'bookings' => $bookings,
            'rfqs' => $rfqs,
            'serviceBookings' => $serviceBookings,
            'totalSpent' => $totalSpent,
            'totalDue' => $totalDue,
            'totalGuests' => $totalGuests,
            'upcomingBooking' => $upcomingBooking,
        ]);
    }
}
