<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Livewire\AdminDashboard;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\CalendarShiftGrid;
use App\Livewire\CustomerDashboard;
use App\Livewire\CustomerLogin;
use App\Livewire\CustomerRegister;
use App\Livewire\Dashboard;
use App\Livewire\ExpenseTracker;
use App\Livewire\FloorPlanDesigner;
use App\Livewire\HallMediaManager;
use App\Livewire\InventoryTracker;
use App\Livewire\KitchenKotBoard;
use App\Livewire\PackageManager;
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
use App\Livewire\PublicVenues;
use App\Livewire\StaffAttendance;
use App\Livewire\StaffManager;
use App\Livewire\StaffPayroll;
use App\Livewire\UtilityFuelTracker;
use App\Livewire\WhatsAppRsvpManager;
use App\Models\Hall;
use App\Models\Package;
use App\Models\Tenant;
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
// Cinematic Homepage (Cached for 5 minutes with automatic state invalidation)
Route::get('/', function () {
    $cacheStamp = md5(implode('|', [
        (string) Hall::max('updated_at'),
        (string) Hall::count(),
        (string) Tenant::max('updated_at'),
        (string) Package::max('updated_at'),
        (string) Package::count(),
    ]));

    [$halls, $packages] = Cache::remember("home.featured.{$cacheStamp}", 300, function () {
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

    $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

    foreach ($staticUrls as $loc) {
        $xml .= '  <url>'."\n";
        $xml .= '    <loc>'.htmlspecialchars($loc, ENT_XML1, 'UTF-8').'</loc>'."\n";
        $xml .= '    <changefreq>daily</changefreq>'."\n";
        $xml .= '    <priority>0.8</priority>'."\n";
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

// Visual Gallery, Packages & Menus
Route::get('/gallery', PublicGallery::class)->middleware('throttle:public-pages')->name('public.gallery');
Route::get('/packages', PublicMenus::class)->middleware('throttle:public-pages')->name('public.packages');
Route::get('/menus', PublicMenus::class)->middleware('throttle:public-pages')->name('public.menus');
Route::get('/services', PublicServices::class)->middleware('throttle:public-pages')->name('public.services');

// Company & Customer Assistance
Route::get('/about', PublicAbout::class)->middleware('throttle:public-pages')->name('public.about');
Route::get('/contact', PublicContact::class)->name('public.contact')->middleware('throttle:30,1');
Route::get('/faqs', PublicFaqs::class)->middleware('throttle:public-pages')->name('public.faqs');
Route::get('/check-availability', PublicAvailabilityChecker::class)->name('public.availability')->middleware('throttle:public-availability');
Route::redirect('/availability', '/check-availability', 301);
Route::get('/rsvp', PublicGuestRsvp::class)->name('public.rsvp')->middleware('throttle:30,1');

// Legal Policies
Route::get('/policies/{policy?}', PublicPolicies::class)->middleware('throttle:public-pages')->name('public.policies');
Route::get('/policies/terms', [PublicPolicies::class, '__invoke'])->name('public.policies.terms');
Route::get('/policies/privacy', [PublicPolicies::class, '__invoke'])->name('public.policies.privacy');
Route::get('/policies/cancellation', [PublicPolicies::class, '__invoke'])->name('public.policies.cancellation');
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
    Route::get('/login', CustomerLogin::class)->name('customer.login')->middleware('throttle:60,1');
    Route::get('/register', CustomerRegister::class)->name('customer.register')->middleware('throttle:60,1');
});

Route::middleware('auth')->prefix('customer')->group(function () {
    Route::get('/dashboard', CustomerDashboard::class)->name('customer.dashboard');
    Route::get('/bookings', CustomerDashboard::class)->name('customer.bookings');
});

/*
|--------------------------------------------------------------------------
| 3. Unified & Vendor Authentication Routes (Livewire + Native HTTP POST)
|--------------------------------------------------------------------------
*/
Route::get('/login', Login::class)->name('login')->middleware('throttle:60,1');
Route::get('/register', Register::class)->name('register')->middleware('throttle:60,1');

$authenticatePortalRequest = function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    $throttleKey = Str::lower($credentials['email']).'|'.$request->ip();

    if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
        $seconds = RateLimiter::availableIn($throttleKey);

        return back()->withErrors([
            'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
        ])->withInput($request->only('email'));
    }

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();
        $request->session()->forget('url.intended');

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

        if ($user->role === 'ROLE_SUPER_ADMIN' || $user->isSuperAdmin()) {
            return redirect()->route('admin.dashboard')->with('message', "Welcome back, {$user->name}!");
        }

        if ($user->isCustomer()) {
            return redirect()->route('customer.dashboard')->with('message', "Welcome back, {$user->name}!");
        }

        if ($user->role === 'ROLE_CHEF') {
            return redirect()->route('kitchen.board')->with('message', "Welcome back, {$user->name}!");
        }

        if (in_array($user->role, ['ROLE_FRONT_DESK', 'ROLE_FLOOR_STAFF'], true)) {
            return redirect()->route('calendar')->with('message', "Welcome back, {$user->name}!");
        }

        return redirect()->route('dashboard')->with('message', "Welcome back, {$user->name}!");
    }

    RateLimiter::hit($throttleKey, 60);

    return back()->withErrors([
        'email' => 'These credentials do not match our registered records.',
    ])->withInput($request->only('email'));
};

Route::post('/login', $authenticatePortalRequest)->name('login.perform')->middleware('throttle:60,1');
Route::post('/customer/login', $authenticatePortalRequest)->name('customer.login.perform')->middleware('throttle:60,1');

Route::match(['get', 'post'], '/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login')->with('message', 'You have been signed out successfully.');
})->name('logout');

/*
|--------------------------------------------------------------------------
| 4. Vendor ERP Management Suite (Protected by Auth & Tenant Staff Guard)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'tenant.staff'])->group(function () {
    Route::middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER,ROLE_ACCOUNTANT')->group(function () {
        Route::get('/dashboard', Dashboard::class)->name('dashboard');
        Route::get('/vendor/dashboard', Dashboard::class)->name('vendor.dashboard');
    });

    Route::middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER,ROLE_FRONT_DESK,ROLE_FLOOR_STAFF')->get('/calendar', CalendarShiftGrid::class)->name('calendar');
    Route::middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER,ROLE_CHEF,ROLE_FLOOR_STAFF')->get('/kitchen-kots', KitchenKotBoard::class)->name('kitchen.board');
    Route::middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER')->get('/utility-fuel', UtilityFuelTracker::class)->name('utility.fuel');
    Route::middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER,ROLE_FLOOR_STAFF')->get('/seating-planner', FloorPlanDesigner::class)->name('seating.planner');
    Route::middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER,ROLE_FRONT_DESK')->get('/whatsapp-rsvps', WhatsAppRsvpManager::class)->name('whatsapp.rsvp');
    Route::middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER')->get('/staff', StaffManager::class)->name('staff');
    Route::middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER,ROLE_ACCOUNTANT')->get('/staff/attendance', StaffAttendance::class)->name('staff.attendance');
    Route::middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER,ROLE_ACCOUNTANT')->get('/staff/payroll', StaffPayroll::class)->name('staff.payroll');
    Route::middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER,ROLE_CHEF')->get('/inventory', InventoryTracker::class)->name('inventory');
    Route::middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER,ROLE_CHEF')->get('/catering-packages', PackageManager::class)->name('packages.manager');
    Route::middleware('portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER,ROLE_ACCOUNTANT')->get('/expenses', ExpenseTracker::class)->name('expenses');
});

/*
|--------------------------------------------------------------------------
| 5. Platform Super Administration Console (Protected by Auth)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'super.admin'])->prefix('admin')->group(function () {
    Route::get('/', AdminDashboard::class)->name('admin.dashboard');
    Route::get('/dashboard', AdminDashboard::class);
});

/*
|--------------------------------------------------------------------------
| 6. Authoritative Printable & Downloadable Documents
|--------------------------------------------------------------------------
*/
Route::prefix('documents')->name('documents.')->group(function () {
    Route::get('/agreement/{id}', [DocumentController::class, 'bookingAgreement'])->name('agreement');
    Route::get('/receipt/{id}', [DocumentController::class, 'paymentReceipt'])->name('receipt');
    Route::get('/kot/{id}', [DocumentController::class, 'kitchenKot'])->name('kot');
    Route::get('/quotation/{id}', [DocumentController::class, 'bookingQuotation'])->name('quotation');
    Route::get('/invoice/{id}', [DocumentController::class, 'taxInvoice'])->name('invoice');
    Route::get('/salary-slip/{id}', [DocumentController::class, 'salarySlip'])->name('salary-slip');
});

/*
|--------------------------------------------------------------------------
| 7. Meta WhatsApp Cloud API 2-Way Webhook
|--------------------------------------------------------------------------
*/
Route::get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify'])->middleware('throttle:120,1');
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'handle'])->middleware('throttle:120,1');
