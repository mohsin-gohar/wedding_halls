<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Expense;
use App\Models\Hall;
use App\Models\PurchaseOrder;
use App\Support\CashOnlyPayments;
use App\Support\PermissionMatrix;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ExpenseTracker extends Component
{
    public string $period = 'THIS_MONTH'; // THIS_MONTH, LAST_MONTH, ALL_TIME

    public string $filterCategory = 'ALL';

    public bool $isCreating = false;

    // Form inputs
    public ?string $hall_id = null;

    public string $category = 'GENERATOR_DIESEL';

    public string $title = '';

    public string $amount = '';

    public string $expense_date = '';

    public string $payee = '';

    public string $payment_method = 'CASH';

    public string $receipt_number = '';

    public string $notes = '';

    // Edit Form State
    public bool $isEditing = false;

    public ?string $editingExpenseId = null;

    public ?string $edit_hall_id = null;

    public string $edit_category = 'GENERATOR_DIESEL';

    public string $edit_title = '';

    public string $edit_amount = '';

    public string $edit_expense_date = '';

    public string $edit_payee = '';

    public string $edit_payment_method = 'CASH';

    public string $edit_receipt_number = '';

    public string $edit_notes = '';

    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public bool $showVoided = false;

    public ?string $voidingExpenseId = null;

    public string $voidReason = '';

    protected $rules = [
        'category' => 'required|in:GENERATOR_DIESEL,WAPDA_ELECTRICITY,RAW_FOOD_MEAT,FRESH_FLORAL_DECOR,STAFF_WAGES,STAFF_DAILY_WAGES,MAINTENANCE_REPAIRS,CATERING_SUPPLIES,MARKETING_ADS,OTHER,CHILLER_AC,CLEANING_HYGIENE,KITCHEN_GAS,MISC_OPERATIONAL',
        'title' => 'required|string|max:150',
        'amount' => 'required|numeric|min:1|max:9999999999.99',
        'expense_date' => 'required|date',
        'payee' => 'nullable|string|max:150',
        'payment_method' => 'required|in:CASH',
        'receipt_number' => 'nullable|string|max:80',
        'notes' => 'nullable|string|max:2000',
    ];

    public function mount()
    {
        $this->authorizeExpenseViewer();
        $this->expense_date = Carbon::now()->format('Y-m-d');
    }

    private function authorizeExpenseViewer(): void
    {
        $user = Auth::user();

        abort_unless(
            $user && $user->tenant_id && PermissionMatrix::userCan($user, 'expenses.view'),
            403,
            'Unauthorized. Expense visibility requires expenses.view within a tenant.'
        );
    }

    private function authorizeExpenseManager(): void
    {
        $user = Auth::user();

        abort_unless(
            $user && $user->tenant_id && PermissionMatrix::userCan($user, 'expenses.manage'),
            403,
            'Unauthorized. Expense changes require expenses.manage within a tenant.'
        );
    }

    public function openCreateModal()
    {
        $this->authorizeExpenseManager();
        $this->reset(['title', 'amount', 'payee', 'receipt_number', 'notes', 'errorMessage', 'feedbackMessage']);
        $this->expense_date = Carbon::now()->format('Y-m-d');
        $this->category = 'GENERATOR_DIESEL';
        $this->payment_method = 'CASH';
        $this->isCreating = true;
    }

    public function closeCreateModal()
    {
        $this->isCreating = false;
    }

    public function createExpense()
    {
        $this->authorizeExpenseManager();
        $tenantId = Auth::user()->tenant_id;
        $this->validate(array_merge($this->rules, [
            'hall_id' => ['nullable', Rule::exists('halls', 'id')->where('tenant_id', $tenantId)],
        ]));

        $expense = Expense::create([
            'tenant_id' => Auth::user()->tenant_id,
            'hall_id' => ! empty($this->hall_id) ? $this->hall_id : null,
            'category' => $this->category,
            'title' => $this->title,
            'amount' => round((float) $this->amount, 2),
            'expense_date' => $this->expense_date,
            'payee' => $this->payee,
            'payment_method' => $this->payment_method,
            'receipt_number' => $this->receipt_number,
            'notes' => $this->notes,
            'created_by_user_id' => Auth::id(),
        ]);

        AuditLog::log(
            'EXPENSE_RECORDED',
            "Recorded [{$this->category}] expense of PKR ".number_format($expense->amount)." for [{$expense->title}].",
            Auth::user()->tenant_id,
            'Expense',
            $expense->id
        );

        $this->feedbackMessage = 'Expense of PKR '.number_format($expense->amount).' logged successfully!';
        $this->isCreating = false;
        $this->reset(['title', 'amount', 'payee', 'receipt_number', 'notes']);
    }

    public function openEditModal(string $expenseId)
    {
        $this->authorizeExpenseManager();
        $expense = Expense::where('tenant_id', Auth::user()->tenant_id)->findOrFail($expenseId);
        $this->assertExpenseCanBeManuallyAdjusted($expense);
        $this->editingExpenseId = $expense->id;
        $this->edit_hall_id = $expense->hall_id;
        $this->edit_category = $expense->category;
        $this->edit_title = $expense->title;
        $this->edit_amount = (string) $expense->amount;
        $this->edit_expense_date = $expense->expense_date->format('Y-m-d');
        $this->edit_payee = $expense->payee ?? '';
        $this->edit_payment_method = $expense->payment_method ?? 'CASH';
        $this->edit_receipt_number = $expense->receipt_number ?? '';
        $this->edit_notes = $expense->notes ?? '';
        $this->errorMessage = null;
        $this->isEditing = true;
    }

    public function closeEditModal()
    {
        $this->isEditing = false;
        $this->editingExpenseId = null;
        $this->reset(['edit_title', 'edit_amount', 'edit_payee', 'edit_receipt_number', 'edit_notes', 'errorMessage']);
    }

    public function updateExpense()
    {
        $this->authorizeExpenseManager();
        if (! $this->editingExpenseId) {
            return;
        }

        $tenantId = Auth::user()->tenant_id;
        $expense = Expense::where('tenant_id', $tenantId)->findOrFail($this->editingExpenseId);
        $this->assertExpenseCanBeManuallyAdjusted($expense);
        $before = [
            'category' => $expense->category,
            'title' => $expense->title,
            'amount' => (string) $expense->amount,
            'expense_date' => $expense->expense_date?->format('Y-m-d'),
        ];
        $originalMethod = (string) ($expense->payment_method ?? '');
        $allowedMethods = $originalMethod === CashOnlyPayments::CASH
            ? CashOnlyPayments::METHODS
            : [$originalMethod, CashOnlyPayments::CASH];

        $this->validate([
            'edit_hall_id' => ['nullable', Rule::exists('halls', 'id')->where('tenant_id', $tenantId)],
            'edit_category' => ['required', Rule::in([
            'GENERATOR_DIESEL', 'WAPDA_ELECTRICITY', 'RAW_FOOD_MEAT',
            'FRESH_FLORAL_DECOR', 'STAFF_WAGES', 'STAFF_DAILY_WAGES',
            'MAINTENANCE_REPAIRS', 'CATERING_SUPPLIES', 'MARKETING_ADS',
            'OTHER', 'CHILLER_AC', 'CLEANING_HYGIENE', 'KITCHEN_GAS', 'MISC_OPERATIONAL',
        ])],
            'edit_title' => 'required|string|max:150',
            'edit_amount' => 'required|numeric|min:1|max:9999999999.99',
            'edit_expense_date' => 'required|date',
            'edit_payee' => 'nullable|string|max:150',
            'edit_payment_method' => ['required', Rule::in($allowedMethods)],
            'edit_receipt_number' => 'nullable|string|max:80',
            'edit_notes' => 'nullable|string|max:2000',
        ]);

        $update = [
            'hall_id' => ! empty($this->edit_hall_id) ? $this->edit_hall_id : null,
            'category' => $this->edit_category,
            'title' => $this->edit_title,
            'amount' => round((float) $this->edit_amount, 2),
            'expense_date' => $this->edit_expense_date,
            'payee' => $this->edit_payee,
            'receipt_number' => $this->edit_receipt_number,
            'notes' => $this->edit_notes,
        ];

        // Preserve historical labels on unrelated edits; only an explicit
        // conversion to cash may rewrite the method field.
        if ($this->edit_payment_method === CashOnlyPayments::CASH && $originalMethod) {
            $update['payment_method'] = CashOnlyPayments::CASH;
        }

        $expense->update($update);

        AuditLog::log(
            'EXPENSE_UPDATED',
            'Updated expense. Before: '.json_encode($before, JSON_UNESCAPED_SLASHES).' After: '.json_encode([
                'category' => $expense->category,
                'title' => $expense->title,
                'amount' => (string) $expense->amount,
                'expense_date' => $expense->expense_date?->format('Y-m-d'),
            ], JSON_UNESCAPED_SLASHES),
            $tenantId,
            Expense::class,
            (string) $expense->id
        );

        $this->feedbackMessage = "Expense [{$expense->title}] updated successfully!";
        $this->closeEditModal();
    }

    private function assertExpenseCanBeManuallyAdjusted(Expense $expense): void
    {
        if ($expense->payroll_id) {
            abort(403, 'Payroll-linked expenses must be corrected through the payroll workflow.');
        }

        if ($expense->receipt_number && PurchaseOrder::where('tenant_id', $expense->tenant_id)
            ->where('po_number', $expense->receipt_number)
            ->where('status', 'PAID')
            ->exists()) {
            abort(403, 'Purchase-order expenses must be corrected through the procurement workflow.');
        }
    }

    public function openVoidModal(string $expenseId): void
    {
        $this->authorizeExpenseManager();
        $expense = Expense::where('tenant_id', Auth::user()->tenant_id)->findOrFail($expenseId);
        $this->assertExpenseCanBeVoided($expense);
        $this->voidingExpenseId = $expenseId;
        $this->voidReason = '';
        $this->errorMessage = null;
    }

    public function cancelVoid(): void
    {
        $this->voidingExpenseId = null;
        $this->voidReason = '';
    }

    public function voidExpense(): void
    {
        $this->authorizeExpenseManager();
        $this->validate([
            'voidingExpenseId' => ['required', Rule::exists('expenses', 'id')->where('tenant_id', Auth::user()->tenant_id)],
            'voidReason' => 'required|string|min:8|max:500',
        ]);

        $tenantId = Auth::user()->tenant_id;
        $userId = Auth::id();

        DB::transaction(function () use ($tenantId, $userId): void {
            $expense = Expense::where('tenant_id', $tenantId)
                ->whereKey($this->voidingExpenseId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($expense->is_voided) {
                return;
            }

            $this->assertExpenseCanBeVoided($expense);

            $expense->forceFill([
                'is_voided' => true,
                'voided_at' => now(),
                'voided_by_user_id' => $userId,
                'void_reason' => trim($this->voidReason),
            ])->save();

            AuditLog::log(
                'EXPENSE_VOIDED',
                'Voided expense PKR '.number_format((float) $expense->amount, 2).' ['.$expense->title.']. Reason: '.trim($this->voidReason),
                $tenantId,
                'Expense',
                (string) $expense->id
            );
        });

        $this->feedbackMessage = 'Expense voided. Its original record is retained for audit history.';
        $this->cancelVoid();
    }

    public function render()
    {
        $this->authorizeExpenseViewer();
        $tenantId = Auth::user()->tenant_id;
        $halls = Hall::where('tenant_id', $tenantId)->get();

        // Date range
        $now = Carbon::now();
        if ($this->period === 'THIS_MONTH') {
            $startDate = $now->copy()->startOfMonth()->format('Y-m-d');
            $endDate = $now->copy()->endOfMonth()->format('Y-m-d');
        } elseif ($this->period === 'LAST_MONTH') {
            $startDate = $now->copy()->subMonth()->startOfMonth()->format('Y-m-d');
            $endDate = $now->copy()->subMonth()->endOfMonth()->format('Y-m-d');
        } else {
            $startDate = '2000-01-01';
            $endDate = '2099-12-31';
        }

        // Queries
        $expensesQuery = Expense::withVoided()
            ->where('tenant_id', $tenantId)
            ->where('is_voided', $this->showVoided)
            ->whereBetween('expense_date', [$startDate, $endDate]);

        if ($this->filterCategory !== 'ALL') {
            $expensesQuery->where('category', $this->filterCategory);
        }

        $expenses = $expensesQuery->with(['hall', 'creator', 'voidedBy'])->latest('expense_date')->get();
        $totalExpenses = (float) Expense::where('tenant_id', $tenantId)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->sum('amount');

        // Revenue query in same period
        $bookingsQuery = Booking::where('tenant_id', $tenantId)
            ->where('status', 'CONFIRMED')
            ->whereBetween('event_date', [$startDate, $endDate]);

        $totalRevenue = (float) $bookingsQuery->sum('grand_total');
        $netProfit = $totalRevenue - $totalExpenses;
        $operatingMargin = $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 1) : 0;

        // Group by category for breakdown
        $categoryBreakdown = Expense::where('tenant_id', $tenantId)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->selectRaw('category, SUM(amount) as total_amount')
            ->groupBy('category')
            ->pluck('total_amount', 'category')
            ->toArray();

        return view('livewire.expense-tracker', [
            'expenses' => $expenses,
            'halls' => $halls,
            'totalExpenses' => $totalExpenses,
            'totalRevenue' => $totalRevenue,
            'netProfit' => $netProfit,
            'operatingMargin' => $operatingMargin,
            'categoryBreakdown' => $categoryBreakdown,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
}
