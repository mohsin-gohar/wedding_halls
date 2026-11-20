<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ServiceBookingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Riwaj Grand API (v1) — Mobile & Partner Integrations
|--------------------------------------------------------------------------
|
| Stateless, bearer-token authenticated surface for the mobile application
| and approved partner integrations. A browser session is also accepted so
| the ERP and partner dashboards can reuse the same endpoints.
|
| Authorization rules:
|   * Every endpoint requires an authenticated principal (401 otherwise).
|   * Bearer tokens are additionally limited to the abilities granted when
|     issued; older unrestricted tokens keep working.
|   * Tenant staff are scoped to their own tenant; customers are scoped to
|     their own records. There is no path that returns another tenant's data.
|   * Payments are manual cash records. There are no gateway initiation or
|     webhook callback endpoints.
|
*/

Route::prefix('v1')->group(function () {
    // ── Identity ────────────────────────────────────────────────────
    Route::middleware('auth.api')->group(function () {
        Route::get('/me', function (Request $request) {
            $user = $request->user();

            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'tenant_id' => $user->tenant_id,
                'is_super_admin' => $user->isSuperAdmin(),
                'is_customer' => $user->isCustomer(),
                'vendor_category' => $user->tenant?->vendorCategory?->slug,
            ]);
        });
    });

    // Availability — declared BEFORE /bookings/{id} so it is not shadowed.
    Route::middleware('auth.api:bookings.read')->group(function () {
        Route::get('/bookings/availability', [BookingController::class, 'availability']);
        Route::get('/bookings', [BookingController::class, 'index']);
        Route::get('/bookings/{id}', [BookingController::class, 'show'])->whereUuid('id');
    });

    // Hall bookings (legacy concurrency-safe engine, preserved)
    Route::middleware('auth.api:bookings.create')->group(function () {
        Route::post('/bookings', [BookingController::class, 'store']);
    });
    Route::middleware('auth.api:bookings.update')->group(function () {
        Route::patch('/bookings/{id}', [BookingController::class, 'update'])->whereUuid('id');
    });
    Route::middleware('auth.api:bookings.delete')->group(function () {
        Route::delete('/bookings/{id}', [BookingController::class, 'destroy'])->whereUuid('id');
    });

    // Service bookings (photography, catering, decoration, beauty, …)
    Route::middleware('auth.api:service-bookings.read')->group(function () {
        Route::get('/service-bookings', [ServiceBookingController::class, 'index']);
        Route::get('/service-bookings/{id}', [ServiceBookingController::class, 'show'])->whereUuid('id');
    });
    Route::middleware('auth.api:payments.record')->group(function () {
        Route::post('/service-bookings/{id}/payments', [ServiceBookingController::class, 'recordPayment'])->whereUuid('id');
    });

    // Payments — manual cash only, staff-authenticated mutations
    Route::middleware('auth.api:payments.read')->group(function () {
        Route::get('/payments', [PaymentController::class, 'list']);
        Route::get('/payments/summary', [PaymentController::class, 'summary']);
    });
    Route::middleware('auth.api:payments.record')->group(function () {
        Route::post('/payments/record', [PaymentController::class, 'record']);
    });
    Route::middleware('auth.api:payments.refund')->group(function () {
        Route::post('/payments/refund', [PaymentController::class, 'refund']);
    });
});
