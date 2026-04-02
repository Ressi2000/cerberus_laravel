<?php

namespace App\Livewire\Usuarios;

use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\On;

class UsuarioViewModal extends Component
{
    public $open = false;
    public ?User $user = null;

    #[On('openUserView')]
    public function openUserView($id)
    {
        $this->user = User::with([
            'roles',
            'empresaNomina',
            'departamento',
            'cargo',
            'ubicacion'
        ])->findOrFail($id);

        $this->open = true;
    }

    public function close()
    {
        $this->reset(['open', 'user']);
    }

    public function render()
    {
        return view('livewire.usuarios.usuario-view-modal');
    }
}
