<?php

namespace App\Livewire\Configuracion\Departamentos;

use App\Models\Departamento;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class DepartamentoDeleteModal extends Component
{
    public bool          $open          = false;
    public ?Departamento $departamento  = null;
    public int           $totalUsuarios = 0;

    #[On('openDepartamentoEliminar')]
    public function abrir(int $id): void
    {
        $this->departamento  = Departamento::withCount('usuarios')->findOrFail($id);
        $this->totalUsuarios = $this->departamento->usuarios_count;
        $this->open          = true;
    }

    public function eliminar(): void
    {
        if (! $this->departamento) return;

        if ($this->totalUsuarios > 0) {
            $this->dispatch(
                'toast',
                type: 'error',
                message: "No se puede eliminar: tiene {$this->totalUsuarios} usuario(s) asociado(s)."
            );
            $this->close();
            return;
        }

        try {
            $nombre = $this->departamento->nombre;
            $this->departamento->delete(); // SoftDelete

            $this->close();
            $this->dispatch('departamentoEliminado');
            $this->dispatch('toast', type: 'success', message: "Departamento «{$nombre}» eliminado.");

        } catch (\Exception $e) {
            Log::error('DepartamentoDeleteModal@eliminar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al eliminar el departamento.');
            $this->close();
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['departamento', 'totalUsuarios']);
    }

    public function render()
    {
        return view('livewire.configuracion.departamentos.departamento-delete-modal');
    }
}