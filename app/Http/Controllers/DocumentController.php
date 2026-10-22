<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\KitchenKot;
use App\Models\Payment;
use App\Models\StaffPayroll;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    /**
     * Authorize access to customer-facing booking documents (Agreements, Invoices, Quotations).
     */
    protected function authorizeBookingAccess(Booking $booking): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403, 'Unauthorized. Please sign in to view confidential documents.');
        }

        // 1. Super Admin has global oversight
        if ($user->isSuperAdmin()) {
            return;
        }

        // 2. Tenant owner and authorized staff of this specific tenant
        if ($user->tenant_id && $user->tenant_id === $booking->tenant_id) {
            return;
        }

        // 3. Customer who owns the booking
        if ($booking->customer_id && $booking->customer_id === $user->id) {
            return;
        }
        if ($booking->customer_email && strtolower($booking->customer_email) === strtolower($user->email)) {
            return;
        }

        abort(403, 'Forbidden. You do not have permission to access records belonging to another tenant or customer.');
    }

    /**
     * Authorize access to official payment receipts.
     */
    protected function authorizePaymentAccess(Payment $payment): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403, 'Unauthorized. Please sign in to view payment receipts.');
        }

        // 1. Super Admin has platform oversight
        if ($user->isSuperAdmin()) {
            return;
        }

        // 2. Tenant owner and staff of this specific tenant
        if ($user->tenant_id && $user->tenant_id === $payment->tenant_id) {
            return;
        }

        // 3. Customer who owns the associated booking
        if ($payment->booking) {
            if ($payment->booking->customer_id && $payment->booking->customer_id === $user->id) {
                return;
            }
            if ($payment->booking->customer_email && strtolower($payment->booking->customer_email) === strtolower($user->email)) {
                return;
            }
        }

        abort(403, 'Forbidden. You do not have permission to access financial receipts belonging to another business.');
    }

    /**
     * Authorize access to internal kitchen production tickets (KOT).
     * Requirement: Customers must NEVER access internal kitchen tickets even if they own the booking.
     */
    protected function authorizeInternalKotAccess(KitchenKot $kot): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403, 'Unauthorized. Please sign in to view operational records.');
        }

        // 1. Super Admin oversight
        if ($user->isSuperAdmin()) {
            return;
        }

        // 2. Operational staff of this specific tenant (Chefs, Managers, Owners, Operations)
        if ($user->tenant_id && $user->tenant_id === $kot->tenant_id) {
            if ($user->isTenantOwner() || $user->isManager() || $user->isChef() || $user->role === 'ROLE_FLOOR_STAFF' || $user->role === 'ROLE_ACCOUNTANT') {
                return;
            }
        }

        // Customers are strictly forbidden from viewing internal kitchen production tickets
        abort(403, 'Forbidden. Internal Kitchen Order Tickets (KOT) are restricted to authorized culinary and venue operational staff.');
    }

    /**
     * Generate A4 Legal Booking Agreement.
     */
    public function bookingAgreement(string $id, Request $request)
    {
        $booking = Booking::with(['hall.venue', 'tenant', 'package', 'payments'])->findOrFail($id);
        $this->authorizeBookingAccess($booking);

        if ($request->boolean('pdf')) {
            $pdf = Pdf::loadView('documents.agreement', compact('booking'))
                ->setPaper('a4', 'portrait');

            return $pdf->download("VenueCloud_Contract_{$booking->id}.pdf");
        }

        return view('documents.agreement', compact('booking'));
    }

    /**
     * Generate 80mm Thermal Cash Receipt.
     */
    public function paymentReceipt(string $id, Request $request)
    {
        $payment = Payment::with(['booking.hall.venue', 'tenant', 'receivedByUser'])->findOrFail($id);
        $this->authorizePaymentAccess($payment);

        if ($request->boolean('pdf')) {
            // 80mm width in points is approx 226pt (80mm / 25.4 * 72)
            $pdf = Pdf::loadView('documents.thermal-receipt', compact('payment'))
                ->setPaper([0, 0, 226, 600], 'portrait');

            return $pdf->download("Receipt_{$payment->receipt_no}.pdf");
        }

        return view('documents.thermal-receipt', compact('payment'));
    }

    /**
     * Generate Chef Kitchen Order Ticket (KOT).
     * Strictly restricted to operational staff / managers / super admin.
     */
    public function kitchenKot(string $id, Request $request)
    {
        $kot = KitchenKot::with(['booking.hall.venue', 'tenant'])->findOrFail($id);
        $this->authorizeInternalKotAccess($kot);

        if ($request->boolean('pdf')) {
            $pdf = Pdf::loadView('documents.chef-kot', compact('kot'))
                ->setPaper('a4', 'portrait');

            return $pdf->download("KOT_{$kot->kot_number}.pdf");
        }

        return view('documents.chef-kot', compact('kot'));
    }

    /**
     * Generate Formal Customer Booking Quotation.
     */
    public function bookingQuotation(string $id, Request $request)
    {
        $booking = Booking::with(['hall.venue', 'tenant', 'package'])->findOrFail($id);
        $this->authorizeBookingAccess($booking);

        if ($request->boolean('pdf')) {
            $pdf = Pdf::loadView('documents.quotation', compact('booking'))
                ->setPaper('a4', 'portrait');

            return $pdf->download("Quotation_{$booking->id}.pdf");
        }

        return view('documents.quotation', compact('booking'));
    }

    /**
     * Generate Official Banquet Tax Invoice & Installment Schedule.
     */
    public function taxInvoice(string $id, Request $request)
    {
        $booking = Booking::with(['hall.venue', 'tenant', 'package', 'payments'])->findOrFail($id);
        $this->authorizeBookingAccess($booking);

        if ($request->boolean('pdf')) {
            $pdf = Pdf::loadView('documents.invoice', compact('booking'))
                ->setPaper('a4', 'portrait');

            return $pdf->download("Tax_Invoice_{$booking->id}.pdf");
        }

        return view('documents.invoice', compact('booking'));
    }

    /**
     * Generate Official Staff Salary Disbursement Slip.
     */
    public function salarySlip(string $id, Request $request)
    {
        $payroll = StaffPayroll::with(['user', 'tenant'])->findOrFail($id);

        $user = auth()->user();
        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        // Super Admin or Tenant Admin/Accountant/Manager or the employee themselves
        $isTenantStaff = ($user->tenant_id === $payroll->tenant_id && ($user->isTenantOwner() || $user->isManager() || $user->isAccountant() || $user->id === $payroll->user_id));
        if (! $user->isSuperAdmin() && ! $isTenantStaff) {
            abort(403, 'Forbidden. You do not have permission to view this confidential salary slip.');
        }

        if ($request->boolean('pdf')) {
            $pdf = Pdf::loadView('documents.salary-slip', compact('payroll'))
                ->setPaper('a4', 'portrait');

            return $pdf->download("Salary_Slip_{$payroll->id}.pdf");
        }

        return view('documents.salary-slip', compact('payroll'));
    }
}
