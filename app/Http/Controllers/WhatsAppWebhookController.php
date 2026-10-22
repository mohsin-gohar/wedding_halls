<?php

namespace App\Http\Controllers;

use App\Models\GuestRsvp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Webhook verification challenge for Meta WhatsApp Cloud API.
     */
    public function verify(Request $request)
    {
        $verifyToken = config('services.whatsapp.verify_token');

        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($verifyToken && $mode === 'subscribe' && hash_equals($verifyToken, (string) $token)) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    /**
     * Process incoming WhatsApp messages and update RSVP headcount.
     */
    public function handle(Request $request)
    {
        $appSecret = config('services.whatsapp.app_secret');
        $signature = (string) $request->header('X-Hub-Signature-256');
        $expectedSignature = $appSecret
            ? 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret)
            : '';

        if (! $expectedSignature || ! hash_equals($expectedSignature, $signature)) {
            return response()->json(['status' => 'unauthorized'], 403);
        }

        $payload = $request->all();
        Log::info('WhatsApp Webhook received');

        $entry = $payload['entry'][0]['changes'][0]['value'] ?? null;
        if (! $entry || empty($entry['messages'])) {
            return response()->json(['status' => 'ignored'], 200);
        }

        $messageObj = $entry['messages'][0];
        $fromPhone = $messageObj['from'] ?? null;
        $messageBody = trim($messageObj['text']['body'] ?? '');

        if (! $fromPhone || empty($messageBody)) {
            return response()->json(['status' => 'empty'], 200);
        }

        // Clean phone digits for search
        $cleanedPhone = preg_replace('/[^0-9]/', '', $fromPhone);
        $last7Digits = substr($cleanedPhone, -7);

        // Find matching guest RSVP record
        $rsvp = GuestRsvp::where('phone', 'like', "%{$last7Digits}%")
            ->latest()
            ->first();

        $replyText = 'Thank you for contacting Wedding_Hall.';

        if ($rsvp) {
            $status = 'ATTENDING';
            $count = 1;

            if (preg_match('/(decline|no|nahi|cannot|cancel)/i', $messageBody)) {
                $status = 'DECLINED';
                $count = 0;
                $replyText = "Thank you {$rsvp->guest_name}. We have noted your decline for the wedding event.";
            } elseif (preg_match('/(?:attending|yes|aunga|haan|persons?|people|guests?|head)[\s:]*(\d+)/i', $messageBody, $matches)) {
                $status = 'ATTENDING';
                $count = (int) $matches[1];
                $replyText = "Thank you {$rsvp->guest_name}! Your attendance with {$count} guest(s) has been confirmed on the hall guest list.";
            } elseif (preg_match('/^(\d+)$/', $messageBody, $matches)) {
                $status = 'ATTENDING';
                $count = (int) $matches[1];
                $replyText = "Thank you {$rsvp->guest_name}! Confirmed {$count} guest(s). We look forward to welcoming you.";
            } else {
                $status = 'ATTENDING';
                $replyText = "Thank you {$rsvp->guest_name}! Your RSVP has been confirmed. We look forward to seeing you!";
            }

            $rsvp->update([
                'rsvp_status' => $status,
                'attending_count' => $count,
                'last_reply_message' => $messageBody,
            ]);
        }

        // Send WhatsApp reply back via Meta Cloud API or log
        $this->sendWhatsAppMessage($fromPhone, $replyText);

        return response()->json([
            'status' => 'success',
            'rsvp_id' => $rsvp ? $rsvp->id : null,
            'reply' => $replyText,
        ], 200);
    }

    /**
     * Dispatch an outbound WhatsApp message.
     */
    protected function sendWhatsAppMessage(string $toPhone, string $message): void
    {
        $token = config('services.whatsapp.access_token');
        $phoneId = config('services.whatsapp.phone_number_id');
        $apiVersion = config('services.whatsapp.api_version', 'v19.0');

        if ($token && $phoneId) {
            try {
                Http::withToken($token)->post("https://graph.facebook.com/{$apiVersion}/{$phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $toPhone,
                    'type' => 'text',
                    'text' => ['body' => $message],
                ]);
            } catch (\Exception $e) {
                Log::error('WhatsApp dispatch error: '.$e->getMessage());
            }
        } else {
            Log::info("WhatsApp Sandbox Outbound to [{$toPhone}]: {$message}");
        }
    }
}
