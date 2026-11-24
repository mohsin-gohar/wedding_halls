<?php

namespace App\Livewire;

use App\Models\CustomerEvent;
use App\Models\CustomerEventTask;
use App\Models\CustomerEventVendor;
use App\Models\ServiceBooking;
use App\Models\Tenant;
use App\Models\VendorCategory;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Customer Event Workspace (Phase 4).
 *
 * A customer manages an entire wedding — Mehndi, Baraat, Walima, nikkah,
 * corporate or custom — with its own vendor shortlist, task list, budget and
 * linked bookings.
 *
 * Every read and write is scoped to the signed-in customer; a customer can
 * never open or mutate another customer's event.
 */
#[Layout('components.layouts.public', ['title' => 'My Event Workspace — Riwaj Grand'])]
class CustomerEventWorkspace extends Component
{
    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public ?string $selectedEventId = null;

    public bool $showEventModal = false;

    public bool $isEditingEvent = false;

    // Event form
    public string $eventName = '';

    public string $eventType = 'WEDDING';

    public string $eventDate = '';

    public string $eventCity = '';

    public string $venueText = '';

    public string $guestCount = '';

    public string $estimatedBudget = '';

    public string $eventNotes = '';

    public string $eventStatus = 'PLANNING';

    // Task form
    public string $taskTitle = '';

    public string $taskDueDate = '';

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->isCustomer()) {
            abort(403, 'Unauthorized. The event workspace is available to customer accounts only.');
        }

        $this->eventDate = now()->addMonth()->toDateString();
    }

    // ── Data ──────────────────────────────────────────────────────

    public function getEventsProperty()
    {
        return CustomerEvent::query()
            ->where('user_id', Auth::id())
            ->withCount(['bookings', 'tasks', 'vendors'])
            ->orderByRaw("CASE status WHEN 'PLANNING' THEN 0 WHEN 'BOOKED' THEN 1 ELSE 2 END")
            ->orderByDesc('event_date')
            ->get();
    }

    public function getSelectedEventProperty(): ?CustomerEvent
    {
        if (! $this->selectedEventId) {
            return null;
        }

        return $this->ownedEvent($this->selectedEventId);
    }

    public function getEventBookingsProperty()
    {
        $event = $this->ownedSelectedEvent();

        if (! $event) {
            return collect();
        }

        return ServiceBooking::query()
            ->where('customer_event_id', $event->id)
            ->where('customer_id', Auth::id())
            ->with(['tenant:id,name,slug', 'vendorCategory:id,name'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function getEventTasksProperty()
    {
        $event = $this->ownedSelectedEvent();

        if (! $event) {
            return collect();
        }

        return CustomerEventTask::query()
            ->where('customer_event_id', $event->id)
            ->orderBy('sort')
            ->orderBy('due_date')
            ->get();
    }

    public function getEventVendorsProperty()
    {
        $event = $this->ownedSelectedEvent();

        if (! $event) {
            return collect();
        }

        return CustomerEventVendor::query()
            ->where('customer_event_id', $event->id)
            ->with(['tenant:id,name,slug,vendor_category_id', 'booking:id,booking_reference,quoted_amount,status'])
            ->get();
    }

    public function getEventBudgetProperty(): array
    {
        $bookings = $this->eventBookings;

        $quoted = (float) $bookings->sum('quoted_amount');
        $paid = (float) $bookings->sum('paid_amount');

        return [
            'quoted' => $quoted,
            'paid' => $paid,
            'outstanding' => max(0, $quoted - $paid),
            'booking_count' => $bookings->count(),
            'task_total' => $this->eventTasks->count(),
            'task_done' => $this->eventTasks->where('status', 'DONE')->count(),
        ];
    }

    /** The signed-in customer's service bookings not yet linked to any event. */
    public function getUnlinkedBookingsProperty()
    {
        return ServiceBooking::query()
            ->where('customer_id', Auth::id())
            ->whereNull('customer_event_id')
            ->with(['tenant:id,name', 'vendorCategory:id,name'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function getMarketplaceVendorsProperty()
    {
        // Approved, visible and unlocked vendors the customer can shortlist,
        // excluding those already attached to this (owned) event.
        $event = $this->ownedSelectedEvent();
        $attached = $event
            ? CustomerEventVendor::query()->where('customer_event_id', $event->id)->pluck('tenant_id')->all()
            : [];

        return Tenant::query()
            ->marketplaceVisible()
            ->whereNotIn('id', $attached)
            ->with('vendorCategory:id,name,slug')
            ->orderBy('name')
            ->limit(40)
            ->get();
    }

    public function getCategoriesProperty()
    {
        return VendorCategory::query()->where('is_active', true)->orderBy('sort')->get(['id', 'name', 'slug']);
    }

    // ── Event actions ─────────────────────────────────────────────

    public function selectEvent(string $eventId): void
    {
        if (! $this->ownedEvent($eventId)) {
            abort(404, 'Event not found.');
        }

        $this->selectedEventId = $eventId;
        $this->errorMessage = null;
    }

    public function openCreateEventModal(): void
    {
        $this->reset(['eventName', 'eventCity', 'venueText', 'guestCount', 'estimatedBudget', 'eventNotes']);
        $this->eventType = 'WEDDING';
        $this->eventStatus = 'PLANNING';
        $this->eventDate = now()->addMonth()->toDateString();
        $this->isEditingEvent = false;
        $this->showEventModal = true;
        $this->errorMessage = null;
    }

    public function openEditEventModal(string $eventId): void
    {
        $event = $this->ownedEvent($eventId);

        if (! $event) {
            abort(404, 'Event not found.');
        }

        $this->eventName = (string) $event->name;
        $this->eventType = (string) $event->event_type;
        $this->eventDate = $event->event_date?->toDateString() ?? '';
        $this->eventCity = (string) ($event->city ?? '');
        $this->venueText = (string) ($event->venue_text ?? '');
        $this->guestCount = (string) ($event->guest_count ?? '');
        $this->estimatedBudget = (string) ($event->estimated_budget ?? '');
        $this->eventNotes = (string) ($event->notes ?? '');
        $this->eventStatus = (string) $event->status;

        $this->selectedEventId = $eventId;
        $this->isEditingEvent = true;
        $this->showEventModal = true;
        $this->errorMessage = null;
    }

    public function closeEventModal(): void
    {
        $this->showEventModal = false;
        $this->isEditingEvent = false;
    }

    public function saveEvent(): void
    {
        $this->validate([
            'eventName' => ['required', 'string', 'max:150'],
            'eventType' => ['required', 'in:'.implode(',', CustomerEvent::EVENT_TYPES)],
            'eventDate' => ['nullable', 'date'],
            'eventCity' => ['nullable', 'string', 'max:80'],
            'venueText' => ['nullable', 'string', 'max:200'],
            'guestCount' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'estimatedBudget' => ['nullable', 'numeric', 'min:0'],
            'eventNotes' => ['nullable', 'string', 'max:2000'],
            'eventStatus' => ['required', 'in:'.implode(',', CustomerEvent::STATUSES)],
        ]);

        $payload = [
            'name' => trim($this->eventName),
            'event_type' => $this->eventType,
            'event_date' => $this->eventDate ?: null,
            'city' => $this->eventCity ?: null,
            'venue_text' => $this->venueText ?: null,
            'guest_count' => $this->guestCount !== '' ? (int) $this->guestCount : null,
            'estimated_budget' => $this->estimatedBudget !== '' ? round((float) $this->estimatedBudget, 2) : null,
            'notes' => $this->eventNotes ?: null,
            'status' => $this->eventStatus,
        ];

        if ($this->isEditingEvent) {
            $event = $this->ownedEvent((string) $this->selectedEventId);

            if (! $event) {
                abort(404, 'Event not found.');
            }

            $event->update($payload);
            $this->feedbackMessage = 'Event updated successfully.';
        } else {
            $event = CustomerEvent::create(array_merge($payload, ['user_id' => Auth::id()]));
            $this->selectedEventId = $event->id;
            $this->feedbackMessage = 'Event created — start adding vendors and tasks.';
        }

        $this->closeEventModal();
    }

    public function deleteEvent(string $eventId): void
    {
        $event = $this->ownedEvent($eventId);

        if (! $event) {
            abort(404, 'Event not found.');
        }

        DB::transaction(function () use ($event) {
            // Detach linked bookings rather than deleting them.
            ServiceBooking::query()
                ->where('customer_event_id', $event->id)
                ->update(['customer_event_id' => null]);

            CustomerEventTask::query()->where('customer_event_id', $event->id)->delete();
            CustomerEventVendor::query()->where('customer_event_id', $event->id)->delete();
            $event->delete();
        });

        if ($this->selectedEventId === $eventId) {
            $this->selectedEventId = null;
        }

        $this->feedbackMessage = 'Event removed.';
    }

    // ── Task actions ──────────────────────────────────────────────

    public function addTask(): void
    {
        $event = $this->currentEvent();

        $this->validate([
            'taskTitle' => ['required', 'string', 'max:200'],
            'taskDueDate' => ['nullable', 'date'],
        ]);

        $sort = (int) CustomerEventTask::query()->where('customer_event_id', $event->id)->max('sort');

        CustomerEventTask::create([
            'customer_event_id' => $event->id,
            'title' => trim($this->taskTitle),
            'due_date' => $this->taskDueDate ?: null,
            'status' => 'PENDING',
            'sort' => $sort + 1,
        ]);

        $this->reset(['taskTitle', 'taskDueDate']);
        $this->feedbackMessage = 'Task added to the checklist.';
    }

    public function toggleTask(string $taskId): void
    {
        $event = $this->currentEvent();

        $task = CustomerEventTask::query()
            ->where('customer_event_id', $event->id)
            ->where('id', $taskId)
            ->first();

        if (! $task) {
            abort(404, 'Task not found.');
        }

        $task->update(['status' => $task->status === 'DONE' ? 'PENDING' : 'DONE']);

        $this->feedbackMessage = $task->status === 'DONE' ? 'Task completed.' : 'Task reopened.';
    }

    public function deleteTask(string $taskId): void
    {
        $event = $this->currentEvent();

        CustomerEventTask::query()
            ->where('customer_event_id', $event->id)
            ->where('id', $taskId)
            ->delete();

        $this->feedbackMessage = 'Task removed.';
    }

    // ── Vendor shortlist ──────────────────────────────────────────

    public function attachVendor(string $tenantId): void
    {
        $event = $this->currentEvent();

        $tenant = Tenant::query()
            ->where('id', $tenantId)
            ->where('verification_status', 'APPROVED')
            ->where('is_active', true)
            ->where('marketplace_visible', true)
            ->first();

        if (! $tenant || app(SubscriptionService::class)->isLocked($tenant)) {
            $this->errorMessage = 'That vendor is not available for shortlisting.';

            return;
        }

        CustomerEventVendor::updateOrCreate(
            [
                'customer_event_id' => $event->id,
                'tenant_id' => $tenant->id,
            ],
            [
                'status' => 'SHORTLISTED',
                'service_booking_id' => null,
            ]
        );

        $this->feedbackMessage = $tenant->name.' added to the shortlist.';
    }

    public function detachVendor(string $vendorRowId): void
    {
        $event = $this->currentEvent();

        $row = CustomerEventVendor::query()
            ->where('customer_event_id', $event->id)
            ->where('id', $vendorRowId)
            ->first();

        if (! $row) {
            abort(404, 'Vendor entry not found.');
        }

        $row->delete();

        $this->feedbackMessage = 'Vendor removed from the shortlist.';
    }

    public function updateVendorStatus(string $vendorRowId, string $status): void
    {
        $event = $this->currentEvent();

        if (! in_array($status, CustomerEventVendor::STATUSES, true)) {
            $this->errorMessage = 'Invalid vendor status.';

            return;
        }

        $row = CustomerEventVendor::query()
            ->where('customer_event_id', $event->id)
            ->where('id', $vendorRowId)
            ->first();

        if (! $row) {
            abort(404, 'Vendor entry not found.');
        }

        $row->update(['status' => $status]);

        $this->feedbackMessage = 'Vendor status updated.';
    }

    // ── Booking links ─────────────────────────────────────────────

    public function linkBooking(string $bookingId): void
    {
        $event = $this->currentEvent();

        $booking = ServiceBooking::query()
            ->where('id', $bookingId)
            ->where('customer_id', Auth::id())
            ->first();

        if (! $booking) {
            abort(404, 'Booking not found.');
        }

        if ($booking->customer_event_id && $booking->customer_event_id !== $event->id) {
            $this->errorMessage = 'This booking is already linked to another event. Unlink it there first.';

            return;
        }

        $booking->update(['customer_event_id' => $event->id]);

        // If the booked vendor is on the shortlist, mark them booked and
        // attach the reference so the shortlist reflects reality.
        CustomerEventVendor::query()
            ->where('customer_event_id', $event->id)
            ->where('tenant_id', $booking->tenant_id)
            ->update([
                'status' => 'BOOKED',
                'service_booking_id' => $booking->id,
            ]);

        $this->feedbackMessage = 'Booking '.$booking->booking_reference.' linked to this event.';
    }

    public function unlinkBooking(string $bookingId): void
    {
        $event = $this->currentEvent();

        $booking = ServiceBooking::query()
            ->where('id', $bookingId)
            ->where('customer_id', Auth::id())
            ->where('customer_event_id', $event->id)
            ->first();

        if (! $booking) {
            abort(404, 'Booking not found.');
        }

        $booking->update(['customer_event_id' => null]);

        // Keep the shortlist row, but drop the booking link so it no longer
        // claims a reference that isn't attached to this event.
        CustomerEventVendor::query()
            ->where('customer_event_id', $event->id)
            ->where('service_booking_id', $booking->id)
            ->update(['service_booking_id' => null]);

        $this->feedbackMessage = 'Booking unlinked from this event.';
    }

    // ── Internals ─────────────────────────────────────────────────

    private function currentEvent(): CustomerEvent
    {
        if (! $this->selectedEventId) {
            abort(400, 'Select an event first.');
        }

        $event = $this->ownedEvent($this->selectedEventId);

        if (! $event) {
            abort(404, 'Event not found.');
        }

        return $event;
    }

    private function ownedEvent(string $eventId): ?CustomerEvent
    {
        return CustomerEvent::query()
            ->where('id', $eventId)
            ->where('user_id', Auth::id())
            ->first();
    }

    /** Ownership resolver for computed properties: no data leaks on a tampered id. */
    private function ownedSelectedEvent(): ?CustomerEvent
    {
        if (! $this->selectedEventId) {
            return null;
        }

        return $this->ownedEvent($this->selectedEventId);
    }

    public function render()
    {
        return view('livewire.customer-event-workspace')
            ->layout('components.layouts.public', [
                'title' => 'My Event Workspace — Riwaj Grand',
            ]);
    }
}
