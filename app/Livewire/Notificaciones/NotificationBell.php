<?php

namespace App\Livewire\Notificaciones;

use Illuminate\Notifications\DatabaseNotification;
use Livewire\Component;

class NotificationBell extends Component
{
    public int $unreadCount = 0;

    public function getListeners(): array
    {
        $id = auth()->id();
        return [
            "echo-private:users.{$id},Illuminate\\Notifications\\Events\\BroadcastNotificationCreated" => 'onNewNotification',
        ];
    }

    public function mount(): void
    {
        $this->unreadCount = auth()->user()?->unreadNotifications()->count() ?? 0;
    }

    public function refresh(): void
    {
        $this->unreadCount = auth()->user()?->unreadNotifications()->count() ?? 0;
    }

    public function onNewNotification(): void
    {
        $this->unreadCount = auth()->user()?->unreadNotifications()->count() ?? 0;
        $this->dispatch('notificacion-nueva');
    }

    public function markAllRead(): void
    {
        auth()->user()?->unreadNotifications->markAsRead();
        $this->unreadCount = 0;
    }

    public function markRead(string $id): void
    {
        auth()->user()?->notifications()->find($id)?->markAsRead();
        $this->unreadCount = auth()->user()?->unreadNotifications()->count() ?? 0;
    }

    public function render()
    {
        $recientes = auth()->user()
            ?->notifications()
            ->latest()
            ->take(6)
            ->get() ?? collect();

        return view('livewire.notificaciones.notification-bell', compact('recientes'));
    }
}
