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

    <!-- Month Filter & Metric Cards -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-4 transition-colors">
        <div class="flex items-center gap-3">
            <label class="text-xs font-mono uppercase text-[#171513] dark:text-[#C6A15B] font-bold">Payroll Month:</label>
            <input type="month" wire:model.live="selectedMonth" 
                   class="bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 text-[#171513] dark:text-white text-xs font-mono px-3 py-1.5 outline-none focus:border-[#C6A15B]">
            <span class="text-xs font-serif italic text-stone-500 dark:text-stone-400">
                Period: {{ \Carbon\Carbon::parse($selectedMonth . '-01')->format('F Y') }}
            </span>
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
    <div class="bg-white border border-[#E7E5E4] p-6 space-y-4 shadow-sm">
        <div class="border-b border-[#E7E5E4] pb-3 flex items-center justify-between">
            <h3 class="font-serif text-2xl text-[#171513] font-normal">Salary Disbursement Registry</h3>
            <span class="text-xs font-mono text-stone-500">Auto-calculated Net Salary = Basic + OT + Allowances - Deductions</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left border-collapse border border-[#E7E5E4]">
                <thead>
                    <tr class="bg-[#F8F5EF] border-b border-[#E7E5E4] text-[10px] uppercase font-bold tracking-wider text-stone-600 font-mono">
                        <th class="p-3 border-r border-[#E7E5E4]">Employee Name</th>
                        <th class="p-3 border-r border-[#E7E5E4]">Designation</th>
                        <th class="p-3 border-r border-[#E7E5E4]">Basic Pay</th>
                        <th class="p-3 border-r border-[#E7E5E4]">Overtime (OT)</th>
                        <th class="p-3 border-r border-[#E7E5E4]">Allowances</th>
                        <th class="p-3 border-r border-[#E7E5E4]">Deductions</th>
                        <th class="p-3 border-r border-[#E7E5E4] text-[#8F7239]">Net Payable (PKR)</th>
                        <th class="p-3 border-r border-[#E7E5E4]">Disbursement Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E7E5E4]">
                    @forelse($payrolls as $p)
                        <tr class="hover:bg-[#F8F5EF] transition font-mono">
                            <td class="p-3 border-r border-[#E7E5E4] font-sans">
                                <div class="font-bold text-[#171513] text-sm">{{ $p->user->name ?? 'Staff' }}</div>
                                <span class="text-[10px] text-stone-500 font-mono">{{ $p->user->phone ?: $p->user->email }}</span>
                            </td>
                            <td class="p-3 border-r border-[#E7E5E4] text-stone-600">
                                <span class="px-2 py-0.5 bg-stone-100 border border-stone-200 uppercase font-semibold text-[10px]">
                                    {{ str_replace('ROLE_', '', $p->user->role ?? '') }}
                                </span>
                            </td>
                            <td class="p-3 border-r border-[#E7E5E4] text-stone-800">
                                PKR {{ number_format($p->basic_salary) }}
                            </td>
                            <td class="p-3 border-r border-[#E7E5E4] text-stone-800">
                                <div>{{ number_format($p->overtime_hours, 1) }}h @ {{ number_format($p->overtime_rate) }}</div>
                                <span class="text-[10px] text-[#8F7239] font-semibold">+ PKR {{ number_format($p->overtime_hours * $p->overtime_rate) }}</span>
                            </td>
                            <td class="p-3 border-r border-[#E7E5E4] text-[#8F7239] font-semibold">
                                + PKR {{ number_format($p->allowances) }}
                            </td>
                            <td class="p-3 border-r border-[#E7E5E4] text-[#171513] font-semibold">
                                - PKR {{ number_format($p->deductions) }}
                            </td>
                            <td class="p-3 border-r border-[#E7E5E4] font-bold text-sm text-[#171513]">
                                PKR {{ number_format($p->net_salary) }}
                            </td>
                            <td class="p-3 border-r border-[#E7E5E4]">
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
                            <td class="p-3 text-right">
                                <div class="flex items-center justify-end gap-1.5 text-[11px]">
                                    <button wire:click="openEditModal('{{ $p->id }}')" 
                                            class="px-2.5 py-1 bg-stone-100 hover:bg-stone-200 text-stone-800 border border-stone-300">
                                        Edit
                                    </button>

                                    @if($p->status !== 'PAID')
                                        <button wire:click="openPayModal('{{ $p->id }}')" 
                                                class="px-2.5 py-1 bg-[#C6A15B] hover:bg-[#C6A15B] text-white font-bold">
                                            Pay Now
                                        </button>
                                    @endif

                                    <a href="{{ route('documents.salary-slip', $p->id) }}" target="_blank"
                                       class="px-2.5 py-1 bg-[#171513] text-[#C6A15B] border border-[#C6A15B] hover:bg-[#C6A15B] hover:text-[#171513] transition">
                                        Slip ↗
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
