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
|   * Tenant staff are scoped to their own tenant; customers are scoped to
|     their own records. There is no path that returns another tenant's data.
|   * Payments are manual cash records. There are no gateway initiation or
|     webhook callback endpoints.
|
*/

Route::prefix('v1')->group(function () {
    // ── Authenticated surface ─────────────────────────────────────────
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

        // Availability — declared BEFORE /bookings/{id} so it is not shadowed.
        Route::get('/bookings/availability', [BookingController::class, 'availability']);

        // Hall bookings (legacy concurrency-safe engine, preserved)
        Route::get('/bookings', [BookingController::class, 'index']);
        Route::post('/bookings', [BookingController::class, 'store']);
        Route::get('/bookings/{id}', [BookingController::class, 'show'])->whereUuid('id');
        Route::patch('/bookings/{id}', [BookingController::class, 'update'])->whereUuid('id');
        Route::delete('/bookings/{id}', [BookingController::class, 'destroy'])->whereUuid('id');

        // Service bookings (photography, catering, decoration, beauty, …)
        Route::get('/service-bookings', [ServiceBookingController::class, 'index']);
        Route::get('/service-bookings/{id}', [ServiceBookingController::class, 'show'])->whereUuid('id');
        Route::post('/service-bookings/{id}/payments', [ServiceBookingController::class, 'recordPayment'])->whereUuid('id');

        // Payments — manual cash only, staff-authenticated mutations
        Route::get('/payments', [PaymentController::class, 'list']);
        Route::post('/payments/record', [PaymentController::class, 'record']);
        Route::post('/payments/refund', [PaymentController::class, 'refund']);
        Route::get('/payments/summary', [PaymentController::class, 'summary']);
    });
});
