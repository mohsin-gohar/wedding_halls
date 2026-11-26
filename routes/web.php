<?php

use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Middleware\RequirePortalPermission;
use App\Livewire\AdminCategoryManager;
use App\Livewire\AdminDashboard;
use App\Livewire\AttendanceCalendar;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\TwoFactorAuthComponent;
use App\Livewire\Auth\VerifyEmail;
use App\Livewire\BeautyBridalDashboard;
use App\Livewire\CalendarShiftGrid;
use App\Livewire\CatererDashboard;
use App\Livewire\CustomerDashboard;
use App\Livewire\CustomerEventWorkspace;
use App\Livewire\CustomerLogin;
use App\Livewire\CustomerRegister;
use App\Livewire\Dashboard;
use App\Livewire\DecoratorDashboard;
use App\Livewire\DirectBooking;
use App\Livewire\ExpenseTracker;
use App\Livewire\FloorPlanDesigner;
use App\Livewire\HallChat;
use App\Livewire\HallMediaManager;
use App\Livewire\InventoryTracker;
use App\Livewire\KitchenKotBoard;
use App\Livewire\NotificationCenter;
use App\Livewire\PackageManager;
use App\Livewire\PhotographerDashboard;
use App\Livewire\PublicAbout;
use App\Livewire\PublicAvailabilityChecker;
use App\Livewire\PublicContact;
use App\Livewire\PublicFaqs;
use App\Livewire\PublicGallery;
use App\Livewire\PublicGuestRsvp;
use App\Livewire\PublicHallDetails;
use App\Livewire\PublicMarketplace;
use App\Livewire\PublicMenus;
use App\Livewire\PublicPolicies;
use App\Livewire\PublicServices;
use App\Livewire\PublicVendorProfile;
use App\Livewire\PublicVendors;
use App\Livewire\PublicVenues;
use App\Livewire\StaffAttendance;
use App\Livewire\StaffManager;
use App\Livewire\StaffPayroll;
use App\Livewire\SupportCenter;
use App\Livewire\UtilityFuelTracker;
use App\Livewire\VendorBookingsBoard;
use App\Livewire\VendorFinanceBoard;
use App\Livewire\VendorLocationsManager;
use App\Livewire\VendorPortfolioManager;
use App\Livewire\VendorQuoteRequest;
use App\Livewire\VendorQuotesBoard;
use App\Livewire\VendorServicesManager;
use App\Livewire\WeddingPlannerDashboard;
use App\Livewire\WhatsAppRsvpManager;
use App\Livewire\WhiteLabelSite;
use App\Models\AuditLog;
use App\Models\Hall;
use App\Models\Package;
use App\Models\ServicePayment;
use App\Models\Tenant;
use App\Models\VendorCategory;
use App\Models\VendorPayout;
use App\Services\ServiceBookingService;
use App\Support\CashOnlyPayments;
use App\Support\PermissionMatrix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| 1. Public Marketplace & Guest Experience Routes
|--------------------------------------------------------------------------
*/
// Cinematic Homepage (Cached with automatic fallback)
Route::get('/', function () {
    [$halls, $packages] = Cache::remember('home.featured.v3', 120, function () {
        $featuredHalls = Hall::with(['venue', 'tenant'])
            ->publicVisible()
            ->whereHas('tenant', fn ($q) => $q->where('tenants.marketplace_visible', true))
            ->take(6)
            ->get();

        $featuredPackages = Package::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('tenant_id')
                    ->orWhereHas('tenant', function ($q) {
                        $q->marketplaceVisible();
                    });
            })
            ->take(4)
            ->get();

        return [$featuredHalls, $featuredPackages];
    });

    return view('welcome', compact('halls', 'packages'));
})->middleware('throttle:public-pages')->name('home');

// Dynamic robots.txt so the sitemap URL follows APP_URL/config instead of a
// hardcoded production hostname.
Route::get('/robots.txt', function () {
    $lines = [
        'User-agent: *',
        'Allow: /',
        'Disallow: /admin',
        'Disallow: /dashboard',
        'Disallow: /vendor/dashboard',
        'Disallow: /vendor/bookings',
        'Disallow: /vendor/quotes',
        'Disallow: /vendor/services',
        'Disallow: /vendor/finance',
        'Disallow: /vendor/portfolio',
        'Disallow: /vendor/locations',
        'Disallow: /vendor/team',
        'Disallow: /vendor/settings',
        'Disallow: /customer/dashboard',
        'Disallow: /documents/',
        '',
        'Sitemap: '.url('/sitemap.xml'),
    ];

    return response(implode("\n", $lines), 200, [
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Cache-Control' => 'public, max-age=3600',
    ]);
})->middleware('throttle:public-pages')->name('robots');

// Dynamic XML Sitemap for Search Engines
Route::get('/sitemap.xml', function () {
    $staticUrls = [
        route('home'),
        route('public.venues'),
        route('marketplace'),
        route('public.vendors'),
        route('public.gallery'),
        route('public.packages'),
        route('public.menus'),
        route('public.services'),
        route('public.about'),
        route('public.contact'),
        route('public.faqs'),
        route('public.availability'),
    ];

    $halls = Hall::publicVisible()
        ->whereHas('tenant', fn ($q) => $q->where('tenants.marketplace_visible', true))
        ->whereNotNull('slug')
        ->select(['slug', 'updated_at'])
        ->get();

    // Only approved, marketplace-visible businesses and active top-level
    // categories belong in the index. Private ERP routes stay disallowed in
    // robots.txt and are never emitted here.
    $categories = VendorCategory::query()
        ->active()
        ->topLevel()
        ->get(['slug', 'updated_at']);

    $vendors = Tenant::query()
        ->marketplaceVisible()
        ->whereNotNull('slug')
        ->get(['slug', 'updated_at']);

    $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

    foreach ($staticUrls as $loc) {
        $xml .= '  <url>'."\n";
        $xml .= '    <loc>'.htmlspecialchars($loc, ENT_XML1, 'UTF-8').'</loc>'."\n";
        $xml .= '    <changefreq>daily</changefreq>'."\n";
        $xml .= '    <priority>0.8</priority>'."\n";
        $xml .= '  </url>'."\n";
    }

    foreach ($categories as $category) {
        $loc = route('public.vendors', ['category' => $category->slug]);
        $lastmod = optional($category->updated_at)->toAtomString() ?? now()->toAtomString();
        $xml .= '  <url>'."\n";
        $xml .= '    <loc>'.htmlspecialchars($loc, ENT_XML1, 'UTF-8').'</loc>'."\n";
        $xml .= '    <lastmod>'.$lastmod.'</lastmod>'."\n";
        $xml .= '    <changefreq>weekly</changefreq>'."\n";
        $xml .= '    <priority>0.7</priority>'."\n";
        $xml .= '  </url>'."\n";
    }

    foreach ($halls as $hall) {
        $loc = route('hall.showcase', $hall->slug);
        $lastmod = optional($hall->updated_at)->toAtomString() ?? now()->toAtomString();
        $xml .= '  <url>'."\n";
        $xml .= '    <loc>'.htmlspecialchars($loc, ENT_XML1, 'UTF-8').'</loc>'."\n";
        $xml .= '    <lastmod>'.$lastmod.'</lastmod>'."\n";
        $xml .= '    <changefreq>weekly</changefreq>'."\n";
        $xml .= '    <priority>0.9</priority>'."\n";
        $xml .= '  </url>'."\n";
    }

    foreach ($vendors as $vendor) {
        $loc = route('public.vendor-profile', $vendor->slug);
        $lastmod = optional($vendor->updated_at)->toAtomString() ?? now()->toAtomString();
        $xml .= '  <url>'."\n";
        $xml .= '    <loc>'.htmlspecialchars($loc, ENT_XML1, 'UTF-8').'</loc>'."\n";
        $xml .= '    <lastmod>'.$lastmod.'</lastmod>'."\n";
        $xml .= '    <changefreq>weekly</changefreq>'."\n";
        $xml .= '    <priority>0.8</priority>'."\n";
        $xml .= '  </url>'."\n";
    }

    $xml .= '</urlset>';

    return response($xml, 200, [
        'Content-Type' => 'application/xml; charset=UTF-8',
        'Cache-Control' => 'public, max-age=3600',
    ]);
})->middleware('throttle:public-pages')->name('sitemap');

// Vendor Hall Media (defined before /hall/{id} wildcard)
Route::middleware(['auth', 'tenant.staff', 'portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER'])->get('/hall/media', HallMediaManager::class)->name('hall.media');

// Venue Exploration & Showcase
Route::get('/venues', PublicVenues::class)->middleware('throttle:public-pages')->name('public.venues');
Route::get('/marketplace', PublicMarketplace::class)->middleware('throttle:public-pages')->name('marketplace');
Route::get('/venues/{slug}', PublicHallDetails::class)->middleware('throttle:public-pages')->name('public.venue.details');
Route::get('/hall/{slug}', PublicHallDetails::class)->middleware('throttle:public-pages')->name('hall.showcase');
Route::get('/venues/{slug}/chat', HallChat::class)->middleware('throttle:public-pages')->name('public.venue.chat');
Route::get('/hall/{slug}/chat', HallChat::class)->middleware('throttle:public-pages')->name('hall.chat');
Route::get('/concierge', HallChat::class)->middleware('throttle:public-pages')->name('public.concierge');

/*
|--------------------------------------------------------------------------
| Multi-Vendor Marketplace ERP — Authenticated Vendor Staff Routes
|--------------------------------------------------------------------------
*/
// Permission gates mirror PermissionMatrix (including enterprise role aliases)
// so route access, sidebar navigation and Livewire actions agree. The matrix
// fallback also covers legacy accounts whose string role predates Spatie.
// Middleware is referenced as an FQCN string with a permission parameter so
// Laravel can resolve it without a bootstrap alias registration.
Route::middleware(['auth', 'tenant.staff'])->group(function () {
    Route::get('/vendor/quotes', VendorQuotesBoard::class)
        ->middleware(RequirePortalPermission::class.':rfq.view')
        ->name('vendor.quotes');
    Route::get('/vendor/bookings', VendorBookingsBoard::class)
        ->middleware(RequirePortalPermission::class.':service-bookings.view')
        ->name('vendor.bookings');
    Route::get('/vendor/services', VendorServicesManager::class)
        ->middleware(RequirePortalPermission::class.':services.view')
        ->name('vendor.services');
    Route::get('/vendor/portfolio', VendorPortfolioManager::class)
        ->middleware(RequirePortalPermission::class.':portfolio.view')
        ->name('vendor.portfolio');
    Route::get('/vendor/locations', VendorLocationsManager::class)
        ->middleware(RequirePortalPermission::class.':locations.view')
        ->name('vendor.locations');
    Route::get('/vendor/finance', VendorFinanceBoard::class)
        ->middleware(RequirePortalPermission::class.':wallet.view')
        ->name('vendor.finance');

    // Staff-only settlement of customer-submitted cash payments. Customer API
    // submissions remain PENDING; only this authenticated, tenant-scoped action
    // may verify a payment and settle the vendor wallet.
    Route::post('/vendor/payments/{payment}/verify', function (string $payment) {
        $user = auth()->user();

        abort_unless($user && $user->tenant_id, 403, 'Unauthorized. Vendor staff access is required.');
        abort_unless(PermissionMatrix::userCan($user, 'payments.verify'), 403, 'Unauthorized. You may not verify service payments.');

        $record = ServicePayment::query()->whereKey($payment)->firstOrFail();

        abort_unless((string) $record->tenant_id === (string) $user->tenant_id, 404, 'Payment not found.');
        abort_unless($record->method === CashOnlyPayments::CASH, 422, 'Only cash payments can be verified here.');
        abort_unless($record->status === 'PENDING', 422, 'Only pending payments can be verified.');

        $result = app(ServiceBookingService::class)->verifyServicePayment($record, (int) $user->id);

        abort_unless(($result['success'] ?? false) === true, 422, $result['message'] ?? 'Payment could not be verified.');

        $message = ! empty($result['already_verified'])
            ? 'This cash payment was already verified. No duplicate ledger entry was created.'
            : 'Cash payment verified. Booking balance and wallet ledger updated.';

        return back()->with('message', $message);
    })->name('vendor.payments.verify');
});

// Multi-vendor Services Marketplace (categories are DB-driven via vendor_categories)
// NOTE: the public /vendor/{slug} catch-all is registered AFTER the vendor ERP
// block further down this file so it can never shadow /vendor/dashboard.
Route::get('/vendors', PublicVendors::class)->middleware('throttle:public-pages')->name('public.vendors');

// Visual Gallery, Packages & Menus
Route::get('/gallery', PublicGallery::class)->middleware('throttle:public-pages')->name('public.gallery');
Route::get('/packages', PublicMenus::class)->middleware('throttle:public-pages')->name('public.packages');
Route::get('/menus', PublicMenus::class)->middleware('throttle:public-pages')->name('public.menus');
Route::get('/services', PublicServices::class)->middleware('throttle:public-pages')->name('public.services');

// Company & Customer Assistance
Route::get('/about', PublicAbout::class)->middleware('throttle:public-pages')->name('public.about');
Route::get('/contact', PublicContact::class)->name('public.contact')->middleware('throttle:reload-protection');
Route::get('/faqs', PublicFaqs::class)->middleware('throttle:public-pages')->name('public.faqs');
Route::get('/check-availability', PublicAvailabilityChecker::class)->name('public.availability')->middleware('throttle:public-availability');
Route::redirect('/availability', '/check-availability', 301);
Route::get('/rsvp', PublicGuestRsvp::class)->name('public.rsvp')->middleware('throttle:reload-protection');

// Legal Policies
Route::get('/policies/terms', PublicPolicies::class)->middleware('throttle:public-pages')->name('public.policies.terms');
Route::get('/policies/privacy', PublicPolicies::class)->middleware('throttle:public-pages')->name('public.policies.privacy');
Route::get('/policies/cancellation', PublicPolicies::class)->middleware('throttle:public-pages')->name('public.policies.cancellation');
Route::get('/policies/{policy?}', PublicPolicies::class)->middleware('throttle:public-pages')->name('public.policies');
Route::get('/terms', function () {
    return redirect()->route('public.policies', ['policy' => 'terms']);
})->name('terms');
Route::get('/privacy', function () {
    return redirect()->route('public.policies', ['policy' => 'privacy']);
})->name('privacy');
Route::get('/cancellation', function () {
    return redirect()->route('public.policies', ['policy' => 'cancellation']);
})->name('cancellation');
Route::get('/refund', function () {
    return redirect()->route('public.policies', ['policy' => 'refund']);
})->name('refund');

/*
|--------------------------------------------------------------------------
| 2. Customer Portal Authentication & Experience
|--------------------------------------------------------------------------
*/
Route::prefix('customer')->group(function () {
    Route::get('/login', CustomerLogin::class)->name('customer.login')->middleware('throttle:public-pages');
    Route::get('/register', CustomerRegister::class)->name('customer.register')->middleware('throttle:public-pages');
});

Route::middleware(['auth', 'portal.role:ROLE_CUSTOMER'])->prefix('customer')->group(function () {
    Route::get('/dashboard', CustomerDashboard::class)->name('customer.dashboard');
    Route::get('/bookings', CustomerDashboard::class)->name('customer.bookings');
    Route::get('/events', CustomerEventWorkspace::class)->name('customer.events');
});

/*
|--------------------------------------------------------------------------
| 3. Unified & Vendor Authentication Routes (Livewire + Native HTTP POST)
|--------------------------------------------------------------------------
*/
Route::get('/login', Login::class)->name('login')->middleware('throttle:public-pages');
Route::get('/register', Register::class)->name('register')->middleware('throttle:public-pages');

$authenticatePortalRequest = function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    $throttleKey = Str::lower($credentials['email']).'|'.$request->ip();

    if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
        $seconds = RateLimiter::availableIn($throttleKey);

        return back()->withErrors([
            'email' => "Aap dobara se login koshish nahi kar saktay. Baraye meherbani {$seconds} seconds intezar karein. (Too many login attempts. Please try again in {$seconds} seconds.)",
        ])->withInput($request->only('email'));
    }

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        $user = Auth::user();

        if (! in_array($user->status, ['ACTIVE'], true)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Your account is not active. Contact the platform administrator.',
            ])->withInput($request->only('email'));
        }

        $user->recordLogin();

        // Honor the stored intended URL (e.g. a marketplace page the guest
        // came from), falling back to the portal home.
        return redirect()->intended(route($user->portalHomeRoute()))->with('message', "Welcome back, {$user->name}!");
    }

    RateLimiter::hit($throttleKey, 60);

    return back()->withErrors([
        'email' => 'These credentials do not match our registered records.',
    ])->withInput($request->only('email'));
};

Route::post('/login', $authenticatePortalRequest)->name('login.perform')->middleware('throttle:reload-protection');
Route::post('/customer/login', $authenticatePortalRequest)->name('customer.login.perform')->middleware('throttle:reload-protection');

// POST-only: a GET logout is CSRF-able and can be triggered by a crafted
// image/link, silently terminating a victim's session.
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login')->with('message', 'You have been signed out successfully.');
})->middleware('auth')->name('logout');

Route::middleware(['auth'])->prefix('account')->group(function () {
    Route::get('/support', SupportCenter::class)->name('support.center');
});

/*
|--------------------------------------------------------------------------
| 3.6 API Token Management (Mobile / Partner Integrations)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('account/api-tokens')->group(function () {
    Route::get('/', [ApiTokenController::class, 'index'])->name('account.api-tokens.index');
    Route::post('/', [ApiTokenController::class, 'store'])->name('account.api-tokens.store');
    Route::delete('/{id}', [ApiTokenController::class, 'destroy'])->name('account.api-tokens.destroy');
});

/*
|--------------------------------------------------------------------------
| 3.5 Password Reset & Email Verification Routes
|--------------------------------------------------------------------------
*/
Route::get('/password/forgot', ForgotPassword::class)->name('password.forgot')->middleware('throttle:reload-protection');
Route::get('/password/reset', ResetPassword::class)->name('password.reset')->middleware('throttle:reload-protection');
Route::get('/verify/email', VerifyEmail::class)->name('verify.email')->middleware('throttle:reload-protection');
Route::get('/2fa/setup', TwoFactorAuthComponent::class)->name('2fa.setup')->middleware('auth');
Route::get('/2fa/verify', TwoFactorAuthComponent::class)->name('2fa.verify')->middleware('auth');

/*
|--------------------------------------------------------------------------
| 4. Vendor ERP Management Suite (Protected by Auth & Tenant Staff Guard)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'tenant.staff'])->group(function () {
    Route::middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER,ROLE_ACCOUNTANT')->group(function () {
        // Primary vendor dashboard. The route keeps the legacy "dashboard"
        // name because the ERP layout, the login redirect and the customer
        // dashboard all link to route('dashboard').
        Route::get('/dashboard', Dashboard::class)->name('dashboard');

        // Category-specific dashboards
        Route::get('/vendor/dashboard/photography', PhotographerDashboard::class)
            ->name('vendor.dashboard.photography')
            ->middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER');

        Route::get('/vendor/dashboard/catering', CatererDashboard::class)
            ->name('vendor.dashboard.catering')
            ->middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER');

        Route::get('/vendor/dashboard/decoration', DecoratorDashboard::class)
            ->name('vendor.dashboard.decoration')
            ->middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER');

        Route::get('/vendor/dashboard/beauty-bridal', BeautyBridalDashboard::class)
            ->name('vendor.dashboard.beauty-bridal')
            ->middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER');

        Route::get('/vendor/dashboard/wedding-planners', WeddingPlannerDashboard::class)
            ->name('vendor.dashboard.wedding-planners')
            ->middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER');

        // Generic dashboard (fallback)
        Route::get('/vendor/dashboard', Dashboard::class)->name('vendor.dashboard.generic');
    });

    Route::middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER,ROLE_FRONT_DESK,ROLE_FLOOR_STAFF')->get('/calendar', CalendarShiftGrid::class)->name('calendar');
    Route::middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER,ROLE_CHEF,ROLE_FLOOR_STAFF')->get('/kitchen-kots', KitchenKotBoard::class)->name('kitchen.board');
    Route::middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER')->get('/utility-fuel', UtilityFuelTracker::class)->name('utility.fuel');
    Route::middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER,ROLE_FLOOR_STAFF')->get('/seating-planner', FloorPlanDesigner::class)->name('seating.planner');
    Route::middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER,ROLE_FRONT_DESK')->get('/whatsapp-rsvps', WhatsAppRsvpManager::class)->name('whatsapp.rsvp');
    Route::middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER')->get('/staff', StaffManager::class)->name('staff');
    Route::middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER,ROLE_ACCOUNTANT')->get('/staff/attendance', StaffAttendance::class)->name('staff.attendance');
    Route::middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER,ROLE_ACCOUNTANT')->get('/staff/attendance/calendar', AttendanceCalendar::class)->name('staff.attendance.calendar');
    Route::middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER,ROLE_ACCOUNTANT')->get('/staff/payroll', StaffPayroll::class)->name('staff.payroll');
    Route::middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER,ROLE_CHEF')->get('/inventory', InventoryTracker::class)->name('inventory');
    Route::middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER,ROLE_CHEF')->get('/catering-packages', PackageManager::class)->name('packages.manager');
    Route::middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER,ROLE_ACCOUNTANT')->get('/expenses', ExpenseTracker::class)->name('expenses');
    Route::middleware(['auth', 'tenant.staff'])->get('/notifications', NotificationCenter::class)->name('notifications');
});

/*
|--------------------------------------------------------------------------
| 4.1 Public Vendor Profile (registered after the vendor ERP routes so the
|     /vendor/{slug} catch-all can never shadow /vendor/dashboard)
|--------------------------------------------------------------------------
*/
Route::get('/vendor/{slug}', PublicVendorProfile::class)->middleware('throttle:public-pages')->name('public.vendor-profile');
Route::get('/vendor/{slug}/request-quote', VendorQuoteRequest::class)->middleware(['auth', 'throttle:public-pages'])->name('public.vendor-quote');
Route::get('/vendor/{slug}/book', DirectBooking::class)->middleware(['auth', 'throttle:public-pages'])->name('public.vendor-book');

// White-label vendor microsite — the vendor's own branded website.
Route::get('/site/{slug}', WhiteLabelSite::class)->middleware('throttle:public-pages')->name('public.vendor-site');

/*
|--------------------------------------------------------------------------
| 5. Platform Super Administration Console (Protected by Auth)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'super.admin'])->prefix('admin')->group(function () {
    Route::get('/', AdminDashboard::class)->name('admin.dashboard');
    Route::get('/dashboard', AdminDashboard::class);
    // Marketplace taxonomy management (hierarchical categories/subcategories).
    Route::get('/categories', AdminCategoryManager::class)->name('admin.categories');

    // Payout approvals are platform money movement: super admin only. Vendors
    // may request payouts but can never process them.
    Route::get('/vendor-payouts', function () {
        return view('livewire.vendor-payout-review', [
            'payouts' => VendorPayout::query()
                ->with(['tenant:id,name', 'processor:id,name'])
                ->orderByDesc('requested_at')
                ->limit(50)
                ->get(),
        ])->layout('components.layouts.app', ['title' => 'Vendor Payout Approvals — Platform Admin']);
    })->name('admin.vendor-payouts');

    Route::post('/vendor-payouts/{payout}/process', function (string $payout) {
        $user = auth()->user();

        abort_unless($user && $user->isSuperAdmin(), 403, 'Only the platform super admin may process vendor payouts.');

        $record = VendorPayout::query()->whereKey($payout)->firstOrFail();

        $processed = app(ServiceBookingService::class)->processVendorPayout($record, (int) $user->id);

        AuditLog::log(
            'VENDOR_PAYOUT_PROCESSED',
            sprintf(
                'Vendor payout %s (%s PKR) processed for tenant %s by super admin %s.',
                $processed->reference ?? $processed->id,
                number_format((float) $processed->amount, 2),
                $processed->tenant_id,
                $user->email ?? (string) $user->id
            ),
            (string) $processed->tenant_id,
            VendorPayout::class,
            (string) $processed->id
        );

        return back()->with('message', 'Payout marked as paid and wallet debited.');
    })->name('admin.vendor-payouts.process');
});

/*
|--------------------------------------------------------------------------
| 6. Authoritative Printable & Downloadable Documents
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('documents')->name('documents.')->group(function () {
    Route::get('/agreement/{id}', [DocumentController::class, 'bookingAgreement'])->name('agreement');
    Route::get('/receipt/{id}', [DocumentController::class, 'paymentReceipt'])->name('receipt');
    Route::get('/kot/{id}', [DocumentController::class, 'kitchenKot'])->name('kot');
    Route::get('/quotation/{id}', [DocumentController::class, 'bookingQuotation'])->name('quotation');
    Route::get('/invoice/{id}', [DocumentController::class, 'taxInvoice'])->name('invoice');
    Route::get('/salary-slip/{id}', [DocumentController::class, 'salarySlip'])->name('salary-slip');
    Route::get('/vendor-payment-proof/{id}', [DocumentController::class, 'vendorPaymentProof'])->name('vendor-payment-proof');
});

/*
|--------------------------------------------------------------------------
| 7. Meta WhatsApp Cloud API 2-Way Webhook
|--------------------------------------------------------------------------
*/
Route::get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify'])->middleware('throttle:120,1');
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'handle'])->middleware('throttle:120,1');

/*
|--------------------------------------------------------------------------
| 8. REST API for Mobile Apps & Third-Party Integrations
|--------------------------------------------------------------------------
|
| The API now lives in routes/api.php (registered as /api/*) with a real
| bearer-token guard, fail-closed authorization and idempotent webhooks.
| The previous session-only /api/v1 group in this file was removed because
| it applied no tenant scoping when the caller was unauthenticated.
|
*/
