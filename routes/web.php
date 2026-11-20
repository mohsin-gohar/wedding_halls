<?php

use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\WhatsAppWebhookController;
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
use App\Models\Hall;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\VendorCategory;
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
    [$halls, $packages] = Cache::remember('home.featured.v2', 120, function () {
        $featuredHalls = Hall::with(['venue', 'tenant'])
            ->where('is_active', true)
            ->whereHas('tenant', function ($q) {
                $q->where('is_active', true)
                    ->where('verification_status', 'APPROVED');
            })
            ->take(6)
            ->get();

        $featuredPackages = Package::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('tenant_id')
                    ->orWhereHas('tenant', function ($q) {
                        $q->where('is_active', true)
                            ->where('verification_status', 'APPROVED');
                    });
            })
            ->take(4)
            ->get();

        return [$featuredHalls, $featuredPackages];
    });

    return view('welcome', compact('halls', 'packages'));
})->middleware('throttle:public-pages')->name('home');

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

    $halls = Hall::where('is_active', true)
        ->whereNotNull('slug')
        ->whereHas('tenant', function ($q) {
            $q->where('is_active', true)
                ->where('verification_status', 'APPROVED');
        })
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
        ->where('marketplace_visible', true)
        ->where('is_active', true)
        ->where('verification_status', 'APPROVED')
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
Route::middleware(['auth', 'tenant.staff'])->group(function () {
    Route::middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER,ROLE_SERVICE_STAFF')->group(function () {
        Route::get('/vendor/quotes', VendorQuotesBoard::class)->name('vendor.quotes');
    });

    Route::middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER')->group(function () {
        Route::get('/vendor/bookings', VendorBookingsBoard::class)->name('vendor.bookings');
        Route::get('/vendor/services', VendorServicesManager::class)->name('vendor.services');
        Route::get('/vendor/portfolio', VendorPortfolioManager::class)->name('vendor.portfolio');
        Route::get('/vendor/locations', VendorLocationsManager::class)->name('vendor.locations');
        Route::get('/vendor/finance', VendorFinanceBoard::class)->name('vendor.finance');
    });
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
    Route::get('/login', CustomerLogin::class)->name('customer.login')->middleware('throttle:reload-protection');
    Route::get('/register', CustomerRegister::class)->name('customer.register')->middleware('throttle:reload-protection');
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
Route::get('/login', Login::class)->name('login')->middleware('throttle:reload-protection');
Route::get('/register', Register::class)->name('register')->middleware('throttle:reload-protection');

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

        if ($user->status === 'SUSPENDED') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Your account has been suspended by the platform administrator.',
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

Route::match(['get', 'post'], '/logout', function () {
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
