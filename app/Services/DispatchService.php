<?php

namespace App\Services;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;

class DispatchService
{
    protected GuzzleClient $http;

    public function __construct()
    {
        $this->http = new GuzzleClient([
            'timeout' => 30.0,
        ]);
    }

    public function sendBookingConfirmation(string $bookingReference, string $clientPhone, string $clientName, string $currency, float $amount): void
    {
        $this->sendWhatsAppBookingConfirmation($clientPhone, $clientName, $bookingReference, $currency, $amount);
        $this->sendSMSConfirmation($clientPhone, $clientName, $bookingReference, $currency, $amount);
        $this->logDispatch('booking_confirmation', $clientPhone, 'CLIENT');
    }

    public function sendVendorPaymentNotification(string $vendorName, string $vendorPhone, float $amount, string $currency, string $bookingRef): void
    {
        $this->sendWhatsAppPaymentNotification($vendorPhone, $vendorName, $amount, $currency, $bookingRef);
        $this->logDispatch('payment_notification', $vendorPhone, 'VENDOR');
    }

    private function sendWhatsAppBookingConfirmation(string $phone, string $name, string $reference, string $currency, float $amount): void
    {
        $formattedAmount = number_format($amount, 2, '.', ',');
        $message = "🎉 Booking Confirmed! Reference: {$reference}. Total: {$currency}{$formattedAmount}. Welcome to Jashn Planner!";
        $this->sendWhatsApp($phone, $message);
    }

    private function sendSMSConfirmation(string $phone, string $name, string $reference, string $currency, float $amount): void
    {
        $formattedAmount = number_format($amount, 2, '.', ',');
        $message = "Jashn Planner: Your event is confirmed! Ref: {$reference}. Total: {$currency}{$formattedAmount}. Visit jashnplanner.com";
        $this->sendSMS($phone, $message);
    }

    private function sendWhatsAppPaymentNotification(string $phone, string $name, float $amount, string $currency, string $bookingRef): void
    {
        $formattedAmount = number_format($amount, 2, '.', ',');
        $message = "Payment of {$currency}{$formattedAmount} received for booking {$bookingRef}. Thank you!";
        $this->sendWhatsApp($phone, $message);
    }

    private function sendWhatsApp(string $phone, string $message): void
    {
        Log::info("WhatsApp to {$phone}: {$message}");
    }

    private function sendSMS(string $phone, string $message): void
    {
        Log::info("SMS to {$phone}: {$message}");
    }

    private function logDispatch(string $template, string $recipient, string $recipientType): void
    {
        Log::info("Dispatch logged: {$template} to {$recipientType}: {$recipient}");
    }
}
