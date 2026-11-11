<?php

namespace App\Livewire;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Notifications â€” Riwaj Grand'])]
class NotificationCenter extends Component
{
    public string $search = '';
    public string $typeFilter = 'ALL';
    public bool $unreadOnly = false;

    public function markAsRead(string $notificationId): void
    {
        $notification = Notification::where('user_id', Auth::id())
            ->where('id', $notificationId)
            ->firstOrFail();

        $notification->markAsRead();

        $this->dispatch('notificationCountUpdated');
        session()->flash('message', 'Notification marked as read.');
    }

    public function markAllAsRead(): void
    {
        $user = Auth::user();
        $user->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        $this->dispatch('notificationCountUpdated');
        session()->flash('message', 'All notifications marked as read.');
    }

    public function deleteNotification(string $notificationId): void
    {
        $notification = Notification::where('user_id', Auth::id())
            ->where('id', $notificationId)
            ->firstOrFail();

        $notification->delete();

        $this->dispatch('notificationCountUpdated');
        session()->flash('message', 'Notification deleted.');
    }

    public function render()
    {
        $user = Auth::user();

        $query = Notification::where('user_id', $user->id)
            ->with('tenant');

        if ($this->unreadOnly) {
            $query->whereNull('read_at');
        }

        if ($this->typeFilter !== 'ALL') {
            $query->where('type', $this->typeFilter);
        }

        if (trim($this->search) !== '') {
            $search = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                    ->orWhere('message', 'like', $search);
            });
        }

        $notifications = $query->latest()->paginate(20);

        $types = Notification::where('user_id', $user->id)
            ->distinct('type')
            ->pluck('type');

        $unreadCount = $user->unreadNotifications();

        return view('livewire.notification-center', [
            'notifications' => $notifications,
            'types' => $types,
            'unreadCount' => $unreadCount,
        ]);
    }
}