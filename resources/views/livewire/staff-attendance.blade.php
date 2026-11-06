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

    <!-- 1-Click Workforce Switcher Pill Bar -->
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-4 space-y-2.5 shadow-sm transition-colors">
        <div class="flex items-center justify-between">
            <span class="text-[10px] font-mono uppercase tracking-wider text-stone-500 dark:text-stone-400 font-bold flex items-center gap-1.5">
                <span>⚡ 1-Click Employee Attendance Selector</span>
                <span class="text-stone-400">•</span>
                <span class="text-[#8F7239] dark:text-[#C6A15B]">Instant View &amp; Edit Filter</span>
            </span>
            @if($selectedStaffId !== 'ALL')
                <button type="button" wire:click="selectStaff('ALL')" class="text-[11px] font-mono text-[#8F7239] dark:text-[#C6A15B] hover:underline font-bold cursor-pointer">
                    ✕ Reset to All Staff
                </button>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" wire:click="selectStaff('ALL')" 
                    class="px-3.5 py-1.5 text-xs font-mono font-bold uppercase transition border cursor-pointer {{ $selectedStaffId === 'ALL' ? 'bg-[#C6A15B] text-[#171513] border-[#C6A15B] shadow-md' : 'bg-[#F8F5EF] dark:bg-white/5 text-stone-700 dark:text-stone-300 border-[#E7E5E4] dark:border-white/15 hover:border-[#C6A15B]' }}">
                👥 All Staff (Whole Team)
            </button>
            @foreach($staffOptions as $opt)
                <button type="button" wire:click="selectStaff('{{ $opt->id }}')" 
                        class="px-3 py-1.5 text-xs font-mono transition border flex items-center gap-1.5 cursor-pointer {{ $selectedStaffId == $opt->id ? 'bg-[#171513] text-[#C6A15B] border-[#C6A15B] shadow-[0_0_15px_rgba(198,161,91,0.35)] font-bold dark:bg-[#C6A15B]/20' : 'bg-[#F8F5EF] dark:bg-white/5 text-stone-700 dark:text-stone-300 border-[#E7E5E4] dark:border-white/15 hover:border-[#C6A15B]' }}">
                    <span class="w-2 h-2 rounded-full {{ $selectedStaffId == $opt->id ? 'bg-[#C6A15B]' : 'bg-stone-400' }}"></span>
                    <span>{{ $opt->name }}</span>
                    <span class="text-[10px] text-stone-400 dark:text-stone-500">({{ str_replace('ROLE_', '', $opt->role) }})</span>
                </button>
            @endforeach
        </div>
    </div>

    <!-- Employee Spotlight Banner -->
    @if($selectedStaffId !== 'ALL' && ($spotlightStaff = $staffOptions->firstWhere('id', (int) $selectedStaffId)))
        <div class="bg-[#C6A15B]/10 border border-[#C6A15B]/40 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 border border-[#C6A15B] bg-[#171513] text-[#C6A15B] flex items-center justify-center font-cinzel font-bold text-lg">
                    {{ substr($spotlightStaff->name, 0, 1) }}
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-sm text-[#171513] dark:text-white">{{ $spotlightStaff->name }}</span>
                        <span class="text-[10px] font-mono uppercase bg-[#C6A15B]/20 text-[#8F7239] dark:text-[#C6A15B] px-1.5 py-0.5 font-bold">
                            {{ str_replace('ROLE_', '', $spotlightStaff->role) }}
                        </span>
                    </div>
                    <span class="text-xs font-mono text-stone-500 dark:text-stone-400">
                        Base: {{ $spotlightStaff->base_salary ? 'PKR ' . number_format((float) $spotlightStaff->base_salary) : 'Role default' }} • {{ $spotlightStaff->email }}
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-2 font-mono text-xs">
                <a href="{{ route('staff.payroll', ['staff_id' => $spotlightStaff->id]) }}" 
                   class="btn-gold !h-8 !px-3 font-bold uppercase inline-flex items-center gap-1 shadow-sm">
                    <span>💰 View Salary Slips &rarr;</span>
                </a>
                <a href="{{ route('staff') }}" 
                   class="btn-outline-gold !h-8 !px-3 font-bold uppercase inline-flex items-center gap-1">
                    <span>👤 Staff Profile &rarr;</span>
                </a>
            </div>
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
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-6 space-y-6 shadow-sm dark:shadow-xl">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E7E5E4] pb-4">
                <div class="flex items-center gap-3">
                    <label class="text-xs font-mono uppercase text-stone-600 font-bold">Select Date:</label>
                    <input type="date" wire:model.live="selectedDate" 
                           class="bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/20 text-xs font-mono px-3 py-1.5 text-stone-900 dark:text-stone-100 outline-none focus:border-[#C6A15B]">
                    <span class="text-xs font-serif italic text-stone-500">
                        {{ \Carbon\Carbon::parse($selectedDate)->format('l, d F Y') }}
                    </span>
                    <label for="attendance-staff-filter" class="sr-only">Filter attendance by staff member</label>
                    <select id="attendance-staff-filter" wire:model.live="selectedStaffId" class="bg-[#171513] border border-[#C6A15B]/40 px-3 py-1.5 text-xs text-white">
                        <option value="ALL">All active staff</option>
                        @foreach($staffOptions as $staffOption)
                            <option value="{{ $staffOption->id }}">{{ $staffOption->name }}</option>
                        @endforeach
                    </select>
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

            <div class="table-wrapper-enterprise" tabindex="0" role="region"><table class="table-enterprise">
                    <thead>
                        <tr class="bg-[#F8F5EF] dark:bg-white/5 border-b border-[#E7E5E4] dark:border-white/10 text-[10px] uppercase font-bold tracking-wider text-stone-600 dark:text-stone-300 font-mono">
                            <th class="p-3">Staff Member</th>
                            <th class="p-3">Role / Designation</th>
                            <th class="p-3">Attendance Status</th>
                            <th class="p-3">Shift In</th>
                            <th class="p-3">Shift Out</th>
                            <th class="p-3">Overtime (Hours)</th>
                            <th class="p-3">Shift Notes</th>
                            <th class="p-3 text-right">Quick Edit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4]">
                        @forelse($staff as $m)
                            <tr class="hover:bg-[#F8F5EF] dark:hover:bg-white/5 transition">
                                <td class="p-3">
                                    <div class="font-bold text-[#171513] text-sm">{{ $m->name }}</div>
                                    <span class="text-[10px] text-stone-500 font-mono block">{{ $m->phone ?: $m->email }}</span>
                                </td>
                                <td class="p-3 font-mono text-[11px] text-stone-600">
                                    <span class="px-2 py-0.5 bg-stone-100 border border-stone-200 uppercase font-semibold">
                                        {{ str_replace('ROLE_', '', $m->role) }}
                                    </span>
                                </td>
                                <td class="p-3">
                                    <select wire:model="attendanceData.{{ $m->id }}.status" 
                                            class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/20 text-xs font-mono font-bold px-2 py-1 outline-none focus:border-[#C6A15B]">
                                        <option value="PRESENT" class="text-[#8F7239]">PRESENT (HAZIR)</option>
                                        <option value="ABSENT" class="text-[#171513]">ABSENT (GHAIR HAZIR)</option>
                                        <option value="HALF_DAY" class="text-[#8F7239]">HALF DAY</option>
                                        <option value="LATE" class="text-[#8F7239]">LATE ARRIVAL</option>
                                        <option value="ON_LEAVE" class="text-[#8F7239]">ON LEAVE (RUKHSAT)</option>
                                    </select>
                                </td>
                                <td class="p-3">
                                    <input type="time" wire:model="attendanceData.{{ $m->id }}.check_in" 
                                           class="w-24 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/20 text-xs font-mono px-2 py-1 outline-none focus:border-[#C6A15B]">
                                </td>
                                <td class="p-3">
                                    <input type="time" wire:model="attendanceData.{{ $m->id }}.check_out" 
                                           class="w-24 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/20 text-xs font-mono px-2 py-1 outline-none focus:border-[#C6A15B]">
                                </td>
                                <td class="p-3">
                                    <input type="number" step="0.5" min="0" max="12" wire:model="attendanceData.{{ $m->id }}.overtime_hours" 
                                           class="w-20 bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/20 text-xs font-mono px-2 py-1 text-center outline-none focus:border-[#C6A15B]">
                                </td>
                                <td class="p-3">
                                    <input type="text" placeholder="e.g. Covered evening walima shift" wire:model="attendanceData.{{ $m->id }}.notes" 
                                           class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/20 text-xs px-2 py-1 outline-none focus:border-[#C6A15B]">
                                </td>
                                <td class="p-3 text-right whitespace-nowrap">
                                    <button wire:click="openEditRecordModal({{ $m->id }})" type="button" 
                                            class="tbtn tbtn-outline" title="Edit Daily Log">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        <span>Edit</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-8 text-center text-stone-400 font-sans">
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
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-6 space-y-6 shadow-sm dark:shadow-xl">
            <div class="flex items-center justify-between border-b border-[#E7E5E4] pb-4">
                <div>
                    <h3 class="font-serif text-2xl text-[#171513] font-normal">Team Leave Applications</h3>
                    <p class="text-xs text-stone-500">Approve or reject staff sick days, emergency absences, and casual leaves.</p>
                </div>
                <button wire:click="openLeaveModal" class="btn-gold !h-9 !px-4 text-xs font-bold uppercase font-mono">
                    + New Leave Application
                </button>
            </div>

            <div class="table-wrapper-enterprise" tabindex="0" role="region"><table class="table-enterprise">
                    <thead>
                        <tr class="bg-[#F8F5EF] dark:bg-white/5 border-b border-[#E7E5E4] dark:border-white/10 text-[10px] uppercase font-bold tracking-wider text-stone-600 dark:text-stone-300 font-mono">
                            <th class="p-3">Staff Member</th>
                            <th class="p-3">Leave Type</th>
                            <th class="p-3">Duration</th>
                            <th class="p-3">Days</th>
                            <th class="p-3">Reason</th>
                            <th class="p-3">Status</th>
                            <th class="p-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4]">
                        @forelse($leaves as $lv)
                            <tr class="hover:bg-[#F8F5EF] dark:hover:bg-white/5 transition font-mono">
                                <td class="p-3 font-sans">
                                    <div class="font-bold text-stone-900">{{ $lv->user->name ?? 'Staff' }}</div>
                                    <span class="text-[10px] text-stone-500 font-mono">{{ $lv->user->role ?? '' }}</span>
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 bg-stone-100 text-stone-800 font-semibold border border-stone-200">
                                        {{ $lv->leave_type }}
                                    </span>
                                </td>
                                <td class="p-3 text-stone-700">
                                    {{ $lv->start_date->format('d M') }} — {{ $lv->end_date->format('d M Y') }}
                                </td>
                                <td class="p-3 font-bold text-stone-900">
                                    {{ $lv->days }} day(s)
                                </td>
                                <td class="p-3 font-sans text-stone-600 max-w-xs truncate">
                                    {{ $lv->reason }}
                                </td>
                                <td class="p-3">
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
                                                    class="tbtn tbtn-green" title="Approve Leave">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                <span>Approve</span>
                                            </button>
                                            <button wire:click="rejectLeave('{{ $lv->id }}')" 
                                                    class="tbtn tbtn-red" title="Reject Leave">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                <span>Reject</span>
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
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-6 space-y-6 shadow-sm dark:shadow-xl">
            <div class="flex items-center justify-between border-b border-[#E7E5E4] pb-4">
                <div class="flex flex-wrap items-center gap-3">
                    <label class="text-xs font-mono uppercase text-stone-600 font-bold">Select Month:</label>
                    <button type="button" wire:click="previousMonth" aria-label="Previous month" class="h-8 w-8 border border-[#C6A15B]/40 text-[#8F7239]">&larr;</button>
                    <input type="month" wire:model.live="selectedMonth" 
                           class="bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/20 text-xs font-mono px-3 py-1.5 text-stone-900 dark:text-stone-100 outline-none focus:border-[#C6A15B]">
                    <button type="button" wire:click="nextMonth" aria-label="Next month" class="h-8 w-8 border border-[#C6A15B]/40 text-[#8F7239]">&rarr;</button>
                    <label for="monthly-attendance-staff-filter" class="sr-only">Filter monthly attendance by staff member</label>
                    <select id="monthly-attendance-staff-filter" wire:model.live="selectedStaffId" class="bg-[#171513] border border-[#C6A15B]/40 px-3 py-1.5 text-xs text-white">
                        <option value="ALL">All active staff</option>
                        @foreach($staffOptions as $staffOption)
                            <option value="{{ $staffOption->id }}">{{ $staffOption->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="text-xs font-mono text-stone-500">
                    Calculated for {{ \Carbon\Carbon::createFromFormat('!Y-m', $selectedMonth)->format('F Y') }}
                </div>
            </div>

            <div class="table-wrapper-enterprise" tabindex="0" role="region"><table class="table-enterprise">
                    <thead>
                        <tr class="bg-[#F8F5EF] dark:bg-white/5 border-b border-[#E7E5E4] dark:border-white/10 text-[10px] uppercase font-bold tracking-wider text-stone-600 dark:text-stone-300 font-mono">
                            <th class="p-3">Staff Member</th>
                            <th class="p-3">Designation</th>
                            <th class="p-3 text-[#8F7239]">Present (Days)</th>
                            <th class="p-3 text-[#8F7239]">Half Days</th>
                            <th class="p-3 text-[#171513]">Absent (Days)</th>
                            <th class="p-3 text-[#8F7239]">Approved Leaves</th>
                            <th class="p-3 text-right text-[#8F7239]">Total Overtime Hours</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E7E5E4]">
                        @foreach($summary as $uId => $row)
                            <tr class="hover:bg-[#F8F5EF] dark:hover:bg-white/5 transition font-mono">
                                <td class="p-3 font-sans font-bold text-stone-900">
                                    {{ $row['name'] }}
                                </td>
                                <td class="p-3 text-[11px] text-stone-600">
                                    {{ str_replace('ROLE_', '', $row['role']) }}
                                </td>
                                <td class="p-3 font-bold text-[#8F7239]">
                                    {{ $row['present'] }}
                                </td>
                                <td class="p-3 font-bold text-[#8F7239]">
                                    {{ $row['half_day'] }}
                                </td>
                                <td class="p-3 font-bold text-[#171513]">
                                    {{ $row['absent'] }}
                                </td>
                                <td class="p-3 font-bold text-[#8F7239]">
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
                        @error('leaveUserId') <span class="text-rose-400 text-[10px] font-mono mt-1 block">{{ $message }}</span> @enderror
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
                        @error('leaveReason') <span class="text-rose-400 text-[10px] font-mono mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="border-t border-white/10 pt-3 flex items-center justify-end gap-2 font-mono text-xs">
                    <button wire:click="closeLeaveModal" class="px-4 py-2 border border-white/20 text-white cursor-pointer">Cancel</button>
                    <button wire:click="submitLeave" class="btn-gold !h-9 !px-4 text-xs font-bold uppercase cursor-pointer">Submit Leave &rarr;</button>
                </div>
            </div>
        </div>
    @endif

    <!-- Quick Edit Attendance Record Modal -->
    @if($showEditRecordModal)
        <div class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4 backdrop-blur-sm">
            <div class="bg-[#171513] border-2 border-[#C6A15B] max-w-md w-full p-6 space-y-5 text-white shadow-2xl">
                <div class="border-b border-white/10 pb-3 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono uppercase text-[#C6A15B] tracking-widest font-bold block">Quick Shift Correction</span>
                        <h3 class="font-serif text-xl text-white font-medium mt-0.5">{{ $editingStaffName }}</h3>
                    </div>
                    <button wire:click="closeEditRecordModal" class="text-white/60 hover:text-white text-2xl font-mono leading-none cursor-pointer">&times;</button>
                </div>

                <div class="space-y-4 font-sans text-xs">
                    <div>
                        <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Attendance Date *</label>
                        <input type="date" wire:model="editingRecordDate" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                        @error('editingRecordDate') <span class="text-rose-400 text-[10px] font-mono mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Status *</label>
                        <select wire:model="editingStatus" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                            <option value="PRESENT">PRESENT (HAZIR)</option>
                            <option value="HALF_DAY">HALF DAY</option>
                            <option value="ABSENT">ABSENT (GHAIR HAZIR)</option>
                            <option value="LATE">LATE ARRIVAL</option>
                            <option value="ON_LEAVE">ON LEAVE (RUKHSAT)</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3 font-mono">
                        <div>
                            <label class="block text-[10px] uppercase text-[#C6A15B] mb-1 font-semibold">Shift In Time</label>
                            <input type="time" wire:model="editingCheckIn" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase text-[#C6A15B] mb-1 font-semibold">Shift Out Time</label>
                            <input type="time" wire:model="editingCheckOut" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Overtime Hours (Hours)</label>
                        <input type="number" step="0.5" min="0" max="24" wire:model="editingOvertime" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        @error('editingOvertime') <span class="text-rose-400 text-[10px] font-mono mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Operational Shift Notes</label>
                        <textarea wire:model="editingNotes" rows="2" placeholder="e.g. Covered evening walima banquet shift" class="w-full bg-[#171513] border border-white/20 text-white text-xs p-2.5 outline-none focus:border-[#C6A15B]"></textarea>
                    </div>
                </div>

                <div class="border-t border-white/10 pt-3 flex items-center justify-end gap-2 font-mono text-xs">
                    <button wire:click="closeEditRecordModal" class="px-4 py-2 border border-white/20 text-white hover:bg-white/10 cursor-pointer">Cancel</button>
                    <button wire:click="saveEditedRecord" class="btn-gold !h-9 !px-5 text-xs font-bold uppercase cursor-pointer">Save Correction &rarr;</button>
                </div>
            </div>
        </div>
    @endif

</div>
