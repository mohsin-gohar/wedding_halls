<div class="space-y-6 max-w-7xl mx-auto pb-16">

    <!-- Header Banner -->
    <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-6 sm:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6 shadow-xl transition-colors duration-200">
        <div class="space-y-1.5">
            <div class="flex items-center gap-2 font-mono text-[11px] text-[#8F7239] dark:text-[#C6A15B] uppercase tracking-widest font-bold">
                <span>Enterprise ERP</span>
                <span class="text-stone-400 dark:text-stone-600">•</span>
                <span>Financial Disbursements</span>
            </div>
            <h1 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                Monthly Staff Payroll &amp; Salary Slips
            </h1>
            <p class="text-xs text-stone-600 dark:text-stone-300 max-w-2xl font-sans leading-relaxed">
                Automated monthly salary calculations, overtime pay from attendance logs, advances/deductions, and official downloadable payment slips with automatic posting to ERP Expenses.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button wire:click="generateMonthlyPayroll" class="btn-gold !h-9 !px-4 text-xs font-bold uppercase font-mono shadow-md">
                ⚡ Compile Monthly Payroll
            </button>
            <a href="{{ route('staff.attendance') }}" class="btn-outline-gold !h-9 !px-4 text-xs font-mono uppercase font-bold">
                Attendance Roster &rarr;
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

    <!-- 1-Click Payroll Employee Switcher Pill Bar -->
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-4 space-y-2.5 shadow-sm transition-colors">
        <div class="flex items-center justify-between">
            <span class="text-[10px] font-mono uppercase tracking-wider text-stone-500 dark:text-stone-400 font-bold flex items-center gap-1.5">
                <span>⚡ 1-Click Employee Payroll Selector</span>
                <span class="text-stone-400">•</span>
                <span class="text-[#8F7239] dark:text-[#C6A15B]">Instant Salary &amp; Slip Filter</span>
            </span>
            @if($selectedStaffId !== 'ALL')
                <button type="button" wire:click="selectStaff('ALL')" class="text-[11px] font-mono text-[#8F7239] dark:text-[#C6A15B] hover:underline font-bold cursor-pointer">
                    ✕ Reset to All Employees
                </button>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" wire:click="selectStaff('ALL')" 
                    class="px-3.5 py-1.5 text-xs font-mono font-bold uppercase transition border cursor-pointer {{ $selectedStaffId === 'ALL' ? 'bg-[#C6A15B] text-[#171513] border-[#C6A15B] shadow-md' : 'bg-[#F8F5EF] dark:bg-white/5 text-stone-700 dark:text-stone-300 border-[#E7E5E4] dark:border-white/15 hover:border-[#C6A15B]' }}">
                👥 All Staff Payroll
            </button>
            @foreach($staffMembers as $sm)
                <button type="button" wire:click="selectStaff('{{ $sm->id }}')" 
                        class="px-3 py-1.5 text-xs font-mono transition border flex items-center gap-1.5 cursor-pointer {{ $selectedStaffId == $sm->id ? 'bg-[#171513] text-[#C6A15B] border-[#C6A15B] shadow-[0_0_15px_rgba(198,161,91,0.35)] font-bold dark:bg-[#C6A15B]/20' : 'bg-[#F8F5EF] dark:bg-white/5 text-stone-700 dark:text-stone-300 border-[#E7E5E4] dark:border-white/15 hover:border-[#C6A15B]' }}">
                    <span class="w-2 h-2 rounded-full {{ $selectedStaffId == $sm->id ? 'bg-[#C6A15B]' : 'bg-stone-400' }}"></span>
                    <span>{{ $sm->name }}</span>
                    <span class="text-[10px] text-stone-400 dark:text-stone-500">({{ str_replace('ROLE_', '', $sm->role) }})</span>
                </button>
            @endforeach
        </div>
    </div>

    <!-- Employee Payroll Spotlight Banner -->
    @if($selectedStaffId !== 'ALL' && ($spotlightEmployee = $staffMembers->firstWhere('id', (int) $selectedStaffId)))
        <div class="bg-[#C6A15B]/10 border border-[#C6A15B]/40 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 border border-[#C6A15B] bg-[#171513] text-[#C6A15B] flex items-center justify-center font-cinzel font-bold text-lg">
                    {{ substr($spotlightEmployee->name, 0, 1) }}
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-sm text-[#171513] dark:text-white">{{ $spotlightEmployee->name }}</span>
                        <span class="text-[10px] font-mono uppercase bg-[#C6A15B]/20 text-[#8F7239] dark:text-[#C6A15B] px-1.5 py-0.5 font-bold">
                            {{ str_replace('ROLE_', '', $spotlightEmployee->role) }}
                        </span>
                    </div>
                    <span class="text-xs font-mono text-stone-500 dark:text-stone-400">
                        Default Base Salary: {{ $spotlightEmployee->base_salary ? 'PKR ' . number_format((float) $spotlightEmployee->base_salary) : 'Role default' }} • {{ $spotlightEmployee->email }}
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-2 font-mono text-xs">
                <a href="{{ route('staff.attendance', ['staff_id' => $spotlightEmployee->id]) }}" 
                   class="btn-outline-gold !h-8 !px-3 font-bold uppercase inline-flex items-center gap-1">
                    <span>📅 Attendance &amp; OT Logs &rarr;</span>
                </a>
                <a href="{{ route('staff') }}" 
                   class="btn-gold !h-8 !px-3 font-bold uppercase inline-flex items-center gap-1 shadow-sm">
                    <span>👤 Master Profile &rarr;</span>
                </a>
            </div>
        </div>
    @endif

    <!-- Month Filter & Metric Cards -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-4 transition-colors">
        <div class="flex items-center gap-3">
            <label class="text-xs font-mono uppercase text-[#171513] dark:text-[#C6A15B] font-bold">Payroll Month:</label>
            <input type="month" wire:model.live="selectedMonth" 
                   class="bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 text-[#171513] dark:text-white text-xs font-mono px-3 py-1.5 outline-none focus:border-[#C6A15B]">
            <span class="text-xs font-serif italic text-stone-500 dark:text-stone-400">
                Period: {{ \Carbon\Carbon::parse($selectedMonth . '-01')->format('F Y') }}
            </span>
            <label for="payroll-staff-filter" class="sr-only">Filter payroll by employee</label>
            <select id="payroll-staff-filter" wire:model.live="selectedStaffId" class="bg-[#171513] border border-[#C6A15B]/40 px-3 py-1.5 text-xs text-white">
                <option value="ALL">All current staff</option>
                @foreach($staffMembers as $staffMember)
                    <option value="{{ $staffMember->id }}">{{ $staffMember->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-xs font-mono text-stone-500 dark:text-stone-400">{{ $payrolls->count() }} Employees Processed</span>
        </div>
    </div>

    <!-- Financial KPI Summary Strip -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-4 space-y-1 shadow-sm dark:shadow-xl relative overflow-hidden group hover:border-[#C6A15B] transition duration-300">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <span class="text-[10px] uppercase font-mono tracking-widest text-stone-500 dark:text-stone-400 font-bold block">Total Basic Salaries</span>
            <div class="font-cinzel text-xl text-[#171513] dark:text-white font-bold">PKR {{ number_format($totalBasic) }}</div>
        </div>

        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-4 space-y-1 shadow-sm dark:shadow-xl relative overflow-hidden group hover:border-[#C6A15B] transition duration-300">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <span class="text-[10px] uppercase font-mono tracking-widest text-stone-500 dark:text-stone-400 font-bold block">Total Overtime Pay</span>
            <div class="font-cinzel text-xl text-[#8F7239] dark:text-[#C6A15B] font-bold">PKR {{ number_format($totalOvertime) }}</div>
        </div>

        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-4 space-y-1 shadow-sm dark:shadow-xl relative overflow-hidden group hover:border-[#C6A15B] transition duration-300">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <span class="text-[10px] uppercase font-mono tracking-widest text-stone-500 dark:text-stone-400 font-bold block">Disbursed (Paid)</span>
            <div class="font-cinzel text-xl text-[#8F7239] dark:text-[#C6A15B] font-bold">PKR {{ number_format($totalPaid) }}</div>
        </div>

        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-4 space-y-1 shadow-sm dark:shadow-xl relative overflow-hidden group hover:border-[#C6A15B] transition duration-300">
            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-[#C6A15B] to-transparent absolute top-0 left-0 opacity-0 group-hover:opacity-100 dark:opacity-60 transition duration-300"></div>
            <span class="text-[10px] uppercase font-mono tracking-widest text-stone-500 dark:text-stone-400 font-bold block">Outstanding (Pending)</span>
            <div class="font-cinzel text-xl text-[#171513] dark:text-[#F8F5EF] font-bold">PKR {{ number_format($totalPending) }}</div>
        </div>
    </div>

    <!-- Payroll Master Table -->
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-6 space-y-4 shadow-sm dark:shadow-xl">
        <div class="border-b border-[#E7E5E4] pb-3 flex items-center justify-between">
            <h3 class="font-serif text-2xl text-[#171513] font-normal">Salary Disbursement Registry</h3>
            <span class="text-xs font-mono text-stone-500">Auto-calculated Net Salary = Basic + OT + Allowances - Deductions</span>
        </div>

        <div class="table-wrapper-enterprise" tabindex="0" role="region"><table class="table-enterprise">
                <thead>
                    <tr class="bg-[#F8F5EF] dark:bg-white/5 border-b border-[#E7E5E4] dark:border-white/10 text-[10px] uppercase font-bold tracking-wider text-stone-600 dark:text-stone-300 font-mono">
                        <th class="p-3">Employee Name</th>
                        <th class="p-3">Designation</th>
                        <th class="p-3">Basic Pay</th>
                        <th class="p-3">Overtime (OT)</th>
                        <th class="p-3">Allowances</th>
                        <th class="p-3">Deductions</th>
                        <th class="p-3 text-[#8F7239]">Net Payable (PKR)</th>
                        <th class="p-3">Disbursement Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E7E5E4]">
                    @forelse($payrolls as $p)
                        <tr class="hover:bg-[#F8F5EF] dark:hover:bg-white/5 transition font-mono">
                            <td class="p-3 font-sans">
                                <div class="font-bold text-[#171513] text-sm">{{ $p->user->name ?? 'Staff' }}</div>
                                <span class="text-[10px] text-stone-500 font-mono">{{ $p->user->phone ?: $p->user->email }}</span>
                            </td>
                            <td class="p-3 text-stone-600">
                                <span class="px-2 py-0.5 bg-stone-100 border border-stone-200 uppercase font-semibold text-[10px]">
                                    {{ str_replace('ROLE_', '', $p->user->role ?? '') }}
                                </span>
                            </td>
                            <td class="p-3 text-stone-800">
                                PKR {{ number_format($p->basic_salary) }}
                            </td>
                            <td class="p-3 text-stone-800">
                                <div>{{ number_format($p->overtime_hours, 1) }}h @ {{ number_format($p->overtime_rate) }}</div>
                                <span class="text-[10px] text-[#8F7239] font-semibold">+ PKR {{ number_format($p->overtime_hours * $p->overtime_rate) }}</span>
                            </td>
                            <td class="p-3 text-[#8F7239] font-semibold">
                                + PKR {{ number_format($p->allowances) }}
                            </td>
                            <td class="p-3 text-[#171513] font-semibold">
                                - PKR {{ number_format($p->deductions) }}
                            </td>
                            <td class="p-3 font-bold text-sm text-[#171513]">
                                PKR {{ number_format($p->net_salary) }}
                            </td>
                            <td class="p-3">
                                @if($p->status === 'PAID')
                                    <span class="px-2 py-0.5 bg-[#C6A15B]/20 text-[#8F7239] border border-[#C6A15B]/60 font-bold uppercase text-[10px]">
                                        ✓ PAID ({{ $p->payment_method }})
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 bg-[#C6A15B]/20 text-[#8F7239] border border-[#C6A15B]/60 font-bold uppercase text-[10px]">
                                        ⏳ DRAFT / UNPAID
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center justify-end gap-1.5 font-sans">
                                    <button wire:click="openEditModal('{{ $p->id }}')" 
                                            class="tbtn tbtn-outline" title="Edit Payroll Entry">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        <span>Edit</span>
                                    </button>

                                    @if($p->status !== 'PAID')
                                        <button wire:click="openPayModal('{{ $p->id }}')" 
                                                class="tbtn tbtn-gold" title="Disburse Salary">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                            <span>Pay Now</span>
                                        </button>
                                    @endif

                                    <a href="{{ route('documents.salary-slip', $p->id) }}" target="_blank"
                                       class="tbtn tbtn-dark" title="Download Salary Slip">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span>Slip ↗</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-stone-400 font-sans">
                                No payroll entries compiled for {{ \Carbon\Carbon::parse($selectedMonth . '-01')->format('F Y') }}. Click "Compile Monthly Payroll" above to generate them automatically.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===================================================================
         MODAL 1: EDIT SALARY ADJUSTMENTS
         =================================================================== -->
    @if($showEditModal)
        <div class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4">
            <div class="bg-[#171513] border-2 border-[#C6A15B] max-w-lg w-full p-6 space-y-5 text-white shadow-2xl">
                <div class="border-b border-white/10 pb-3 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono uppercase text-[#C6A15B] tracking-widest font-bold">Payroll Adjustments</span>
                        <h3 class="font-serif text-2xl text-white font-normal mt-0.5">{{ $editingEmployeeName }}</h3>
                    </div>
                    <button wire:click="$set('showEditModal', false)" class="text-white/60 hover:text-white text-2xl font-mono leading-none">&times;</button>
                </div>

                <div class="space-y-4 font-sans text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Basic Salary (PKR) *</label>
                            <input type="number" wire:model.live="editBasicSalary" wire:change="recalculateEditNet" 
                                   class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Payment Mode</label>
                            <select wire:model="editPaymentMethod" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                                <option value="CASH">Cash Disbursement</option>
                                <option value="BANK_TRANSFER">Bank Direct Deposit</option>
                                <option value="CHEQUE">Company Cheque</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Overtime Hours</label>
                            <input type="number" step="0.5" wire:model.live="editOvertimeHours" wire:change="recalculateEditNet" 
                                   class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">OT Hourly Rate (PKR)</label>
                            <input type="number" step="10" wire:model.live="editOvertimeRate" wire:change="recalculateEditNet" 
                                   class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-mono uppercase text-[#8F7239] mb-1 font-semibold">+ Allowances / Bonus (PKR)</label>
                            <input type="number" step="500" wire:model.live="editAllowances" wire:change="recalculateEditNet" 
                                   class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-mono uppercase text-[#171513] mb-1 font-semibold">- Deductions / Advances (PKR)</label>
                            <input type="number" step="500" wire:model.live="editDeductions" wire:change="recalculateEditNet" 
                                   class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B] font-mono">
                        </div>
                    </div>

                    <div class="p-3 bg-[#171513] border border-[#C6A15B]/40 flex items-center justify-between">
                        <span class="text-xs font-mono uppercase text-white/80">Net Payable Total:</span>
                        <strong class="font-cinzel text-xl text-[#C6A15B]">PKR {{ number_format($editNetSalary) }}</strong>
                    </div>

                    <div>
                        <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Remarks / Pay Notes</label>
                        <input type="text" wire:model="editNotes" placeholder="e.g. Ramadan bonus included" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                    </div>

                    <div class="p-2.5 bg-black/40 border border-[#C6A15B]/30 rounded-none">
                        <label class="inline-flex items-center gap-2 text-xs font-mono text-[#C6A15B] cursor-pointer">
                            <input type="checkbox" wire:model="updateBaseSalaryOnProfile" class="accent-[#C6A15B] w-4 h-4">
                            <span>Also permanently update {{ $editingEmployeeName }}'s default base salary on their master profile</span>
                        </label>
                    </div>
                </div>

                <div class="border-t border-white/10 pt-3 flex items-center justify-end gap-2 font-mono text-xs">
                    <button wire:click="$set('showEditModal', false)" class="px-4 py-2 border border-white/20 text-white">Cancel</button>
                    <button wire:click="updatePayroll" class="btn-gold !h-9 !px-5 text-xs font-bold uppercase">Save Adjustments &rarr;</button>
                </div>
            </div>
        </div>
    @endif

    <!-- ===================================================================
         MODAL 2: MARK AS PAID & POST EXPENSE
         =================================================================== -->
    @if($showPayModal)
        <div class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4">
            <div class="bg-[#171513] border-2 border-[#C6A15B]/60 max-w-md w-full p-6 space-y-5 text-white shadow-2xl">
                <div class="border-b border-white/10 pb-3 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono uppercase text-[#8F7239] tracking-widest font-bold">Disbursement Confirmation</span>
                        <h3 class="font-serif text-xl text-white font-normal mt-0.5">Disburse Salary</h3>
                    </div>
                    <button wire:click="$set('showPayModal', false)" class="text-white/60 hover:text-white text-2xl font-mono leading-none">&times;</button>
                </div>

                <div class="space-y-4 font-sans text-xs">
                    <p class="text-stone-300">
                        This action will mark the salary as <strong>PAID</strong> and automatically log an entry in your ERP Expense Ledger under <span class="font-mono text-[#C6A15B]">STAFF_WAGES</span>.
                    </p>

                    <div>
                        <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Payment Method *</label>
                        <select wire:model="payMethod" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                            <option value="BANK_TRANSFER">Bank Direct Deposit / Raast</option>
                            <option value="CASH">Cash Disbursement</option>
                            <option value="CHEQUE">Bank Cheque</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-mono uppercase text-[#C6A15B] mb-1 font-semibold">Disbursement Date *</label>
                        <input type="date" wire:model="payDate" class="w-full bg-[#171513] border border-white/20 text-white text-xs px-3 py-2 outline-none focus:border-[#C6A15B]">
                    </div>
                </div>

                <div class="border-t border-white/10 pt-3 flex items-center justify-end gap-2 font-mono text-xs">
                    <button wire:click="$set('showPayModal', false)" class="px-4 py-2 border border-white/20 text-white">Cancel</button>
                    <button wire:click="markAsPaid" class="px-5 py-2 bg-[#C6A15B] hover:bg-[#C6A15B] text-white font-bold uppercase">
                        Confirm Payout &rarr;
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
