<?php

namespace App\Livewire\Notificaciones;

use Livewire\Component;
use Livewire\WithPagination;

class NotificationsIndex extends Component
{
    use WithPagination;

    public string $filtro = 'todas';
    public string $busqueda = '';

    public function updatingFiltro(): void
    {
        $this->resetPage();
    }

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function markRead(string $id): void
    {
        $notif = auth()->user()?->notifications()->find($id);
        $notif?->markAsRead();
    }

    public function markAllRead(): void
    {
        auth()->user()?->unreadNotifications->markAsRead();
    }

    public function deleteNotification(string $id): void
    {
        auth()->user()?->notifications()->where('id', $id)->delete();
    }

    public function render()
    {
        $query = auth()->user()?->notifications();

        if ($this->filtro === 'no_leidas') {
            $query->whereNull('read_at');
        } elseif ($this->filtro === 'leidas') {
            $query->whereNotNull('read_at');
        }

        if ($this->busqueda) {
            $query->where(function ($q) {
                $q->whereRaw("json_extract(data, '$.titulo') LIKE ?", ["%{$this->busqueda}%"])
                  ->orWhereRaw("json_extract(data, '$.mensaje') LIKE ?", ["%{$this->busqueda}%"]);
            });
        }

        $notificaciones = $query->latest()->paginate(15);
        $totalNoLeidas  = auth()->user()?->unreadNotifications()->count() ?? 0;

        return view('livewire.notificaciones.notifications-index', compact('notificaciones', 'totalNoLeidas'));
    }
}
