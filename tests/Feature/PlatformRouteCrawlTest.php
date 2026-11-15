<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Hall;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Platform crawl QA (Phase 5).
 *
 * Requests every GET route for every persona and asserts that:
 *   • no route ever returns a 5xx,
 *   • no route returns an embedded exception page,
 *   • protected surfaces are actually closed to the wrong persona
 *     (admin → customer, customer → vendor ERP, vendor → admin).
 *
 * This is the automated equivalent of clicking every page of the product.
 */
class PlatformRouteCrawlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Concrete GET routes (parameterised routes are covered separately with
     * real ids so they are exercised rather than skipped).
     */
    private function crawlableRoutes(): array
    {
        $routes = [];

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $uri = $route->uri();

            if (str_contains($uri, '{')) {
                continue;
            }

            if (str_starts_with($uri, 'livewire/') || str_starts_with($uri, 'storage/') || $uri === 'up') {
                continue;
            }

            $routes[$uri] = $route->getName();
        }

        ksort($routes);

        return $routes;
    }

    private function personas(): array
    {
        $vendorTenant = Tenant::query()->where('slug', 'nawab-caterers')->firstOrFail();

        $vendorOwner = User::query()->where('tenant_id', $vendorTenant->id)
            ->where('role', 'ROLE_TENANT_OWNER')->firstOrFail();

        $customer = User::query()->where('email', 'customer@example.com')->firstOrFail();
        $admin = User::query()->where('email', 'admin@eventhub.com')->firstOrFail();

        $staff = User::query()->where('tenant_id', $vendorTenant->id)
            ->where('role', '!=', 'ROLE_TENANT_OWNER')
            ->whereNotNull('id')
            ->first();

        return [
            'guest' => null,
            'customer' => $customer,
            'vendor_owner' => $vendorOwner,
            'vendor_staff' => $staff,
            'admin' => $admin,
        ];
    }

    public function test_no_route_returns_a_server_error_for_any_persona(): void
    {
        $failures = [];

        foreach ($this->personas() as $persona => $user) {
            foreach ($this->crawlableRoutes() as $uri => $name) {
                $response = $user
                    ? $this->actingAs($user)->get('/'.$uri)
                    : $this->get('/'.$uri);

                $status = $response->getStatusCode();

                if ($status >= 500) {
                    $failures[] = "[{$persona}] /{$uri} -> {$status}";

                    continue;
                }

                $content = $response->getContent();

                if (str_contains($content, 'Symfony\Component\ErrorHandler')
                    || str_contains($content, 'Whoops, looks like something went wrong')
                    || str_contains($content, 'ParseError')
                    || str_contains($content, 'Call to undefined method')) {
                    $failures[] = "[{$persona}] /{$uri} rendered an exception page";
                }
            }
        }

        $this->assertSame([], $failures, 'Server errors or exception pages detected: '.implode(' | ', $failures));
    }

    public function test_parameterised_routes_resolve_for_their_owner(): void
    {
        $owner = User::query()->where('email', 'owner@venuecloud.pk')->firstOrFail();
        $hall = Hall::query()->firstOrFail();
        $booking = Booking::query()->firstOrFail();
        $payment = Payment::query()->firstOrFail();

        $this->actingAs($owner);

        $this->get(route('public.venue.details', ['slug' => $hall->slug]))->assertOk();
        $this->get(route('documents.agreement', ['id' => $booking->id]))->assertOk();
        $this->get(route('documents.invoice', ['id' => $booking->id]))->assertOk();
        $this->get(route('documents.receipt', ['id' => $payment->id]))->assertOk();
    }

    public function test_protected_surfaces_are_closed_to_the_wrong_persona(): void
    {
        $customer = User::query()->where('email', 'customer@example.com')->firstOrFail();

        // The customer must never reach the platform console.
        $this->actingAs($customer)->get('/admin')->assertForbidden();

        // …nor the vendor ERP.
        $this->actingAs($customer)->get('/vendor/locations')->assertRedirect(route('customer.dashboard'));
        $this->actingAs($customer)->get('/vendor/finance')->assertRedirect(route('customer.dashboard'));
        $this->actingAs($customer)->get('/staff')->assertRedirect(route('customer.dashboard'));

        // The vendor owner must never reach the platform console.
        $owner = User::query()->where('email', 'owner@venuecloud.pk')->firstOrFail();
        $this->actingAs($owner)->get('/admin')->assertForbidden();
    }

    public function test_a_guest_is_sent_to_the_login_screen(): void
    {
        // A guest must be sent to the login screen.
        $this->get('/admin')->assertRedirect(route('login'));
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/customer/events')->assertRedirect(route('login'));
        $this->get('/vendor/locations')->assertRedirect(route('login'));
        $this->get('/account/support')->assertRedirect(route('login'));
    }

    public function test_every_public_page_renders_for_a_guest(): void
    {
        $publicPages = [
            '/', '/venues', '/vendors', '/marketplace', '/gallery', '/packages',
            '/menus', '/services', '/about', '/faqs', '/check-availability',
            '/policies/terms', '/policies/privacy', '/policies/cancellation',
            '/login', '/register', '/customer/login', '/customer/register',
        ];

        foreach ($publicPages as $page) {
            $this->get($page)->assertOk();
        }
    }

    public function test_vendor_public_pages_render_for_an_approved_vendor(): void
    {
        $vendor = Tenant::query()->where('slug', 'nawab-caterers')->firstOrFail();

        $this->get('/vendor/'.$vendor->slug)->assertOk();
        $this->get('/site/'.$vendor->slug)->assertOk();
    }
}
