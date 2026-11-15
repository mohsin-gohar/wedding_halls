<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;

class PaymentService
{
    public function createPayment(
        string $tenantId,
        string $bookingId,
        float $amount,
        string $paymentType,
        string $paymentMethod,
        ?string $transactionReference = null,
        ?string $receivedByUserId = null,
        ?string $notes = null
    ): Payment {
        return Payment::create([
            'tenant_id' => $tenantId,
            'booking_id' => $bookingId,
            'amount' => $amount,
            'payment_type' => $paymentType,
            'payment_method' => $paymentMethod,
            'receipt_no' => Payment::generateReceiptNumber(),
            'transaction_reference' => $transactionReference,
            'received_by_user_id' => $receivedByUserId,
            'notes' => $notes,
        ]);
    }

    public function processPayment(
        string $bookingId,
        float $amount,
        string $paymentMethod,
        array $gatewayData = []
    ): array {
        $booking = Booking::findOrFail($bookingId);

        $throttleKey = 'payment_process|'.$bookingId.'|'.$paymentMethod;

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return [
                'success' => false,
                'message' => 'Too many payment attempts. Please wait and try again.',
                'retry_after' => RateLimiter::availableIn($throttleKey),
            ];
        }

        RateLimiter::hit($throttleKey, 60);

        try {
            switch ($paymentMethod) {
                case 'STRIPE':
                    return $this->processStripePayment($booking, $amount, $gatewayData);
                case 'JAZZCASH':
                    return $this->processJazzCashPayment($booking, $amount, $gatewayData);
                case 'EASYPAISA':
                    return $this->processEasyPaisaPayment($booking, $amount, $gatewayData);
                case 'BANK_TRANSFER':
                    return $this->processBankTransfer($booking, $amount, $gatewayData);
                case 'CASH':
                    return $this->processCashPayment($booking, $amount, $gatewayData);
                default:
                    return [
                        'success' => false,
                        'message' => 'Unsupported payment method: '.$paymentMethod,
                    ];
            }
        } catch (\Throwable $e) {
            Log::error('Payment processing failed', [
                'booking_id' => $bookingId,
                'amount' => $amount,
                'method' => $paymentMethod,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Payment processing failed. Please try again.',
            ];
        }
    }

    protected function processStripePayment(Booking $booking, float $amount, array $data): array
    {
        $stripeKey = config('services.stripe.key');
        $stripeSecret = config('services.stripe.secret');

        if (! $stripeKey || ! $stripeSecret) {
            return [
                'success' => false,
                'message' => 'Stripe is not configured. Please contact the platform administrator.',
            ];
        }

        Stripe::setApiKey($stripeSecret);

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => (int) ($amount * 100),
                'currency' => 'pkr',
                'metadata' => [
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                    'tenant_id' => $booking->tenant_id,
                ],
                'description' => 'Riwaj Grand Booking: '.$booking->booking_reference,
            ]);

            return [
                'success' => true,
                'payment_intent_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
                'amount' => $amount,
                'currency' => 'PKR',
                'message' => 'Payment intent created. Complete payment via Stripe.',
            ];
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'message' => 'Stripe error: '.$e->getMessage(),
            ];
        }
    }

    protected function processJazzCashPayment(Booking $booking, float $amount, array $data): array
    {
        $merchantId = config('services.jazzcash.merchant_id');
        $password = config('services.jazzcash.password');
        $returnUrl = config('services.jazzcash.return_url');

        if (! $merchantId || ! $password) {
            return [
                'success' => false,
                'message' => 'JazzCash is not configured. Please contact the platform administrator.',
            ];
        }

        $pp_TxnType = 'MWALLET';
        $pp_TxnDateTime = now()->format('YmdHis');
        $pp_TxnRef = $booking->booking_reference;
        $pp_Amount = (int) ($amount * 100);
        $pp_BillToPhone = $data['phone'] ?? $booking->customer_phone;
        $pp_Description = 'Riwaj Grand Booking: '.$booking->booking_reference;
        $pp_TxnExpiryDateTime = now()->addHours(24)->format('YmdHis');
        $pp_ReturnURL = $returnUrl;

        $pp_SecureHash = hash_hmac('sha256',
            $pp_TxnType.$pp_TxnDateTime.$pp_TxnRef.$pp_Amount.$pp_BillToPhone.$pp_Description.$pp_TxnExpiryDateTime.$pp_ReturnURL,
            $password
        );

        return [
            'success' => true,
            'gateway_url' => 'https://sandbox.jazzcash.com.pk/CustomerPortal/transactionservices/merchantcustomform',
            'post_data' => [
                'pp_Version' => '1.0',
                'pp_TxnType' => $pp_TxnType,
                'pp_TxnDateTime' => $pp_TxnDateTime,
                'pp_TxnRef' => $pp_TxnRef,
                'pp_Amount' => $pp_Amount,
                'pp_BillToPhone' => $pp_BillToPhone,
                'pp_Description' => $pp_Description,
                'pp_TxnExpiryDateTime' => $pp_TxnExpiryDateTime,
                'pp_ReturnURL' => $pp_ReturnURL,
                'pp_MerchantID' => $merchantId,
                'pp_SecureHash' => $pp_SecureHash,
                'pp_Language' => 'EN',
                'pp_TokenizedCC' => 'false',
            ],
            'amount' => $amount,
            'currency' => 'PKR',
            'message' => 'Redirect to JazzCash for payment.',
        ];
    }

    protected function processEasyPaisaPayment(Booking $booking, float $amount, array $data): array
    {
        $merchantId = config('services.easypaisa.merchant_id');
        $secretKey = config('services.easypaisa.secret_key');
        $returnUrl = config('services.easypaisa.return_url');

        if (! $merchantId || ! $secretKey) {
            return [
                'success' => false,
                'message' => 'EasyPaisa is not configured. Please contact the platform administrator.',
            ];
        }

        $txnRef = $booking->booking_reference;
        $txnDateTime = now()->format('YmdHis');
        $amountInPaisa = (int) ($amount * 100);
        $msisdn = $data['phone'] ?? $booking->customer_phone;

        $signature = hash_hmac('sha256',
            $merchantId.$txnRef.$txnDateTime.$amountInPaisa.$msisdn,
            $secretKey
        );

        return [
            'success' => true,
            'gateway_url' => 'https://api.easypaisa.com.pk/payment',
            'post_data' => [
                'merchantId' => $merchantId,
                'txnRef' => $txnRef,
                'txnDateTime' => $txnDateTime,
                'amount' => $amountInPaisa,
                'msisdn' => $msisdn,
                'signature' => $signature,
                'returnUrl' => $returnUrl,
                'description' => 'Riwaj Grand Booking: '.$booking->booking_reference,
            ],
            'amount' => $amount,
            'currency' => 'PKR',
            'message' => 'Redirect to EasyPaisa for payment.',
        ];
    }

    protected function processBankTransfer(Booking $booking, float $amount, array $data): array
    {
        return [
            'success' => true,
            'payment_method' => 'BANK_TRANSFER',
            'amount' => $amount,
            'currency' => 'PKR',
            'message' => 'Bank transfer initiated. Please deposit to the venue account and upload the receipt.',
            'bank_details' => [
                'account_title' => $booking->tenant->name,
                'account_number' => $data['account_number'] ?? 'To be provided by venue',
                'bank_name' => $data['bank_name'] ?? 'To be provided by venue',
                'branch' => $data['branch'] ?? 'To be provided by venue',
            ],
            'requires_verification' => true,
        ];
    }

    protected function processCashPayment(Booking $booking, float $amount, array $data): array
    {
        return [
            'success' => true,
            'payment_method' => 'CASH',
            'amount' => $amount,
            'currency' => 'PKR',
            'message' => 'Cash payment recorded. Pay at the venue reception.',
            'requires_verification' => false,
        ];
    }

    public function verifyWebhook(array $payload, string $signature, string $gateway): bool
    {
        switch ($gateway) {
            case 'stripe':
                $secret = config('services.stripe.webhook_secret');
                if (! $secret) {
                    return false;
                }
                try {
                    Stripe::setApiKey(config('services.stripe.secret'));
                    $event = Webhook::constructEvent(
                        $payload, $signature, $secret
                    );

                    return $event->type === 'payment_intent.succeeded';
                } catch (\Throwable $e) {
                    Log::error('Stripe webhook verification failed: '.$e->getMessage());

                    return false;
                }
            case 'jazzcash':
                return isset($payload['pp_ResponseCode']) && $payload['pp_ResponseCode'] === '000';
            case 'easypaisa':
                return isset($payload['resultCode']) && $payload['resultCode'] === 'SUCCESS';
            default:
                return false;
        }
    }

    public function getPaymentSummary(string $tenantId, string $period = 'MONTHLY'): array
    {
        $query = Payment::where('tenant_id', $tenantId);

        $now = now();
        switch ($period) {
            case 'DAILY':
                $query->whereDate('created_at', $now->toDateString());
                break;
            case 'WEEKLY':
                $query->whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()]);
                break;
            case 'MONTHLY':
            default:
                $query->whereBetween('created_at', [$now->startOfMonth(), $now->endOfMonth()]);
                break;
            case 'YEARLY':
                $query->whereYear('created_at', $now->year);
                break;
        }

        $total = $query->sum('amount');
        $count = $query->count();
        $byMethod = $query->selectRaw('payment_method, sum(amount) as total, count(*) as count')
            ->groupBy('payment_method')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->payment_method => ['total' => (float) $row->total, 'count' => (int) $row->count]]);

        return [
            'total_amount' => (float) $total,
            'total_count' => (int) $count,
            'period' => $period,
            'by_method' => $byMethod,
        ];
    }
}
