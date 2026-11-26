<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Expense;
use App\Models\Hall;
use App\Models\HallChatMessage;
use App\Models\HallView;
use App\Models\HallWishlist;
use App\Models\InventoryItem;
use App\Models\KitchenKot;
use App\Models\Payment;
use App\Models\StaffAttendance;
use App\Models\User;
use App\Models\UtilityLog;
use App\Support\CashOnlyPayments;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Hall Admin Dashboard — Riwaj Grand'])]
class Dashboard extends Component
{
    // Filter controls for Hall Owner
    public string $timeframe = 'MONTHLY'; // 'WEEKLY', 'MONTHLY', 'QUARTERLY', 'YEARLY'

    public string $selectedHallId = 'ALL';

    public string $chartMetric = 'sales'; // 'sales' or 'programs'

    // Interactive CRM Kanban & Customer Details Drawer
    public bool $showCustomerModal = false;

    public ?string $selectedBookingId = null;

    public string $leadNoteText = '';

    public ?string $crmFeedback = null;

    // Daily Operational Kharcha & Generator Fuel Register (Hall Admin Operations)
    public bool $showDailyExpenseModal = false;

    public ?string $expenseHallId = null;

    public string $expenseCategory = 'GENERATOR_DIESEL'; // GENERATOR_DIESEL, STAFF_DAILY_WAGES, CLEANING_HYGIENE, WAPDA_ELECTRICITY, CHILLER_AC, DECOR_STAGE, SOUND_LIGHTS, KITCHEN_GAS, MISC_OPERATIONAL

    public string $expenseTitle = '';

    public float $expenseAmount = 14250.0;

    public string $expenseDate = '';

    public string $expensePayee = '';

    public string $expensePaymentMethod = 'CASH';

    public string $expenseReceiptNumber = '';

    public string $expenseNotes = '';

    // Historical daily operations ledger month and pagination.
    public string $operationsMonth = '';

    public int $operationsPage = 1;

    // Generator Fuel specific inputs
    public float $fuelDieselLiters = 50.0;

    public float $fuelRatePerLiter = 285.0;

    public float $fuelRunHours = 4.0;

    public ?string $fuelBookingId = null;

    // Wapda / Electricity specific inputs
    public float $electricityUnits = 120.0;

    public float $electricityUnitRate = 65.0;

    public ?string $expenseFeedback = null;

    public ?string $expenseError = null;

    // Live Guest Concierge Chat Inquiries & Tracking
    public bool $showChatModal = false;

    public ?string $selectedChatHallId = null;

    public ?string $selectedChatIp = null;

    public ?string $selectedChatSession = null;

    public array $chatMessages = [];

    public string $chatReplyText = '';

    public function mount()
    {
        $user = Auth::user();
        if ($user && $user->isCustomer()) {
            return $this->redirect(route('customer.dashboard'));
        }
        $this->expenseDate = Carbon::now()->format('Y-m-d');
        $this->operationsMonth = Carbon::now()->format('Y-m');
    }

    public function updatedOperationsMonth(): void
    {
        $this->operationsPage = 1;
    }

    public function previousOperationsMonth(): void
    {
        $this->operationsMonth = $this->normalizedOperationsMonth()->subMonth()->format('Y-m');
        $this->operationsPage = 1;
    }

    public function nextOperationsMonth(): void
    {
        $this->operationsMonth = $this->normalizedOperationsMonth()->addMonth()->format('Y-m');
        $this->operationsPage = 1;
    }

    private function normalizedOperationsMonth(): Carbon
    {
        try {
            $month = Carbon::createFromFormat('!Y-m', $this->operationsMonth);
            if (! $month || $month->format('Y-m') !== $this->operationsMonth || $month->year < 2000 || $month->year > 2100) {
                throw new \InvalidArgumentException;
            }
        } catch (\Throwable) {
            $this->operationsMonth = Carbon::now()->format('Y-m');
            $month = Carbon::now()->startOfMonth();
        }

        return $month->startOfMonth();
    }

    public function openDailyExpenseModal(string $category = 'GENERATOR_DIESEL'): void
    {
        $user = Auth::user();
        $this->expenseDate = Carbon::now()->format('Y-m-d');
        $this->expenseCategory = $category;
        $this->expenseFeedback = null;
        $this->expenseError = null;

        // Auto-select first hall of tenant if not chosen
        if (! $this->expenseHallId && $user && $user->tenant_id) {
            $firstHall = Hall::where('tenant_id', $user->tenant_id)->where('is_active', true)->first();
            $this->expenseHallId = $firstHall?->id;
        }

        $this->syncCategoryAmounts();
        $this->showDailyExpenseModal = true;
    }

    public function closeDailyExpenseModal(): void
    {
        $this->showDailyExpenseModal = false;
        $this->expenseFeedback = null;
        $this->expenseError = null;
    }

    public function updatedFuelDieselLiters(): void
    {
        $this->syncCategoryAmounts();
    }

    public function updatedFuelRatePerLiter(): void
    {
        $this->syncCategoryAmounts();
    }

    public function updatedElectricityUnits(): void
    {
        $this->syncCategoryAmounts();
    }

    public function updatedElectricityUnitRate(): void
    {
        $this->syncCategoryAmounts();
    }

    public function updatedExpenseCategory(): void
    {
        $this->syncCategoryAmounts();
    }

    private function syncCategoryAmounts(): void
    {
        if ($this->expenseCategory === 'GENERATOR_DIESEL') {
            $this->expenseAmount = round($this->fuelDieselLiters * $this->fuelRatePerLiter, 2);
            if (empty($this->expenseTitle) || str_starts_with($this->expenseTitle, 'Generator Diesel Refill')) {
                $this->expenseTitle = "Generator Diesel Refill ({$this->fuelDieselLiters}L @ Rs {$this->fuelRatePerLiter})";
            }
        } elseif ($this->expenseCategory === 'WAPDA_ELECTRICITY') {
            $this->expenseAmount = round($this->electricityUnits * $this->electricityUnitRate, 2);
            if (empty($this->expenseTitle) || str_starts_with($this->expenseTitle, 'Commercial Electricity / WAPDA')) {
                $this->expenseTitle = "Commercial Electricity / WAPDA Units ({$this->electricityUnits} Units)";
            }
        } elseif ($this->expenseCategory === 'STAFF_DAILY_WAGES') {
            if (empty($this->expenseTitle) || str_starts_with($this->expenseTitle, 'Staff Daily Dihaari')) {
                $this->expenseTitle = 'Staff Daily Dihaari / Waiters & Cleaning Duty';
            }
        } elseif ($this->expenseCategory === 'CLEANING_HYGIENE') {
            if (empty($this->expenseTitle) || str_starts_with($this->expenseTitle, 'Hall Cleaning')) {
                $this->expenseTitle = 'Hall Cleaning, Washroom Hygiene & Disinfectants';
            }
        } elseif ($this->expenseCategory === 'KITCHEN_GAS') {
            if (empty($this->expenseTitle) || str_starts_with($this->expenseTitle, 'LPG Commercial Gas')) {
                $this->expenseTitle = 'LPG Commercial Gas Cylinder Refill for Bawarchi';
            }
        }
    }

    public function saveDailyExpense(): void
    {
        $user = Auth::user();
        $tenantId = $user?->tenant_id;

        if (! $tenantId) {
            $this->expenseError = 'Session tenant not found. Please log in again.';

            return;
        }

        $this->syncCategoryAmounts();

        $this->validate([
            'expenseCategory' => 'required|string',
            'expenseTitle' => 'required|string|max:160',
            'expenseAmount' => 'required|numeric|min:1',
            'expenseDate' => 'required|date',
            'expensePaymentMethod' => CashOnlyPayments::validationRule(),
            'expenseHallId' => ['nullable', Rule::exists('halls', 'id')->where('tenant_id', $tenantId)],
        ]);

        // 1. Create Expense record
        $expense = Expense::create([
            'tenant_id' => $tenantId,
            'hall_id' => $this->expenseHallId,
            'category' => $this->expenseCategory,
            'title' => trim($this->expenseTitle),
            'amount' => (float) $this->expenseAmount,
            'expense_date' => $this->expenseDate,
            'payee' => ! empty($this->expensePayee) ? trim($this->expensePayee) : 'Cash Drawer / Vendor',
            'payment_method' => $this->expensePaymentMethod,
            'receipt_number' => $this->expenseReceiptNumber,
            'notes' => $this->expenseNotes,
            'created_by_user_id' => $user->id,
        ]);

        // 2. If Generator Fuel or Wapda, log into utility_logs for shift tracking
        if ($this->expenseCategory === 'GENERATOR_DIESEL' || $this->expenseCategory === 'WAPDA_ELECTRICITY') {
            UtilityLog::create([
                'tenant_id' => $tenantId,
                'booking_id' => ! empty($this->fuelBookingId) ? $this->fuelBookingId : null,
                'generator_run_hours' => $this->expenseCategory === 'GENERATOR_DIESEL' ? $this->fuelRunHours : 0,
                'diesel_liters_consumed' => $this->expenseCategory === 'GENERATOR_DIESEL' ? $this->fuelDieselLiters : 0,
                'diesel_rate_per_liter' => $this->expenseCategory === 'GENERATOR_DIESEL' ? $this->fuelRatePerLiter : 0,
                'wapda_units_consumed' => $this->expenseCategory === 'WAPDA_ELECTRICITY' ? $this->electricityUnits : 0,
                'wapda_unit_rate' => $this->expenseCategory === 'WAPDA_ELECTRICITY' ? $this->electricityUnitRate : 0,
                'total_utility_cost' => (float) $this->expenseAmount,
                'logged_by' => $user->name,
                'notes' => "Logged from Daily Kharcha Desk. Ref: {$expense->title}",
            ]);
        }

        $this->showDailyExpenseModal = false;
        $this->expenseFeedback = 'Daily Kharcha [Rs '.number_format($this->expenseAmount).'] recorded successfully.';

        // Reset form
        $this->expenseTitle = '';
        $this->expensePayee = '';
        $this->expenseReceiptNumber = '';
        $this->expenseNotes = '';
    }

    public function deleteDailyExpense(string $expenseId): void
    {
        $tenantId = Auth::user()?->tenant_id;
        $expense = Expense::where('tenant_id', $tenantId)->findOrFail($expenseId);
        $amount = $expense->amount;
        $expense->delete();

        $this->expenseFeedback = 'Expense record [Rs '.number_format($amount).'] deleted.';
    }

    public function setTimeframe(string $tf): void
    {
        if (in_array($tf, ['WEEKLY', 'MONTHLY', 'QUARTERLY', 'YEARLY'])) {
            $this->timeframe = $tf;
        }
    }

    public function setChartMetric(string $metric): void
    {
        if (in_array($metric, ['sales', 'programs'])) {
            $this->chartMetric = $metric;
        }
    }

    public function openCustomerDetails(string $bookingId): void
    {
        $this->selectedBookingId = $bookingId;
        $this->leadNoteText = '';
        $this->showCustomerModal = true;
    }

    public function closeCustomerDetails(): void
    {
        $this->showCustomerModal = false;
        $this->selectedBookingId = null;
        $this->crmFeedback = null;
    }

    public function updateLeadStage(string $bookingId, string $newStage): void
    {
        $actor = Auth::user();
        abort_unless($actor && \App\Support\PermissionMatrix::userCan($actor, 'bookings.update'), 403);

        $tenantId = $actor->tenant_id;
        if (! $tenantId) {
            $this->crmFeedback = 'Session tenant not found. Please log in again.';

            return;
        }

        $allowedStages = ['NEW_INQUIRY', 'SITE_VISIT', 'QUOTATION', 'CONFIRMED', 'CANCELLED'];
        if (! in_array($newStage, $allowedStages)) {
            return;
        }

        $booking = Booking::where('tenant_id', $tenantId)->findOrFail($bookingId);

        if (in_array($newStage, ['CONFIRMED', 'CANCELLED'], true) && $booking->status !== $newStage) {
            try {
                $booking = app(\App\Services\BookingService::class)->transitionStatus($booking, $newStage);
            } catch (\App\Exceptions\SlotAlreadyBookedException|\InvalidArgumentException $exception) {
                $this->crmFeedback = $exception->getMessage();

                return;
            }
        }

        $booking->update(['crm_stage' => $newStage]);

        $this->crmFeedback = "Lead [{$booking->customer_name}] moved to ".str_replace('_', ' ', $newStage).'.';
    }

    public function addLeadNote(): void
    {
        if (empty(trim($this->leadNoteText)) || ! $this->selectedBookingId) {
            return;
        }

        $tenantId = Auth::user()?->tenant_id;
        if (! $tenantId) {
            $this->crmFeedback = 'Session tenant not found. Please log in again.';

            return;
        }
        $booking = Booking::where('tenant_id', $tenantId)->findOrFail($this->selectedBookingId);

        $timestamp = Carbon::now()->format('d M Y, h:i A');
        $author = Auth::user()?->name ?? 'Staff';
        $newNote = "[{$timestamp} by {$author}]: ".trim($this->leadNoteText);

        $existingNotes = $booking->notes ? $booking->notes."\n" : '';
        $booking->update(['notes' => $existingNotes.$newNote]);

        $this->leadNoteText = '';
        $this->crmFeedback = 'Interaction note recorded successfully.';
    }

    public function openChatModal(string $hallId, string $ip, string $session): void
    {
        $hall = $this->assertOwnedHall($hallId);

        $this->selectedChatHallId = $hall->id;
        $this->selectedChatIp = $ip;
        $this->selectedChatSession = $session;
        $this->chatReplyText = '';
        $this->chatMessages = HallChatMessage::getConversation($hall->id, $ip, $session);
        HallChatMessage::markAsRead($hall->id, $ip, $session);
        $this->showChatModal = true;
    }

    public function closeChatModal(): void
    {
        $this->showChatModal = false;
        $this->selectedChatHallId = null;
        $this->selectedChatIp = null;
        $this->selectedChatSession = null;
        $this->chatMessages = [];
        $this->chatReplyText = '';
    }

    public function sendChatReply(): void
    {
        $reply = trim($this->chatReplyText);
        if (empty($reply) || ! $this->selectedChatHallId) {
            return;
        }

        $hall = $this->assertOwnedHall($this->selectedChatHallId);

        HallChatMessage::create([
            'hall_id' => $hall->id,
            'user_id' => Auth::id(),
            'ip_address' => $this->selectedChatIp,
            'session_id' => $this->selectedChatSession,
            'sender_type' => 'hall_owner',
            'message_type' => 'text',
            'message' => $reply,
            'voice_file' => null,
            'is_read' => false,
        ]);

        $this->chatReplyText = '';
        $this->chatMessages = HallChatMessage::getConversation(
            $hall->id,
            $this->selectedChatIp,
            $this->selectedChatSession
        );
    }

    /**
     * Ensure chat actions only touch halls belonging to the authenticated tenant.
     */
    protected function assertOwnedHall(string $hallId): Hall
    {
        $user = Auth::user();
        abort_unless($user && $user->tenant_id && ! $user->isCustomer(), 403);

        return Hall::where('id', $hallId)
            ->where('tenant_id', $user->tenant_id)
            ->firstOrFail();
    }

    public function render()
    {
        $now = Carbon::now();
        $user = Auth::user();
        $tenantId = $user ? $user->tenant_id : null;

        // Zero-trust read scope: a session without tenant context must see no
        // platform-wide aggregates, so match nothing instead of skipping the filter.
        $tenantScope = fn ($q) => $q->where('tenant_id', $tenantId ?? '00000000-0000-0000-0000-000000000000');

        // Fetch Halls strictly scoped by tenant
        $hallsQuery = Hall::where('is_active', true);
        if ($tenantId) {
            $hallsQuery->where('tenant_id', $tenantId);
        } else {
            $hallsQuery->whereRaw('1 = 0');
        }
        $halls = $hallsQuery->get();
        $tenantHallIds = $halls->pluck('id')->toArray();

        // 1. Determine Date Range based on Timeframe
        switch ($this->timeframe) {
            case 'WEEKLY':
                $startDate = $now->copy()->startOfWeek()->format('Y-m-d');
                $endDate = $now->copy()->endOfWeek()->format('Y-m-d');
                $previousStartDate = $now->copy()->subWeek()->startOfWeek()->format('Y-m-d');
                $previousEndDate = $now->copy()->subWeek()->endOfWeek()->format('Y-m-d');
                $periodLabel = 'This Week ('.Carbon::parse($startDate)->format('d M').' - '.Carbon::parse($endDate)->format('d M').')';
                break;

            case 'QUARTERLY':
                $startDate = $now->copy()->subDays(90)->format('Y-m-d');
                $endDate = $now->copy()->format('Y-m-d');
                $previousStartDate = $now->copy()->subDays(180)->format('Y-m-d');
                $previousEndDate = $now->copy()->subDays(90)->format('Y-m-d');
                $periodLabel = 'Last 90 Days';
                break;

            case 'YEARLY':
                $startDate = $now->copy()->startOfYear()->format('Y-m-d');
                $endDate = $now->copy()->endOfYear()->format('Y-m-d');
                $previousStartDate = $now->copy()->subYear()->startOfYear()->format('Y-m-d');
                $previousEndDate = $now->copy()->subYear()->endOfYear()->format('Y-m-d');
                $periodLabel = 'Year '.$now->year;
                break;

            case 'MONTHLY':
            default:
                $startDate = $now->copy()->startOfMonth()->format('Y-m-d');
                $endDate = $now->copy()->endOfMonth()->format('Y-m-d');
                $previousStartDate = $now->copy()->subMonth()->startOfMonth()->format('Y-m-d');
                $previousEndDate = $now->copy()->subMonth()->endOfMonth()->format('Y-m-d');
                $periodLabel = 'This Month ('.$now->format('F Y').')';
                break;
        }

        // 2. Base Query strictly scoped to Timeframe and Tenant
        $bookingsQuery = Booking::whereBetween('event_date', [$startDate, $endDate])
            ->whereIn('status', ['CONFIRMED', 'COMPLETED', 'HOLD']);

        $prevQuery = Booking::whereBetween('event_date', [$previousStartDate, $previousEndDate])
            ->whereIn('status', ['CONFIRMED', 'COMPLETED', 'HOLD']);

        if ($tenantId) {
            $bookingsQuery->where('tenant_id', $tenantId);
            $prevQuery->where('tenant_id', $tenantId);
        } else {
            $bookingsQuery->whereRaw('1 = 0');
            $prevQuery->whereRaw('1 = 0');
        }

        if ($this->selectedHallId !== 'ALL' && in_array($this->selectedHallId, $tenantHallIds)) {
            $bookingsQuery->where('hall_id', $this->selectedHallId);
            $prevQuery->where('hall_id', $this->selectedHallId);
        }

        $kitchenEnabled = (bool) config('platform.kitchen_module_enabled', false);
        $bookingWithRelations = $kitchenEnabled
            ? ['hall', 'package', 'payments', 'kitchenKot', 'utilityLog']
            : ['hall', 'package', 'payments', 'utilityLog'];
        $allPeriodBookings = (clone $bookingsQuery)->with($bookingWithRelations)->get();

        $prevProgramsCount = (clone $prevQuery)->count();
        $prevSalesTurnover = (float) (clone $prevQuery)->sum('grand_total');

        // 3. Core Financial & Program Metrics
        $totalPrograms = $allPeriodBookings->count();
        $confirmedPrograms = $allPeriodBookings->where('status', 'CONFIRMED')->count();
        $holdPrograms = $allPeriodBookings->where('status', 'HOLD')->count();

        $grossSalesTurnover = (float) $allPeriodBookings->sum('grand_total');
        $advanceCollected = (float) $allPeriodBookings->sum('advance_paid');
        $totalBalanceDue = (float) $allPeriodBookings->sum('balance_due');
        $totalGuestsServed = (int) $allPeriodBookings->sum('guest_count');

        // Growth percentages (Safely calculated: zero when both are zero, never misleading 100%)
        if ($prevProgramsCount > 0) {
            $programsGrowth = round((($totalPrograms - $prevProgramsCount) / $prevProgramsCount) * 100, 1);
        } else {
            $programsGrowth = $totalPrograms > 0 ? 100.0 : 0.0;
        }

        if ($prevSalesTurnover > 0) {
            $salesGrowth = round((($grossSalesTurnover - $prevSalesTurnover) / $prevSalesTurnover) * 100, 1);
        } else {
            $salesGrowth = $grossSalesTurnover > 0 ? 100.0 : 0.0;
        }

        // 4. Actual Recorded Overheads vs Benchmark Cost Estimation
        // Actual Recorded Expenses from ERP Expense Register
        $expenseQuery = Expense::whereBetween('expense_date', [$startDate, $endDate]);
        if ($tenantId) {
            $expenseQuery->where('tenant_id', $tenantId);
        } else {
            $expenseQuery->whereRaw('1 = 0');
        }
        if ($this->selectedHallId !== 'ALL' && in_array($this->selectedHallId, $tenantHallIds)) {
            $expenseQuery->where(function ($q) {
                $q->where('hall_id', $this->selectedHallId)->orWhereNull('hall_id');
            });
        }
        $actualRecordedExpenses = (float) $expenseQuery->sum('amount');

        // Industry Benchmark Catering Provisions (approx. 52% of per-head food component)
        $foodTurnover = (float) $allPeriodBookings->sum(function ($b) {
            return (float) $b->per_head_rate * (int) $b->guest_count;
        });
        $estimatedFoodCost = round($foodTurnover * 0.52, 2);

        // Utility diesel & Wapda cost from utility logs for period bookings
        $periodBookingIds = $allPeriodBookings->pluck('id');
        $dieselLiters = (float) UtilityLog::whereIn('booking_id', $periodBookingIds)->sum('diesel_liters_consumed');
        $totalUtilityCost = (float) UtilityLog::whereIn('booking_id', $periodBookingIds)->sum('total_utility_cost');

        // Net Operating Profit: uses actual recorded expenses if available, otherwise baseline overhead
        $totalOperationalCost = $actualRecordedExpenses > 0 ? $actualRecordedExpenses : ($estimatedFoodCost + $totalUtilityCost);
        $netOwnerProfit = $grossSalesTurnover - $totalOperationalCost;
        $profitMarginPercent = $grossSalesTurnover > 0
            ? round(($netOwnerProfit / $grossSalesTurnover) * 100, 1)
            : 0;

        // 5. Programs Breakdown by Event Type
        $eventTypesBreakdown = $allPeriodBookings->groupBy('event_type')->map(function ($items, $type) {
            return [
                'type' => $type,
                'count' => $items->count(),
                'sales' => (float) $items->sum('grand_total'),
                'guests' => (int) $items->sum('guest_count'),
            ];
        })->sortByDesc('sales');

        // 6. Shift Occupancy Rate (Afternoon, Evening, Night)
        $shiftCounts = [
            'AFTERNOON' => $allPeriodBookings->where('slot_type', 'AFTERNOON')->count(),
            'EVENING' => $allPeriodBookings->where('slot_type', 'EVENING')->count(),
            'NIGHT' => $allPeriodBookings->where('slot_type', 'NIGHT')->count(),
        ];
        $totalShifts = max(1, array_sum($shiftCounts));
        $shiftPercentages = [
            'AFTERNOON' => round(($shiftCounts['AFTERNOON'] / $totalShifts) * 100),
            'EVENING' => round(($shiftCounts['EVENING'] / $totalShifts) * 100),
            'NIGHT' => round(($shiftCounts['NIGHT'] / $totalShifts) * 100),
        ];

        // 7. Hall-by-Hall Performance Matrix (Scoped to Owner's Halls)
        $hallPerformance = $halls->map(function ($h) use ($allPeriodBookings) {
            $hallBookings = $allPeriodBookings->where('hall_id', $h->id);

            return [
                'id' => $h->id,
                'name' => $h->name,
                'programs' => $hallBookings->count(),
                'sales' => (float) $hallBookings->sum('grand_total'),
                'advance' => (float) $hallBookings->sum('advance_paid'),
                'guests' => (int) $hallBookings->sum('guest_count'),
                'avgPerProgram' => $hallBookings->count() > 0 ? round($hallBookings->sum('grand_total') / $hallBookings->count()) : 0,
            ];
        });

        // 8. Trend Series for Visual SVG Bar Chart
        $chartData = $this->buildChartData($this->timeframe, $this->selectedHallId, $this->chartMetric, $tenantHallIds, $tenantId);

        // 9. Today's active functions (Strictly Scoped to Owner's Tenant)
        $today = $now->format('Y-m-d');
        $todayWithRelations = $kitchenEnabled
            ? ['hall.venue', 'payments', 'kitchenKot', 'utilityLog']
            : ['hall.venue', 'payments', 'utilityLog'];
        $todayQuery = Booking::with($todayWithRelations)
            ->whereDate('event_date', $today)
            ->whereIn('status', ['CONFIRMED', 'HOLD']);

        if ($tenantId) {
            $todayQuery->where('tenant_id', $tenantId);
        } else {
            $todayQuery->whereRaw('1 = 0');
        }

        if ($this->selectedHallId !== 'ALL' && in_array($this->selectedHallId, $tenantHallIds)) {
            $todayQuery->where('hall_id', $this->selectedHallId);
        }
        $todayBookings = $todayQuery->orderBy('slot_type')->get();

        // 10. Horizon bookings list (Next 6 functions)
        $upcomingQuery = Booking::with(['hall', 'package'])
            ->where('event_date', '>=', $today)
            ->whereIn('status', ['CONFIRMED', 'HOLD'])
            ->orderBy('event_date')
            ->orderBy('slot_type')
            ->limit(6);

        if ($tenantId) {
            $upcomingQuery->where('tenant_id', $tenantId);
        } else {
            $upcomingQuery->whereRaw('1 = 0');
        }

        if ($this->selectedHallId !== 'ALL' && in_array($this->selectedHallId, $tenantHallIds)) {
            $upcomingQuery->where('hall_id', $this->selectedHallId);
        }
        $upcomingBookingsList = $upcomingQuery->get();

        // 11. Quick counts for top operational snapshot
        $totalHallsCount = $halls->count();
        $todayEventsCount = $todayBookings->count();
        $upcomingEventsCount = Booking::where('event_date', '>=', $today)
            ->whereIn('status', ['CONFIRMED', 'HOLD'])
            ->where($tenantScope)
            ->count();
        $pendingInquiriesCount = Booking::whereIn('status', ['INQUIRY', 'PENDING'])
            ->where($tenantScope)
            ->count();

        // 12. Hall Analytics: Views, Wishlists, Bookings per hall
        $hallAnalytics = $halls->map(function ($h) use ($tenantScope) {
            $views = HallView::where('hall_id', $h->id)->count();
            $wishlists = HallWishlist::where('hall_id', $h->id)->count();
            $bookings = Booking::where('hall_id', $h->id)
                ->where($tenantScope)
                ->count();
            $confirmedBookings = Booking::where('hall_id', $h->id)
                ->where('status', 'CONFIRMED')
                ->where($tenantScope)
                ->count();
            $totalRevenue = (float) Booking::where('hall_id', $h->id)
                ->whereIn('status', ['CONFIRMED', 'COMPLETED'])
                ->where($tenantScope)
                ->sum('grand_total');

            return [
                'id' => $h->id,
                'name' => $h->name,
                'slug' => $h->slug,
                'views' => $views,
                'wishlists' => $wishlists,
                'total_bookings' => $bookings,
                'confirmed_bookings' => $confirmedBookings,
                'total_revenue' => $totalRevenue,
            ];
        })->sortByDesc('views');

        $totalHallViews = HallView::where('tenant_id', $tenantId)->count();
        $totalHallWishlists = HallWishlist::where('tenant_id', $tenantId)->count();
        $totalHallBookings = Booking::where('tenant_id', $tenantId)->count();

        // 12b. Traffic Pulse — public hall views per day for the last 14 days
        // (rendered as a premium bar graph in the marketing analytics card).
        $trafficPulseStart = $now->copy()->subDays(13)->startOfDay();
        $trafficPulseRows = HallView::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$trafficPulseStart, $now->copy()->endOfDay()])
            ->get(['created_at']);
        $trafficPulseByDate = $trafficPulseRows->groupBy(
            fn ($row) => optional($row->created_at)->format('Y-m-d')
        );
        $trafficPulse = [];
        for ($pulseOffset = 13; $pulseOffset >= 0; $pulseOffset--) {
            $pulseDay = $now->copy()->subDays($pulseOffset);
            $trafficPulse[] = [
                'date' => $pulseDay->format('Y-m-d'),
                'label' => $pulseDay->format('d M'),
                'views' => (int) ($trafficPulseByDate->get($pulseDay->format('Y-m-d'), collect())->count()),
            ];
        }

        // 13. Interactive Kanban CRM Leads (Strictly Scoped to Tenant)
        $crmLeadsQuery = Booking::with(['hall', 'package', 'payments'])
            ->where($tenantScope)
            ->latest();

        if ($this->selectedHallId !== 'ALL' && in_array($this->selectedHallId, $tenantHallIds)) {
            $crmLeadsQuery->where('hall_id', $this->selectedHallId);
        }

        $allLeads = $crmLeadsQuery->take(60)->get();

        $kanbanColumns = [
            'NEW_INQUIRY' => $allLeads->filter(fn ($b) => ($b->crm_stage === 'NEW_INQUIRY' || empty($b->crm_stage)) && $b->status !== 'CONFIRMED'),
            'SITE_VISIT' => $allLeads->filter(fn ($b) => $b->crm_stage === 'SITE_VISIT' || ($b->status === 'HOLD' && $b->crm_stage !== 'CONFIRMED' && $b->crm_stage !== 'QUOTATION')),
            'QUOTATION' => $allLeads->filter(fn ($b) => $b->crm_stage === 'QUOTATION'),
            'CONFIRMED' => $allLeads->filter(fn ($b) => $b->crm_stage === 'CONFIRMED' || $b->status === 'CONFIRMED'),
        ];

        // 14. Live Guest Concierge Chat Inquiries (Strictly Scoped to Tenant's Halls)
        $liveChatInquiries = HallChatMessage::with('hall')
            ->whereIn('hall_id', $tenantHallIds)
            ->where('sender_type', 'customer')
            ->select('ip_address', 'session_id', 'hall_id')
            ->selectRaw('COUNT(*) as message_count, MAX(created_at) as last_message_at')
            ->groupBy('ip_address', 'session_id', 'hall_id')
            ->orderByDesc('last_message_at')
            ->take(8)
            ->get();

        $selectedLead = null;
        if ($this->showCustomerModal && $this->selectedBookingId) {
            $selectedLead = Booking::with(['hall', 'package', 'payments'])
                ->where($tenantScope)
                ->find($this->selectedBookingId);
        }

        // 14. Daily Operational Kharcha & Generator Fuel Tracking (Hall Admin Daily Operations)
        $todayExpensesQuery = Expense::where('tenant_id', $tenantId)->whereDate('expense_date', $today);
        $todayExpensesSum = (float) $todayExpensesQuery->sum('amount');

        $todayDieselLiters = (float) UtilityLog::where('tenant_id', $tenantId)
            ->whereDate('created_at', $today)
            ->sum('diesel_liters_consumed');

        $todayDieselCost = (float) Expense::where('tenant_id', $tenantId)
            ->whereDate('expense_date', $today)
            ->where('category', 'GENERATOR_DIESEL')
            ->sum('amount');

        $todayWagesCost = (float) Expense::where('tenant_id', $tenantId)
            ->whereDate('expense_date', $today)
            ->where('category', 'STAFF_DAILY_WAGES')
            ->sum('amount');

        $todayCleaningCost = (float) Expense::where('tenant_id', $tenantId)
            ->whereDate('expense_date', $today)
            ->where('category', 'CLEANING_HYGIENE')
            ->sum('amount');

        $todayCashInflow = (float) Payment::where('tenant_id', $tenantId)
            ->whereDate('created_at', $today)
            ->sum('amount');

        $todayNetBalance = $todayCashInflow - $todayExpensesSum;

        $operationsMonthStart = $this->normalizedOperationsMonth();
        $operationsMonthEnd = $operationsMonthStart->copy()->endOfMonth();
        $operationsStart = $operationsMonthStart->format('Y-m-d');
        $operationsEnd = $operationsMonthEnd->format('Y-m-d');

        $monthExpensesQuery = Expense::where('tenant_id', $tenantId)
            ->whereBetween('expense_date', [$operationsStart, $operationsEnd]);
        if ($this->selectedHallId !== 'ALL' && in_array($this->selectedHallId, $tenantHallIds, true)) {
            $monthExpensesQuery->where('hall_id', $this->selectedHallId);
        }
        $operationsExpensesTotal = (float) (clone $monthExpensesQuery)->sum('amount');
        $operationsExpenseCount = (clone $monthExpensesQuery)->count();
        $operationsDieselCost = (float) (clone $monthExpensesQuery)->where('category', 'GENERATOR_DIESEL')->sum('amount');
        $operationsWagesCost = (float) (clone $monthExpensesQuery)->whereIn('category', ['STAFF_DAILY_WAGES', 'STAFF_WAGES'])->sum('amount');
        $operationsCleaningCost = (float) (clone $monthExpensesQuery)->where('category', 'CLEANING_HYGIENE')->sum('amount');

        $operationsPaymentsQuery = Payment::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$operationsMonthStart->copy()->startOfDay(), $operationsMonthEnd->copy()->endOfDay()]);
        if ($this->selectedHallId !== 'ALL' && in_array($this->selectedHallId, $tenantHallIds, true)) {
            $operationsPaymentsQuery->whereHas('booking', fn ($query) => $query->where('hall_id', $this->selectedHallId));
        }
        $operationsCashInflow = (float) $operationsPaymentsQuery->sum('amount');
        $operationsNetBalance = $operationsCashInflow - $operationsExpensesTotal;

        $operationsExpensePages = max(1, (int) ceil($operationsExpenseCount / 15));
        $this->operationsPage = min(max(1, $this->operationsPage), $operationsExpensePages);
        $recentExpenses = (clone $monthExpensesQuery)
            ->with('hall')
            ->orderByDesc('expense_date')
            ->orderByDesc('created_at')
            ->skip(($this->operationsPage - 1) * 15)
            ->take(15)
            ->get();

        // 15. Real-Time ERP Modular Health Matrix
        // Kitchen/KOT engine is toggleable — zero DB work while switched off.
        if ((bool) config('platform.kitchen_module_enabled', false)) {
            $todayKotsQuery = KitchenKot::where('tenant_id', $tenantId)
                ->whereHas('booking', fn ($q) => $q->whereDate('event_date', $today));
            $todayKitchenKotsCount = (clone $todayKotsQuery)->count();
            $todayTotalDiners = (int) (clone $todayKotsQuery)->sum('guest_count');
            $todayMeatNeeded = (float) (clone $todayKotsQuery)->sum('raw_meat_kg');
            $todayRiceNeeded = (float) (clone $todayKotsQuery)->sum('raw_rice_kg');
        } else {
            $todayKitchenKotsCount = 0;
            $todayTotalDiners = 0;
            $todayMeatNeeded = 0.0;
            $todayRiceNeeded = 0.0;
        }

        $totalInventoryItems = InventoryItem::where('tenant_id', $tenantId)->where('is_active', true)->count();
        $lowStockInventoryCount = InventoryItem::where('tenant_id', $tenantId)->where('is_active', true)->whereColumn('current_stock', '<=', 'min_threshold')->count();

        $todayStaffPresentCount = StaffAttendance::where('tenant_id', $tenantId)->whereDate('date', $today)->whereIn('status', ['PRESENT', 'HALF_DAY'])->count();
        $todayStaffTotalCount = User::hallStaff($tenantId)->where('status', 'ACTIVE')->count();
        $todayOvertimeHours = (float) StaffAttendance::where('tenant_id', $tenantId)->whereDate('date', $today)->sum('overtime_hours');

        $todayGeneratorHours = (float) UtilityLog::where('tenant_id', $tenantId)->whereDate('created_at', $today)->sum('generator_run_hours');
        $todayWapdaUnits = (float) UtilityLog::where('tenant_id', $tenantId)->whereDate('created_at', $today)->sum('wapda_units_consumed');

        // Period-aware workforce & utility aggregates (follow the selected
        // timeframe so monthly/yearly views are not stuck on "today" only).
        $periodStaffPresentCount = (int) StaffAttendance::where('tenant_id', $tenantId)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', ['PRESENT', 'HALF_DAY'])
            ->count();
        $periodOvertimeHours = (float) StaffAttendance::where('tenant_id', $tenantId)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('overtime_hours');
        $periodGeneratorHours = (float) UtilityLog::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->sum('generator_run_hours');
        $periodDieselLiters = (float) UtilityLog::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->sum('diesel_liters_consumed');
        $periodWapdaUnits = (float) UtilityLog::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->sum('wapda_units_consumed');

        return view('livewire.dashboard', [
            'kitchenEnabled' => (bool) config('platform.kitchen_module_enabled', false),
            'todayKitchenKotsCount' => $todayKitchenKotsCount,
            'todayTotalDiners' => $todayTotalDiners,
            'todayMeatNeeded' => $todayMeatNeeded,
            'todayRiceNeeded' => $todayRiceNeeded,
            'totalInventoryItems' => $totalInventoryItems,
            'lowStockInventoryCount' => $lowStockInventoryCount,
            'todayStaffPresentCount' => $todayStaffPresentCount,
            'todayStaffTotalCount' => $todayStaffTotalCount,
            'todayOvertimeHours' => $todayOvertimeHours,
            'todayGeneratorHours' => $todayGeneratorHours,
            'todayWapdaUnits' => $todayWapdaUnits,
            'periodStaffPresentCount' => $periodStaffPresentCount,
            'periodOvertimeHours' => $periodOvertimeHours,
            'periodGeneratorHours' => $periodGeneratorHours,
            'periodDieselLiters' => $periodDieselLiters,
            'periodWapdaUnits' => $periodWapdaUnits,
            'halls' => $halls,
            'periodLabel' => $periodLabel,
            'allPeriodBookings' => $allPeriodBookings,
            'totalPrograms' => $totalPrograms,
            'confirmedPrograms' => $confirmedPrograms,
            'holdPrograms' => $holdPrograms,
            'grossSalesTurnover' => $grossSalesTurnover,
            'advanceCollected' => $advanceCollected,
            'totalBalanceDue' => $totalBalanceDue,
            'totalGuestsServed' => $totalGuestsServed,
            'programsGrowth' => $programsGrowth,
            'salesGrowth' => $salesGrowth,
            'actualRecordedExpenses' => $actualRecordedExpenses,
            'estimatedFoodCost' => $estimatedFoodCost,
            'totalUtilityCost' => $totalUtilityCost,
            'netOwnerProfit' => $netOwnerProfit,
            'profitMarginPercent' => $profitMarginPercent,
            'eventTypesBreakdown' => $eventTypesBreakdown,
            'shiftCounts' => $shiftCounts,
            'shiftPercentages' => $shiftPercentages,
            'hallPerformance' => $hallPerformance,
            'chartData' => $chartData,
            'todayBookings' => $todayBookings,
            'upcomingBookingsList' => $upcomingBookingsList,
            'totalHallsCount' => $totalHallsCount,
            'todayEventsCount' => $todayEventsCount,
            'upcomingEventsCount' => $upcomingEventsCount,
            'pendingInquiriesCount' => $pendingInquiriesCount,
            'hallAnalytics' => $hallAnalytics,
            'totalHallViews' => $totalHallViews,
            'totalHallWishlists' => $totalHallWishlists,
            'totalHallBookings' => $totalHallBookings,
            'trafficPulse' => $trafficPulse,
            'kanbanColumns' => $kanbanColumns,
            'allLeads' => $allLeads,
            'selectedLead' => $selectedLead,
            'liveChatInquiries' => $liveChatInquiries,
            'todayExpensesSum' => $todayExpensesSum,
            'todayDieselLiters' => $todayDieselLiters,
            'todayDieselCost' => $todayDieselCost,
            'todayWagesCost' => $todayWagesCost,
            'todayCleaningCost' => $todayCleaningCost,
            'todayCashInflow' => $todayCashInflow,
            'todayNetBalance' => $todayNetBalance,
            'recentExpenses' => $recentExpenses,
            'operationsMonthTitle' => $operationsMonthStart->format('F Y'),
            'operationsExpensesTotal' => $operationsExpensesTotal,
            'operationsExpenseCount' => $operationsExpenseCount,
            'operationsDieselCost' => $operationsDieselCost,
            'operationsWagesCost' => $operationsWagesCost,
            'operationsCleaningCost' => $operationsCleaningCost,
            'operationsCashInflow' => $operationsCashInflow,
            'operationsNetBalance' => $operationsNetBalance,
            'operationsExpensePages' => $operationsExpensePages,
            'waveData' => $this->buildWaveData($this->timeframe, $this->selectedHallId, $tenantHallIds, $tenantId),
            'miniCalendar' => $this->buildMiniCalendar($tenantId, $tenantHallIds, $this->selectedHallId),
        ]);
    }

    private function buildChartData(string $timeframe, string $hallId, string $metric, array $allowedHallIds = [], ?string $tenantId = null): array
    {
        $now = Carbon::now();
        $points = [];

        if ($timeframe === 'WEEKLY') {
            $start = $now->copy()->startOfWeek();
            for ($i = 0; $i < 7; $i++) {
                $day = $start->copy()->addDays($i);
                $baseQ = Booking::whereDate('event_date', $day->format('Y-m-d'))
                    ->whereIn('status', ['CONFIRMED', 'COMPLETED', 'HOLD']);

                if ($tenantId) {
                    $baseQ->where('tenant_id', $tenantId);
                } else {
                    $baseQ->whereRaw('1 = 0');
                }

                if ($hallId !== 'ALL' && in_array($hallId, $allowedHallIds)) {
                    $baseQ->where('hall_id', $hallId);
                } elseif (! empty($allowedHallIds)) {
                    $baseQ->whereIn('hall_id', $allowedHallIds);
                }

                $eveningQ = (clone $baseQ)->where('slot_type', 'EVENING');
                $afternoonQ = (clone $baseQ)->where('slot_type', 'AFTERNOON');

                $val = ($metric === 'sales') ? (float) $eveningQ->sum('grand_total') : (int) $eveningQ->count();
                $secVal = ($metric === 'sales') ? (float) $afternoonQ->sum('grand_total') : (int) $afternoonQ->count();
                $totalVal = ($metric === 'sales') ? (float) $baseQ->sum('grand_total') : (int) $baseQ->count();

                $points[] = [
                    'label' => $day->format('D'),
                    'fullLabel' => $day->format('D, d M'),
                    'value' => $val,
                    'secondaryValue' => $secVal,
                    'totalValue' => $totalVal,
                    'formatted' => ($metric === 'sales') ? 'PKR '.number_format($val) : $val.' Eve',
                    'secondaryFormatted' => ($metric === 'sales') ? 'PKR '.number_format($secVal) : $secVal.' Aft',
                ];
            }
        } elseif ($timeframe === 'YEARLY') {
            for ($m = 1; $m <= 12; $m++) {
                $mDate = Carbon::create($now->year, $m, 1);
                $baseQ = Booking::whereBetween('event_date', [$mDate->copy()->startOfMonth()->format('Y-m-d'), $mDate->copy()->endOfMonth()->format('Y-m-d')])
                    ->whereIn('status', ['CONFIRMED', 'COMPLETED', 'HOLD']);

                if ($tenantId) {
                    $baseQ->where('tenant_id', $tenantId);
                } else {
                    $baseQ->whereRaw('1 = 0');
                }

                if ($hallId !== 'ALL' && in_array($hallId, $allowedHallIds)) {
                    $baseQ->where('hall_id', $hallId);
                } elseif (! empty($allowedHallIds)) {
                    $baseQ->whereIn('hall_id', $allowedHallIds);
                }

                $eveningQ = (clone $baseQ)->where('slot_type', 'EVENING');
                $afternoonQ = (clone $baseQ)->where('slot_type', 'AFTERNOON');

                $val = ($metric === 'sales') ? (float) $eveningQ->sum('grand_total') : (int) $eveningQ->count();
                $secVal = ($metric === 'sales') ? (float) $afternoonQ->sum('grand_total') : (int) $afternoonQ->count();
                $totalVal = ($metric === 'sales') ? (float) $baseQ->sum('grand_total') : (int) $baseQ->count();

                $points[] = [
                    'label' => $mDate->format('M'),
                    'fullLabel' => $mDate->format('F Y'),
                    'value' => $val,
                    'secondaryValue' => $secVal,
                    'totalValue' => $totalVal,
                    'formatted' => ($metric === 'sales') ? 'PKR '.number_format($val) : $val.' Eve',
                    'secondaryFormatted' => ($metric === 'sales') ? 'PKR '.number_format($secVal) : $secVal.' Aft',
                ];
            }
        } else {
            $start = $now->copy()->startOfMonth();
            for ($w = 1; $w <= 4; $w++) {
                $wStart = $start->copy()->addDays(($w - 1) * 7);
                $wEnd = ($w === 4) ? $now->copy()->endOfMonth() : $wStart->copy()->addDays(6);

                $baseQ = Booking::whereBetween('event_date', [$wStart->format('Y-m-d'), $wEnd->format('Y-m-d')])
                    ->whereIn('status', ['CONFIRMED', 'COMPLETED', 'HOLD']);

                if ($tenantId) {
                    $baseQ->where('tenant_id', $tenantId);
                } else {
                    $baseQ->whereRaw('1 = 0');
                }

                if ($hallId !== 'ALL' && in_array($hallId, $allowedHallIds)) {
                    $baseQ->where('hall_id', $hallId);
                } elseif (! empty($allowedHallIds)) {
                    $baseQ->whereIn('hall_id', $allowedHallIds);
                }

                $eveningQ = (clone $baseQ)->where('slot_type', 'EVENING');
                $afternoonQ = (clone $baseQ)->where('slot_type', 'AFTERNOON');

                $val = ($metric === 'sales') ? (float) $eveningQ->sum('grand_total') : (int) $eveningQ->count();
                $secVal = ($metric === 'sales') ? (float) $afternoonQ->sum('grand_total') : (int) $afternoonQ->count();
                $totalVal = ($metric === 'sales') ? (float) $baseQ->sum('grand_total') : (int) $baseQ->count();

                $points[] = [
                    'label' => 'Wk '.$w,
                    'fullLabel' => 'Week '.$w.' ('.$wStart->format('d M').' - '.$wEnd->format('d M').')',
                    'value' => $val,
                    'secondaryValue' => $secVal,
                    'totalValue' => $totalVal,
                    'formatted' => ($metric === 'sales') ? 'PKR '.number_format($val) : $val.' Eve',
                    'secondaryFormatted' => ($metric === 'sales') ? 'PKR '.number_format($secVal) : $secVal.' Aft',
                ];
            }
        }

        $allValues = [];
        foreach ($points as $p) {
            $allValues[] = $p['value'];
            $allValues[] = $p['secondaryValue'];
        }
        $maxValue = max(1, ...$allValues);

        $peakIndex = 0;
        $highestCombined = -1;
        foreach ($points as $idx => $p) {
            $combined = $p['value'] + $p['secondaryValue'];
            if ($combined > $highestCombined) {
                $highestCombined = $combined;
                $peakIndex = $idx;
            }
        }

        foreach ($points as $idx => &$p) {
            $p['percentage'] = round(($p['value'] / $maxValue) * 100);
            $p['secondaryPercentage'] = round(($p['secondaryValue'] / $maxValue) * 100);
            $p['isPeak'] = ($idx === $peakIndex && $highestCombined > 0);
            if ($metric === 'sales') {
                $p['peakPill'] = 'PKR '.number_format($p['totalValue'] / 1000).'k';
            } else {
                $p['peakPill'] = $p['totalValue'].' Functions';
            }
        }

        return $points;
    }

    private function buildWaveData(string $timeframe, string $hallId, array $allowedHallIds = [], ?string $tenantId = null): array
    {
        $now = Carbon::now();
        $buckets = [];

        if ($timeframe === 'WEEKLY') {
            $start = $now->copy()->startOfWeek();
            for ($i = 0; $i < 7; $i++) {
                $day = $start->copy()->addDays($i);
                $dStr = $day->format('Y-m-d');
                $buckets[] = [
                    'label' => $day->format('D'),
                    'start' => $dStr,
                    'end' => $dStr,
                ];
            }
        } elseif ($timeframe === 'YEARLY') {
            for ($m = 1; $m <= 12; $m++) {
                $mDate = Carbon::create($now->year, $m, 1);
                $buckets[] = [
                    'label' => $mDate->format('M'),
                    'start' => $mDate->copy()->startOfMonth()->format('Y-m-d'),
                    'end' => $mDate->copy()->endOfMonth()->format('Y-m-d'),
                ];
            }
        } else {
            $start = $now->copy()->startOfMonth();
            for ($w = 1; $w <= 4; $w++) {
                $wStart = $start->copy()->addDays(($w - 1) * 7);
                $wEnd = ($w === 4) ? $now->copy()->endOfMonth() : $wStart->copy()->addDays(6);
                $buckets[] = [
                    'label' => 'Wk '.$w,
                    'start' => $wStart->format('Y-m-d'),
                    'end' => $wEnd->format('Y-m-d'),
                ];
            }
        }

        $inflowVals = [];
        $expenseVals = [];
        $pointsCount = count($buckets);

        foreach ($buckets as $b) {
            $bQ = Booking::whereBetween('event_date', [$b['start'], $b['end']])
                ->whereIn('status', ['CONFIRMED', 'COMPLETED', 'HOLD']);
            if ($tenantId) {
                $bQ->where('tenant_id', $tenantId);
            } else {
                $bQ->whereRaw('1 = 0');
            }
            if ($hallId !== 'ALL' && in_array($hallId, $allowedHallIds)) {
                $bQ->where('hall_id', $hallId);
            } elseif (! empty($allowedHallIds)) {
                $bQ->whereIn('hall_id', $allowedHallIds);
            }
            $inf = (float) $bQ->sum('grand_total');

            $eQ = Expense::whereBetween('expense_date', [$b['start'], $b['end']]);
            if ($tenantId) {
                $eQ->where('tenant_id', $tenantId);
            } else {
                $eQ->whereRaw('1 = 0');
            }
            if ($hallId !== 'ALL' && in_array($hallId, $allowedHallIds)) {
                $eQ->where('hall_id', $hallId);
            } elseif (! empty($allowedHallIds)) {
                $eQ->whereIn('hall_id', $allowedHallIds);
            }
            $exp = (float) $eQ->sum('amount');

            $inflowVals[] = $inf;
            $expenseVals[] = $exp;
        }

        $maxVal = max(1, ...$inflowVals, ...$expenseVals);
        $chartW = 600;
        $topPad = 20;
        $bottomBase = 120;
        $usableH = $bottomBase - $topPad;
        $stepX = ($pointsCount > 1) ? ($chartW - 60) / ($pointsCount - 1) : 0;

        $inflowCoords = [];
        $expenseCoords = [];

        for ($i = 0; $i < $pointsCount; $i++) {
            $x = round(30 + ($i * $stepX), 1);
            $yInflow = round($bottomBase - (($inflowVals[$i] / $maxVal) * $usableH), 1);
            $yExpense = round($bottomBase - (($expenseVals[$i] / $maxVal) * $usableH), 1);

            $inflowCoords[] = [
                'x' => $x,
                'y' => $yInflow,
                'val' => $inflowVals[$i],
                'label' => $buckets[$i]['label'],
                'formatted' => 'PKR '.number_format($inflowVals[$i]),
            ];
            $expenseCoords[] = [
                'x' => $x,
                'y' => $yExpense,
                'val' => $expenseVals[$i],
                'label' => $buckets[$i]['label'],
                'formatted' => 'PKR '.number_format($expenseVals[$i]),
            ];
        }

        $buildSpline = function (array $coords) use ($bottomBase) {
            if (empty($coords)) {
                return ['path' => '', 'area' => ''];
            }
            $path = "M {$coords[0]['x']} {$coords[0]['y']}";
            for ($i = 1; $i < count($coords); $i++) {
                $prev = $coords[$i - 1];
                $curr = $coords[$i];
                $cpx1 = round($prev['x'] + ($curr['x'] - $prev['x']) / 2, 1);
                $cpy1 = $prev['y'];
                $cpx2 = round($prev['x'] + ($curr['x'] - $prev['x']) / 2, 1);
                $cpy2 = $curr['y'];
                $path .= " C {$cpx1} {$cpy1}, {$cpx2} {$cpy2}, {$curr['x']} {$curr['y']}";
            }
            $lastX = end($coords)['x'];
            $firstX = $coords[0]['x'];
            $area = "{$path} L {$lastX} {$bottomBase} L {$firstX} {$bottomBase} Z";

            return ['path' => $path, 'area' => $area];
        };

        $inflowSpline = $buildSpline($inflowCoords);
        $expenseSpline = $buildSpline($expenseCoords);

        return [
            'inflowPath' => $inflowSpline['path'],
            'inflowArea' => $inflowSpline['area'],
            'expensePath' => $expenseSpline['path'],
            'expenseArea' => $expenseSpline['area'],
            'inflowCoords' => $inflowCoords,
            'expenseCoords' => $expenseCoords,
            'totalInflow' => array_sum($inflowVals),
            'totalExpense' => array_sum($expenseVals),
        ];
    }

    private function buildMiniCalendar(?string $tenantId = null, array $allowedHallIds = [], string $selectedHallId = 'ALL'): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $daysInMonth = $now->daysInMonth;

        $leadBlanks = ($startOfMonth->dayOfWeekIso - 1);

        $bookingsQ = Booking::whereBetween('event_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->whereIn('status', ['CONFIRMED', 'COMPLETED', 'HOLD']);
        if ($tenantId) {
            $bookingsQ->where('tenant_id', $tenantId);
        } else {
            $bookingsQ->whereRaw('1 = 0');
        }
        if ($selectedHallId !== 'ALL' && in_array($selectedHallId, $allowedHallIds)) {
            $bookingsQ->where('hall_id', $selectedHallId);
        } elseif (! empty($allowedHallIds)) {
            $bookingsQ->whereIn('hall_id', $allowedHallIds);
        }
        $monthBookings = $bookingsQ->get();

        $days = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = $startOfMonth->copy()->addDays($d - 1);
            $dStr = $date->format('Y-m-d');
            $dayBookings = $monthBookings->filter(fn ($b) => Carbon::parse($b->event_date)->format('Y-m-d') === $dStr);

            $days[] = [
                'day' => $d,
                'date' => $dStr,
                'isToday' => ($d === $now->day),
                'hasEvening' => $dayBookings->contains('slot_type', 'EVENING'),
                'hasAfternoon' => $dayBookings->contains('slot_type', 'AFTERNOON'),
                'count' => $dayBookings->count(),
            ];
        }

        $nextBookingQ = Booking::with('hall')
            ->whereDate('event_date', '>=', $now->format('Y-m-d'))
            ->whereIn('status', ['CONFIRMED', 'HOLD']);
        if ($tenantId) {
            $nextBookingQ->where('tenant_id', $tenantId);
        } else {
            $nextBookingQ->whereRaw('1 = 0');
        }
        if ($selectedHallId !== 'ALL' && in_array($selectedHallId, $allowedHallIds)) {
            $nextBookingQ->where('hall_id', $selectedHallId);
        } elseif (! empty($allowedHallIds)) {
            $nextBookingQ->whereIn('hall_id', $allowedHallIds);
        }
        $nextBooking = $nextBookingQ->orderBy('event_date')->first();

        return [
            'monthName' => $now->format('F Y'),
            'leadBlanks' => $leadBlanks,
            'days' => $days,
            'nextBooking' => $nextBooking ? [
                'date' => Carbon::parse($nextBooking->event_date)->format('d M'),
                'slot' => ucfirst(strtolower($nextBooking->slot_type ?? 'Evening')),
                'hall' => $nextBooking->hall->name ?? 'Ballroom',
                'client' => $nextBooking->customer_name ?? 'Client',
            ] : null,
        ];
    }
}
