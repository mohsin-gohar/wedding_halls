<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\KitchenKot;
use App\Models\Payment;
use App\Models\ServicePayment;
use App\Models\StaffPayroll;
use App\Support\PermissionMatrix;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Authorize access to customer-facing booking documents (Agreements, Invoices, Quotations).
     */
    protected function authorizeBookingAccess(Booking $booking, string $permission): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403, 'Unauthorized. Please sign in to view confidential documents.');
        }

        // Platform finance may read only the financial document types granted
        // by the central matrix; vendor staff need the same permission plus
        // an exact tenant match.
        if ($user->isSuperAdmin()
            || ($user->isPlatformFinance() && PermissionMatrix::userCan($user, $permission))) {
            return;
        }

        if ($user->tenant_id
            && (string) $user->tenant_id === (string) $booking->tenant_id
            && PermissionMatrix::userCan($user, $permission)) {
            return;
        }

        // 3. Verified customer account linked to the booking (never match email alone — IDOR risk)
        if ($user->isCustomer() && $booking->customer_id && (string) $booking->customer_id === (string) $user->id) {
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

        if ($user->isSuperAdmin()
            || ($user->isPlatformFinance() && PermissionMatrix::userCan($user, 'payments.view'))) {
            return;
        }

        if ($user->tenant_id
            && (string) $user->tenant_id === (string) $payment->tenant_id
            && PermissionMatrix::userCan($user, 'payments.view')) {
            return;
        }

        // 3. Verified customer linked to the associated booking
        if ($payment->booking && $user->isCustomer()
            && $payment->booking->customer_id
            && (string) $payment->booking->customer_id === (string) $user->id) {
            return;
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

        if ($user->isSuperAdmin()) {
            return;
        }

        if ($user->tenant_id
            && (string) $user->tenant_id === (string) $kot->tenant_id
            && PermissionMatrix::userCan($user, 'kitchen.view')) {
            return;
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
        $this->authorizeBookingAccess($booking, 'contracts.view');

        if ($request->boolean('pdf')) {
            $pdf = Pdf::loadView('documents.agreement', compact('booking'))
                ->setPaper('a4', 'portrait');

            return $pdf->download("RiwajGrand_Contract_{$booking->id}.pdf");
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
        $this->authorizeBookingAccess($booking, 'quotations.view');

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
        $this->authorizeBookingAccess($booking, 'invoices.view');

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

        // Super Admin, an explicitly payroll-authorized tenant user, or the
        // employee viewing their own slip within the same tenant.
        $sameTenant = $user->tenant_id
            && (string) $user->tenant_id === (string) $payroll->tenant_id;
        $isEmployee = $sameTenant && (string) $user->id === (string) $payroll->user_id;
        $isPayrollStaff = $sameTenant && PermissionMatrix::userCan($user, 'payroll.view');

        if (! $user->isSuperAdmin() && ! $isEmployee && ! $isPayrollStaff) {
            abort(403, 'Forbidden. You do not have permission to view this confidential salary slip.');
        }

        if ($request->boolean('pdf')) {
            $pdf = Pdf::loadView('documents.salary-slip', compact('payroll'))
                ->setPaper('a4', 'portrait');

            return $pdf->download("Salary_Slip_{$payroll->id}.pdf");
        }

        return view('documents.salary-slip', compact('payroll'));
    }

    /**
     * Serve a customer-uploaded manual payment proof (bank transfer receipt or
     * cash deposit slip) from private storage. Legacy historical uploads with
     * old payment labels remain readable.
     *
     * The file is NEVER served directly by the web server — it lives outside
     * the public directory and every download is authorised. The stored path
     * is validated against traversal before it is read.
     */
    public function vendorPaymentProof(string $id)
    {
        $payment = ServicePayment::with(['booking.tenant', 'booking.customer'])->findOrFail($id);

        $user = auth()->user();

        if (! $user) {
            abort(403, 'Unauthorized. Please sign in to view payment proofs.');
        }

        if (! $user->isSuperAdmin()) {
            // Least privilege: only financial roles of the owning vendor and
            // the paying customer may open a confidential cash proof. General
            // operational staff (chef, floor, front desk, inventory) do not.
            $isPlatformFinance = $user->isPlatformFinance();

            $isVendorFinanceStaff = $user->tenant_id
                && (string) $user->tenant_id === (string) $payment->tenant_id
                && PermissionMatrix::userCan($user, 'payments.view');

            // The customer who made the payment may always see their own proof.
            $isPayingCustomer = $user->isCustomer()
                && ((string) $payment->customer_id === (string) $user->id
                    || ($payment->booking && (string) $payment->booking->customer_id === (string) $user->id));

            if (! $isPlatformFinance && ! $isVendorFinanceStaff && ! $isPayingCustomer) {
                abort(403, 'Forbidden. You do not have permission to view this payment proof.');
            }
        }

        if (! $payment->proof_path) {
            abort(404, 'No proof document has been attached to this payment.');
        }

        // Reject traversal attempts outright — the stored path must resolve
        // inside the private proofs directory.
        $relative = ltrim(str_replace('\\', '/', $payment->proof_path), '/');

        if (str_contains($relative, '..') || str_starts_with($relative, 'php://') || preg_match('/^[a-z]+:\/\//i', $relative)) {
            abort(400, 'Invalid proof path.');
        }

        // Confidential proofs are only ever read from the private proofs disk;
        // they must never be web-accessible even when media uses a public disk.
        $disk = Storage::disk(config('filesystems.proofs_disk', 'local'));

        if (! $disk->exists($relative)) {
            abort(404, 'The proof file could not be found.');
        }

        return $disk->download($relative, basename($relative));
    }
}
