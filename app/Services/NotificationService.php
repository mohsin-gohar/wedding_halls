<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

class NotificationService
{
    /**
     * Send an in-app notification to a user.
     */
    public function send(
        string $userId,
        string $type,
        string $title,
        string $message,
        ?string $tenantId = null,
        array $data = []
    ): Notification {
        $user = User::query()->findOrFail($userId);

        if ($tenantId !== null && (string) $user->tenant_id !== (string) $tenantId) {
            throw new \\InvalidArgumentException('A notification cannot be assigned to a different tenant than its recipient.');
        }

        // Tenant-owned users inherit their tenant context when callers omit it.
        $tenantId ??= $user->tenant_id;

        $notification = Notification::createNotification(
            $userId, $type, $title, $message, $tenantId, $data
        );

        if ($user && $user->email_notifications_enabled) {
            $this->sendEmailNotification($notification, $user);
        }

        if ($user && $user->sms_notifications_enabled && $user->phone) {
            $this->sendSmsNotification($notification, $user);
        }

        return $notification;
    }

    /**
     * Send notification to multiple users.
     */
    public function sendToUsers(
        array $userIds,
        string $type,
        string $title,
        string $message,
        ?string $tenantId = null,
        array $data = []
    ): array {
        $notifications = [];

        foreach ($userIds as $userId) {
            $notifications[] = $this->send($userId, $type, $title, $message, $tenantId, $data);
        }

        return $notifications;
    }

    /**
     * Send notification to all staff of a tenant.
     */
    public function sendToTenantStaff(
        string $tenantId,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): array {
        $users = User::where('tenant_id', $tenantId)
            ->whereNotIn('role', ['ROLE_CUSTOMER'])
            ->get();

        return $this->sendToUsers(
            $users->pluck('id')->toArray(),
            $type,
            $title,
            $message,
            $tenantId,
            $data
        );
    }

    /**
     * Send email notification through the configured Laravel mailer.
     *
     * Local/test mailers (log, array, null) do not deliver to a real recipient,
     * so they must never be recorded as a sent email.
     */
    protected function sendEmailNotification(Notification $notification, User $user): void
    {
        try {
            $throttleKey = 'email_notification|'.$user->id.'|'.$notification->type;

            if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
                return;
            }

            RateLimiter::hit($throttleKey, 60);

            Mail::send('emails.notification', [
                'user' => $user,
                'notification' => $notification,
            ], function ($mail) use ($user, $notification) {
                $mail->to($user->email)
                    ->subject($notification->title);
            });

            $mailer = (string) config('mail.default');

            if (in_array($mailer, ['log', 'array', 'null'], true)) {
                Log::info('Email notification rendered but not delivered', [
                    'user_id' => $user->id,
                    'notification_id' => $notification->id,
                    'mailer' => $mailer,
                ]);
            } else {
                $notification->update(['email_sent_at' => now()]);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send email notification', [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Deliver SMS through the configured gateway.
     *
     * Local logging must never be reported as delivery: `sms_sent_at` is only
     * written after a real gateway accepts the request with a 2xx response.
     */
    protected function sendSmsNotification(Notification $notification, User $user): void
    {
        try {
            $throttleKey = 'sms_notification|'.$user->id.'|'.$notification->type;

            if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
                return;
            }

            RateLimiter::hit($throttleKey, 60);

            $config = config('services.sms', []);
            $driver = (string) ($config['driver'] ?? 'log');
            $endpoint = (string) ($config['endpoint'] ?? '');
            $username = (string) ($config['username'] ?? '');
            $password = (string) ($config['password'] ?? '');

            if ($driver === 'null') {
                return;
            }

            if ($driver === 'log' || $endpoint === '') {
                Log::info('SMS notification not delivered', [
                    'user_id' => $user->id,
                    'notification_id' => $notification->id,
                    'reason' => $driver === 'log' ? 'log_driver' : 'gateway_not_configured',
                ]);

                return;
            }

            $scheme = strtolower((string) parse_url($endpoint, PHP_URL_SCHEME));
            $allowInsecure = (bool) ($config['allow_insecure'] ?? false);

            if ($driver !== 'http' || $username === '' || $password === ''
                || ! filter_var($endpoint, FILTER_VALIDATE_URL)
                || ($scheme !== 'https' && ! $allowInsecure)) {
                Log::warning('SMS notification skipped: invalid gateway configuration', [
                    'user_id' => $user->id,
                    'notification_id' => $notification->id,
                    'driver' => $driver,
                    'scheme' => $scheme,
                ]);

                return;
            }

            $response = Http::asForm()
                ->acceptJson()
                ->timeout((int) ($config['timeout'] ?? 10))
                ->withBasicAuth($username, $password)
                ->post($endpoint, [
                    'to' => $user->phone,
                    'message' => $notification->message,
                    'sender' => (string) ($config['sender'] ?? 'RiwajGrand'),
                    'reference' => $notification->id,
                ]);

            if ($response->failed()) {
                throw new RuntimeException('SMS gateway returned HTTP '.$response->status());
            }

            // Some providers answer 200 even when the message was rejected.
            // Honour an explicit rejection in the JSON body before recording
            // the message as delivered.
            $gatewayPayload = $response->json();
            if (is_array($gatewayPayload)) {
                $gatewayStatus = strtolower((string) ($gatewayPayload['status'] ?? ''));
                $rejectedStatus = $gatewayStatus !== '' && ! in_array($gatewayStatus, ['accepted', 'success', 'sent', 'ok', 'queued'], true);
                $explicitFailure = array_key_exists('success', $gatewayPayload) && ! $gatewayPayload['success'];

                if ($rejectedStatus || $explicitFailure) {
                    throw new RuntimeException('SMS gateway rejected the message.');
                }
            }

            $notification->update(['sms_sent_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('Failed to send SMS notification', [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get unread notification count for a user.
     */
    public function unreadCount(string $userId): int
    {
        return Notification::unreadCountForUser($userId);
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(string $userId): int
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
