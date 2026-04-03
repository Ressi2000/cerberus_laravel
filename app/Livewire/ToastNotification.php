<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;

class ToastNotification extends Component
{
    /**
     * Cola de toasts activos.
     * Cada item: ['id' => int, 'type' => string, 'message' => string]
     */
    public array $toasts = [];

    private int $nextId = 0;

    // ─────────────────────────────────────────────────────────────────────────
    // Boot: al montar el componente, convertir cualquier session flash
    // pendiente en toasts. Esto resuelve el problema de toasts cross-page:
    // cuando hay un redirect (wire:navigate), el evento Livewire se pierde,
    // pero la sesión persiste. La página de destino monta este componente
    // y aquí recoge el mensaje.
    // ─────────────────────────────────────────────────────────────────────────
    public function mount(): void
    {
        foreach (['success', 'error', 'warning', 'info'] as $type) {
            if (session()->has($type)) {
                $this->toasts[] = [
                    'id'      => ++$this->nextId,
                    'type'    => $type,
                    'message' => session($type),
                ];
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Evento en tiempo real (mismo ciclo de vida, sin redirect).
    // Uso: $this->dispatch('toast', type: 'success', message: '...');
    // ─────────────────────────────────────────────────────────────────────────
    #[On('toast')]
    public function addToast(string $type, string $message): void
    {
        $this->toasts[] = [
            'id'      => ++$this->nextId,
            'type'    => $type,
            'message' => $message,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Eliminar un toast por id (llamado desde Alpine al expirar o cerrar)
    // ─────────────────────────────────────────────────────────────────────────
    public function remove(int $id): void
    {
        $this->toasts = array_values(
            array_filter($this->toasts, fn($t) => $t['id'] !== $id)
        );
    }

    public function render()
    {
        return view('livewire.toast-notification');
    }
}