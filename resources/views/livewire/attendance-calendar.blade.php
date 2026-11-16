<div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6 text-[#171513] dark:text-stone-200">
    @if(session('status'))
        <div class="p-3 border border-emerald-300 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-300 text-sm">{{ session('status') }}</div>
    @endif

    <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 p-6 sm:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6 shadow-xl">
        <div class="space-y-1.5">
            <div class="text-[10px] font-mono uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold">
                Enterprise ERP / Workforce &amp; Human Resources
            </div>
            <h1 class="font-serif-luxury text-2xl sm:text-4xl font-bold">Attendance Calendar</h1>
            <p class="text-xs text-stone-600 dark:text-stone-300 max-w-2xl">
                Day-by-day attendance, approved leave, weekly offs and holidays — derived from real records.
            </p>
        </div>
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label for="staff-filter" class="block text-[10px] uppercase font-mono tracking-wider text-[#8F7239] dark:text-[#C6A15B] mb-1 font-bold">Employee</label>
                <select id="staff-filter" wire:model.live="selectedStaffId" class="bg-[#F8F5EF] dark:bg-[#24211E] border border-[#E7E5E4] dark:border-white/20 text-xs px-3 py-2 font-mono outline-none focus:border-[#C6A15B]">
                    <option value="ALL">All active staff</option>
                    @foreach(\App\Models\User::hallStaff(auth()->user()->tenant_id)->orderBy('name')->get() as $option)
                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>
            <button wire:click="openHolidayModal" class="lux-btn lux-btn--gold lux-btn--sm">+ Add Holiday</button>
            <a href="{{ route('staff.attendance') }}" class="lux-btn lux-btn--outline lux-btn--sm">Daily Register</a>
        </div>
    </div>

    @if($feedbackMessage)
        <div class="p-3 border border-emerald-300 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-300 text-xs font-mono" role="status">{{ $feedbackMessage }}</div>
    @endif
    @if($errorMessage)
        <div class="p-3 border border-red-300 bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-xs font-mono" role="alert">{{ $errorMessage }}</div>
    @endif

    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-4 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm">
        <div class="flex items-center gap-2">
            <button wire:click="previousMonth" aria-label="Previous month" class="w-8 h-8 border border-[#E7E5E4] dark:border-white/10 hover:border-[#C6A15B] bg-[#F8F5EF] dark:bg-white/5 flex items-center justify-center">&larr;</button>
            <h2 class="font-cinzel text-xl sm:text-2xl font-bold px-3 min-w-[190px] text-center">{{ $monthTitle }}</h2>
            <button wire:click="nextMonth" aria-label="Next month" class="w-8 h-8 border border-[#E7E5E4] dark:border-white/10 hover:border-[#C6A15B] bg-[#F8F5EF] dark:bg-white/5 flex items-center justify-center">&rarr;</button>
            <button wire:click="goToToday" class="ml-2 px-3 py-1.5 border border-[#E7E5E4] dark:border-white/10 text-xs font-mono uppercase tracking-wider">Today</button>
        </div>
        <div class="flex flex-wrap items-center gap-2 text-[10px] font-mono">
            @foreach([
                'PRESENT' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                'ABSENT' => 'bg-red-100 text-red-800 border-red-300',
                'LATE' => 'bg-amber-100 text-amber-900 border-amber-300',
                'HALF_DAY' => 'bg-orange-100 text-orange-800 border-orange-300',
                'ON_LEAVE' => 'bg-violet-100 text-violet-800 border-violet-300',
                'WEEKLY_OFF' => 'bg-stone-100 text-stone-600 border-stone-300',
                'HOLIDAY' => 'bg-sky-100 text-sky-800 border-sky-300',
                'NOT_MARKED' => 'bg-white text-stone-400 border-stone-200',
            ] as $legendStatus => $legendClass)
                <span class="inline-flex items-center px-2 py-0.5 border {{ $legendClass }} font-bold uppercase">{{ str_replace('_', ' ', $legendStatus) }}</span>
            @endforeach
        </div>
    </div>

    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 shadow-sm overflow-x-auto">
        <table class="min-w-full border-collapse text-[11px] font-mono">
            <thead>
                <tr class="bg-[#F8F5EF] dark:bg-[#24211E] text-left">
                    <th class="sticky left-0 z-10 bg-[#F8F5EF] dark:bg-[#24211E] px-3 py-2 border-b border-r border-[#E7E5E4] dark:border-white/10 uppercase tracking-wider min-w-[190px]">Employee</th>
                    @foreach($days as $day)
                        <th class="px-1 py-2 text-center border-b border-[#E7E5E4] dark:border-white/10 min-w-[38px] {{ $day->isToday() ? 'text-[#C6A15B]' : '' }}">
                            <div class="font-bold">{{ $day->format('j') }}</div>
                            <div class="text-[9px] text-stone-400 uppercase">{{ $day->format('D') }}</div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($staff as $member)
                    <tr class="hover:bg-stone-50 dark:hover:bg-white/5">
                        <th class="sticky left-0 z-10 bg-white dark:bg-[#171513] px-3 py-2 border-b border-r border-[#E7E5E4] dark:border-white/10 text-left font-sans font-semibold whitespace-nowrap">
                            {{ $member->name }}
                            <span class="block text-[9px] font-mono text-stone-400">{{ str_replace('ROLE_', '', $member->role) }}</span>
                        </th>
                        @foreach($days as $day)
                            @php
                                $key = $member->id.'_'.$day->format('Y-m-d');
                                $cell = $cells[$key] ?? ['status' => 'NOT_MARKED', 'detail' => ''];
                                $classes = match($cell['status']) {
                                    'PRESENT' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                    'ABSENT' => 'bg-red-100 text-red-800 border-red-300',
                                    'LATE' => 'bg-amber-100 text-amber-900 border-amber-300',
                                    'HALF_DAY' => 'bg-orange-100 text-orange-800 border-orange-300',
                                    'ON_LEAVE' => 'bg-violet-100 text-violet-800 border-violet-300',
                                    'WEEKLY_OFF' => 'bg-stone-100 text-stone-500 border-stone-300',
                                    'HOLIDAY' => 'bg-sky-100 text-sky-800 border-sky-300',
                                    default => 'bg-white text-stone-300 border-stone-200',
                                };
                                $short = match($cell['status']) {
                                    'PRESENT' => 'P', 'ABSENT' => 'A', 'LATE' => 'L', 'HALF_DAY' => '½',
                                    'ON_LEAVE' => 'LV', 'WEEKLY_OFF' => 'WO', 'HOLIDAY' => 'H', default => '·',
                                };
                            @endphp
                            <td class="px-0.5 py-1 border-b border-[#E7E5E4] dark:border-white/10 text-center">
                                <a href="{{ route('staff.attendance', ['staff_id' => $member->id, 'date' => $day->format('Y-m-d')]) }}"
                                   title="{{ $day->format('d M Y') }} — {{ str_replace('_', ' ', $cell['status']) }}{{ $cell['detail'] ? ' ('.$cell['detail'].')' : '' }}"
                                   aria-label="{{ $member->name }} {{ $day->format('d M Y') }}: {{ str_replace('_', ' ', $cell['status']) }}"
                                   class="inline-flex items-center justify-center w-7 h-7 border {{ $classes }} font-bold hover:opacity-75 transition">{{ $short }}</a>
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($days) + 1 }}" class="p-10 text-center text-stone-400 font-sans">No active staff to display.</td>
                    </tr>
                @endforelse
            </tbody>
            @if($staff->isNotEmpty())
                <tfoot>
                    <tr class="bg-[#F8F5EF] dark:bg-[#24211E] font-bold">
                        <th class="sticky left-0 z-10 bg-[#F8F5EF] dark:bg-[#24211E] px-3 py-2 border-r border-[#E7E5E4] dark:border-white/10 text-left">Monthly totals</th>
                        <td colspan="{{ count($days) }}" class="px-3 py-2 text-stone-600 dark:text-stone-300">
                            @foreach($summary as $status => $count)
                                <span class="inline-block mr-3">{{ str_replace('_', ' ', $status) }}: <strong class="text-[#8F7239] dark:text-[#C6A15B]">{{ $count }}</strong></span>
                            @endforeach
                        </td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    @if($holidays->isNotEmpty())
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-5 shadow-sm">
            <h3 class="font-cinzel text-lg font-bold mb-3">Holidays in {{ $monthTitle }}</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($holidays as $holiday)
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 border border-sky-300 bg-sky-50 dark:bg-sky-900/20 text-sky-800 dark:text-sky-300 text-[11px] font-mono">
                        <strong>{{ $holiday->date->format('d M') }}</strong> {{ $holiday->name }}
                        <span class="text-[9px] uppercase opacity-70">{{ $holiday->type }}</span>
                        <button wire:click="deleteHoliday('{{ $holiday->id }}')" wire:confirm="Remove this holiday?" class="ml-1 text-red-600 dark:text-red-400 font-bold" aria-label="Remove holiday">&times;</button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($showHolidayModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="bg-white dark:bg-[#171513] border-2 border-[#C6A15B] max-w-md w-full p-6 shadow-2xl space-y-4" role="dialog" aria-modal="true" aria-labelledby="holiday-title">
                <div class="flex items-start justify-between border-b border-[#E7E5E4] dark:border-white/10 pb-4">
                    <h3 id="holiday-title" class="font-cinzel text-xl font-bold">Add Holiday</h3>
                    <button wire:click="closeHolidayModal" class="text-xl font-bold leading-none" aria-label="Close">&times;</button>
                </div>
                <div class="space-y-3">
                    <div>
                        <label for="holiday-date" class="block text-[10px] uppercase font-mono font-bold text-[#8F7239] dark:text-[#C6A15B] mb-1">Date</label>
                        <input id="holiday-date" type="date" wire:model="holidayDate" class="w-full bg-[#F8F5EF] dark:bg-[#24211E] border border-[#E7E5E4] dark:border-white/20 px-3 py-2 text-sm outline-none focus:border-[#C6A15B]">
                        @error('holidayDate') <span class="text-red-600 text-[11px]">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="holiday-name" class="block text-[10px] uppercase font-mono font-bold text-[#8F7239] dark:text-[#C6A15B] mb-1">Name</label>
                        <input id="holiday-name" type="text" wire:model="holidayName" placeholder="e.g. Eid ul Fitr" class="w-full bg-[#F8F5EF] dark:bg-[#24211E] border border-[#E7E5E4] dark:border-white/20 px-3 py-2 text-sm outline-none focus:border-[#C6A15B]">
                        @error('holidayName') <span class="text-red-600 text-[11px]">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="holiday-type" class="block text-[10px] uppercase font-mono font-bold text-[#8F7239] dark:text-[#C6A15B] mb-1">Type</label>
                        <select id="holiday-type" wire:model="holidayType" class="w-full bg-[#F8F5EF] dark:bg-[#24211E] border border-[#E7E5E4] dark:border-white/20 px-3 py-2 text-sm outline-none focus:border-[#C6A15B]">
                            <option value="PUBLIC">Public Holiday</option>
                            <option value="COMPANY">Company Holiday</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button wire:click="closeHolidayModal" class="lux-btn lux-btn--outline lux-btn--sm">Cancel</button>
                    <button wire:click="addHoliday" class="btn-gold !h-9 !px-5 text-xs font-bold">Save Holiday</button>
                </div>
            </div>
        </div>
    @endif
</div>
