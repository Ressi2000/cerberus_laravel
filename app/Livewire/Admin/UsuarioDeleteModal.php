<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;

class UsuarioDeleteModal extends Component
{
    public $open = false;
    public ?User $user = null;

    protected $listeners = ['openUserDelete'];

    public function openUserDelete($id)
    {
        $this->user = User::findOrFail($id);
        $this->open = true;
    }

    public function delete()
    {
        if (! $this->user) {
            return;
        }

        return $this->destroy($this->user);
    }

    public function destroy(User $usuario)
    {
        $this->authorize('delete', $usuario);

        try {
            $usuario->update(['estado' => 'Inactivo']);
            $this->close();

            $this->dispatch('userDeleted');

            return redirect()->route('admin.usuarios.index')->with('success', 'Usuario inactivado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error inactivando usuario: ' . $e->getMessage());
            return redirect()->route('admin.usuarios.index')->with('error', 'Ocurrió un error al inactivar el usuario.');
        }
    }

    public function close()
    {
        $this->reset(['open', 'user']);
    }

    public function render()
    {
        return view('livewire.admin.usuario-delete-modal');
    }
}
