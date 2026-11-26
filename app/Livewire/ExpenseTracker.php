<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Expense;
use App\Models\Hall;
use App\Support\CashOnlyPayments;
use App\Support\PermissionMatrix;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
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

    protected $rules = [
        'category' => 'required|string',
        'title' => 'required|string|max:150',
        'amount' => 'required|numeric|min:1|max:9999999999.99',
        'expense_date' => 'required|date',
        'payee' => 'nullable|string|max:150',
        'payment_method' => 'required|in:CASH',
        'receipt_number' => 'nullable|string|max:80',
        'notes' => 'nullable|string',
    ];

    private function authorizeExpenseAction(string $permission): void
    {
        $user = Auth::user();
        abort_unless($user && $user->tenant_id, 403);
        abort_unless(PermissionMatrix::userCan($user, $permission), 403, 'Missing expense permission.');
    }

    public function mount()
    {
        $this->authorizeExpenseAction('expenses.view');

        $this->expense_date = Carbon::now()->format('Y-m-d');
    }

    public function openCreateModal()
    {
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
        $this->authorizeExpenseAction('expenses.manage');
        $tenantId = Auth::user()->tenant_id;
        $this->validate(array_merge($this->rules, [
            'hall_id' => ['nullable', Rule::exists('halls', 'id')->where('tenant_id', $tenantId)],
        ]));

        $expense = Expense::create([
            'tenant_id' => Auth::user()->tenant_id,
            'hall_id' => ! empty($this->hall_id) ? $this->hall_id : null,
            'category' => $this->category,
            'title' => $this->title,
            'amount' => (float) $this->amount,
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
        $expense = Expense::where('tenant_id', Auth::user()->tenant_id)->findOrFail($expenseId);
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
        $this->authorizeExpenseAction('expenses.manage');
        if (! $this->editingExpenseId) {
            return;
        }

        $tenantId = Auth::user()->tenant_id;
        $expense = Expense::where('tenant_id', $tenantId)->findOrFail($this->editingExpenseId);
        $originalMethod = (string) ($expense->payment_method ?? '');
        $allowedMethods = $originalMethod === CashOnlyPayments::CASH
            ? CashOnlyPayments::METHODS
            : [$originalMethod, CashOnlyPayments::CASH];

        $this->validate([
            'edit_hall_id' => ['nullable', Rule::exists('halls', 'id')->where('tenant_id', $tenantId)],
            'edit_category' => 'required|string',
            'edit_title' => 'required|string|max:150',
            'edit_amount' => 'required|numeric|min:1|max:9999999999.99',
            'edit_expense_date' => 'required|date',
            'edit_payee' => 'nullable|string|max:150',
            'edit_payment_method' => ['required', Rule::in($allowedMethods)],
            'edit_receipt_number' => 'nullable|string|max:80',
            'edit_notes' => 'nullable|string',
        ]);

        $update = [
            'hall_id' => ! empty($this->edit_hall_id) ? $this->edit_hall_id : null,
            'category' => $this->edit_category,
            'title' => $this->edit_title,
            'amount' => (float) $this->edit_amount,
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
            "Updated expense [{$expense->title}] of PKR ".number_format($expense->amount),
            Auth::user()->tenant_id,
            'Expense',
            $expense->id
        );

        $this->feedbackMessage = "Expense [{$expense->title}] updated successfully!";
        $this->closeEditModal();
    }

    public function deleteExpense(string $expenseId)
    {
        $this->authorizeExpenseAction('expenses.manage');
        $expense = Expense::where('tenant_id', Auth::user()->tenant_id)->findOrFail($expenseId);
        $amount = $expense->amount;
        $title = $expense->title;
        $expense->delete();

        AuditLog::log(
            'EXPENSE_DELETED',
            'Deleted expense of PKR '.number_format($amount)." [{$title}].",
            Auth::user()->tenant_id,
            'Expense',
            $expenseId
        );

        $this->feedbackMessage = 'Expense record deleted.';
    }

    public function render()
    {
        $this->authorizeExpenseAction('expenses.view');
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
        $expensesQuery = Expense::where('tenant_id', $tenantId)
            ->whereBetween('expense_date', [$startDate, $endDate]);

        if ($this->filterCategory !== 'ALL') {
            $expensesQuery->where('category', $this->filterCategory);
        }

        $expenses = $expensesQuery->with(['hall', 'creator'])->latest('expense_date')->get();
        $totalExpenses = (float) $expenses->sum('amount');

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
