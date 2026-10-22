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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 1. Public Marketplace & Guest Experience Routes
|--------------------------------------------------------------------------
*/
// Cinematic Homepage
Route::get('/', function () {
    $halls = Hall::with(['venue', 'tenant'])
        ->where('is_active', true)
        ->whereHas('tenant', function ($q) {
            $q->where('is_active', true)
                ->where('verification_status', 'APPROVED');
        })
        ->take(6)
        ->get();

    $packages = Package::where('is_active', true)
        ->where(function ($query) {
            $query->whereNull('tenant_id')
                ->orWhereHas('tenant', function ($q) {
                    $q->where('is_active', true)
                        ->where('verification_status', 'APPROVED');
                });
        })
        ->take(4)
        ->get();

    return view('welcome', compact('halls', 'packages'));
})->name('home');

// Vendor Hall Media (defined before /hall/{id} wildcard)
Route::middleware(['auth', 'tenant.staff', 'portal.role:ROLE_TENANT_OWNER,ROLE_MANAGER'])->get('/hall/media', HallMediaManager::class)->name('hall.media');

// Venue Exploration & Showcase
Route::get('/venues', PublicVenues::class)->name('public.venues');
Route::get('/marketplace', PublicMarketplace::class)->name('marketplace');
Route::get('/venues/{slug}', PublicHallDetails::class)->name('public.venue.details');
Route::get('/hall/{slug}', PublicHallDetails::class)->name('hall.showcase');

// Visual Gallery, Packages & Menus
Route::get('/gallery', PublicGallery::class)->name('public.gallery');
Route::get('/packages', PublicMenus::class)->name('public.packages');
Route::get('/menus', PublicMenus::class)->name('public.menus');
Route::get('/services', PublicServices::class)->name('public.services');

// Company & Customer Assistance
Route::get('/about', PublicAbout::class)->name('public.about');
Route::get('/contact', PublicContact::class)->name('public.contact')->middleware('throttle:30,1');
Route::get('/faqs', PublicFaqs::class)->name('public.faqs');
Route::get('/check-availability', PublicAvailabilityChecker::class)->name('public.availability');
Route::get('/rsvp', PublicGuestRsvp::class)->name('public.rsvp')->middleware('throttle:30,1');

// Legal Policies
Route::get('/policies/{policy?}', PublicPolicies::class)->name('public.policies');
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
Route::middleware('guest')->prefix('customer')->group(function () {
    Route::get('/login', CustomerLogin::class)->name('customer.login')->middleware('throttle:60,1');
    Route::get('/register', CustomerRegister::class)->name('customer.register')->middleware('throttle:60,1');
});

Route::middleware('auth')->prefix('customer')->group(function () {
    Route::get('/dashboard', CustomerDashboard::class)->name('customer.dashboard');
    Route::get('/bookings', CustomerDashboard::class)->name('customer.bookings');
});

/*
|--------------------------------------------------------------------------
| 3. Vendor Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login')->middleware('throttle:60,1');
    Route::get('/register', Register::class)->name('register')->middleware('throttle:60,1');
});

Route::post('/logout', function () {
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
Route::middleware('auth')->prefix('admin')->group(function () {
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
