<div class="space-y-6 max-w-7xl mx-auto pb-16">
    
    <!-- Top Executive Header -->
    <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-6 sm:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6 shadow-xl transition-colors duration-200">
        <div class="space-y-1.5">
            <div class="flex items-center gap-2 font-mono text-[11px] text-[#8F7239] dark:text-[#C6A15B] uppercase tracking-widest font-bold">
                <span>Enterprise ERP</span>
                <span class="text-stone-400 dark:text-stone-600">•</span>
                <span>Workforce &amp; Human Resources</span>
            </div>
            <h1 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                Staff Attendance &amp; Leave Roster
            </h1>
            <p class="text-xs text-stone-600 dark:text-stone-300 max-w-2xl font-sans leading-relaxed">
                Manage daily shift punch times, overtime allowances, leave approvals, and monthly attendance rosters for banquet managers, waitstaff, and kitchen brigades.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button wire:click="openLeaveModal" class="btn-outline-gold !h-9 !px-4 text-xs font-mono uppercase font-bold">
                + Apply Leave
            </button>
            <a href="{{ route('staff') }}" class="btn-gold !h-9 !px-4 text-xs font-mono uppercase font-bold shadow">
                Manage Staff Roles &rarr;
            </a>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if($feedbackMessage)
        <div class="p-4 bg-[#171513]/85 border border-[#C6A15B]/50 text-[#C6A15B] text-xs font-mono flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="font-bold">✓</span>
                <span>{{ $feedbackMessage }}</span>
            </div>
            <button wire:click="$set('feedbackMessage', null)" class="text-[#8F7239] hover:text-white">&times;</button>
        </div>
    @endif

    @if($errorMessage)
        <div class="p-4 bg-[#171513]/85 border border-[#C6A15B]/50 text-[#F8F5EF] text-xs font-mono flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="font-bold">⚠️</span>
                <span>{{ $errorMessage }}</span>
            </div>
            <button wire:click="$set('errorMessage', null)" class="text-[#171513] hover:text-white">&times;</button>
        </div>
    @endif

    <!-- Tab Strip -->
    <div class="flex border-b border-[#E7E5E4] dark:border-white/10 bg-white dark:bg-[#171513] text-xs font-mono uppercase overflow-x-auto">
        <button wire:click="setTab('daily')" 
                class="px-6 py-3.5 border-b-2 font-bold tracking-wider transition {{ $activeTab === 'daily' ? 'border-[#C6A15B] text-[#171513] dark:text-[#C6A15B] bg-[#F8F5EF] dark:bg-[#171513]' : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-[#171513] dark:hover:text-white' }}">
            📅 Daily Attendance Register
        </button>
        <button wire:click="setTab('leaves')" 
                class="px-6 py-3.5 border-b-2 font-bold tracking-wider transition {{ $activeTab === 'leaves' ? 'border-[#C6A15B] text-[#171513] dark:text-[#C6A15B] bg-[#F8F5EF] dark:bg-[#171513]' : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-[#171513] dark:hover:text-white' }}">
            ✈️ Leave Applications ({{ $leaves->total() }})
        </button>
        <button wire:click="setTab('monthly')" 
                class="px-6 py-3.5 border-b-2 font-bold tracking-wider transition {{ $activeTab === 'monthly' ? 'border-[#C6A15B] text-[#171513] dark:text-[#C6A15B] bg-[#F8F5EF] dark:bg-[#171513]' : 'border-transparent text-stone-500 dark:text-stone-400 hover:text-[#171513] dark:hover:text-white' }}">
            📊 Monthly Summary Roster
        </button>
    </div>

    <!-- ===================================================================
         TAB 1: DAILY ATTENDANCE REGISTER
         =================================================================== -->
    @if($activeTab === 'daily')
        <div class="bg-white border border-[#E7E5E4] p-6 space-y-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E7E5E4] pb-4">
                <div class="flex items-center gap-3">
                    <label class="text-xs font-mono uppercase text-stone-600 font-bold">Select Date:</label>
                    <input type="date" wire:model.live="selectedDate" 
                           class="bg-[#F8F5EF] border border-[#E7E5E4] text-xs font-mono px-3 py-1.5 text-stone-900 outline-none focus:border-[#C6A15B]">
                    <span class="text-xs font-serif italic text-stone-500">
                        {{ \Carbon\Carbon::parse($selectedDate)->format('l, d F Y') }}
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <button wire:click="markAllPresent" type="button" class="btn-secondary !h-8 !px-3 text-xs font-mono">
                        Mark All Present
                    </button>
                    <button wire:click="saveDailyAttendance" type="button" class="btn-gold !h-8 !px-4 text-xs font-bold font-mono uppercase shadow">
                        💾 Save Attendance
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse border border-[#E7E5E4]">
                    <thead>
                        <tr class="bg-[#F8F5EF] border-b border-[#E7E5E4] text-[10px] uppercase font-bold tracking-wider text-stone-600 font-mono">
                            <th class="p-3 border-r border-[#E7E5E4]">Staff Member</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Role / Designation</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Attendance Status</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Shift In</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Shift Out</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Overtime (Hours)</th>
                            <th class="p-3">Shift Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4]">
                        @forelse($staff as $m)
                            <tr class="hover:bg-[#F8F5EF] transition">
                                <td class="p-3 border-r border-[#E7E5E4]">
                                    <div class="font-bold text-[#171513] text-sm">{{ $m->name }}</div>
                                    <span class="text-[10px] text-stone-500 font-mono block">{{ $m->phone ?: $m->email }}</span>
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] font-mono text-[11px] text-stone-600">
                                    <span class="px-2 py-0.5 bg-stone-100 border border-stone-200 uppercase font-semibold">
                                        {{ str_replace('ROLE_', '', $m->role) }}
                                    </span>
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4]">
                                    <select wire:model="attendanceData.{{ $m->id }}.status" 
                                            class="w-full bg-[#F8F5EF] border border-[#E7E5E4] text-xs font-mono font-bold px-2 py-1 outline-none focus:border-[#C6A15B]">
                                        <option value="PRESENT" class="text-[#8F7239]">PRESENT (HAZIR)</option>
                                        <option value="ABSENT" class="text-[#171513]">ABSENT (GHAIR HAZIR)</option>
                                        <option value="HALF_DAY" class="text-[#8F7239]">HALF DAY</option>
                                        <option value="LATE" class="text-[#8F7239]">LATE ARRIVAL</option>
                                        <option value="ON_LEAVE" class="text-[#8F7239]">ON LEAVE (RUKHSAT)</option>
                                    </select>
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4]">
                                    <input type="time" wire:model="attendanceData.{{ $m->id }}.check_in" 
                                           class="w-24 bg-[#F8F5EF] border border-[#E7E5E4] text-xs font-mono px-2 py-1 outline-none focus:border-[#C6A15B]">
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4]">
                                    <input type="time" wire:model="attendanceData.{{ $m->id }}.check_out" 
                                           class="w-24 bg-[#F8F5EF] border border-[#E7E5E4] text-xs font-mono px-2 py-1 outline-none focus:border-[#C6A15B]">
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4]">
                                    <input type="number" step="0.5" min="0" max="12" wire:model="attendanceData.{{ $m->id }}.overtime_hours" 
                                           class="w-20 bg-[#F8F5EF] border border-[#E7E5E4] text-xs font-mono px-2 py-1 text-center outline-none focus:border-[#C6A15B]">
                                </td>
                                <td class="p-3">
                                    <input type="text" placeholder="e.g. Covered evening walima shift" wire:model="attendanceData.{{ $m->id }}.notes" 
                                           class="w-full bg-[#F8F5EF] border border-[#E7E5E4] text-xs px-2 py-1 outline-none focus:border-[#C6A15B]">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-8 text-center text-stone-400 font-sans">
                                    No staff members registered for this venue yet. Add team members in Staff Roles.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end pt-2">
                <button wire:click="saveDailyAttendance" type="button" class="btn-gold !h-10 !px-6 text-xs font-bold font-mono uppercase shadow-md">
                    💾 Save Attendance Records &rarr;
                </button>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         TAB 2: LEAVE APPLICATIONS
         =================================================================== -->
    @if($activeTab === 'leaves')
        <div class="bg-white border border-[#E7E5E4] p-6 space-y-6 shadow-sm">
            <div class="flex items-center justify-between border-b border-[#E7E5E4] pb-4">
                <div>
                    <h3 class="font-serif text-2xl text-[#171513] font-normal">Team Leave Applications</h3>
                    <p class="text-xs text-stone-500">Approve or reject staff sick days, emergency absences, and casual leaves.</p>
                </div>
                <button wire:click="openLeaveModal" class="btn-gold !h-9 !px-4 text-xs font-bold uppercase font-mono">
                    + New Leave Application
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse border border-[#E7E5E4]">
                    <thead>
                        <tr class="bg-[#F8F5EF] border-b border-[#E7E5E4] text-[10px] uppercase font-bold tracking-wider text-stone-600 font-mono">
                            <th class="p-3 border-r border-[#E7E5E4]">Staff Member</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Leave Type</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Duration</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Days</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Reason</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Status</th>
                            <th class="p-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4]">
                        @forelse($leaves as $lv)
                            <tr class="hover:bg-[#F8F5EF] transition font-mono">
                                <td class="p-3 border-r border-[#E7E5E4] font-sans">
                                    <div class="font-bold text-stone-900">{{ $lv->user->name ?? 'Staff' }}</div>
                                    <span class="text-[10px] text-stone-500 font-mono">{{ $lv->user->role ?? '' }}</span>
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4]">
                                    <span class="px-2 py-0.5 bg-stone-100 text-stone-800 font-semibold border border-stone-200">
                                        {{ $lv->leave_type }}
                                    </span>
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] text-stone-700">
                                    {{ $lv->start_date->format('d M') }} — {{ $lv->end_date->format('d M Y') }}
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] font-bold text-stone-900">
                                    {{ $lv->days }} day(s)
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] font-sans text-stone-600 max-w-xs truncate">
                                    {{ $lv->reason }}
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4]">
                                    @if($lv->status === 'APPROVED')
                                        <span class="px-2 py-0.5 bg-[#C6A15B]/20 text-[#8F7239] border border-[#C6A15B]/60 font-bold">APPROVED</span>
                                    @elseif($lv->status === 'REJECTED')
                                        <span class="px-2 py-0.5 bg-[#171513]/10 text-[#171513] border border-[#C6A15B]/50 font-bold">REJECTED</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-[#C6A15B]/20 text-[#8F7239] border border-[#C6A15B]/60 font-bold">PENDING</span>
                                    @endif
                                </td>
                                <td class="p-3 text-right">
                                    @if($lv->status === 'PENDING')
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button wire:click="approveLeave('{{ $lv->id }}')" 
                                                    class="px-2.5 py-1 bg-[#C6A15B] hover:bg-[#C6A15B] text-white font-bold text-[10px]">
                                                Approve
                                            </button>
                                            <button wire:click="rejectLeave('{{ $lv->id }}')" 
                                                    class="px-2 py-1 bg-[#171513] hover:bg-[#171513] text-white font-bold text-[10px]">
                                                Reject
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-stone-400 text-[10px]">Settled</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-8 text-center text-stone-400 font-sans">
                                    No leave applications on record.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div>
                {{ $leaves->links() }}
            </div>
        </div>
    @endif

    <!-- ===================================================================
         TAB 3: MONTHLY SUMMARY ROSTER
         =================================================================== -->
    @if($activeTab === 'monthly')
        <div class="bg-white border border-[#E7E5E4] p-6 space-y-6 shadow-sm">
            <div class="flex items-center justify-between border-b border-[#E7E5E4] pb-4">
                <div class="flex items-center gap-3">
                    <label class="text-xs font-mono uppercase text-stone-600 font-bold">Select Month:</label>
                    <input type="month" wire:model.live="selectedMonth" 
                           class="bg-[#F8F5EF] border border-[#E7E5E4] text-xs font-mono px-3 py-1.5 text-stone-900 outline-none focus:border-[#C6A15B]">
                </div>
                <div class="text-xs font-mono text-stone-500">
                    Calculated for {{ \Carbon\Carbon::parse($selectedMonth . '-01')->format('F Y') }}
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse border border-[#E7E5E4]">
                    <thead>
                        <tr class="bg-[#F8F5EF] border-b border-[#E7E5E4] text-[10px] uppercase font-bold tracking-wider text-stone-600 font-mono">
                            <th class="p-3 border-r border-[#E7E5E4]">Staff Member</th>
                            <th class="p-3 border-r border-[#E7E5E4]">Designation</th>
                            <th class="p-3 border-r border-[#E7E5E4] text-[#8F7239]">Present (Days)</th>
                            <th class="p-3 border-r border-[#E7E5E4] text-[#8F7239]">Half Days</th>
                            <th class="p-3 border-r border-[#E7E5E4] text-[#171513]">Absent (Days)</th>
                            <th class="p-3 border-r border-[#E7E5E4] text-[#8F7239]">Approved Leaves</th>
                            <th class="p-3 text-right text-[#8F7239]">Total Overtime Hours</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4]">
                        @foreach($summary as $uId => $row)
                            <tr class="hover:bg-[#F8F5EF] transition font-mono">
                                <td class="p-3 border-r border-[#E7E5E4] font-sans font-bold text-stone-900">
                                    {{ $row['name'] }}
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] text-[11px] text-stone-600">
                                    {{ str_replace('ROLE_', '', $row['role']) }}
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] font-bold text-[#8F7239]">
                                    {{ $row['present'] }}
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] font-bold text-[#8F7239]">
                                    {{ $row['half_day'] }}
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] font-bold text-[#171513]">
                                    {{ $row['absent'] }}
                                </td>
                                <td class="p-3 border-r border-[#E7E5E4] font-bold text-[#8F7239]">
                                    {{ $row['leave'] }}
                                </td>
                                <td class="p-3 text-right font-bold text-[#8F7239]">
                                    {{ number_format($row['overtime_hours'], 1) }} hrs
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         MODAL: SUBMIT LEAVE APPLICATION
         =================================================================== -->
    @if($showLeaveModal)
        <div class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4">
            <div class="bg-[#171513] border-2 border-[#C6A15B] max-w-md w-full p-6 space-y-5 text-white shadow-2xl">
                <div class="border-b border-white/10 pb-3 flex items-center justify-between">
                    <h3 class="font-serif text-xl text-white font-medium">Apply Staff Leave</h3>
                    <button wire:click="closeLeaveModal" class="text-white/60 hover:text-white text-2xl font-mono leading-none">&times;</button>
                </div>

                <div class="space-y-4 font-sans text-xs">
                    <div>
                        <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Select Employee *</label>
                        <select wire:model="leaveUserId" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                            @foreach($staff as $m)
                                <option value="{{ $m->id }}">{{ $m->name }} ({{ str_replace('ROLE_', '', $m->role) }})</option>
                            @endforeach
                        </select>
                        @error('leaveUserId') <span class="text-[#171513] text-[10px] font-mono mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Leave Category *</label>
                        <select wire:model="leaveType" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                            <option value="CASUAL">Casual Leave (Ittefaqia Rukhsat)</option>
                            <option value="SICK">Medical / Sick Leave (Bemari)</option>
                            <option value="ANNUAL">Annual Paid Leave</option>
                            <option value="EMERGENCY">Emergency Family Leave</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Start Date *</label>
                            <input type="date" wire:model="leaveStartDate" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                        </div>
                        <div>
                            <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">End Date *</label>
                            <input type="date" wire:model="leaveEndDate" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Reason for Absence *</label>
                        <textarea wire:model="leaveReason" rows="3" placeholder="Provide justification for absence..." class="w-full bg-[#171513] border border-white/20 text-white text-xs p-2.5 outline-none focus:border-[#C6A15B]"></textarea>
                        @error('leaveReason') <span class="text-[#171513] text-[10px] font-mono mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="border-t border-white/10 pt-3 flex items-center justify-end gap-2 font-mono text-xs">
                    <button wire:click="closeLeaveModal" class="px-4 py-2 border border-white/20 text-white">Cancel</button>
                    <button wire:click="submitLeave" class="btn-gold !h-9 !px-4 text-xs font-bold uppercase">Submit Leave &rarr;</button>
                </div>
            </div>
        </div>
    @endif

</div>
