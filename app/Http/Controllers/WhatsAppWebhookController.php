<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\GuestRsvp;
use App\Models\ProcessedWebhookMessage;
use Illuminate\Database\UniqueConstraintViolationException;
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
     *
     * Security rules:
     *   - A valid HMAC signature is mandatory.
     *   - Every message is claimed exactly once by provider + message id.
     *   - Invitations are matched by the complete normalized phone number,
     *     never by a 7-digit suffix. Ambiguous matches are never mutated.
     *   - Optional tenant/booking metadata narrows the match further.
     */
    public function handle(Request $request)
    {
        $rawBody = $request->getContent();
        $appSecret = config('services.whatsapp.app_secret');
        $signature = (string) $request->header('X-Hub-Signature-256');
        $expectedSignature = $appSecret
            ? 'sha256='.hash_hmac('sha256', $rawBody, $appSecret)
            : '';

        if (! $expectedSignature || ! hash_equals($expectedSignature, $signature)) {
            return response()->json(['status' => 'unauthorized'], 403);
        }

        $payload = json_decode($rawBody, true);
        if (! is_array($payload)) {
            $payload = $request->all();
        }

        Log::info('WhatsApp Webhook received');

        $entries = $payload['entry'] ?? [];
        $results = [];

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];
            $value = $changes[0]['value'] ?? null;

            if (! $value || empty($value['messages'])) {
                continue;
            }

            foreach ($value['messages'] as $messageObj) {
                $results[] = $this->processMessage($messageObj, $value, $rawBody);
            }
        }

        if ($results === []) {
            return response()->json(['status' => 'ignored'], 200);
        }

        $first = $results[0];

        return response()->json([
            'status' => $first['status'],
            'rsvp_id' => $first['rsvp_id'],
            'reply' => $first['reply'],
            'results' => $results,
        ], 200);
    }

    /**
     * Claim one webhook message, resolve its invitation and apply the RSVP.
     *
     * @param  array<string, mixed>  $message
     * @param  array<string, mixed>  $value
     * @return array<string, mixed>
     */
    private function processMessage(array $message, array $value, string $rawBody): array
    {
        $messageId = trim((string) ($message['id'] ?? ''));
        $dedupeKey = $messageId !== ''
            ? $messageId
            : 'hash:'.hash('sha256', $rawBody);
        $payloadHash = hash('sha256', $rawBody);

        try {
            ProcessedWebhookMessage::create([
                'provider' => 'whatsapp',
                'message_id' => $dedupeKey,
                'payload_hash' => $payloadHash,
                'processed_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return [
                'status' => 'duplicate',
                'rsvp_id' => null,
                'reply' => 'Duplicate message ignored.',
            ];
        }

        $fromPhone = $message['from'] ?? null;
        $messageBody = trim((string) ($message['text']['body'] ?? ''));

        if (! $fromPhone || $messageBody === '') {
            return [
                'status' => 'empty',
                'rsvp_id' => null,
                'reply' => 'Thank you for contacting Riwaj Grand.',
            ];
        }

        $rsvp = $this->resolveInvitation($fromPhone, $value);
        $replyText = 'Thank you for contacting Riwaj Grand.';

        if (! $rsvp) {
            $this->sendWhatsAppMessage($fromPhone, $replyText);

            return [
                'status' => 'unmatched',
                'rsvp_id' => null,
                'reply' => $replyText,
            ];
        }

        $status = null;
        $count = null;
        $replyText = "We could not understand your RSVP. Please reply YES, YES followed by your guest count, or NO to decline.";

        if (preg_match('/(?:\\bdecline\\b|\\bnahi\\b|\\bcannot\\b|\\bcancel\\b|\\bno\\b)/i', $messageBody)) {
            $status = 'DECLINED';
            $count = 0;
            $replyText = "Thank you {$rsvp->guest_name}. We have noted your decline for the wedding event.";
        } elseif (preg_match('/(?:\\battending\\b|\\byes\\b|\\baunga\\b|\\bhaan\\b|\\bpersons?\\b|\\bpeople\\b|\\bguests?\\b|\\bhead\\b)[\\s:]*(\\d+)?/i', $messageBody, $matches)
            || preg_match('/^(\\d+)$/', $messageBody, $matches)) {
            $status = 'ATTENDING';
            $count = isset($matches[1]) && $matches[1] !== ''
                ? max(1, min(20, (int) $matches[1]))
                : 1;
            $replyText = "Thank you {$rsvp->guest_name}! Your attendance with {$count} guest(s) has been confirmed on the hall guest list.";
        } else {
            // An arbitrary message (e.g. "hello" or "what time?") is not an
            // RSVP decision and must never silently confirm a guest.
            $this->sendWhatsAppMessage($fromPhone, $replyText);

            return [
                'status' => 'needs_clarification',
                'rsvp_id' => $rsvp->id,
                'reply' => $replyText,
            ];
        }

        $rsvp->update([
            'rsvp_status' => $status,
            'attending_count' => $count,
            'last_reply_message' => $messageBody,
        ]);

        AuditLog::log(
            'WHATSAPP_RSVP_UPDATED',
            "WhatsApp message [{$dedupeKey}] set RSVP [{$rsvp->id}] to {$status}/{$count}.",
            $rsvp->tenant_id,
            'GuestRsvp',
            $rsvp->id,
        );

        $this->sendWhatsAppMessage($fromPhone, $replyText);

        return [
            'status' => 'success',
            'rsvp_id' => $rsvp->id,
            'reply' => $replyText,
        ];
    }

    /**
     * Resolve an invitation by exact normalized full phone number.
     *
     * Optional tenant_id / booking_id in the webhook value narrow the search
     * for integrations that carry event context. If more than one invitation
     * still matches, none is updated: guessing a number suffix must never
     * change another event's headcount.
     */
    private function resolveInvitation(?string $fromPhone, array $value): ?GuestRsvp
    {
        $canonical = $this->normalizePhone($fromPhone);

        if (strlen($canonical) < 10) {
            return null;
        }

        // Narrow by the commonly contiguous final seven digits; stored numbers
        // may contain spaces/dashes in their country code. Exact full-number
        // matching below is the authority, so a short candidate needle cannot
        // create a false positive.
        $needle = substr($canonical, -7);
        $query = GuestRsvp::with('booking')
            ->where('phone', 'like', "%{$needle}");

        $metadata = is_array($value['metadata'] ?? null) ? $value['metadata'] : [];
        $tenantId = $value['tenant_id'] ?? $metadata['tenant_id'] ?? null;
        $bookingId = $value['booking_id'] ?? $metadata['booking_id'] ?? null;

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        if ($bookingId) {
            $query->where('booking_id', $bookingId);
        }

        $matches = $query->limit(50)->get()
            ->filter(fn (GuestRsvp $rsvp): bool => $this->normalizePhone($rsvp->phone) === $canonical)
            ->values();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    /** Normalize local (03XX) and international (+923XX) numbers to one form. */
    private function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D/', '', (string) $phone) ?? '';

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return substr($digits, 1);
        }
        if (strlen($digits) === 12 && str_starts_with($digits, '92')) {
            return substr($digits, 2);
        }

        return $digits;
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
