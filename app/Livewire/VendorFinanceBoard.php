<?php

namespace App\Livewire;

use App\Models\ServiceBooking;
use App\Models\VendorPayout;
use App\Models\VendorWallet;
use App\Models\WalletTransaction;
use App\Services\ServiceBookingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Vendor ERP — Finance & commission board.
 *
 * Shows the vendor exactly what it earned, what the platform commission was,
 * what is still outstanding and what has been paid out — backed by the
 * immutable wallet ledger rather than by ad-hoc numbers.
 */
#[Layout('components.layouts.app', ['title' => 'Finance & Earnings — Vendor ERP'])]
class VendorFinanceBoard extends Component
{
    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public bool $showPayoutModal = false;

    public string $payoutAmount = '';

    public string $payoutMethod = 'BANK_TRANSFER';

    public string $payoutAccountName = '';

    public string $payoutAccountDetails = '';

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->tenant_id || ! $user->hasPermissionTo('wallet.view')) {
            abort(403, 'Unauthorized. Finance is restricted to vendor staff.');
        }
    }

    public function getWalletProperty(): ?VendorWallet
    {
        return VendorWallet::query()
            ->where('tenant_id', Auth::user()->tenant_id)
            ->where('is_primary', true)
            ->first();
    }

    public function getEarningsProperty(): array
    {
        $tenantId = Auth::user()->tenant_id;

        $bookings = ServiceBooking::query()
            ->where('tenant_id', $tenantId)
            ->whereNotIn('status', ['CANCELLED', 'DECLINED'])
            ->get(['quoted_amount', 'discount_amount', 'commission_amount', 'net_vendor_amount', 'paid_amount', 'payment_status', 'status']);

        $gross = (float) $bookings->sum('quoted_amount');
        $commission = (float) $bookings->sum('commission_amount');
        $net = (float) $bookings->sum('net_vendor_amount');
        $collected = (float) $bookings->sum('paid_amount');

        $paidOut = (float) VendorPayout::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'PAID')
            ->sum('amount');

        $pendingPayout = (float) VendorPayout::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['REQUESTED', 'PROCESSING'])
            ->sum('amount');

        $walletBalance = (float) ($this->wallet->balance ?? 0);

        return [
            'gross' => $gross,
            'commission' => $commission,
            'net' => $net,
            'collected' => $collected,
            'outstanding' => max(0, $gross - $collected),
            'paid_out' => $paidOut,
            'pending_payout' => $pendingPayout,
            'wallet_balance' => $walletBalance,
            'available' => max(0, $walletBalance),
            'booking_count' => $bookings->count(),
            'effective_rate' => $gross > 0 ? round(($commission / $gross) * 100, 2) : 0.0,
        ];
    }

    public function getTransactionsProperty()
    {
        return WalletTransaction::query()
            ->where('tenant_id', Auth::user()->tenant_id)
            ->with(['serviceBooking:id,booking_reference,event_name', 'payout:id,reference,status'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
    }

    public function getPayoutsProperty()
    {
        return VendorPayout::query()
            ->where('tenant_id', Auth::user()->tenant_id)
            ->orderByDesc('requested_at')
            ->limit(30)
            ->get();
    }

    public function openPayoutModal(): void
    {
        $this->reset(['payoutAmount', 'payoutAccountName', 'payoutAccountDetails']);
        $this->payoutMethod = 'BANK_TRANSFER';
        $this->showPayoutModal = true;
        $this->errorMessage = null;
    }

    public function closePayoutModal(): void
    {
        $this->showPayoutModal = false;
    }

    public function requestPayout(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->tenant_id) {
            abort(403);
        }

        if (! $user->hasPermissionTo('payouts.request')) {
            abort(403, 'Unauthorized. You may not request payouts.');
        }

        $this->validate([
            'payoutAmount' => ['required', 'numeric', 'min:1'],
            'payoutMethod' => ['required', 'in:CASH,BANK_TRANSFER'],
            'payoutAccountName' => ['required', 'string', 'max:150'],
            'payoutAccountDetails' => ['nullable', 'string', 'max:255'],
        ]);

        $earnings = $this->earnings;

        if ((float) $this->payoutAmount > $earnings['available'] + 0.009) {
            $this->addError('payoutAmount', 'You can only withdraw up to PKR '.number_format($earnings['available'], 2).' — your available wallet balance.');

            return;
        }

        DB::transaction(function () use ($user) {
            app(ServiceBookingService::class)->requestVendorPayout(
                (string) $user->tenant_id,
                (int) $user->id,
                round((float) $this->payoutAmount, 2),
                $this->payoutMethod,
                array_filter([
                    'account_name' => $this->payoutAccountName,
                    'account_details' => $this->payoutAccountDetails ?: null,
                ])
            );
        });

        $this->closePayoutModal();
        $this->feedbackMessage = 'Payout requested — our finance team will review and process it.';
    }

    public function render()
    {
        return view('livewire.vendor-finance-board')
            ->layout('components.layouts.app', [
                'title' => 'Finance & Earnings — Vendor ERP',
            ]);
    }
}
