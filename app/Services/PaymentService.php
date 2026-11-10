<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Booking;
use App\Models\Tenant;
use Exception;
use Stripe;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    use \App\Traits\ServiceTrait;
    
    /** * Process Stripe payment * * @param array $data * @return array */
    public function processStripePayment(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $amount = $data['amount'] * 100; // Stripe expects cents
            $currency = $data['currency'] ?? 'PKR';
            
            try {
                $paymentIntent = Stripe\PaymentIntent::create([
                    'amount' => $amount,
                    'currency' => strtolower($currency) === 'pkr' ? 'usd' : strtolower($currency),
                    'metadata' => [
                        'tenant_id' => $data['tenant_id'],
                        'booking_id' => $data['booking_id'],
                        'payment_type' => $data['payment_type'],
                    ],
                ]);
                
                // Create payment record
                $payment = Payment::create([
                    'id' => (string) Str::uuid(),
                    'tenant_id' => $data['tenant_id'],
                    'booking_id' => $data['booking_id'],
                    'provider' => 'stripe',
                    'provider_payment_id' => $paymentIntent->id,
                    'status' => 'pending',
                    'amount' => $data['amount'],
                    'currency' => $data['currency'],
                    'payment_type' => $data['payment_type'],
                    'metadata' => ['stripe_payment_intent' => $paymentIntent->id],
                ]);
                
                return $this->success($payment, 'Stripe payment intent created. Redirect user to complete payment.');
                
            } catch (\Stripe\Exception\CardException $e) {
                return $this->error('Card declined: ' . $e->getMessage(), 400);
            } catch (\Stripe\Exception\StripeException $e) {
                Log::error('Stripe error: ' . $e->getMessage());
                return $this->error('Payment processor error. Please try again.', 500);
            }
        });
    }
    
    /** * Verify Stripe webhook event * * @param string $eventId * @param string $signature * @return array */
    public function verifyStripeWebhook(string $eventId, string $signature): array
    {
        $endpointSecret = config('services.stripe.webhook_secret');
        
        try {
            $event = \Stripe\Webhook::constructEvent(
                file_get_contents("php://input"),
                $signature,
                $endpointSecret
            );
            
            // Handle the event
            switch ($event->type) {
                case '\Stripe\Event::PAYMENT_INTENT_SUCCEEDED':
                    $paymentIntent = $event->data->object;
                    $this->handleStripePaymentSucceeded($paymentIntent);
                    break;
                    
                case '\Stripe\Event::PAYMENT_INTENT_FAILED':
                    $paymentIntent = $event->data->object;
                    $this->handleStripePaymentFailed($paymentIntent);
                    break;
                    
                default:
                    // Ignore other events
                    break;
            }
            
            return $this->success(['event' => $event->type], 'Webhook event handled.');
            
        } catch (\Exception $e) {
            Log::error('Webhook error: ' . $e->getMessage());
            return $this->error('Webhook verification failed.', 400);
        }
    }
    
    /** * Handle successful Stripe payment * * @param array $paymentIntent */
    private function handleStripePaymentSucceeded(array $paymentIntent)
    {
        $intentId = $paymentIntent['id'];
        
        // Find and update payment record
        $payment = Payment::where('provider_payment_id', $intentId)->first();
        
        if ($payment) {
            $payment->status = 'completed';
            $payment->save();
            
            // Update booking status if needed
            if ($payment->booking_id) {
                $booking = Booking::find($payment->booking_id);
                if ($booking) {
                    $booking = (new BookingService())->updateBookingStatus($booking, 'CONFIRMED');
                }
            }
            
            // Send confirmation notifications
            // app/Services/NotificationService::sendPaymentConfirmation($payment);
        }
    }
    
    /** * Handle failed Stripe payment * * @param array $paymentIntent */
    private function handleStripePaymentFailed(array $paymentIntent)
    {
        $intentId = $paymentIntent['id'];
        
        $payment = Payment::where('provider_payment_id', $intentId)->first();
        
        if ($payment) {
            $payment->status = 'failed';
            $payment->save();
            
            // Send failure notification
            // Notification::create([...]);
        }
    }
    
    /** * Process JazzCash payment * * @param array $data * @return array */
    public function processJazzCashPayment(array $data): array
    {
        // Integration with JazzCash production/sandbox
        // This is a placeholder for the actual API integration
        
        return DB::transaction(function () use ($data) {
            // Simulate JazzCash API response
            $response = [
                'status' => 'success', // or 'failed'
                'transaction_id' => 'JC-'. $faker->randomNumber(8),
                'amount' => $data['amount'],
                'currency' => 'PKR',
            ];
            
            $payment = Payment::create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $data['tenant_id'],
                'booking_id' => $data['booking_id'],
                'provider' => 'jazzcash',
                'provider_payment_id' => $response['transaction_id'],
                'status' => $response['status'],
                'amount' => $data['amount'],
                'currency' => 'PKR',
                'payment_type' => $data['payment_type'],
                'metadata' => ['gateway_response' => $response],
            ]);
            
            return $this->success($payment, 'JazzCash payment processed.');
        });
    }
    
    /** * Process EasyPaisa payment * * @param array $data * @return array */
    public function processEasyPaisaPayment(array $data): array
    {
        // Similar to JazzCash - placeholder for actual API integration
        return $this->processJazzCashPayment($data); // Shared logic for Pakistan bank transfers
    }
    
    /** * Process Bank Transfer payment * * @param array $data * @return array */
    public function processBankTransferPayment(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // Create pending payment record
            $payment = Payment::create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $data['tenant_id'],
                'booking_id' => $data['booking_id'],
                'provider' => 'bank_transfer',
                'provider_payment_id' => 'BT-'. $faker->randomNumber(8),
                'status' => 'pending',
                'amount' => $data['amount'],
                'currency' => 'PKR',
                'payment_type' => $data['payment_type'],
                'metadata' => [
                    'bank_name' => $data['bank_name'] ?? 'Unknown',
                    'account_number' => substr($data['account_number'] ?? '', -4),
                ],
            ]);
            
            // Send notification to tenant about bank transfer
            // Notification::create([...]);
            
            return $this->success($payment, 'Bank transfer payment record created. Awaiting confirmation.');
        });
    }
    
    /** * Process Cash payment * * @param array $data * @return array */
    public function processCashPayment(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $payment = Payment::create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $data['tenant_id'],
                'booking_id' => $data['booking_id'],
                'provider' => 'cash',
                'provider_payment_id' => 'CASH-'. $faker->randomNumber(6),
                'status' => 'completed', // Cash is typically immediate
                'amount' => $data['amount'],
                'currency' => 'PKR',
                'payment_type' => $data['payment_type'],
                'metadata' => ['received_by' => $data['received_by'] ?? 'staff'],
            ]);
            
            // Immediately update booking status for cash
            if ($payment->booking_id) {
                (new BookingService())->updateBookingStatus(
                    Booking::find($payment->booking_id), 
                    'CONFIRMED'
                );
            }
            
            // Send confirmation
            // Notification::create([...]);
            
            return $this->success($payment, 'Cash payment received and booking confirmed.');
        });
    }
    
    /** * Get payment summary for a booking * * @param Booking $booking * @return array */
    public function getPaymentSummary(Booking $booking): array
    {
        return DB::transaction(function () use ($booking) {
            $payments = $booking->payments()->get();
            
            $total = 0;
            $advance = 0;
            $balance = 0;
            $statuses = [];
            
            foreach ($payments as $payment) {
                $total += $payment->amount;
                if ($payment->payment_type === 'deposit') {
                    $advance += $payment->amount;
                }
                $balance = max(0, $total - $advance);
                $statuses[] = $payment->status;
            }
            
            $isPaid = in_array('completed', $statuses) && $total > 0;
            
            return $this->success([
                'total_amount' => $total,
                'advance_paid' => $advance,
                'balance_due' => $balance,
                'is_paid' => $isPaid,
                'payment_statuses' => array_count_values($statuses),
                'number_of_payments' => count($payments),
            ], 'Payment summary retrieved.');
        });
    }
}