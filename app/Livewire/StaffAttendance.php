<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\StaffAttendance as AttendanceRecord;
use App\Models\StaffLeave;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Staff Attendance & Leave Management — VenueCloud ERP'])]
class StaffAttendance extends Component
{
    public string $activeTab = 'daily'; // daily, leaves, monthly

    public string $selectedDate = '';

    public string $selectedMonth = '';

    // Daily attendance rows: [user_id => ['status' => 'PRESENT', 'check_in' => '10:00', 'check_out' => '22:00', 'overtime_hours' => 0, 'notes' => '']]
    public array $attendanceData = [];

    // Leave request modal state
    public bool $showLeaveModal = false;

    public ?int $leaveUserId = null;

    public string $leaveType = 'CASUAL';

    public string $leaveStartDate = '';

    public string $leaveEndDate = '';

    public string $leaveReason = '';

    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public function mount()
    {
        $user = Auth::user();
        if (! $user || (! $user->isTenantOwner() && ! $user->isManager() && ! $user->isSuperAdmin() && ! $user->isAccountant())) {
            abort(403, 'Unauthorized. Only Venue Owners or Managers have access to Attendance ERP.');
        }

        $this->selectedDate = Carbon::today()->format('Y-m-d');
        $this->selectedMonth = Carbon::today()->format('Y-m');
        $this->leaveStartDate = Carbon::today()->format('Y-m-d');
        $this->leaveEndDate = Carbon::today()->format('Y-m-d');

        $this->loadAttendanceForDate();
    }

    public function setTab(string $tab)
    {
        $this->activeTab = $tab;
        $this->feedbackMessage = null;
        $this->errorMessage = null;
    }

    public function updatedSelectedDate()
    {
        $this->loadAttendanceForDate();
    }

    public function loadAttendanceForDate()
    {
        $tenantId = Auth::user()->tenant_id;
        $staff = User::where('tenant_id', $tenantId)
            ->where('role', '!=', 'ROLE_CUSTOMER')
            ->get();

        $existing = AttendanceRecord::where('tenant_id', $tenantId)
            ->where('date', $this->selectedDate)
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
        foreach ($this->attendanceData as $userId => $val) {
            $this->attendanceData[$userId]['status'] = 'PRESENT';
        }
        $this->feedbackMessage = 'Marked all team members as Present for '.$this->selectedDate;
    }

    public function saveDailyAttendance()
    {
        $tenantId = Auth::user()->tenant_id;

        foreach ($this->attendanceData as $userId => $data) {
            AttendanceRecord::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'date' => $this->selectedDate,
                ],
                [
                    'status' => $data['status'] ?? 'PRESENT',
                    'check_in' => ! empty($data['check_in']) ? $data['check_in'] : null,
                    'check_out' => ! empty($data['check_out']) ? $data['check_out'] : null,
                    'overtime_hours' => (float) ($data['overtime_hours'] ?? 0),
                    'notes' => $data['notes'] ?? null,
                    'created_by' => Auth::id(),
                ]
            );
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

    // ==========================================
    // LEAVE MANAGEMENT
    // ==========================================
    public function openLeaveModal()
    {
        $firstStaff = User::where('tenant_id', Auth::user()->tenant_id)->first();
        $this->leaveUserId = $firstStaff ? $firstStaff->id : null;
        $this->leaveType = 'CASUAL';
        $this->leaveStartDate = Carbon::today()->format('Y-m-d');
        $this->leaveEndDate = Carbon::today()->format('Y-m-d');
        $this->leaveReason = '';
        $this->showLeaveModal = true;
    }

    public function closeLeaveModal()
    {
        $this->showLeaveModal = false;
    }

    public function submitLeave()
    {
        $this->validate([
            'leaveUserId' => 'required|exists:users,id',
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
        $leave = StaffLeave::where('tenant_id', Auth::user()->tenant_id)->findOrFail($leaveId);
        $leave->update([
            'status' => 'APPROVED',
            'action_by' => Auth::id(),
        ]);

        $this->feedbackMessage = "Leave application approved for [{$leave->user->name}].";
    }

    public function rejectLeave(string $leaveId)
    {
        $leave = StaffLeave::where('tenant_id', Auth::user()->tenant_id)->findOrFail($leaveId);
        $leave->update([
            'status' => 'REJECTED',
            'action_by' => Auth::id(),
        ]);

        $this->feedbackMessage = 'Leave application rejected.';
    }

    public function render()
    {
        $tenantId = Auth::user()->tenant_id;

        $staff = User::where('tenant_id', $tenantId)
            ->where('role', '!=', 'ROLE_CUSTOMER')
            ->orderBy('name')
            ->get();

        $leaves = StaffLeave::where('tenant_id', $tenantId)
            ->with(['user', 'actionBy'])
            ->latest()
            ->paginate(15);

        // Monthly summary calculations
        $monthStart = Carbon::parse($this->selectedMonth.'-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $monthlyRecords = AttendanceRecord::where('tenant_id', $tenantId)
            ->whereBetween('date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
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
            'leaves' => $leaves,
            'summary' => $summary,
        ]);
    }
}
