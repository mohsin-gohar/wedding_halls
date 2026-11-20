<div class="min-h-full py-6 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-extrabold text-[#171513] tracking-tight">Notifications</h2>
        <div class="flex items-center gap-3">
            <button wire:click="markAllAsRead" 
                class="tbtn tbtn-outline px-4 py-2 text-xs text-[#171513] hover:bg-[#F8F5EF]">
                Mark All as Read
            </button>
        </div>
    </div>

    <!-- Existing component filters, now exposed in the UI -->
    <div class="bg-white dark:bg-[#171513] rounded-xl border border-[#E7E5E4] dark:border-white/10 p-4 mb-6 flex flex-col sm:flex-row sm:items-end gap-3">
        <div class="flex-1">
            <label for="notification-search" class="block text-[10px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400 mb-1">Search</label>
            <input id="notification-search" type="search" wire:model.live="search" placeholder="Title or message…" class="w-full rounded-lg border border-stone-200 dark:border-white/10 bg-white dark:bg-[#1f1c18] px-3 py-2 text-sm text-[#171513] dark:text-white">
        </div>
        <div class="sm:w-56">
            <label for="notification-type" class="block text-[10px] font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400 mb-1">Type</label>
            <select id="notification-type" wire:model.live="typeFilter" class="w-full rounded-lg border border-stone-200 dark:border-white/10 bg-white dark:bg-[#1f1c18] px-3 py-2 text-sm text-[#171513] dark:text-white">
                <option value="ALL">All types</option>
                @foreach($types as $type)
                    <option value="{{ $type }}">{{ str_replace('_', ' ', $type) }}</option>
                @endforeach
            </select>
        </div>
        <label class="inline-flex items-center gap-2 text-xs text-stone-600 dark:text-stone-300 pb-2">
            <input type="checkbox" wire:model.live="unreadOnly" class="rounded border-stone-300 text-[#C6A15B]">
            Unread only
        </label>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($notifications as $notification)
            <div class="bg-white dark:bg-[#171513] rounded-xl border border-[#E7E5E4] dark:border-white/10 p-5 transition-all hover:shadow-lg {{ $notification->isRead() ? '' : 'border-[#C6A15B]/40 bg-[#C6A15B]/5' }}">
                <div class="flex items-start justify-between mb-2">
                    <span class="text-xs font-semibold text-[#C6A15B] uppercase tracking-wider">{{ $notification->type }}</span>
                    @if (! $notification->isRead())
                        <span class="w-2 h-2 rounded-full bg-[#C6A15B]"></span>
                    @endif
                </div>
                
                <h3 class="text-sm font-bold text-[#171513] dark:text-white mb-2 line-clamp-2">{{ $notification->title }}</h3>
                <p class="text-sm text-stone-600 dark:text-stone-400 mb-4 line-clamp-3">{{ $notification->message }}</p>
                
                <div class="flex items-center justify-between text-xs text-stone-500 dark:text-stone-400">
                    <span>{{ $notification->created_at->diffForHumans() }}</span>
                    <div class="flex gap-2">
                        @if (! $notification->isRead())
                            <button wire:click="markAsRead('{{ $notification->id }}')" 
                                class="p-1.5 hover:text-[#C6A15B] transition" title="Mark as read">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </button>
                        @endif
                        <button wire:click="deleteNotification('{{ $notification->id }}')" 
                            class="p-1.5 hover:text-red-500 transition" title="Delete">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-12">
                <div class="w-16 h-16 bg-[#F8F5EF] dark:bg-[#1f1c18] rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-lg font-semibold text-[#171513] dark:text-white">No notifications</h3>
                <p class="text-stone-500 dark:text-stone-400">You're all caught up. Come back later for updates.</p>
            </div>
        @endforelse
    </div>

    {{ $notifications->links() }}
</div>