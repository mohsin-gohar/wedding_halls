<?php

namespace App\Livewire;

use App\Exceptions\ServiceBookingException;
use App\Models\RFQ;
use App\Models\RFQBid;
use App\Models\Tenant;
use App\Services\ServiceBookingService;
use Illuminate\Support\Facades\Auth;
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

        if (! $user || (! $user->isTenantOwner() && ! $user->isManager() && ! $user->hasPermissionTo('rfq.view'))) {
            abort(403, 'Unauthorized. Quotation management is restricted to vendor business owners.');
        }
    }

    private function findRfqForUser(int $rfqId): ?RFQ
    {
        $tenantId = Auth::user()->tenant_id;

        if (! $tenantId) {
            return null;
        }

        return RFQ::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $rfqId)
            ->first();
    }

    public function getRfqsProperty()
    {
        $tenantId = Auth::user()->tenant_id;

        if (! $tenantId) {
            abort(403, 'Forbidden. You do not have an active vendor organization assigned.');
        }

        return RFQ::query()
            ->where('tenant_id', $tenantId)
            ->with(['bids' => fn ($q) => $q->with('vendor:id,name,email')])
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

        return RFQ::query()
            ->where('tenant_id', $tenantId)
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

        $latestBid = $rfq->bids()->latest('created_at')->first();

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

        if (! $user->hasPermissionTo('rfq.bid')) {
            abort(403, 'Unauthorized. Quotation bidding is restricted for this vendor.');
        }

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

        RFQBid::updateOrCreate(
            [
                'rfq_id' => $rfq->id,
                'vendor_id' => $user->id,
            ],
            [
                'bid_amount' => $bidAmount,
                'proposal' => $proposal,
                'status' => 'PENDING',
            ]
        );

        $this->showBidModal = false;
        $this->errorMessage = null;
        $this->feedbackMessage = 'Quotation submitted successfully.';
    }

    public function markRfqViewed(int $rfqId): void
    {
        $tenantId = Auth::user()->tenant_id;

        if (! $tenantId) {
            abort(403);

            return;
        }

        $rfq = RFQ::query()->where('tenant_id', $tenantId)->where('id', $rfqId)->first();

        if (! $rfq) {
            abort(404);
        }

        $rfq->targets()->updateOrCreate(
            ['tenant_id' => $tenantId],
            ['viewed_at' => now()]
        );

        $this->feedbackMessage = 'Quotation marked as viewed.';
    }

    public function declineRfq(int $rfqId): void
    {
        $tenantId = Auth::user()->tenant_id;

        if (! $tenantId) {
            abort(403);
        }

        $rfq = RFQ::query()->where('tenant_id', $tenantId)->where('id', $rfqId)->first();

        if (! $rfq) {
            abort(404);
        }

        $rfq->update(['status' => 'CLOSED']);

        $this->feedbackMessage = 'Quotation declined. The customer will be notified.';
    }

    public function awardQuote(int $rfqBidId): void
    {
        $user = Auth::user();

        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        if (! $user->hasPermissionTo('rfq.award')) {
            abort(403, 'Unauthorized. Quotation awarding is restricted for this vendor.');
        }

        try {
            $booking = app(ServiceBookingService::class)->acceptQuote($rfqBidId);

            $this->feedbackMessage = 'Quotation awarded. Service booking created for the customer.';
            $this->errorMessage = null;
        } catch (ServiceBookingException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.vendor-quotes-board')
            ->layout('components.layouts.app', [
                'title' => 'Quotations & Bids — Vendor ERP',
            ]);
    }
}
