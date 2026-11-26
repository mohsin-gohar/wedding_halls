<?php

namespace App\Livewire;

use App\Exceptions\ServiceBookingException;
use App\Models\RFQ;
use App\Models\RFQBid;
use App\Services\ServiceBookingService;
use App\Support\PermissionMatrix;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Vendor ERP — Quotations & Bids board (Phase 2).
 *
 * Vendors (tenant owners / managers / service staff) see the OPEN RFQs
 * addressed to their business plus incoming bids, and can respond with a
 * quotation. Awarding a quote converts into a ServiceBooking in Phase 3.
 */
#[Layout('components.layouts.app', ['title' => 'Quotations & Bids — Vendor ERP'])]
class VendorQuotesBoard extends Component
{
    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public bool $showBidModal = false;

    public ?int $selectedRfqId = null;

    public ?RFQ $selectedRfq = null;

    public float $bidAmount = 0;

    public string $bidProposal = '';

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! PermissionMatrix::userCan($user, 'rfq.view')) {
            abort(403, 'Unauthorized. Quotation management is restricted to vendor staff.');
        }
    }

    public function hasPortalPermission(string $permission): bool
    {
        return PermissionMatrix::userCan(Auth::user(), $permission);
    }

    private function assertCan(string $permission): void
    {
        abort_unless($this->hasPortalPermission($permission), 403, 'Unauthorized. Missing permission: '.$permission);
    }

    /**
     * RFQs are visible to a vendor when explicitly targeted via rfq_targets or,
     * for legacy untargeted records, when the RFQ itself belongs to the tenant.
     */
    private function visibleRfqs(?string $tenantId)
    {
        return RFQ::query()->where(function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId)
                ->orWhereHas('targets', fn ($targets) => $targets->where('tenant_id', $tenantId));
        });
    }

    private function findRfqForUser(int $rfqId): ?RFQ
    {
        $tenantId = Auth::user()->tenant_id;

        if (! $tenantId) {
            return null;
        }

        return $this->visibleRfqs($tenantId)
            ->whereKey($rfqId)
            ->first();
    }

    /**
     * Closing an RFQ is an owner-level action on the vendor's OWN request;
     * being merely targeted by another tenant's RFQ must not grant that right.
     */
    private function findOwnedRfqForUser(int $rfqId): ?RFQ
    {
        $tenantId = Auth::user()->tenant_id;

        if (! $tenantId) {
            return null;
        }

        return RFQ::query()
            ->whereKey($rfqId)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function getRfqsProperty()
    {
        $tenantId = Auth::user()->tenant_id;

        if (! $tenantId) {
            abort(403, 'Forbidden. You do not have an active vendor organization assigned.');
        }

        // Vendor bids are commercially confidential. Each vendor may see
        // only its own offer, never a competitor's amount or identity.
        return $this->visibleRfqs($tenantId)
            ->with(['bids' => fn ($q) => $q
                ->where('vendor_id', Auth::id())
                ->with('vendor:id,name')])
            ->where('status', 'OPEN')
            ->orderByDesc('created_at')
            ->paginate(10);
    }

    public function getOpenQuoteCountProperty(): int
    {
        $tenantId = Auth::user()->tenant_id;

        if (! $tenantId) {
            abort(403, 'Forbidden. You do not have an active vendor organization assigned.');
        }

        return $this->visibleRfqs($tenantId)
            ->where('status', 'OPEN')
            ->count();
    }

    public function openBidModal(int $rfqId): void
    {
        $rfq = $this->findRfqForUser($rfqId);

        if (! $rfq) {
            $this->errorMessage = 'This quotation is not available for your business.';

            return;
        }

        $latestBid = $rfq->bids()
            ->where('vendor_id', Auth::id())
            ->latest('created_at')
            ->first();

        $this->selectedRfqId = $rfq->id;
        $this->selectedRfq = $rfq;
        $this->bidAmount = (float) ($latestBid?->bid_amount ?? 0);
        $this->bidProposal = $latestBid ? (string) $latestBid->proposal : '';
        $this->showBidModal = true;
        $this->errorMessage = null;
    }

    public function closeBidModal(): void
    {
        $this->showBidModal = false;
        $this->selectedRfqId = null;
        $this->selectedRfq = null;
        $this->bidAmount = 0;
        $this->bidProposal = '';
    }

    public function submitBid(int $rfqId, float $bidAmount, string $proposal): void
    {
        $user = Auth::user();

        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        $this->assertCan('rfq.bid');

        $rfq = $this->findRfqForUser($rfqId);

        if (! $rfq) {
            abort(404, 'Quotation not found.');
        }

        if ($bidAmount <= 0) {
            $this->addError('bidAmount', 'Bid amount must be greater than zero.');

            return;
        }

        if (mb_strlen(trim($proposal)) < 20) {
            $this->addError('bidProposal', 'Please provide a detailed proposal (at least 20 characters) so the client can compare offers.');

            return;
        }

        // Serialize competing submissions against the RFQ itself and
        // re-check OPEN status under lock. A stale Livewire page must not
        // create or overwrite an offer after the customer closes/awards it.
        $saved = DB::transaction(function () use ($user, $rfq, $bidAmount, $proposal): bool {
            $locked = $this->visibleRfqs((string) $user->tenant_id)
                ->whereKey($rfq->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || $locked->status !== 'OPEN') {
                return false;
            }

            RFQBid::updateOrCreate(
                [
                    'rfq_id' => $locked->id,
                    'vendor_id' => $user->id,
                ],
                [
                    'bid_amount' => $bidAmount,
                    'proposal' => $proposal,
                    'status' => 'PENDING',
                ]
            );

            return true;
        });

        if (! $saved) {
            $this->errorMessage = 'This quotation is no longer open for offers.';

            return;
        }

        $this->showBidModal = false;
        $this->errorMessage = null;
        $this->feedbackMessage = 'Quotation submitted successfully.';
    }

    public function markRfqViewed(int $rfqId): void
    {
        $this->assertCan('rfq.view');

        $tenantId = Auth::user()->tenant_id;

        if (! $tenantId) {
            abort(403);
        }

        $rfq = $this->findRfqForUser($rfqId);

        if (! $rfq) {
            abort(404);
        }

        $rfq->targets()->updateOrCreate(
            ['tenant_id' => $tenantId],
            ['viewed_at' => now()]
        );

        $this->feedbackMessage = 'Quotation marked as viewed.';
    }

    /**
     * Decline goes through the transactional service so every still-pending
     * offer is rejected under lock and cannot be awarded afterwards.
     */
    public function declineRfq(int $rfqId): void
    {
        $this->assertCan('rfq.close');

        $rfq = $this->findOwnedRfqForUser($rfqId);

        if (! $rfq) {
            abort(404);
        }

        try {
            app(ServiceBookingService::class)->declineQuote($rfq->id);

            $this->feedbackMessage = 'Quotation declined. All pending offers were rejected.';
            $this->errorMessage = null;
        } catch (ServiceBookingException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    // Awarding is intentionally NOT available to vendors. Only the customer
    // who owns the RFQ may accept a bid (CustomerDashboard::acceptQuote).

    public function render()
    {
        $user = Auth::user();
        abort_unless($user && $user->tenant_id && PermissionMatrix::userCan($user, 'rfq.view'), 403);

        return view('livewire.vendor-quotes-board')
            ->layout('components.layouts.app', [
                'title' => 'Quotations & Bids — Vendor ERP',
            ]);
    }
}
