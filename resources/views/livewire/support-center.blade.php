<div class="space-y-8 max-w-7xl mx-auto pb-16">
    <!-- Header -->
    <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-6 sm:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6 shadow-xl transition-colors duration-200">
        <div class="space-y-2">
            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 border border-[#C6A15B]/50 bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] text-[10px] font-mono uppercase tracking-widest font-bold">
                🎧 Support Center
            </div>
            <h1 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                Help &amp; Complaints
            </h1>
            <p class="text-xs text-stone-600 dark:text-stone-300 font-sans max-w-2xl leading-relaxed">
                Raise a ticket about a booking, a payment, a verification or a dispute — and keep the whole conversation in one place.
            </p>
        </div>

        <button wire:click="openNewTicket" class="btn-gold !h-10 !px-5 text-xs font-bold uppercase font-mono shadow-md">
            + Raise Ticket
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

    @php($stats = $this->stats)

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-[#171513] text-white border border-[#C6A15B]/40 p-5 shadow-xl">
            <p class="text-[10px] font-mono uppercase tracking-widest text-[#C6A15B]">Open</p>
            <p class="font-serif-luxury text-2xl mt-1">{{ $stats['open'] }}</p>
        </div>
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm">
            <p class="text-[10px] font-mono uppercase tracking-widest text-stone-400">Urgent</p>
            <p class="font-serif-luxury text-2xl mt-1 text-red-600 dark:text-red-400">{{ $stats['urgent'] }}</p>
        </div>
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm">
            <p class="text-[10px] font-mono uppercase tracking-widest text-stone-400">Resolved</p>
            <p class="font-serif-luxury text-2xl mt-1 text-emerald-600 dark:text-emerald-400">{{ $stats['resolved'] }}</p>
        </div>
        <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm">
            <p class="text-[10px] font-mono uppercase tracking-widest text-stone-400">All tickets</p>
            <p class="font-serif-luxury text-2xl mt-1 text-[#171513] dark:text-white">{{ $stats['total'] }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Ticket list -->
        <div class="lg:col-span-5 space-y-4">
            <div class="flex items-center gap-2">
                <h2 class="font-serif-luxury text-xl text-[#171513] dark:text-white">Tickets</h2>
                <select wire:change="filterStatus = $event.target.value" class="input-luxury !py-1.5 !text-[11px] w-auto ml-auto">
                    <option value="ALL">All statuses</option>
                    @foreach(\App\Models\SupportTicket::STATUSES as $status)
                        <option value="{{ $status }}" @selected($filterStatus === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>

            @if($this->tickets->isEmpty())
                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-8 text-center shadow-sm">
                    <p class="text-sm text-stone-600 dark:text-stone-300">No tickets to show.</p>
                </div>
            @else
                @foreach($this->tickets as $ticket)
                    <button type="button" wire:click="openTicket('{{ $ticket->id }}')"
                        class="w-full text-left bg-white dark:bg-[#171513] border {{ $selectedTicketId === $ticket->id ? 'border-[#C6A15B]' : 'border-[#E7E5E4] dark:border-[#C6A15B]/30' }} p-4 shadow-sm hover:border-[#C6A15B] transition">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-mono text-[10px] uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold">{{ $ticket->ticket_no }}</p>
                                <p class="text-sm font-bold text-[#171513] dark:text-white truncate">{{ $ticket->subject }}</p>
                                <p class="text-[11px] text-stone-500 dark:text-stone-400 font-sans">
                                    {{ $ticket->category }} · {{ $ticket->requester?->name ?? 'Unknown' }} · {{ $ticket->created_at?->format('M d, Y') }}
                                </p>
                            </div>
                            <div class="flex flex-col items-end gap-1 shrink-0">
                                <span class="px-2 py-0.5 text-[10px] font-mono uppercase tracking-widest font-bold border border-[#C6A15B]/50 bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B]">
                                    {{ $ticket->status }}
                                </span>
                                @if($ticket->priority === 'URGENT' || $ticket->priority === 'HIGH')
                                    <span class="px-2 py-0.5 text-[10px] font-mono uppercase tracking-widest font-bold bg-red-600 text-white">{{ $ticket->priority }}</span>
                                @endif
                            </div>
                        </div>
                    </button>
                @endforeach
            @endif
        </div>

        <!-- Conversation -->
        <div class="lg:col-span-7">
            @if(! $this->showThread || ! $this->selectedTicket)
                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-12 text-center shadow-sm">
                    <h3 class="font-serif-luxury text-2xl text-[#171513] dark:text-white mb-2">Select a ticket</h3>
                    <p class="text-sm text-stone-600 dark:text-stone-300">Choose a ticket on the left to read the conversation and reply.</p>
                </div>
            @else
                @php($ticket = $this->selectedTicket)
                <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 shadow-sm dark:shadow-xl">
                    <div class="p-6 border-b border-[#E7E5E4] dark:border-white/10">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-mono text-[10px] uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold">{{ $ticket->ticket_no }}</p>
                                <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white">{{ $ticket->subject }}</h3>
                                <p class="text-[11px] text-stone-500 dark:text-stone-400 font-sans mt-1">
                                    {{ $ticket->category }} · raised by {{ $ticket->requester?->name ?? 'Unknown' }}
                                    @if($ticket->assignee) · assigned to {{ $ticket->assignee->name }} @endif
                                </p>
                            </div>
                            <button wire:click="closeThread" class="text-[10px] font-mono uppercase tracking-wider text-stone-400 hover:text-[#171513] dark:hover:text-white">Close</button>
                        </div>

                        @if(! auth()->user()->isCustomer())
                            <div class="flex flex-wrap items-center gap-2 mt-4 pt-4 border-t border-[#E7E5E4] dark:border-white/10">
                                @foreach(\App\Models\SupportTicket::STATUSES as $status)
                                    <button wire:click="updateStatus('{{ $status }}')" class="btn-outline-gold !h-8 !px-3 text-[10px] font-mono uppercase">{{ str_replace('_', ' ', $status) }}</button>
                                @endforeach
                                <button wire:click="assignToMe" class="btn-gold !h-8 !px-3 text-[10px] font-mono uppercase">Assign to me</button>
                            </div>
                        @elseif($ticket->status === 'RESOLVED')
                            <div class="mt-4 pt-4 border-t border-[#E7E5E4] dark:border-white/10">
                                <button wire:click="updateStatus('CLOSED')" class="btn-outline-gold !h-8 !px-3 text-[10px] font-mono uppercase">Close ticket</button>
                            </div>
                        @endif
                    </div>

                    <!-- Messages -->
                    <div class="p-6 space-y-4 max-h-[28rem] overflow-y-auto">
                        @foreach($this->messages as $message)
                            <div class="flex {{ $message->is_staff_reply ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[85%] border px-4 py-3 {{ $message->is_staff_reply ? 'border-[#C6A15B]/50 bg-[#C6A15B]/10' : 'border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#1C1917]' }}">
                                    <p class="text-[10px] font-mono uppercase tracking-wider text-stone-400">
                                        {{ $message->author?->name ?? 'Unknown' }} · {{ $message->created_at?->format('M d, H:i') }}
                                    </p>
                                    <p class="text-sm text-[#171513] dark:text-white font-sans mt-1 whitespace-pre-line">{{ $message->message }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Reply -->
                    @if($ticket->status === 'CLOSED')
                        <div class="p-6 border-t border-[#E7E5E4] dark:border-white/10 text-xs text-stone-500 dark:text-stone-400 font-sans">
                            This ticket is closed. Replies are disabled to keep the record immutable.
                        </div>
                    @else
                        <form wire:submit="reply" class="p-6 border-t border-[#E7E5E4] dark:border-white/10 space-y-3">
                            <label for="reply-body" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400">Reply</label>
                            <textarea id="reply-body" wire:model="replyBody" rows="3" class="input-luxury w-full text-sm resize-y" placeholder="Write your reply…"></textarea>
                            @error('replyBody') <p class="text-[11px] text-red-600">{{ $message }}</p> @enderror
                            <div class="flex justify-end">
                                <button type="submit" class="btn-gold !h-10 !px-6 text-[11px] font-mono uppercase">Send Reply →</button>
                            </div>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- New ticket modal -->
    @if($showNewTicket)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true" role="dialog" aria-labelledby="ticket-modal-title">
            <div class="absolute inset-0 bg-[#171513]/70 backdrop-blur-sm" wire:click="closeNewTicket"></div>
            <div class="relative mx-auto mt-16 w-full max-w-xl rounded-2xl bg-white dark:bg-[#171513] border border-[#C6A15B]/50 shadow-2xl p-6">
                <h2 id="ticket-modal-title" class="font-serif-luxury text-2xl text-[#171513] dark:text-white mb-4">Raise a ticket</h2>

                <form wire:submit="createTicket" class="space-y-4">
                    <div>
                        <label for="tkt-subject" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Subject <span class="text-[#8F7239]">*</span></label>
                        <input id="tkt-subject" type="text" wire:model="subject" maxlength="200" placeholder="e.g. Advance payment not reflected on my booking">
                        @error('subject') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="tkt-category" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Category</label>
                            <select id="tkt-category" wire:model="category" class="input-luxury w-full text-sm">
                                @foreach(\App\Models\SupportTicket::CATEGORIES as $cat)
                                    <option value="{{ $cat }}">{{ ucfirst(strtolower($cat)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="tkt-priority" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Priority</label>
                            <select id="tkt-priority" wire:model="priority" class="input-luxury w-full text-sm">
                                @foreach(\App\Models\SupportTicket::PRIORITIES as $p)
                                    <option value="{{ $p }}">{{ ucfirst(strtolower($p)) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="tkt-body" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Details <span class="text-[#8F7239]">*</span></label>
                        <textarea id="tkt-body" wire:model="body" rows="5" class="input-luxury w-full text-sm resize-y" placeholder="Describe the issue, including booking reference and dates where relevant."></textarea>
                        @error('body') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" wire:click="closeNewTicket" class="btn-outline-gold !h-10 !px-5 text-[11px] font-mono uppercase">Cancel</button>
                        <button type="submit" class="btn-gold !h-10 !px-6 text-[11px] font-mono uppercase">Submit Ticket →</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
