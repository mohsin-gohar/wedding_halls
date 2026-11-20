<?php

namespace App\Livewire;

use App\Exceptions\ServiceBookingException;
use App\Models\ServiceBooking;
use App\Models\ServicePayment;
use App\Models\VendorPayout;
use App\Models\VendorWallet;
use App\Models\WalletTransaction;
use App\Services\ServiceBookingService;
use App\Support\CashOnlyPayments;
use App\Support\PermissionMatrix;
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

    public string $payoutMethod = CashOnlyPayments::CASH;

    public string $payoutAccountName = '';

    public string $payoutAccountDetails = '';

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->tenant_id || ! PermissionMatrix::userCan($user, 'wallet.view')) {
            abort(403, 'Unauthorized. Finance is restricted to vendor staff.');
        }
    }

    public function getCanVerifyPaymentsProperty(): bool
    {
        return PermissionMatrix::userCan(Auth::user(), 'payments.verify');
    }

    public function getCanRequestPayoutProperty(): bool
    {
        return PermissionMatrix::userCan(Auth::user(), 'payouts.request');
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

    /**
     * Customer-submitted cash payments awaiting staff confirmation. Nothing
     * here touches the booking balance or wallet until verifyPayment() runs.
     */
    public function getPendingPaymentsProperty()
    {
        return ServicePayment::query()
            ->where('tenant_id', Auth::user()->tenant_id)
            ->where('status', 'PENDING')
            ->where('method', CashOnlyPayments::CASH)
            ->with(['serviceBooking:id,booking_reference,event_name', 'customer:id,name'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
    }

    /**
     * Verify one pending cash payment. Scoped to the signed-in vendor tenant
     * and gated by payments.verify; the service settles booking + wallet
     * atomically, and every settlement is written to the audit log.
     */
    public function verifyPayment(string $paymentId): void
    {
        $user = Auth::user();

        if (! $user || ! $user->tenant_id) {
            abort(403);
        }

        if (! PermissionMatrix::userCan($user, 'payments.verify')) {
            abort(403, 'Unauthorized. You may not verify service payments.');
        }

        $payment = ServicePayment::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('method', CashOnlyPayments::CASH)
            ->where('status', 'PENDING')
            ->findOrFail($paymentId);

        try {
            $result = app(ServiceBookingService::class)->verifyServicePayment($payment, (int) $user->id);

            if (($result['success'] ?? false) !== true) {
                $this->errorMessage = $result['message'] ?? 'The payment could not be verified.';

                return;
            }

            if (! empty($result['already_verified'])) {
                $this->feedbackMessage = 'This cash payment was already verified. No duplicate ledger entry was created.';

                return;
            }

            $this->feedbackMessage = 'Cash payment verified. Booking balance and wallet ledger updated.';
            $this->errorMessage = null;
        } catch (ServiceBookingException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function openPayoutModal(): void
    {
        $this->reset(['payoutAmount', 'payoutAccountName', 'payoutAccountDetails']);
        $this->payoutMethod = CashOnlyPayments::CASH;
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

        if (! PermissionMatrix::userCan($user, 'payouts.request')) {
            abort(403, 'Unauthorized. You may not request payouts.');
        }

        $this->validate([
            'payoutAmount' => ['required', 'numeric', 'min:1'],
            'payoutMethod' => CashOnlyPayments::validationRule(),
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
