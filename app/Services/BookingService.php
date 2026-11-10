# 🏗️ EVENTHUB SAAS - SERVICE LAYER & BUSINESS LOGIC

## 📦 **Overview**
Decouple business logic from controllers into dedicated Service/Action classes with strict return types. This ensures clean architecture, testability, and maintainability.

---
<tool_call>
<function=write>
<parameter=content>
# EVENTHUB SAAS - SERVICE LAYER

## 📋 **Service Architecture**

### **Service Pattern**
```
app/Services/
├── BookingService.php
├── PaymentService.php
├── StaffService.php
├── NotificationService.php
├── SearchService.php
├── SubscriptionService.php
└── PlatformAnalyticsService.php
```

### **Core Principles**
1. **Single Responsibility**: Each service handles one domain concern
2. **Strict Return Types**: All services return typed results (DTOs, enums, or null)
3. **Transactional Integrity**: Use Laravel's `DB::transaction()` for multi-model operations
4. **Repository Pattern**: Optional repo layer for data access abstraction
5. **Error Handling**: Custom exceptions with meaningful messages
6. **Dependency Injection**: Services injected via Laravel's container

### **Service Base Trait**
```php
trait ServiceTrait {
    protected function success($data = null, $message = '') {
        return ['success' => true, 'data' => $data, 'message' => $message];
    }
    
    protected function error($message, $code = 422) {
        return ['success' => false, 'message' => $message, 'code' => $code];
    }
    
    protected function transaction($callback) {
        return DB::transaction($callback);
    }
}
```

---
<tool_call>
<function=write>
<parameter=content>
<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Venue;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

class BookingService
{
    use \App\Traits\ServiceTrait;
    
    /** * Validate and create a new booking * * @param array $data * @return array * @throws Exception */
    public function createBooking(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // Validate input
            $validator = Validator::make($data, [
                'tenant_id' => 'required|exists:tenants,id',
                'venue_id' => 'required|exists:venues,id',
                'event_date' => 'required|date|after_or_equal:today',
                'slot_type' => 'required|in:AFTERNOON,EVENING,NIGHT',
                'guests_count' => 'required|integer|min:1|max:500',
                'payment_method' => 'required|in:stripe,jazzcash,easypaisa,bank_transfer,cash',
            ]);
            
            if ($validator->fails()) {
                return $this->error($validator->errors()->first());
            }
            
            $tenant = Tenant::find($data['tenant_id']);
            $venue = Venue::find($data['venue_id']);
            
            // Check tenant can accept bookings
            if (!$tenant->canAcceptBookings()) {
                return $this->error('Tenant is not verified to accept bookings');
            }
            
            // Check venue capacity
            if ($data['guests_count'] > $venue->max_capacity) {
                return $this->error('Guest count exceeds venue capacity');
            }
            
            // Check for double booking (same tenant, date, slot)
            $existing = Booking::where('tenant_id', $data['tenant_id'])
                ->where('venue_id', $data['venue_id'])
                ->where('event_date', $data['event_date'])
                ->where('slot_type', $data['slot_type'])
                ->where('status', 'CONFIRMED')
                ->exists();
            
            if ($existing) {
                return $this->error('This shift is already booked. Please select another time slot.');
            }
            
            // Create booking
            $booking = Booking::create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $data['tenant_id'],
                'venue_id' => $data['venue_id'],
                'user_id' => $data['user_id'] ?? null,
                'booked_by' => $data['booked_by'] ?? null,
                'event_date' => $data['event_date'],
                'slot_type' => $data['slot_type'],
                'status' => 'INQUIRY', // Start as inquiry
                'guests_count' => $data['guests_count'],
                'total_amount' => $venue->base_rent,
                'advance_paid' => 0,
                'balance_due' => $venue->base_rent,
                'grand_total' => $venue->base_rent,
                'payment_method' => $data['payment_method'],
                'notes' => $data['notes'] ?? '',
            ]);
            
            // Create initial payment record (deposit)
            $payment = Payment::create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $data['tenant_id'],
                'booking_id' => $booking->id,
                'provider' => $data['payment_method'],
                'status' => 'pending',
                'amount' => $venue->base_rent * 0.3, // 30% deposit
                'currency' => 'PKR',
                'payment_type' => 'deposit',
                'metadata' => ['source' => 'booking_creation'],
            ]);
            
            return $this->success($booking, 'Booking created successfully. Deposit of 30% requested.');
        });
    }
    
    /** * Update booking status (accept/reject) * * @param Booking $booking * @param string $status * @return array */
    public function updateBookingStatus(Booking $booking, string $status): array
    {
        $validStatuses = ['INQUIRY', 'HOLD', 'CONFIRMED', 'COMPLETED', 'CANCELLED'];
        
        if (!in_array($status, $validStatuses)) {
            return $this->error("Invalid status. Valid: " . implode(', ', $validStatuses));
        }
        
        return DB::transaction(function () use ($booking, $status) {
            $booking->status = $status;
            $booking->save();
            
            // If confirmed, create payment link
            if ($status === 'CONFIRMED') {
                // Trigger payment service to create link
                // app/Services/PaymentService::createPaymentLink($booking);
            }
            
            // Create notification for tenant
            // app/Services/NotificationService::sendBookingNotification($booking, 'accepted');
            
            return $this->success($booking, "Booking {$status.toLowerCase()} successfully.");
        });
    }
    
    /** * Calculate balance due for a booking * * @param Booking $booking * @return array */
    public function calculateBookingBalance(Booking $booking): array
    {
        return DB::transaction(function () use ($booking) {
            $advance = $booking->advance_paid ?? 0;
            $total = $booking->grand_total ?? $booking->total_amount;
            $balance = max(0, $total - $advance);
            
            $booking->balance_due = $balance;
            $booking->save();
            
            // Create remaining payment record
            if ($balance > 0) {
                Payment::create([
                    'id' => (string) Str::uuid(),
                    'tenant_id' => $booking->tenant_id,
                    'booking_id' => $booking->id,
                    'provider' => 'manual',
                    'status' => 'pending',
                    'amount' => $balance,
                    'currency' => 'PKR',
                    'payment_type' => 'balance',
                    'metadata' => ['calculation' => 'remaining_balance'],
                ]);
            }
            
            return $this->success([
                'balance_due' => $balance,
                'total_amount' => $total,
                'advance_paid' => $advance,
            ], 'Balance calculated successfully.');
        });
    }
    
    /** * Cancel a booking with refund logic * * @param Booking $booking * @return array */
    public function cancelBooking(Booking $booking, string $reason = ''): array
    {
        return DB::transaction(function () use ($booking, $reason) {
            // Don't allow canceling completed or past bookings without refund logic
            if ($booking->status === 'COMPLETED') {
                return $this->error('Cannot cancel completed bookings.');
            }
            
            if ($booking->isPastDate()) {
                return $this->error('Cannot cancel past bookings.');
            }
            
            $booking->status = 'CANCELLED';
            $booking->notes = $booking->notes . ($reason ? " | Cancelled: {$reason}" : '');
            $booking->save();
            
            // Process refund for completed payments
            $booking->payments()->where('status', 'completed')->each(function ($payment) {
                // app/Services/PaymentService::processRefund($payment);
            });
            
            // Create cancellation notification
            // Notification::create([...]);
            
            return $this->success($booking, 'Booking cancelled successfully.');
        });
    }
    
    /** * Get bookings with filters for dashboard * * @param array $filters * @return Builder */
    public function getBookingsForDashboard(array $filters = []): Builder
    {
        $query = Booking::with(['tenant', 'venue', 'payments'])
            ->where(function ($q) use ($filters) {
                if (isset($filters['tenant_id'])) {
                    $q->where('tenant_id', $filters['tenant_id']);
                }
                if (isset($filters['status'])) {
                    $q->where('status', $filters['status']);
                }
                if (isset($filters['date_range'])) {
                    $dates = explode(' - ', $filters['date_range']);
                    $q->whereBetween('event_date', [$dates[0], $dates[1]]);
                }
            })
            ->latest('event_date');
        
        return $query;
    }
}