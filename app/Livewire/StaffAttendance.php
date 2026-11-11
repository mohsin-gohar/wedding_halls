<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\StaffAttendance as AttendanceRecord;
use App\Models\StaffLeave;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Staff Attendance & Leave Management — VenueCloud ERP'])]
class StaffAttendance extends Component
{
    public string $activeTab = 'daily'; // daily, leaves, monthly

    public string $selectedDate = '';

    public string $selectedMonth = '';

    public string $selectedStaffId = 'ALL';

    // Daily attendance rows: [user_id => ['status' => 'PRESENT', 'check_in' => '10:00', 'check_out' => '22:00', 'overtime_hours' => 0, 'notes' => '']]
    public array $attendanceData = [];

    // Leave request modal state
    public bool $showLeaveModal = false;

    public ?int $leaveUserId = null;

    public string $leaveType = 'CASUAL';

    public string $leaveStartDate = '';

    public string $leaveEndDate = '';

    public string $leaveReason = '';

    // Quick Edit Attendance Record Modal
    public bool $showEditRecordModal = false;

    public ?int $editingUserId = null;

    public string $editingStaffName = '';

    public string $editingRecordDate = '';

    public string $editingStatus = 'PRESENT';

    public string $editingCheckIn = '10:00';

    public string $editingCheckOut = '22:00';

    public float $editingOvertime = 0.0;

    public string $editingNotes = '';

    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public function mount()
    {
        $this->authorizeAttendanceManager();
        $this->selectedDate = Carbon::today()->format('Y-m-d');
        $this->selectedMonth = Carbon::today()->format('Y-m');
        $this->leaveStartDate = Carbon::today()->format('Y-m-d');
        $this->leaveEndDate = Carbon::today()->format('Y-m-d');

        if (request()->has('staff_id')) {
            $this->selectedStaffId = (string) request()->query('staff_id');
        }

        $this->loadAttendanceForDate();
    }

    private function authorizeAttendanceManager(): void
    {
        $user = Auth::user();
        if (! $user || (! $user->isTenantOwner() && ! $user->isManager() && ! $user->isAccountant()) || ! $user->tenant_id) {
            abort(403, 'Unauthorized. Only Venue Owners or Managers have access to Attendance ERP.');
        }
    }

    public function setTab(string $tab)
    {
        $this->authorizeAttendanceManager();
        $this->activeTab = $tab;
        $this->feedbackMessage = null;
        $this->errorMessage = null;
    }

    public function updatedSelectedDate()
    {
        $this->loadAttendanceForDate();
    }

    public function updatedSelectedStaffId(): void
    {
        $this->loadAttendanceForDate();
    }

    public function previousMonth(): void
    {
        $this->selectedMonth = $this->normalizedSelectedMonth()->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->selectedMonth = $this->normalizedSelectedMonth()->addMonth()->format('Y-m');
    }

    private function normalizedSelectedMonth(): Carbon
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

    public function loadAttendanceForDate()
    {
        $this->authorizeAttendanceManager();
        $tenantId = Auth::user()->tenant_id;
        $staffQuery = User::hallStaff($tenantId)
            ->where('status', '!=', 'SUSPENDED');
        if ($this->selectedStaffId !== 'ALL') {
            $staffQuery->whereKey($this->selectedStaffId);
        }
        $staff = $staffQuery->get();

        $existing = AttendanceRecord::where('tenant_id', $tenantId)
            ->whereDate('date', $this->selectedDate)
            ->get()
            ->keyBy('user_id');

        $data = [];
        foreach ($staff as $member) {
            if ($existing->has($member->id)) {
                $rec = $existing->get($member->id);
                $data[$member->id] = [
                    'status' => $rec->status,
                    'check_in' => $rec->check_in ? substr($rec->check_in, 0, 5) : '10:00',
                    'check_out' => $rec->check_out ? substr($rec->check_out, 0, 5) : '22:00',
                    'overtime_hours' => (float) $rec->overtime_hours,
                    'notes' => $rec->notes ?? '',
                ];
            } else {
                $data[$member->id] = [
                    'status' => 'PRESENT',
                    'check_in' => '10:00',
                    'check_out' => '22:00',
                    'overtime_hours' => 0.0,
                    'notes' => '',
                ];
            }
        }

        $this->attendanceData = $data;
    }

    public function markAllPresent()
    {
        $this->authorizeAttendanceManager();
        foreach ($this->attendanceData as $userId => $val) {
            $this->attendanceData[$userId]['status'] = 'PRESENT';
        }
        $this->feedbackMessage = 'Marked all team members as Present for '.$this->selectedDate;
    }

    public function saveDailyAttendance()
    {
        $this->authorizeAttendanceManager();
        $tenantId = Auth::user()->tenant_id;
        $staffIds = User::hallStaff($tenantId)
            ->where('status', '!=', 'SUSPENDED')
            ->when($this->selectedStaffId !== 'ALL', fn ($query) => $query->whereKey($this->selectedStaffId))
            ->pluck('id')
            ->all();

        $this->validate([
            'selectedDate' => 'required|date_format:Y-m-d',
            'attendanceData' => 'array',
        ]);

        foreach ($this->attendanceData as $userId => $data) {
            if (! in_array((int) $userId, $staffIds, true)) {
                continue;
            }

            validator($data, [
                'status' => 'required|in:PRESENT,ABSENT,HALF_DAY,LATE,ON_LEAVE',
                'check_in' => 'nullable|date_format:H:i',
                'check_out' => 'nullable|date_format:H:i',
                'overtime_hours' => 'nullable|numeric|min:0|max:24',
                'notes' => 'nullable|string|max:1000',
            ])->validate();

            $record = AttendanceRecord::where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->whereDate('date', $this->selectedDate)
                ->first();

            $recordData = [
                'status' => $data['status'] ?? 'PRESENT',
                'check_in' => ! empty($data['check_in']) ? $data['check_in'] : null,
                'check_out' => ! empty($data['check_out']) ? $data['check_out'] : null,
                'overtime_hours' => (float) ($data['overtime_hours'] ?? 0),
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ];

            if ($record) {
                $record->update($recordData);
            } else {
                try {
                    AttendanceRecord::create(array_merge([
                        'tenant_id' => $tenantId,
                        'user_id' => $userId,
                        'date' => $this->selectedDate,
                    ], $recordData));
                } catch (UniqueConstraintViolationException $e) {
                    $record = AttendanceRecord::where('tenant_id', $tenantId)
                        ->where('user_id', $userId)
                        ->whereDate('date', $this->selectedDate)
                        ->first();
                    if ($record) {
                        $record->update($recordData);
                    }
                }
            }
        }

        AuditLog::log(
            'ATTENDANCE_LOGGED',
            "Attendance successfully logged for date [{$this->selectedDate}].",
            $tenantId,
            'StaffAttendance',
            $this->selectedDate
        );

        $this->feedbackMessage = 'Daily attendance recorded successfully for '.Carbon::parse($this->selectedDate)->format('d M Y').'.';
    }

    public function selectStaff(string $staffId): void
    {
        $this->selectedStaffId = $staffId;
        $this->loadAttendanceForDate();
    }

    public function openEditRecordModal(int $userId, ?string $recordDate = null): void
    {
        $this->authorizeAttendanceManager();
        $date = $recordDate ?? $this->selectedDate;
        $user = User::where('tenant_id', Auth::user()->tenant_id)->findOrFail($userId);

        $rec = AttendanceRecord::where('tenant_id', Auth::user()->tenant_id)
            ->where('user_id', $userId)
            ->whereDate('date', $date)
            ->first();

        $this->editingUserId = $userId;
        $this->editingStaffName = $user->name;
        $this->editingRecordDate = $date;
        $this->editingStatus = $rec?->status ?? 'PRESENT';
        $this->editingCheckIn = $rec?->check_in ? substr($rec->check_in, 0, 5) : '10:00';
        $this->editingCheckOut = $rec?->check_out ? substr($rec->check_out, 0, 5) : '22:00';
        $this->editingOvertime = $rec ? (float) $rec->overtime_hours : 0.0;
        $this->editingNotes = $rec?->notes ?? '';
        $this->showEditRecordModal = true;
    }

    public function closeEditRecordModal(): void
    {
        $this->showEditRecordModal = false;
        $this->editingUserId = null;
    }

    public function saveEditedRecord(): void
    {
        $this->authorizeAttendanceManager();
        $tenantId = Auth::user()->tenant_id;
        $this->validate([
            'editingUserId' => ['required', Rule::exists('users', 'id')->where('tenant_id', $tenantId)],
            'editingRecordDate' => 'required|date_format:Y-m-d',
            'editingStatus' => 'required|in:PRESENT,ABSENT,HALF_DAY,LATE,ON_LEAVE',
            'editingCheckIn' => 'nullable|date_format:H:i',
            'editingCheckOut' => 'nullable|date_format:H:i',
            'editingOvertime' => 'nullable|numeric|min:0|max:24',
            'editingNotes' => 'nullable|string|max:1000',
        ]);

        $record = AttendanceRecord::where('tenant_id', $tenantId)
            ->where('user_id', $this->editingUserId)
            ->whereDate('date', $this->editingRecordDate)
            ->first();

        $recordData = [
            'status' => $this->editingStatus,
            'check_in' => ! empty($this->editingCheckIn) ? $this->editingCheckIn : null,
            'check_out' => ! empty($this->editingCheckOut) ? $this->editingCheckOut : null,
            'overtime_hours' => $this->editingOvertime,
            'notes' => $this->editingNotes ?: null,
            'created_by' => Auth::id(),
        ];

        if ($record) {
            $record->update($recordData);
        } else {
            try {
                AttendanceRecord::create(array_merge([
                    'tenant_id' => $tenantId,
                    'user_id' => $this->editingUserId,
                    'date' => $this->editingRecordDate,
                ], $recordData));
            } catch (UniqueConstraintViolationException $e) {
                $record = AttendanceRecord::where('tenant_id', $tenantId)
                    ->where('user_id', $this->editingUserId)
                    ->whereDate('date', $this->editingRecordDate)
                    ->first();
                if ($record) {
                    $record->update($recordData);
                }
            }
        }

        $this->loadAttendanceForDate();
        $this->showEditRecordModal = false;
        $this->feedbackMessage = "Attendance record for {$this->editingStaffName} ({$this->editingRecordDate}) updated successfully!";
    }

    // ==========================================
    // LEAVE MANAGEMENT
    // ==========================================
    public function openLeaveModal()
    {
        $this->authorizeAttendanceManager();
        $firstStaff = User::hallStaff(Auth::user()->tenant_id)->orderBy('name')->first();
        $this->leaveUserId = $firstStaff ? $firstStaff->id : null;
        $this->leaveType = 'CASUAL';
        $this->leaveStartDate = Carbon::today()->format('Y-m-d');
        $this->leaveEndDate = Carbon::today()->format('Y-m-d');
        $this->leaveReason = '';
        $this->showLeaveModal = true;
    }

    public function closeLeaveModal()
    {
        $this->authorizeAttendanceManager();
        $this->showLeaveModal = false;
    }

    public function submitLeave()
    {
        $this->authorizeAttendanceManager();
        $tenantId = Auth::user()->tenant_id;
        $this->validate([
            'leaveUserId' => ['required', Rule::exists('users', 'id')->where('tenant_id', $tenantId)],
            'leaveType' => 'required|in:CASUAL,SICK,ANNUAL,EMERGENCY',
            'leaveStartDate' => 'required|date',
            'leaveEndDate' => 'required|date|after_or_equal:leaveStartDate',
            'leaveReason' => 'required|string|min:5|max:500',
        ]);

        $start = Carbon::parse($this->leaveStartDate);
        $end = Carbon::parse($this->leaveEndDate);
        $days = $start->diffInDays($end) + 1;

        StaffLeave::create([
            'tenant_id' => Auth::user()->tenant_id,
            'user_id' => $this->leaveUserId,
            'leave_type' => $this->leaveType,
            'start_date' => $this->leaveStartDate,
            'end_date' => $this->leaveEndDate,
            'days' => $days,
            'reason' => $this->leaveReason,
            'status' => 'PENDING',
        ]);

        $this->showLeaveModal = false;
        $this->feedbackMessage = "Leave application for {$days} day(s) submitted successfully.";
    }

    public function approveLeave(string $leaveId)
    {
        $this->authorizeAttendanceManager();
        $leave = StaffLeave::where('tenant_id', Auth::user()->tenant_id)->findOrFail($leaveId);
        $leave->update([
            'status' => 'APPROVED',
            'action_by' => Auth::id(),
        ]);

        $this->feedbackMessage = "Leave application approved for [{$leave->user->name}].";
    }

    public function rejectLeave(string $leaveId)
    {
        $this->authorizeAttendanceManager();
        $leave = StaffLeave::where('tenant_id', Auth::user()->tenant_id)->findOrFail($leaveId);
        $leave->update([
            'status' => 'REJECTED',
            'action_by' => Auth::id(),
        ]);

        $this->feedbackMessage = 'Leave application rejected.';
    }

    public function render()
    {
        $this->authorizeAttendanceManager();
        $tenantId = Auth::user()->tenant_id;

        $staffOptions = User::hallStaff($tenantId)
            ->where('status', '!=', 'SUSPENDED')
            ->orderBy('name')
            ->get();

        $staffQuery = User::hallStaff($tenantId)
            ->where('status', '!=', 'SUSPENDED');
        if ($this->selectedStaffId !== 'ALL') {
            $staffQuery->whereKey($this->selectedStaffId);
        }
        $staff = $staffQuery->orderBy('name')->get();

        $leaves = StaffLeave::where('tenant_id', $tenantId)
            ->with(['user', 'actionBy'])
            ->latest()
            ->paginate(15);

        // Monthly summary calculations
        $monthStart = $this->normalizedSelectedMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $monthlyRecords = AttendanceRecord::where('tenant_id', $tenantId)
            ->whereDate('date', '>=', $monthStart->format('Y-m-d'))
            ->whereDate('date', '<=', $monthEnd->format('Y-m-d'))
            ->get();

        $summary = [];
        foreach ($staff as $m) {
            $userRecords = $monthlyRecords->where('user_id', $m->id);
            $presentCount = $userRecords->where('status', 'PRESENT')->count();
            $halfDayCount = $userRecords->where('status', 'HALF_DAY')->count();
            $absentCount = $userRecords->where('status', 'ABSENT')->count();
            $leaveCount = $userRecords->where('status', 'ON_LEAVE')->count();
            $totalOt = $userRecords->sum('overtime_hours');

            $summary[$m->id] = [
                'name' => $m->name,
                'role' => $m->role,
                'present' => $presentCount,
                'half_day' => $halfDayCount,
                'absent' => $absentCount,
                'leave' => $leaveCount,
                'overtime_hours' => $totalOt,
            ];
        }

        return view('livewire.staff-attendance', [
            'staff' => $staff,
            'staffOptions' => $staffOptions,
            'leaves' => $leaves,
            'summary' => $summary,
        ]);
    }
}
