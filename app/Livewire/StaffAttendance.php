<?php

namespace App\Livewire;

use App\Models\AttendanceCorrection;
use App\Models\AuditLog;
use App\Models\StaffAttendance as AttendanceRecord;
use App\Models\StaffLeave;
use App\Models\StaffPayroll;
use App\Models\User;
use App\Support\PermissionMatrix;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Staff Attendance & Leave Management — Riwaj Grand ERP'])]
class StaffAttendance extends Component
{
    /** Single source of truth for manual overtime entry, shared with the Blade UI. */
    public const MAX_OVERTIME_HOURS = 12;

    public string $activeTab = 'daily'; // daily, leaves, monthly

    public string $selectedDate = '';

    public string $selectedMonth = '';

    public string $selectedStaffId = 'ALL';

    // Daily attendance rows: [user_id => ['status' => '', 'check_in' => '', 'check_out' => '', 'overtime_hours' => 0, 'notes' => '']]
    // Empty status = NOT MARKED. An untouched row must never be saved as PRESENT.
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

    public string $editingStatus = '';

    public string $editingCheckIn = '';

    public string $editingCheckOut = '';

    public float $editingOvertime = 0.0;

    public string $editingNotes = '';

    public bool $editingOvernight = false;

    public string $editingCorrectionReason = '';

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

        $requestedDate = request()->query('date');
        if (is_string($requestedDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedDate) && Carbon::canBeCreatedFromFormat($requestedDate, 'Y-m-d')) {
            $this->selectedDate = $requestedDate;
            $this->selectedMonth = Carbon::parse($requestedDate)->format('Y-m');
        }

        $this->loadAttendanceForDate();
    }

    private function authorizeAttendanceManager(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->tenant_id || ! PermissionMatrix::userCan($user, 'staff.attendance')) {
            abort(403, 'Unauthorized. Attendance management requires staff.attendance within a tenant.');
        }
    }

    /** Finalised payroll locks its source attendance period. */
    private function periodLocked(string $date): bool
    {
        return StaffPayroll::where('tenant_id', Auth::user()->tenant_id)
            ->where('month_year', Carbon::parse($date)->format('Y-m'))
            ->whereIn('status', ['APPROVED', 'PAID'])
            ->exists();
    }

    private function checkOutSequenceError(?string $checkIn, ?string $checkOut, bool $overnight): ?string
    {
        if (empty($checkIn) || empty($checkOut)) {
            return null;
        }

        if ($overnight) {
            return $checkOut === $checkIn
                ? 'Overnight check-out must be a different time from check-in.'
                : null;
        }

        return $checkOut <= $checkIn
            ? 'Check-out must be after check-in. Mark the shift as overnight if it crosses midnight.'
            : null;
    }

    /**
     * @param  array<string, mixed>  $next
     */
    private function writeCorrection(AttendanceRecord $record, array $next, string $reason): void
    {
        $oldIn = $record->check_in ? substr((string) $record->check_in, 0, 5) : null;
        $oldOut = $record->check_out ? substr((string) $record->check_out, 0, 5) : null;
        $newIn = $next['check_in'] ? substr((string) $next['check_in'], 0, 5) : null;
        $newOut = $next['check_out'] ? substr((string) $next['check_out'], 0, 5) : null;

        $changed = $record->status !== $next['status']
            || $oldIn !== $newIn
            || $oldOut !== $newOut
            || (float) $record->overtime_hours !== (float) $next['overtime_hours'];

        if (! $changed) {
            return;
        }

        AttendanceCorrection::create([
            'tenant_id' => $record->tenant_id,
            'attendance_id' => $record->id,
            'user_id' => $record->user_id,
            'date' => $record->date->format('Y-m-d'),
            'old_status' => $record->status,
            'new_status' => $next['status'],
            'old_check_in' => $oldIn,
            'new_check_in' => $newIn,
            'old_check_out' => $oldOut,
            'new_check_out' => $newOut,
            'old_overtime' => $record->overtime_hours,
            'new_overtime' => $next['overtime_hours'],
            'reason' => $reason !== '' ? $reason : 'Attendance correction',
            'actor_id' => Auth::id(),
        ]);
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
                    'check_in' => $rec->check_in ? substr((string) $rec->check_in, 0, 5) : '',
                    'check_out' => $rec->check_out ? substr((string) $rec->check_out, 0, 5) : '',
                    'overtime_hours' => (float) $rec->overtime_hours,
                    'notes' => $rec->notes ?? '',
                    'overnight' => (bool) $rec->overnight,
                    'source' => $rec->source ?: 'RECORDED',
                ];
            } else {
                // No record yet: leave the row unmarked. The manager must make
                // an explicit choice (and provide a check-in for worked states).
                $data[$member->id] = [
                    'status' => '',
                    'check_in' => '',
                    'check_out' => '',
                    'overtime_hours' => 0.0,
                    'notes' => '',
                    'overnight' => false,
                    'source' => 'RECORDED',
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
            $this->attendanceData[$userId]['source'] = 'MANUAL_ATTESTATION';
        }
        $this->feedbackMessage = 'Manager attestation prepared for '.$this->selectedDate.'. No check-in or check-out times were invented. Save to record it.';
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

        if ($this->periodLocked($this->selectedDate)) {
            $this->errorMessage = 'Attendance for this month is locked because payroll has been finalised. Reopen the payroll to make corrections.';

            return;
        }

        $saved = 0;
        $skipped = 0;

        DB::transaction(function () use ($tenantId, $staffIds, &$saved, &$skipped) {
            foreach ($this->attendanceData as $userId => $data) {
                if (! in_array((int) $userId, $staffIds, true)) {
                    continue;
                }

                // Unmarked rows are NOT persisted. Opening/saving the register
                // cannot silently turn an employee into PRESENT.
                if (empty($data['status'])) {
                    $skipped++;

                    continue;
                }

                $workedState = in_array($data['status'], ['PRESENT', 'LATE', 'HALF_DAY'], true);
                $manualAttestation = ($data['source'] ?? '') === 'MANUAL_ATTESTATION' && empty($data['check_in']) && empty($data['check_out']);
                $overnight = (bool) ($data['overnight'] ?? false);
                $field = "attendanceData.{$userId}";
                $this->validate([
                    "{$field}.status" => 'required|in:PRESENT,ABSENT,HALF_DAY,LATE,ON_LEAVE',
                    "{$field}.check_in" => ($workedState && ! $manualAttestation) ? 'required|date_format:H:i' : 'nullable|date_format:H:i',
                    "{$field}.check_out" => [
                        'nullable',
                        'date_format:H:i',
                        function ($attribute, $value, $fail) use ($userId, $overnight) {
                            $message = $this->checkOutSequenceError(
                                $this->attendanceData[$userId]['check_in'] ?? null,
                                $value,
                                $overnight
                            );
                            if ($message) {
                                $fail($message);
                            }
                        },
                    ],
                    "{$field}.overtime_hours" => 'nullable|numeric|min:0|max:'.self::MAX_OVERTIME_HOURS,
                    "{$field}.notes" => 'nullable|string|max:1000',
                ]);

                $recordData = [
                    'status' => $data['status'],
                    'check_in' => ! empty($data['check_in']) ? $data['check_in'] : null,
                    'check_out' => ! empty($data['check_out']) ? $data['check_out'] : null,
                    'overtime_hours' => (float) ($data['overtime_hours'] ?? 0),
                    'notes' => $data['notes'] ?? null,
                    'source' => $manualAttestation ? 'MANUAL_ATTESTATION' : 'RECORDED',
                    'overnight' => $overnight,
                    'created_by' => Auth::id(),
                ];

                $record = AttendanceRecord::where('tenant_id', $tenantId)
                    ->where('user_id', $userId)
                    ->whereDate('date', $this->selectedDate)
                    ->first();

                if ($record) {
                    $this->writeCorrection($record, $recordData, $data['notes'] ?: 'Daily register update');
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
                            $this->writeCorrection($record, $recordData, $data['notes'] ?: 'Daily register update');
                            $record->update($recordData);
                        }
                    }
                }
                $saved++;
            }
        });

        AuditLog::log(
            'ATTENDANCE_LOGGED',
            "Attendance saved for date [{$this->selectedDate}]: {$saved} marked, {$skipped} left unmarked.",
            $tenantId,
            'StaffAttendance',
            $this->selectedDate
        );

        $this->loadAttendanceForDate();
        $this->feedbackMessage = 'Attendance saved for '.Carbon::parse($this->selectedDate)->format('d M Y')." ({$saved} marked".($skipped ? ", {$skipped} left unmarked" : '').').';
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
        $this->editingStatus = $rec?->status ?? '';
        $this->editingCheckIn = $rec?->check_in ? substr($rec->check_in, 0, 5) : '';
        $this->editingCheckOut = $rec?->check_out ? substr($rec->check_out, 0, 5) : '';
        $this->editingOvertime = $rec ? (float) $rec->overtime_hours : 0.0;
        $this->editingNotes = $rec?->notes ?? '';
        $this->editingOvernight = (bool) ($rec?->overnight);
        $this->editingCorrectionReason = '';
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
        $workedState = in_array($this->editingStatus, ['PRESENT', 'LATE', 'HALF_DAY'], true);
        $this->validate([
            'editingUserId' => ['required', Rule::exists('users', 'id')->where('tenant_id', $tenantId)],
            'editingRecordDate' => 'required|date_format:Y-m-d',
            'editingStatus' => 'required|in:PRESENT,ABSENT,HALF_DAY,LATE,ON_LEAVE',
            'editingCheckIn' => $workedState ? 'required|date_format:H:i' : 'nullable|date_format:H:i',
            'editingCheckOut' => [
                'nullable',
                'date_format:H:i',
                function ($attribute, $value, $fail) {
                    $message = $this->checkOutSequenceError($this->editingCheckIn, $value, $this->editingOvernight);
                    if ($message) {
                        $fail($message);
                    }
                },
            ],
            'editingOvertime' => 'nullable|numeric|min:0|max:'.self::MAX_OVERTIME_HOURS,
            'editingNotes' => 'nullable|string|max:1000',
            'editingCorrectionReason' => 'nullable|string|min:8|max:500',
        ]);

        if ($this->periodLocked($this->editingRecordDate)) {
            $this->showEditRecordModal = false;
            $this->errorMessage = 'Attendance for this month is locked by finalised payroll. Reopen the payroll first.';

            return;
        }

        $record = AttendanceRecord::where('tenant_id', $tenantId)
            ->where('user_id', $this->editingUserId)
            ->whereDate('date', $this->editingRecordDate)
            ->first();

        if ($record && trim($this->editingCorrectionReason) === '') {
            $this->addError('editingCorrectionReason', 'A correction reason is required when changing an existing attendance record.');

            return;
        }

        $recordData = [
            'status' => $this->editingStatus,
            'check_in' => ! empty($this->editingCheckIn) ? $this->editingCheckIn : null,
            'check_out' => ! empty($this->editingCheckOut) ? $this->editingCheckOut : null,
            'overtime_hours' => $this->editingOvertime,
            'notes' => $this->editingNotes ?: null,
            'overnight' => $this->editingOvernight,
            'source' => (! empty($this->editingCheckIn) || ! empty($this->editingCheckOut)) ? 'RECORDED' : ($record?->source ?? 'RECORDED'),
            'created_by' => Auth::id(),
        ];

        if ($record) {
            $this->writeCorrection($record, $recordData, trim($this->editingCorrectionReason));
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
                    $this->writeCorrection($record, $recordData, trim($this->editingCorrectionReason));
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
            'leaveType' => 'required|in:CASUAL,SICK,ANNUAL,EMERGENCY,UNPAID',
            'leaveStartDate' => 'required|date',
            'leaveEndDate' => 'required|date|after_or_equal:leaveStartDate',
            'leaveReason' => 'required|string|min:5|max:500',
        ]);

        $start = Carbon::parse($this->leaveStartDate);
        $end = Carbon::parse($this->leaveEndDate);
        $days = $start->diffInDays($end) + 1;

        // Prevent overlapping PENDING/APPROVED leave for the same employee.
        $overlap = StaffLeave::where('tenant_id', $tenantId)
            ->where('user_id', $this->leaveUserId)
            ->whereIn('status', ['PENDING', 'APPROVED'])
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                    ->orWhereBetween('end_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                    ->orWhere(function ($inner) use ($start, $end) {
                        $inner->where('start_date', '<=', $start->format('Y-m-d'))
                            ->where('end_date', '>=', $end->format('Y-m-d'));
                    });
            })
            ->exists();

        if ($overlap) {
            $this->addError('leaveStartDate', 'This employee already has a pending or approved leave covering these dates.');

            return;
        }

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
        $tenantId = Auth::user()->tenant_id;
        $leave = StaffLeave::where('tenant_id', $tenantId)->findOrFail($leaveId);

        // Separation of duties: an approver must never approve their own leave.
        if ((int) $leave->user_id === (int) Auth::id()) {
            abort(403, 'You cannot approve your own leave request.');
        }
        if ($leave->status !== 'PENDING') {
            $this->errorMessage = 'Only pending leave applications can be approved.';

            return;
        }
        if ($this->periodLocked($leave->start_date) || $this->periodLocked($leave->end_date)) {
            $this->errorMessage = 'Leave cannot modify a locked attendance period. Reopen the payroll first.';

            return;
        }

        DB::transaction(function () use ($leave, $tenantId) {
            $leave->update([
                'status' => 'APPROVED',
                'action_by' => Auth::id(),
            ]);

            // Approved leave must be reflected in attendance records so it is
            // visible in the register, monthly summary and future calendar.
            // Existing PRESENT/LATE/HALF_DAY records are left untouched: the
            // employee actually worked and manual attendance wins.
            $day = Carbon::parse($leave->start_date)->startOfDay();
            $last = Carbon::parse($leave->end_date)->startOfDay();
            while ($day->lte($last)) {
                $date = $day->format('Y-m-d');
                $record = AttendanceRecord::where('tenant_id', $tenantId)
                    ->where('user_id', $leave->user_id)
                    ->whereDate('date', $date)
                    ->first();
                if (! $record) {
                    AttendanceRecord::create([
                        'tenant_id' => $tenantId,
                        'user_id' => $leave->user_id,
                        'date' => $date,
                        'status' => 'ON_LEAVE',
                        'created_by' => Auth::id(),
                    ]);
                } elseif ($record->status === 'ABSENT') {
                    $record->update(['status' => 'ON_LEAVE', 'created_by' => Auth::id()]);
                }
                $day->addDay();
            }
        });

        AuditLog::log('LEAVE_APPROVED', "Leave application [{$leaveId}] approved.", $tenantId, 'StaffLeave', $leaveId);
        $this->loadAttendanceForDate();
        $this->feedbackMessage = "Leave application approved for [{$leave->user->name}] and reflected in attendance.";
    }

    public function rejectLeave(string $leaveId)
    {
        $this->authorizeAttendanceManager();
        $tenantId = Auth::user()->tenant_id;
        $leave = StaffLeave::where('tenant_id', $tenantId)->findOrFail($leaveId);
        if ((int) $leave->user_id === (int) Auth::id()) {
            abort(403, 'You cannot reject your own leave request.');
        }
        if ($leave->status !== 'PENDING') {
            $this->errorMessage = 'Only pending leave applications can be rejected.';

            return;
        }
        if ($this->periodLocked($leave->start_date) || $this->periodLocked($leave->end_date)) {
            $this->errorMessage = 'Leave cannot modify a locked attendance period. Reopen the payroll first.';

            return;
        }

        $leave->update([
            'status' => 'REJECTED',
            'action_by' => Auth::id(),
        ]);

        AuditLog::log('LEAVE_REJECTED', "Leave application [{$leaveId}] rejected.", $tenantId, 'StaffLeave', $leaveId);
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
            'maxOvertimeHours' => self::MAX_OVERTIME_HOURS,
        ]);
    }
}
