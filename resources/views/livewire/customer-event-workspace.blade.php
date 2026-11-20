<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    <!-- Header -->
    <div class="bg-[#171513] text-white border border-[#C6A15B]/50 p-6 sm:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6 shadow-xl">
        <div class="space-y-2">
            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 border border-[#C6A15B]/50 bg-[#C6A15B]/10 text-[#E8DCBE] text-[10px] font-mono uppercase tracking-widest font-bold">
                💍 My Wedding Planner
            </div>
            <h1 class="font-serif-luxury text-3xl sm:text-4xl font-normal">
                Event Workspace
            </h1>
            <p class="text-xs text-stone-300 font-sans max-w-2xl leading-relaxed">
                Plan every function of your wedding in one place — Mehndi, Baraat, Walima, Nikkah or corporate events — with your vendor shortlist, checklist and budget.
            </p>
        </div>

        <button wire:click="openCreateEventModal" class="btn-gold !h-10 !px-5 text-xs font-bold uppercase font-mono shadow-md">
            + New Event
        </button>
    </div>

    @if($feedbackMessage)
        <div class="p-4 bg-[#171513]/90 border border-[#C6A15B]/60 text-[#C6A15B] text-xs font-semibold shadow-md">
            ✓ {{ $feedbackMessage }}
        </div>
    @endif

    @if($errorMessage)
        <div class="p-4 bg-[#171513]/90 border border-[#C6A15B]/60 text-[#F8F5EF] text-xs font-semibold shadow-md">
            ⚠️ {{ $errorMessage }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Event list -->
        <div class="lg:col-span-4 space-y-4">
            <h2 class="font-serif-luxury text-xl text-[#171513] dark:text-white">My Events</h2>

            @if($this->events->isEmpty())
                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-8 text-center shadow-sm">
                    <p class="text-sm text-stone-600 dark:text-stone-300">No events yet. Create your first function to start planning.</p>
                    <button wire:click="openCreateEventModal" class="btn-gold mt-5 !h-10 !px-5 text-xs font-bold uppercase font-mono">
                        + New Event
                    </button>
                </div>
            @else
                @foreach($this->events as $event)
                    <button type="button"
                        wire:click="selectEvent('{{ $event->id }}')"
                        class="w-full text-left bg-white dark:bg-[#171513] border {{ $selectedEventId === $event->id ? 'border-[#C6A15B]' : 'border-[#E7E5E4] dark:border-[#C6A15B]/30' }} p-5 shadow-sm hover:border-[#C6A15B] transition">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <span class="text-[10px] font-mono uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold">
                                    {{ str_replace('_', ' ', $event->event_type) }}
                                </span>
                                <h3 class="font-serif-luxury text-lg text-[#171513] dark:text-white truncate">{{ $event->name }}</h3>
                                <p class="text-xs text-stone-500 dark:text-stone-400 font-sans">
                                    {{ $event->event_date?->format('M d, Y') ?? 'Date to be decided' }}
                                    @if($event->city) · {{ $event->city }} @endif
                                </p>
                            </div>
                            <span class="px-2 py-0.5 bg-[#171513] dark:bg-[#C6A15B] text-white dark:text-[#171513] text-[10px] font-mono uppercase tracking-widest font-bold shrink-0">
                                {{ $event->status }}
                            </span>
                        </div>
                        <div class="flex flex-wrap items-center gap-4 mt-3 text-[11px] text-stone-500 dark:text-stone-400 font-sans">
                            <span>{{ $event->bookings_count }} bookings</span>
                            <span>{{ $event->vendors_count }} vendors</span>
                            <span>{{ $event->tasks_count }} tasks</span>
                        </div>
                    </button>
                @endforeach
            @endif
        </div>

        <!-- Event detail -->
        <div class="lg:col-span-8 space-y-6">
            @if(! $this->selectedEvent)
                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-12 text-center shadow-sm">
                    <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white mb-2">Select an event</h3>
                    <p class="text-sm text-stone-600 dark:text-stone-300">Choose one of your events to see its vendors, checklist, bookings and budget.</p>
                </div>
            @else
                @php($event = $this->selectedEvent)
                @php($budget = $this->eventBudget)

                <!-- Detail header -->
                <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/40 p-6 shadow-sm dark:shadow-xl">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <span class="text-[10px] font-mono uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold">{{ str_replace('_', ' ', $event->event_type) }}</span>
                            <h2 class="font-serif-luxury text-2xl sm:text-3xl text-[#171513] dark:text-white">{{ $event->name }}</h2>
                            <p class="text-xs text-stone-600 dark:text-stone-300 font-sans mt-1">
                                {{ $event->event_date?->format('M d, Y') ?? 'Date to be decided' }}
                                @if($event->venue_text) · {{ $event->venue_text }} @endif
                                @if($event->guest_count) · {{ $event->guest_count }} guests @endif
                            </p>
                            <span class="inline-block mt-2 px-2 py-0.5 bg-[#C6A15B]/15 text-[#8F7239] dark:text-[#C6A15B] text-[10px] font-mono uppercase tracking-widest font-bold">
                                Status: {{ str_replace('_', ' ', $event->status) }}
                            </span>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button wire:click="openEditEventModal('{{ $event->id }}')" class="btn-outline-gold !h-9 !px-4 text-[10px] font-mono uppercase">Edit</button>
                            <button wire:click="deleteEvent('{{ $event->id }}')" wire:confirm="Remove this event? Linked bookings are kept but detached." class="btn-outline-gold !h-9 !px-4 text-[10px] font-mono uppercase">Remove</button>
                        </div>
                    </div>

                    <!-- Budget strip -->
                    <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 pt-6 border-t border-[#E7E5E4] dark:border-white/10">
                        <div>
                            <dt class="text-[10px] uppercase tracking-wider font-semibold text-stone-400 font-mono">Quoted</dt>
                            <dd class="font-serif-luxury text-xl text-[#171513] dark:text-white">{{ number_format($budget['quoted']) }} <span class="text-xs font-sans text-stone-500">PKR</span></dd>
                        </div>
                        <div>
                            <dt class="text-[10px] uppercase tracking-wider font-semibold text-stone-400 font-mono">Paid</dt>
                            <dd class="font-serif-luxury text-xl text-emerald-600 dark:text-emerald-400">{{ number_format($budget['paid']) }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] uppercase tracking-wider font-semibold text-stone-400 font-mono">Outstanding</dt>
                            <dd class="font-serif-luxury text-xl text-amber-600 dark:text-amber-400">{{ number_format($budget['outstanding']) }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] uppercase tracking-wider font-semibold text-stone-400 font-mono">Checklist</dt>
                            <dd class="font-serif-luxury text-xl text-[#171513] dark:text-white">{{ $budget['task_done'] }}<span class="text-xs font-sans text-stone-500">/{{ $budget['task_total'] }}</span></dd>
                        </div>
                    </dl>
                </div>

                <!-- Vendors -->
                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 shadow-sm dark:shadow-xl">
                    <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white mb-4">Vendor shortlist</h3>

                    @if($this->eventVendors->isEmpty())
                        <p class="text-sm text-stone-500 dark:text-stone-400 font-sans">No vendors shortlisted yet.</p>
                    @else
                        <div class="space-y-3">
                            @foreach($this->eventVendors as $row)
                                <div class="flex flex-wrap items-center justify-between gap-3 border border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#1C1917] p-4">
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-[#171513] dark:text-white">{{ $row->tenant?->name ?? 'Vendor' }}</p>
                                        <p class="text-[11px] text-stone-500 dark:text-stone-400 font-sans">
                                            {{ $row->tenant?->vendorCategory?->name ?? 'Service vendor' }}
                                            @if($row->booking) · {{ $row->booking->booking_reference }} @endif
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <select wire:change="updateVendorStatus('{{ $row->id }}', $event.target.value)" class="input-luxury !py-1.5 !text-[11px] w-auto">
                                            @foreach(\App\Models\CustomerEventVendor::STATUSES as $status)
                                                <option value="{{ $status }}" @selected($row->status === $status)>{{ ucfirst(strtolower($status)) }}</option>
                                            @endforeach
                                        </select>
                                        <button wire:click="detachVendor('{{ $row->id }}')" class="btn-outline-gold !h-8 !px-3 text-[10px] font-mono uppercase">Remove</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-5 pt-5 border-t border-[#E7E5E4] dark:border-white/10">
                        <label for="add-vendor" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-2">Add a vendor from the marketplace</label>
                        <div class="flex flex-wrap items-center gap-2">
                            <select id="add-vendor" class="input-luxury flex-1 min-w-[200px] text-sm">
                                @foreach($this->marketplaceVendors as $vendor)
                                    <option value="{{ $vendor->id }}">{{ $vendor->name }} — {{ $vendor->vendorCategory?->name ?? 'Vendor' }}</option>
                                @endforeach
                            </select>
                            <button wire:click="attachVendor($el.querySelector('#add-vendor').value)" class="btn-gold !h-10 !px-5 text-[11px] font-mono uppercase">Shortlist</button>
                        </div>
                        @if($this->marketplaceVendors->isEmpty())
                            <p class="text-xs text-stone-500 dark:text-stone-400 font-sans mt-2">Every marketplace vendor is already on this shortlist.</p>
                        @endif
                    </div>
                </div>

                <!-- Tasks -->
                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 shadow-sm dark:shadow-xl">
                    <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white mb-4">Checklist</h3>

                    @if($this->eventTasks->isEmpty())
                        <p class="text-sm text-stone-500 dark:text-stone-400 font-sans">No tasks yet — add your first planning step.</p>
                    @else
                        <ul class="space-y-2">
                            @foreach($this->eventTasks as $task)
                                <li class="flex items-center justify-between gap-3 border-b border-[#E7E5E4] dark:border-white/10 pb-2">
                                    <button type="button" wire:click="toggleTask('{{ $task->id }}')" class="flex items-center gap-3 text-left flex-1">
                                        <span class="w-4 h-4 border {{ $task->status === 'DONE' ? 'bg-[#C6A15B] border-[#C6A15B]' : 'border-stone-400' }} inline-block shrink-0"></span>
                                        <span class="text-sm font-sans {{ $task->status === 'DONE' ? 'line-through text-stone-400' : 'text-[#171513] dark:text-white' }}">{{ $task->title }}</span>
                                        @if($task->due_date)
                                            <span class="text-[10px] font-mono uppercase tracking-wider text-stone-400">{{ $task->due_date->format('M d') }}</span>
                                        @endif
                                    </button>
                                    <button wire:click="deleteTask('{{ $task->id }}')" class="text-[10px] font-mono uppercase text-stone-400 hover:text-red-600">Remove</button>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <form wire:submit="addTask" class="mt-5 pt-5 border-t border-[#E7E5E4] dark:border-white/10 flex flex-wrap items-end gap-3">
                        <div class="flex-1 min-w-[200px]">
                            <label for="task-title" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">New task</label>
                            <input id="task-title" type="text" wire:model="taskTitle" placeholder="e.g. Confirm menu tasting date" class="input-luxury w-full text-sm">
                            @error('taskTitle') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="task-due" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Due date</label>
                            <input id="task-due" type="date" wire:model="taskDueDate" class="input-luxury text-sm">
                        </div>
                        <button type="submit" class="btn-gold !h-10 !px-5 text-[11px] font-mono uppercase">Add Task</button>
                    </form>
                </div>

                <!-- Bookings -->
                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-6 shadow-sm dark:shadow-xl">
                    <div class="flex items-start justify-between gap-3 flex-wrap mb-4">
                        <div>
                            <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white">Bookings on this event</h3>
                            <p class="text-xs text-stone-500 dark:text-stone-400 font-sans mt-1">Service bookings you’ve made (direct or via accepted quotations) linked to this function.</p>
                        </div>
                    </div>

                    @if($this->eventBookings->isEmpty())
                        <p class="text-sm text-stone-500 dark:text-stone-400 font-sans">No service bookings linked yet. Link one of your existing bookings below, or book vendors from the marketplace.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs font-sans">
                                <thead class="text-[10px] uppercase tracking-wider text-stone-400 font-mono">
                                    <tr>
                                        <th class="py-2 pr-4">Reference</th>
                                        <th class="py-2 pr-4">Vendor</th>
                                        <th class="py-2 pr-4">Amount</th>
                                        <th class="py-2 pr-4">Status</th>
                                        <th class="py-2 pr-4">Payment</th>
                                        <th class="py-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#E7E5E4] dark:divide-white/10">
                                    @foreach($this->eventBookings as $booking)
                                        <tr>
                                            <td class="py-2 pr-4 font-mono text-[11px] text-[#8F7239] dark:text-[#C6A15B]">{{ $booking->booking_reference }}</td>
                                            <td class="py-2 pr-4">
                                                <span class="text-[#171513] dark:text-white">{{ $booking->tenant?->name ?? '—' }}</span>
                                                <span class="block text-[10px] text-stone-500 dark:text-stone-400">{{ $booking->vendorCategory?->name ?? 'Service' }}</span>
                                            </td>
                                            <td class="py-2 pr-4 text-[#171513] dark:text-white">PKR {{ number_format((float) $booking->quoted_amount, 0) }}</td>
                                            <td class="py-2 pr-4">{{ str_replace('_', ' ', $booking->status) }}</td>
                                            <td class="py-2 pr-4">{{ str_replace('_', ' ', $booking->payment_status) }}</td>
                                            <td class="py-2">
                                                <button type="button" wire:click="unlinkBooking('{{ $booking->id }}')" class="text-[10px] font-mono uppercase text-stone-400 hover:text-red-600">Unlink</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <div class="mt-5 pt-5 border-t border-[#E7E5E4] dark:border-white/10">
                        <label for="link-booking" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-2">Link an existing booking</label>
                        @if($this->unlinkedBookings->isEmpty())
                            <p class="text-xs text-stone-500 dark:text-stone-400 font-sans">No unlinked service bookings. <a href="{{ route('public.vendors') }}" class="text-[#8F7239] dark:text-[#C6A15B] font-semibold hover:underline">Book a vendor</a> or <a href="{{ route('customer.dashboard') }}" class="text-[#8F7239] dark:text-[#C6A15B] font-semibold hover:underline">accept a quotation</a> first.</p>
                        @else
                            <div class="flex flex-wrap items-center gap-2">
                                <select id="link-booking" class="input-luxury flex-1 min-w-[240px] text-sm">
                                    @foreach($this->unlinkedBookings as $ub)
                                        <option value="{{ $ub->id }}">
                                            {{ $ub->booking_reference }} — {{ $ub->tenant?->name ?? 'Vendor' }} (PKR {{ number_format((float) $ub->quoted_amount, 0) }}, {{ str_replace('_', ' ', $ub->status) }})
                                        </option>
                                    @endforeach
                                </select>
                                <button type="button" wire:click="linkBooking($el.querySelector('#link-booking').value)" class="btn-gold !h-10 !px-5 text-[11px] font-mono uppercase">Link</button>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Event modal -->
    @if($showEventModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true" role="dialog" aria-labelledby="event-modal-title">
            <div class="absolute inset-0 bg-[#171513]/70 backdrop-blur-sm" wire:click="closeEventModal"></div>
            <div class="relative mx-auto mt-16 w-full max-w-2xl rounded-2xl bg-white dark:bg-[#171513] border border-[#C6A15B]/50 shadow-2xl p-6">
                <h2 id="event-modal-title" class="font-serif-luxury text-2xl text-[#171513] dark:text-white mb-4">
                    {{ $isEditingEvent ? 'Edit Event' : 'Create Event' }}
                </h2>

                <form wire:submit="saveEvent" class="space-y-4">
                    <div>
                        <label for="evt-name" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Event name <span class="text-[#8F7239]">*</span></label>
                        <input id="evt-name" type="text" wire:model="eventName" maxlength="150" placeholder="e.g. Ayesha & Bilal — Walima">
                        @error('eventName') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="evt-type" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Function type</label>
                            <select id="evt-type" wire:model="eventType" class="input-luxury w-full text-sm">
                                @foreach(\App\Models\CustomerEvent::EVENT_TYPES as $type)
                                    <option value="{{ $type }}">{{ ucfirst(strtolower($type)) }}</option>
                                @endforeach
                            </select>
                            @error('eventType') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="evt-date" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Event date</label>
                            <input id="evt-date" type="date" wire:model="eventDate" class="input-luxury w-full text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="evt-status" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Status</label>
                            <select id="evt-status" wire:model="eventStatus" class="input-luxury w-full text-sm">
                                @foreach(\App\Models\CustomerEvent::STATUSES as $status)
                                    <option value="{{ $status }}">{{ ucfirst(strtolower(str_replace('_', ' ', $status))) }}</option>
                                @endforeach
                            </select>
                            @error('eventStatus') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="evt-city" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">City</label>
                            <input id="evt-city" type="text" wire:model="eventCity" maxlength="80" placeholder="Karachi">
                        </div>
                        <div>
                            <label for="evt-venue" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Venue</label>
                            <input id="evt-venue" type="text" wire:model="venueText" maxlength="200" placeholder="Riwaj Grand Banquet">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="evt-guests" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Guests</label>
                            <input id="evt-guests" type="number" min="1" wire:model="guestCount" class="input-luxury w-full text-sm">
                            @error('guestCount') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="evt-budget" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Estimated budget (PKR)</label>
                            <input id="evt-budget" type="number" step="0.01" min="0" wire:model="estimatedBudget" class="input-luxury w-full text-sm">
                            @error('estimatedBudget') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="evt-notes" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Notes</label>
                        <textarea id="evt-notes" wire:model="eventNotes" rows="3" class="input-luxury w-full text-sm resize-y"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" wire:click="closeEventModal" class="btn-outline-gold !h-10 !px-5 text-[11px] font-mono uppercase">Cancel</button>
                        <button type="submit" class="btn-gold !h-10 !px-6 text-[11px] font-mono uppercase">
                            {{ $isEditingEvent ? 'Save Changes →' : 'Create Event →' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
