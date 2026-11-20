<?php

namespace App\Livewire;

use App\Models\SupportTicket;
use App\Models\TicketMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Support center (Phase 4).
 *
 * One workspace for three audiences:
 *   • Customers  — raise a ticket and track the conversation.
 *   • Vendors    — answer the tickets raised against their own business.
 *   • Platform   — triage, assign, prioritise and resolve anything.
 *
 * Every query is scoped server-side; a customer can only see their own
 * tickets, a vendor only the tickets addressed to their tenant.
 */
#[Layout('components.layouts.app', ['title' => 'Support Center — Riwaj Grand'])]
class SupportCenter extends Component
{
    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public bool $showNewTicket = false;

    public bool $showThread = false;

    public ?string $selectedTicketId = null;

    // New ticket form
    public string $subject = '';

    public string $category = 'GENERAL';

    public string $priority = 'MEDIUM';

    public string $body = '';

    // Reply form
    public string $replyBody = '';

    public string $filterStatus = 'ALL';

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user) {
            abort(403, 'Unauthorized.');
        }
    }

    // ── Data ──────────────────────────────────────────────────────

    public function getTicketsProperty()
    {
        $user = Auth::user();
        $query = SupportTicket::query()->with(['requester:id,name,email', 'assignee:id,name']);

        if ($user->isCustomer()) {
            $query->where('user_id', $user->id);
        } elseif ($user->isSuperAdmin() || $user->isPlatformSupport()) {
            // Platform staff see every ticket.
        } elseif ($user->tenant_id) {
            // Vendor staff only see tickets raised against their own business.
            // Customer tickets with no tenant belong to the platform queue and
            // are deliberately excluded here.
            $query->where('tenant_id', $user->tenant_id);
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($this->filterStatus !== 'ALL') {
            $query->where('status', $this->filterStatus);
        }

        return $query->orderByDesc('created_at')->limit(100)->get();
    }

    public function getSelectedTicketProperty(): ?SupportTicket
    {
        if (! $this->selectedTicketId) {
            return null;
        }

        return $this->scopedTicket($this->selectedTicketId);
    }

    public function getMessagesProperty()
    {
        if (! $this->selectedTicketId) {
            return collect();
        }

        return TicketMessage::query()
            ->where('support_ticket_id', $this->selectedTicketId)
            ->with('author:id,name,role')
            ->orderBy('created_at')
            ->get();
    }

    public function getStatsProperty(): array
    {
        $tickets = $this->tickets;

        return [
            'open' => $tickets->whereIn('status', ['OPEN', 'IN_PROGRESS'])->count(),
            'urgent' => $tickets->where('priority', 'URGENT')->count(),
            'resolved' => $tickets->whereIn('status', ['RESOLVED', 'CLOSED'])->count(),
            'total' => $tickets->count(),
        ];
    }

    // ── Actions ───────────────────────────────────────────────────

    public function openNewTicket(): void
    {
        $this->reset(['subject', 'body']);
        $this->category = 'GENERAL';
        $this->priority = 'MEDIUM';
        $this->showNewTicket = true;
        $this->errorMessage = null;
    }

    public function closeNewTicket(): void
    {
        $this->showNewTicket = false;
    }

    public function createTicket(): void
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        $this->validate([
            'subject' => ['required', 'string', 'max:200'],
            'category' => ['required', 'in:'.implode(',', SupportTicket::CATEGORIES)],
            'priority' => ['required', 'in:'.implode(',', SupportTicket::PRIORITIES)],
            'body' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $ticket = DB::transaction(function () use ($user) {
            $ticket = SupportTicket::create([
                'ticket_no' => SupportTicket::generateTicketNo(),
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'subject' => trim($this->subject),
                'category' => $this->category,
                'priority' => $this->priority,
                'status' => 'OPEN',
            ]);

            TicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => trim($this->body),
                'is_staff_reply' => false,
            ]);

            return $ticket;
        });

        $this->closeNewTicket();
        $this->selectedTicketId = $ticket->id;
        $this->showThread = true;
        $this->feedbackMessage = 'Ticket '.$ticket->ticket_no.' raised — our team will respond shortly.';
    }

    public function openTicket(string $ticketId): void
    {
        if (! $this->scopedTicket($ticketId)) {
            abort(404, 'Ticket not found.');
        }

        $this->selectedTicketId = $ticketId;
        $this->showThread = true;
        $this->replyBody = '';
        $this->errorMessage = null;
    }

    public function closeThread(): void
    {
        $this->showThread = false;
        $this->selectedTicketId = null;
    }

    public function reply(): void
    {
        $user = Auth::user();
        $ticket = $this->currentTicket();

        $this->validate([
            'replyBody' => ['required', 'string', 'min:2', 'max:5000'],
        ]);

        $isStaff = ! $user->isCustomer();

        TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => trim($this->replyBody),
            'is_staff_reply' => $isStaff,
        ]);

        if ($ticket->status === 'OPEN' && $isStaff) {
            $ticket->update(['status' => 'IN_PROGRESS']);
        }

        $this->replyBody = '';
        $this->feedbackMessage = 'Reply sent.';
    }

    public function updateStatus(string $status): void
    {
        $ticket = $this->currentTicket();

        if (! in_array($status, SupportTicket::STATUSES, true)) {
            $this->errorMessage = 'Invalid ticket status.';

            return;
        }

        // Only platform staff may drive the lifecycle; a customer may close
        // their own ticket once resolved.
        $user = Auth::user();

        if (! $user->isSuperAdmin() && ! $user->isPlatformSupport() && ! $user->isTenantOwner()) {
            $this->errorMessage = 'You may not change the ticket status.';

            return;
        }

        $ticket->update([
            'status' => $status,
            'resolved_at' => in_array($status, ['RESOLVED', 'CLOSED'], true) ? now() : null,
        ]);

        $this->feedbackMessage = 'Ticket marked '.$status.'.';
    }

    public function assignToMe(): void
    {
        $user = Auth::user();

        if (! $user->isSuperAdmin() && ! $user->isPlatformSupport()) {
            $this->errorMessage = 'Only platform support staff can assign tickets.';

            return;
        }

        $ticket = $this->currentTicket();
        $ticket->update(['assigned_to' => $user->id]);

        $this->feedbackMessage = 'Ticket assigned to you.';
    }

    // ── Internals ────────────────────────────────────────────────

    private function currentTicket(): SupportTicket
    {
        if (! $this->selectedTicketId) {
            abort(400, 'Select a ticket first.');
        }

        $ticket = $this->scopedTicket($this->selectedTicketId);

        if (! $ticket) {
            abort(404, 'Ticket not found.');
        }

        return $ticket;
    }

    private function scopedTicket(string $ticketId): ?SupportTicket
    {
        $user = Auth::user();

        $query = SupportTicket::query()->where('id', $ticketId);

        if ($user->isCustomer()) {
            $query->where('user_id', $user->id);
        } elseif (! $user->isSuperAdmin() && ! $user->isPlatformSupport()) {
            if (! $user->tenant_id) {
                return null;
            }

            $query->where('tenant_id', $user->tenant_id);
        }

        return $query->first();
    }

    public function render()
    {
        return view('livewire.support-center')
            ->layout('components.layouts.app', [
                'title' => 'Support Center — Riwaj Grand',
            ]);
    }
}
