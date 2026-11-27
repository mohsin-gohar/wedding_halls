<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_notification_preferences_default_to_enabled(): void
    {
        $user = User::factory()->create();

        $fresh = $user->fresh();

        $this->assertTrue($fresh->email_notifications_enabled);
        $this->assertTrue($fresh->sms_notifications_enabled);
    }

    public function test_log_driver_does_not_mark_sms_as_sent(): void
    {
        config()->set('services.sms.driver', 'log');

        $user = $this->makeUser();
        $notification = app(NotificationService::class)->send(
            $user->id,
            'TEST_NOTIFICATION',
            'Test title',
            'Test message'
        );

        $this->assertNull($notification->fresh()->sms_sent_at);
        Http::assertNothingSent();
    }

    public function test_unconfigured_http_gateway_fails_closed(): void
    {
        config()->set('services.sms.driver', 'http');
        config()->set('services.sms.endpoint', null);

        $notification = app(NotificationService::class)->send(
            $this->makeUser()->id,
            'TEST_NOTIFICATION',
            'Test title',
            'Test message'
        );

        $this->assertNull($notification->fresh()->sms_sent_at);
        Http::assertNothingSent();
    }

    public function test_successful_gateway_response_marks_sms_as_sent(): void
    {
        config()->set('services.sms', [
            'driver' => 'http',
            'endpoint' => 'https://sms.example.test/send',
            'username' => 'gateway-user',
            'password' => 'gateway-pass',
            'sender' => 'RiwajGrand',
            'timeout' => 10,
        ]);

        Http::fake([
            'sms.example.test/*' => Http::response(['status' => 'accepted'], 200),
        ]);

        $user = $this->makeUser();
        $notification = app(NotificationService::class)->send(
            $user->id,
            'TEST_NOTIFICATION',
            'Test title',
            'Test message'
        );

        $this->assertNotNull($notification->fresh()->sms_sent_at);
        Http::assertSent(function ($request) use ($user) {
            return $request->url() === 'https://sms.example.test/send'
                && $request['to'] === $user->phone
                && $request['message'] === 'Test message'
                && $request->hasHeader('Authorization');
        });
    }

    public function test_failed_gateway_response_does_not_mark_sms_as_sent(): void
    {
        config()->set('services.sms', [
            'driver' => 'http',
            'endpoint' => 'https://sms.example.test/send',
            'username' => 'gateway-user',
            'password' => 'gateway-pass',
            'sender' => 'RiwajGrand',
            'timeout' => 10,
        ]);

        Http::fake([
            'sms.example.test/*' => Http::response(['status' => 'rejected'], 500),
        ]);

        $notification = app(NotificationService::class)->send(
            $this->makeUser()->id,
            'TEST_NOTIFICATION',
            'Test title',
            'Test message'
        );

        $this->assertNull($notification->fresh()->sms_sent_at);
    }

    public function test_insecure_http_gateway_is_rejected_when_not_explicitly_allowed(): void
    {
        config()->set('services.sms', [
            'driver' => 'http',
            'endpoint' => 'http://sms.example.test/send',
            'username' => 'gateway-user',
            'password' => 'gateway-pass',
            'sender' => 'RiwajGrand',
            'timeout' => 10,
            'allow_insecure' => false,
        ]);

        Http::fake();

        $notification = app(NotificationService::class)->send(
            $this->makeUser()->id,
            'TEST_NOTIFICATION',
            'Test title',
            'Test message'
        );

        // Plaintext HTTP would leak the Basic credentials, so nothing is sent.
        $this->assertNull($notification->fresh()->sms_sent_at);
        Http::assertNothingSent();
    }

    public function test_gateway_reported_rejection_on_http_200_is_not_marked_sent(): void
    {
        config()->set('services.sms', [
            'driver' => 'http',
            'endpoint' => 'https://sms.example.test/send',
            'username' => 'gateway-user',
            'password' => 'gateway-pass',
            'sender' => 'RiwajGrand',
            'timeout' => 10,
        ]);

        Http::fake([
            'sms.example.test/*' => Http::response(['status' => 'rejected', 'reason' => 'INSUFFICIENT_CREDIT'], 200),
        ]);

        $notification = app(NotificationService::class)->send(
            $this->makeUser()->id,
            'TEST_NOTIFICATION',
            'Test title',
            'Test message'
        );

        // Providers often answer 200 even for rejected messages; a rejection
        // in the body must not be recorded as a successful delivery.
        $this->assertNull($notification->fresh()->sms_sent_at);
    }

    public function test_non_delivering_mailer_does_not_mark_email_as_sent(): void
    {
        config()->set('mail.default', 'array');

        $user = User::factory()->create([
            'phone' => '03001234567',
            'email_notifications_enabled' => true,
            'sms_notifications_enabled' => false,
        ]);

        $notification = app(NotificationService::class)->send(
            $user->id,
            'TEST_NOTIFICATION',
            'Test title',
            'Test message'
        );

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertNull($notification->fresh()->email_sent_at);
    }

    public function test_notification_inherits_the_recipient_tenant_when_omitted(): void
    {
        $tenant = \App\Models\Tenant::create([
            'name' => 'Notification Tenant',
            'slug' => 'notification-'.uniqid(),
            'email' => 'notification-'.uniqid().'@example.test',
            'verification_status' => 'APPROVED',
            'subscription_status' => 'ACTIVE',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_TENANT_OWNER',
            'email_notifications_enabled' => false,
            'sms_notifications_enabled' => false,
        ]);

        $notification = app(NotificationService::class)->send(
            $user->id,
            'TENANT_TEST',
            'Tenant notification',
            'Correct tenant context'
        );

        $this->assertSame($tenant->id, $notification->tenant_id);
    }

    public function test_notification_cannot_be_written_to_another_tenant_context(): void
    {
        $tenant = \App\Models\Tenant::create([
            'name' => 'Notification Tenant',
            'slug' => 'notification-'.uniqid(),
            'email' => 'notification-'.uniqid().'@example.test',
            'verification_status' => 'APPROVED',
            'subscription_status' => 'ACTIVE',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'ROLE_TENANT_OWNER',
            'email_notifications_enabled' => false,
            'sms_notifications_enabled' => false,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        app(NotificationService::class)->send(
            $user->id,
            'TENANT_TEST',
            'Tenant notification',
            'Wrong tenant context',
            '00000000-0000-4000-8000-000000000001'
        );
    }

    private function makeUser(): User
    {
        return User::factory()->create([
            'phone' => '03001234567',
            'email_notifications_enabled' => false,
            'sms_notifications_enabled' => true,
        ]);
    }
}
