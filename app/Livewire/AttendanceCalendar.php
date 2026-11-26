<?php

namespace App\Livewire;

use App\Models\AttendanceHoliday;
use App\Models\AuditLog;
use App\Models\StaffAttendance as AttendanceRecord;
use App\Models\StaffLeave;
use App\Models\Tenant;
use App\Models\User;
use App\Support\PermissionMatrix;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Attendance Calendar — Riwaj Grand ERP'])]
class AttendanceCalendar extends Component
{
    public string $selectedMonth = '';

    public string $selectedStaffId = 'ALL';

    public bool $showHolidayModal = false;

    public string $holidayDate = '';

    public string $holidayName = '';

    public string $holidayType = 'PUBLIC';

    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    /** @var array<int, int> */
    public array $weeklyOffDays = [0];

    public function mount(): void
    {
        $this->authorizeManager();
        $this->selectedMonth = Carbon::today()->format('Y-m');
        $this->weeklyOffDays = Tenant::find(Auth::user()->tenant_id)?->weeklyOffDays() ?? [0];
    }

    public function saveWeeklyOffs(): void
    {
        $this->authorizeManager();
        $tenant = Tenant::findOrFail(Auth::user()->tenant_id);
        $days = array_values(array_unique(array_map('intval', $this->weeklyOffDays)));
        $days = array_values(array_filter($days, fn (int $day) => $day >= 0 && $day <= 6));

        $previous = $tenant->weeklyOffDays();
        if ($previous === $days) {
            $this->weeklyOffDays = $days;
            $this->feedbackMessage = 'Weekly off is unchanged.';

            return;
        }

        $tenant->update(['weekly_off_days' => $days]);
        $this->weeklyOffDays = $days;

        AuditLog::log(
            'WEEKLY_OFF_UPDATED',
            'Weekly off changed from ['.implode(',', $previous).'] to ['.implode(',', $days).'].',
            $tenant->id,
            'Tenant',
            $tenant->id
        );

        $this->feedbackMessage = $days === []
            ? 'Weekly off cleared. No weekday is treated as off.'
            : 'Weekly off saved for this venue.';
    }

    private function authorizeManager(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->tenant_id || ! PermissionMatrix::userCan($user, 'staff.attendance')) {
            abort(403, 'Unauthorized. Staff attendance permission is required.');
        }
    }

    private function month(): Carbon
    {
        try {
            $month = Carbon::createFromFormat('!Y-m', $this->selectedMonth);
            if (! $month || $month->format('Y-m') !== $this->selectedMonth || $month->year < 2000 || $month->year > 2100) {
                throw new \InvalidArgumentException;
            }
        } catch (\Throwable) {
            $this->selectedMonth = Carbon::today()->format('Y-m');
            $month = Carbon::today()->startOfMonth();
        }

        return $month->startOfMonth();
    }

    public function previousMonth(): void
    {
        $this->selectedMonth = $this->month()->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->selectedMonth = $this->month()->addMonth()->format('Y-m');
    }

    public function goToToday(): void
    {
        $this->selectedMonth = Carbon::today()->format('Y-m');
    }

    public function updatedSelectedStaffId(): void
    {
        // Livewire re-renders automatically; hook kept for explicit intent.
    }

    public function openHolidayModal(?string $date = null): void
    {
        $this->authorizeManager();
        $this->holidayDate = $date ?: $this->month()->format('Y-m-d');
        $this->holidayName = '';
        $this->holidayType = 'PUBLIC';
        $this->showHolidayModal = true;
    }

    public function closeHolidayModal(): void
    {
        $this->showHolidayModal = false;
    }

    public function addHoliday(): void
    {
        $this->authorizeManager();
        $tenantId = Auth::user()->tenant_id;

        $this->validate([
            'holidayDate' => 'required|date_format:Y-m-d',
            'holidayName' => 'required|string|min:3|max:120',
            'holidayType' => ['required', Rule::in(['PUBLIC', 'COMPANY'])],
        ]);

        try {
            AttendanceHoliday::create([
                'tenant_id' => $tenantId,
                'date' => $this->holidayDate,
                'name' => $this->holidayName,
                'type' => $this->holidayType,
                'created_by' => Auth::id(),
            ]);
        } catch (UniqueConstraintViolationException $e) {
            $this->addError('holidayDate', 'A holiday is already configured for this date.');

            return;
        }

        AuditLog::log('HOLIDAY_ADDED', "Holiday [{$this->holidayName}] added on {$this->holidayDate}.", $tenantId, 'AttendanceHoliday', $this->holidayDate);
        $this->showHolidayModal = false;
        $this->feedbackMessage = "Holiday '{$this->holidayName}' added for {$this->holidayDate}.";
    }

    public function deleteHoliday(string $holidayId): void
    {
        $this->authorizeManager();
        $tenantId = Auth::user()->tenant_id;
        $holiday = AttendanceHoliday::where('tenant_id', $tenantId)->findOrFail($holidayId);
        $holiday->delete();

        AuditLog::log('HOLIDAY_REMOVED', "Holiday [{$holiday->name}] removed from {$holiday->date->format('Y-m-d')}.", $tenantId, 'AttendanceHoliday', $holidayId);
        $this->feedbackMessage = "Holiday '{$holiday->name}' removed.";
    }

    public function render()
    {
        $this->authorizeManager();
        $tenantId = Auth::user()->tenant_id;

        $staffQuery = User::hallStaff($tenantId)->where('status', '!=', 'SUSPENDED');
        if ($this->selectedStaffId !== 'ALL') {
            $staffQuery->whereKey($this->selectedStaffId);
        }
        $staff = $staffQuery->orderBy('name')->get();

        $start = $this->month();
        $end = $start->copy()->endOfMonth();

        $days = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $days[] = $cursor->copy();
            $cursor->addDay();
        }

        $records = AttendanceRecord::where('tenant_id', $tenantId)
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get()
            ->keyBy(fn ($r) => $r->user_id.'_'.$r->date->format('Y-m-d'));

        // Approved leave days, expanded into a lookup so they are visible even
        // if a legacy record was never created by the leave-approval flow.
        $leaveDays = [];
        StaffLeave::where('tenant_id', $tenantId)
            ->where('status', 'APPROVED')
            ->where('start_date', '<=', $end->format('Y-m-d'))
            ->where('end_date', '>=', $start->format('Y-m-d'))
            ->get()
            ->each(function ($leave) use (&$leaveDays) {
                $day = Carbon::parse($leave->start_date)->startOfDay();
                $last = Carbon::parse($leave->end_date)->startOfDay();
                while ($day->lte($last)) {
                    $leaveDays[$leave->user_id.'_'.$day->format('Y-m-d')] = $leave->leave_type;
                    $day->addDay();
                }
            });

        $weeklyOffs = Tenant::find($tenantId)?->weeklyOffDays() ?? [0];

        $holidays = AttendanceHoliday::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get()
            ->keyBy(fn ($h) => $h->date->format('Y-m-d'));

        $cells = [];
        $summary = [];
        foreach ($staff as $member) {
            foreach ($days as $day) {
                $date = $day->format('Y-m-d');
                $key = $member->id.'_'.$date;
                $record = $records->get($key);
                $holiday = $holidays->get($date);

                if ($record) {
                    $status = $record->status;
                    $detail = trim(($record->check_in ? substr($record->check_in, 0, 5) : '').' '.($record->check_out ? '- '.substr($record->check_out, 0, 5) : ''));
                } elseif ($holiday) {
                    $status = 'HOLIDAY';
                    $detail = $holiday->name;
                } elseif (isset($leaveDays[$key])) {
                    $status = 'ON_LEAVE';
                    $detail = $leaveDays[$key];
                } elseif (in_array($day->dayOfWeek, $weeklyOffs, true)) {
                    $status = 'WEEKLY_OFF';
                    $detail = 'Weekly off';
                } else {
                    $status = 'NOT_MARKED';
                    $detail = '';
                }

                $cells[$key] = ['status' => $status, 'detail' => $detail];
                $summary[$status] = ($summary[$status] ?? 0) + 1;
            }
        }

        return view('livewire.attendance-calendar', [
            'staff' => $staff,
            'days' => $days,
            'cells' => $cells,
            'summary' => $summary,
            'holidays' => $holidays,
            'monthTitle' => $start->format('F Y'),
        ]);
    }
}
