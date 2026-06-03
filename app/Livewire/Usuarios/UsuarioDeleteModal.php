<?php

namespace App\Livewire\Usuarios;

use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;

class UsuarioDeleteModal extends Component
{
    public bool $open    = false;
    public ?int $userId  = null;
    public ?User $user   = null;

    #[On('openUserDelete')]
    public function openUserDelete(int $id): void
    {
        $this->user   = User::findOrFail($id);
        $this->userId = $id;
        $this->open   = true;
    }

    public function confirmar(): void
    {
        if (! $this->user) return;

        if ($this->user->estado === 'Activo') {
            $this->inactivar();
        } else {
            $this->activar();
        }
    }

    private function inactivar(): void
    {
        $this->authorize('delete', $this->user);

        try {
            $nombre = $this->user->name;

            $this->user->estado = 'Inactivo';
            $this->user->registrarAuditoria('INACTIVAR');
            $this->user->saveQuietly();

            $this->cerrar();
            $this->dispatch('toast', type: 'success', message: "Usuario «{$nombre}» inactivado correctamente.");
            $this->dispatch('usuarioActualizado');
        } catch (\Exception $e) {
            Log::error('Error inactivando usuario: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Ocurrió un error al inactivar el usuario.');
        }
    }

    private function activar(): void
    {
        $this->authorize('update', $this->user);

        try {
            $nombre = $this->user->name;

            $this->user->estado = 'Activo';
            $this->user->registrarAuditoria('REACTIVAR');
            $this->user->saveQuietly();

            $this->cerrar();
            $this->dispatch('toast', type: 'success', message: "Usuario «{$nombre}» activado correctamente.");
            $this->dispatch('usuarioActualizado');
        } catch (\Exception $e) {
            Log::error('Error activando usuario: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Ocurrió un error al activar el usuario.');
        }
    }

    public function cerrar(): void
    {
        $this->reset(['open', 'user', 'userId']);
    }

    public function render()
    {
        return view('livewire.usuarios.usuario-delete-modal');
    }
}
