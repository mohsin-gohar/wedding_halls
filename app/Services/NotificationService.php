<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

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
        $notification = Notification::createNotification(
            $userId, $type, $title, $message, $tenantId, $data
        );

        $user = User::find($userId);

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
        $users = \App\Models\User::where('tenant_id', $tenantId)
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
     * Send email notification (placeholder - integrates with Mailgun/SendGrid).
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

            $notification->update(['email_sent_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('Failed to send email notification', [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send SMS notification (placeholder - integrates with Twilio or local gateway).
     */
    protected function sendSmsNotification(Notification $notification, User $user): void
    {
        try {
            $throttleKey = 'sms_notification|'.$user->id.'|'.$notification->type;

            if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
                return;
            }

            RateLimiter::hit($throttleKey, 60);

            // SMS gateway integration point (Twilio, MSG91, local Pakistani gateway, etc.)
            Log::info('SMS notification queued', [
                'to' => $user->phone,
                'message' => $notification->message,
                'notification_id' => $notification->id,
            ]);

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