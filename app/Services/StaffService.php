<?php

namespace App\Services;

use App\Models\StaffAttendance;
use App\Models\StaffLeave;
use App\Models\StaffPayroll;
use App\Models\User;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StaffService
{
    use \App\Traits\ServiceTrait;
    
    /** * Mark staff attendance * * @param array $data ['tenant_id', 'user_id', 'date', 'status', 'check_in', 'check_out', 'overtime_hours', 'notes', 'created_by'] * @return array */
    public function markAttendance(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // Validate required fields
            $validator = Validator::make($data, [
                'tenant_id' => 'required|exists:tenants,id',
                'user_id' => 'required|exists:users,id',
                'date' => 'required|date',
                'status' => 'required|in:PRESENT,ABSENT,HALF_DAY,ON_LEAVE,LATE',
            ]);
            
            if ($validator->fails()) {
                return $this->error($validator->errors()->first());
            }
            
            // Check if attendance already recorded for this date
            $existing = StaffAttendance::where('tenant_id', $data['tenant_id'])
                ->where('user_id', $data['user_id'])
                ->where('date', $data['date'])
                ->first();
            
            if ($existing) {
                // Update existing record
                $existing->status = $data['status'];
                $existing->check_in = $data['check_in'] ?? $existing->check_in;
                $existing->check_out = $data['check_out'] ?? $existing->check_out;
                $existing->overtime_hours = $data['overtime_hours'] ?? $existing->overtime_hours;
                $existing->notes = $data['notes'] ?? $existing->notes;
                $existing->save();
                
                return $this->success($existing, 'Attendance updated successfully.');
            }
            
            // Create new attendance record
            $attendance = StaffAttendance::create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $data['tenant_id'],
                'user_id' => $data['user_id'],
                'date' => $data['date'],
                'status' => $data['status'],
                'check_in' => $data['check_in'] ?? null,
                'check_out' => $data['check_out'] ?? null,
                'overtime_hours' => $data['overtime_hours'] ?? 0,
                'notes' => $data['notes'] ?? '',
                'created_by' => $data['created_by'] ?? null,
            ]);
            
            // If marking ON_LEAVE, create corresponding leave record if not exists
            if ($data['status'] === 'ON_LEAVE' && !$data['notes']) {
                // Check if leave already exists
                $existingLeave = StaffLeave::where('tenant_id', $data['tenant_id'])
                    ->where('user_id', $data['user_id'])
                    ->where('leave_type', 'SICK') // or appropriate type
                    ->where(function ($q) use ($data) {
                        $q->whereBetween('start_date', [$data['date'], $data['date']])
                          ->orWhereBetween('end_date', [$data['date'], $data['date']]);
                    })
                    ->first();
                
                if (!$existingLeave) {
                    // Auto-create leave record
                    // This is simplified - in production, would notify HR
                }
            }
            
            return $this->success($attendance, 'Attendance marked successfully.');
        });
    }
    
    /** * Apply for leave * * @param array $data ['tenant_id', 'user_id', 'leave_type', 'start_date', 'end_date', 'days', 'reason', 'created_by'] * @return array */
    public function applyLeave(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $validator = Validator::make($data, [
                'tenant_id' => 'required|exists:tenants,id',
                'user_id' => 'required|exists:users,id',
                'leave_type' => 'required|in:CASUAL,SICK,ANNUAL,EMERGENCY',
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after_or_equal:start_date',
                'days' => 'required|integer|min:1',
                'reason' => 'sometimes|string',
            ]);
            
            if ($validator->fails()) {
                return $this->error($validator->errors()->first());
            }
            
            // Check if leave overlaps with existing leaves
            $overlapping = StaffLeave::where('tenant_id', $data['tenant_id'])
                ->where('user_id', $data['user_id'])
                ->where('status', 'APPROVED')
                ->where(function ($q) use ($data) {
                    $q->whereBetween('start_date', [$data['start_date'], $data['end_date']])
                      ->orWhereBetween('end_date', [$data['start_date'], $data['end_date']]);
                })
                ->exists();
            
            if ($overlapping) {
                return $this->error('You already have approved leave overlapping these dates.');
            }
            
            // Check if max leaves exceeded (simple example)
            $approvedLeaves = StaffLeave::where('tenant_id', $data['tenant_id'])
                ->where('user_id', $data['user_id'])
                ->where('status', 'APPROVED')
                ->count();
            
            // CASUAL leaves max 12 per year, SICK max 15, etc.
            $leaveLimit = match($data['leave_type']) {
                'CASUAL' => 12,
                'SICK' => 15,
                'ANNUAL' => 21,
                'EMERGENCY' => 5,
                default => 10,
            };
            
            if ($approvedLeaves >= $limit) {
                return $this->error("You have reached the maximum {$data['leave_type']} leaves ({$limit} per year).");
            }
            
            // Create leave application
            $leave = StaffLeave::create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $data['tenant_id'],
                'user_id' => $data['user_id'],
                'leave_type' => $data['leave_type'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'days' => $data['days'],
                'reason' => $data['reason'] ?? '',
                'status' => 'PENDING',
                'action_by' => null,
            ]);
            
            return $this->success($leave, 'Leave application submitted successfully. Awaiting approval.');
        });
    }
    
    /** * Approve/reject leave * * @param string $leaveId * @param string $action 'approve' or 'reject' * @param string $adminId * @return array */
    public function approveRejectLeave(string $leaveId, string $action, string $adminId): array
    {
        return DB::transaction(function () use ($leaveId, $action, $adminId) {
            $validActions = ['approve', 'reject'];
            
            if (!in_array($action, $validActions)) {
                return $this->error("Invalid action. Use 'approve' or 'reject'.");
            }
            
            $leave = StaffLeave::where('id', $leaveId)
                ->where('tenant_id', auth()->user()?->tenant_id) // Tenant isolation
                ->first();
            
            if (!$leave) {
                return $this->error('Leave record not found or not authorized.');
            }
            
            $leave->status = $action === 'approve' ? 'APPROVED' : 'REJECTED';
            $leave->action_by = User::find($adminId)?->id;
            $leave->save();
            
            // If approved, create/update staff attendance with ON_LEAVE status
            if ($action === 'approve') {
                // Mark attendance as on leave for the duration
                // This would typically be done via a scheduled task
                // StaffAttendance::markLeavePeriod($leave);
            }
            
            // Create notification for staff
            // Notification::create([...]);
            
            return $this->success($leave, "Leave {$action}d successfully.");
        });
    }
    
    /** * Generate monthly payroll * * @param string $tenantId * @param string $monthYear YYYY-MM * @param string $adminId * @return array */
    public function generatePayroll(string $tenantId, string $monthYear, string $adminId): array
    {
        return DB::transaction(function () use ($tenantId, $monthYear, $adminId) {
            // Get all active staff for the tenant
            $staff = User::where('tenant_id', $tenantId)
                ->where('role', '!=', 'super_admin')
                ->get();
            
            $payrollRecords = [];
            
            foreach ($staff as $staffUser) {
                // Get attendance for the month
                $attendances = StaffAttendance::where('tenant_id', $tenantId)
                    ->where('user_id', $staffUser->id)
                    ->where('date', 'like', $monthYear . '-%')
                    ->get();
                
                $totalDays = $attendances->count();
                $presentDays = $attendances->where('status', 'PRESENT')->count();
                $halfDays = $attendances->where('status', 'HALF_DAY')->count();
                $absentDays = $attendances->where('status', 'ABSENT')->count();
                $leaveDays = $attendances->where('status', 'ON_LEAVE')->count();
                
                // Calculate working days (exclude weekends + leaves)
                // Simplified: present + half days count as 0.5 each
                $effectiveDays = $presentDays + ($halfDays * 0.5);
                
                // Get basic salary
                $basicSalary = StaffPayroll::where('tenant_id', $tenantId)
                    ->where('user_id', $staffUser->id)
                    ->where('month_year', $monthYear)
                    ->value('basic_salary');
                
                $basicSalary = $basicSalary ?? 50000; // Default
                
                // Calculate overtime
                $overtimeData = StaffAttendance::where('tenant_id', $tenantId)
                    ->where('user_id', $staffUser->id)
                    ->where('date', 'like', $monthYear . '-%')
                    ->whereNotNull('overtime_hours')
                    ->sum('overtime_hours');
                
                $overtimeHours = $overtimeData ?? 0;
                $overtimeRate = 1500; // Default rate per hour
                $overtimeAmount = $overtimeHours * $overtimeRate;
                
                // Allowances (simple: 10% of basic)
                $allowances = round($basicSalary * 0.1);
                
                // Deductions (simple: 5% of basic + 2% per absent day)
                $deductionsRate = 0.05 + ($absentDays * 0.02);
                $deductions = round($basicSalary * $deductionsRate);
                
                // Net salary
                $netSalary = $basicSalary + $overtimeAmount + $allowances - $deductions;
                
                // Create or update payroll record
                $payroll = StaffPayroll::firstOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'user_id' => $staffUser->id,
                        'month_year' => $monthYear,
                    ],
                    [
                        'basic_salary' => $basicSalary,
                        'overtime_hours' => $overtimeHours,
                        'overtime_rate' => $overtimeRate,
                        'allowances' => $allowances,
                        'deductions' => $deductions,
                        'net_salary' => $netSalary,
                        'status' => 'DRAFT',
                    ]
                );
                
                $payrollRecords[] = $payroll;
            }
            
            return $this->success([
                'payroll_records' => $payrollRecords,
                'total_staff' => count($staff),
                'month_year' => $monthYear,
            ], 'Payroll generated for all staff.');
        });
    }
    
    /** * Get staff attendance summary * * @param string $tenantId * @param string $startDate * @param string $endDate * @return array */
    public function getStaffAttendanceSummary(string $tenantId, string $startDate, string $endDate): array
    {
        return DB::transaction(function () use ($tenantId, $startDate, $endDate) {
            $summary = StaffAttendance::where('tenant_id', $tenantId)
                ->whereBetween('date', [$startDate, $endDate])
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->get()
                ->keyBy('status')
                ->toArray();
            
            $totalDays = StaffAttendance::where('tenant_id', $tenantId)
                ->whereBetween('date', [$startDate, $endDate])
                ->count();
            
            $present = $summary['PRESENT']??['count' => 0]['count'] ?? 0;
            $absent = $summary['ABSENT']??['count' => 0]['count'] ?? 0;
            $halfDay = $summary['HALF_DAY']??['count' => 0]['count'] ?? 0;
            $onLeave = $summary['ON_LEAVE']??['count' => 0]['count'] ?? 0;
            
            return $this->success([
                'total_records' => $totalDays,
                'present' => $present,
                'absent' => $absent,
                'half_day' => $halfDay,
                'on_leave' => $onLeave,
                'period' => "{$startDate} to {$endDate}",
            ], 'Attendance summary retrieved.');
        });
    }
}