<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Booking;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Notifications\Notification as NotificationContract;

class NotificationService
{
    use \App\Traits\ServiceTrait;
    
    /** * Create a notification * * @param array $data ['tenant_id', 'user_id', 'type', 'title', 'message', 'priority', 'notificationable_type', 'notificationable_id'] * @return array */
    public function createNotification(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // Validate required fields
            $validator = Validator::make($data, [
                'tenant_id' => 'required|exists:tenants,id',
                'type' => 'required|in:booking,leave,payment,system,announcement',
                'title' => 'required|string|max:255',
                'message' => 'required|string',
            ]);
            
            if ($validator->fails()) {
                return $this->error($validator->errors()->first());
            }
            
            $notification = Notification::create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $data['tenant_id'],
                'user_id' => $data['user_id'] ?? null,
                'type' => $data['type'],
                'title' => $data['title'],
                'message' => $data['message'],
                'is_read' => false,
                'is_email_sent' => false,
                'is_sms_sent' => false,
                'is_whatsapp_sent' => false,
                'priority' => $data['priority'] ?? 2,
                'notificationable_type' => $data['notificationable_type'] ?? null,
                'notificationable_id' => $data['notificationable_id'] ?? null,
            ]);
            
            return $this->success($notification, 'Notification created successfully.');
        });
    }
    
    /** * Send notification to multiple users * * @param array $data ['tenant_id', 'type', 'title', 'message', 'user_ids', 'priority'] * @return array */
    public function sendToMultiple(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $userIds = $data['user_ids'] ?? [];
            $priority = $data['priority'] ?? 2;
            
            $notifications = [];
            
            foreach ($userIds as $userId) {
                $notif = Notification::create([
                    'id' => (string) Str::uuid(),
                    'tenant_id' => $data['tenant_id'],
                    'user_id' => $userId,
                    'type' => $data['type'],
                    'title' => $data['title'],
                    'message' => $data['message'],
                    'is_read' => false,
                    'priority' => $priority,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $notifications[] = $notif;
            }
            
            // Trigger email/SMS/WhatsApp sending (async)
            // $this->deliverNotifications($notifications);
            
            return $this->success($notifications, "Notifications sent to {$count} users.");
        });
    }
    
    /** * Send booking confirmation * * @param Booking $booking * @return array */
    public function sendBookingConfirmation(Booking $booking): array
    {
        return DB::transaction(function () use ($booking) {
            // Create notification
            $notification = $this->createNotification([
                'tenant_id' => $booking->tenant_id,
                'user_id' => $booking->user_id,
                'type' => 'booking',
                'title' => 'Booking Confirmation',
                'message' => "Your booking for {$booking->venue->name} on {$booking->event_date} ({$booking->slot_type}) is {$booking->status}.",
                'priority' => 2,
                'notificationable_type' => Booking::class,
                'notificationable_id' => $booking->id,
            ]);
            
            // Send email (async)
            // Mail::to($booking->user->email)->send(new BookingConfirmed($booking));
            
            // Send WhatsApp notification (if phone available)
            // if ($booking->user->phone) {
            //     // WhatsAppService::send($booking->user->phone, 'Your booking is confirmed...');
            // }
            
            return $this->success($notification, 'Booking confirmation sent.');
        });
    }
    
    /** * Send leave approval notification * * @param StaffLeave $leave * @param string $action 'approved' or 'rejected' * @return array */
    public function sendLeaveApproval(StaffLeave $leave, string $action): array
    {
        return DB::transaction(function () use ($leave, $action) {
            $status = $action === 'approved' ? 'approved' : 'rejected';
            
            $notification = $this->createNotification([
                'tenant_id' => $leave->tenant_id,
                'user_id' => $leave->user_id,
                'type' => 'leave',
                'title' => "Leave {$status}",
                "message => \"Your {$leave->leave_type} leave ({$leave->start_date} to {$leave->end_date}) has been {$status}.\"",
                'priority' => 2,
                'notificationable_type' => StaffLeave::class,
                'notificationable_id' => $leave->id,
            ]);
            
            return $this->success($notification, "Leave {$status} notification sent.");
        });
    }
    
    /** * Send payment receipt * * @param Payment $payment * @return array */
    public function sendPaymentReceipt(Payment $payment): array
    {
        return DB::transaction(function () use ($payment) {
            $notification = $this->createNotification([
                'tenant_id' => $payment->tenant_id,
                'user_id' => $payment->booking?->user_id,
                'type' => 'payment',
                'title' => 'Payment Received',
                'message' => "Payment of PKR " . number_format($payment->amount) . " has been {$payment->status}.",
                'priority' => 2,
                'notificationable_type' => Payment::class,
                'notificationable_id' => $payment->id,
            ]);
            
            // Send email receipt
            // Mail::to($payment->booking->user->email)->send(new PaymentReceipt($payment));
            
            return $this->success($notification, 'Payment receipt sent.');
        });
    }
    
    /** * Send announcement to all tenants * * @param string $title * @param string $message * @param int $priority * @return array */
    public function sendAnnouncement(string $title, string $message, int $priority = 3): array
    {
        return $this->sendToMultiple([
            'tenant_id' => null, // All tenants
            'type' => 'announcement',
            'title' => $title,
            'message' => $message,
            'user_ids' => [], // Will be populated from tenant users
            'priority' => $priority,
        ]);
    }
    
    /** * Deliver notification via email/SMS/WhatsApp * * @param array $notifications * @return void */
    protected function deliverNotifications(array $notifications)
    {
        foreach ($notifications as $notification) {
            try {
                // Send email if not sent and type requires it
                if (!$notification->is_email_sent && in_array($notification->type, ['booking', 'payment', 'leave'])) {
                    // Mail::to($notification->user->email)->send(new ...);
                    // $notification->is_email_sent = true;
                }
                
                // Send SMS if not sent
                // if (!$notification->is_sms_sent) {
                //     // SMSService::send(...);
                //     // $notification->is_sms_sent = true;
                // }
                
                // Send WhatsApp if not sent
                // if (!$notification->is_whatsapp_sent) {
                //     // WhatsAppService::send(...);
                //     // $notification->is_whatsapp_sent = true;
                // }
                
                // Mark as delivered in DB
                // $notification->update(['is_delivered' => true, 'delivered_at' => now()]);
                
            } catch (\Exception $e) {
                Log::error("Notification delivery failed: " . $e->getMessage());
                // Don't throw - keep system resilient
            }
        }
    }
}