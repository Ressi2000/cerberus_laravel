<?php

namespace App\Livewire\Configuracion\Empresas;

use App\Models\Empresa;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class EmpresaDeleteModal extends Component
{
    public bool     $open         = false;
    public ?Empresa $empresa      = null;
    public int      $totalUsuarios = 0;
    public int      $totalEquipos  = 0;

    #[On('openEmpresaEliminar')]
    public function abrir(int $id): void
    {
        $this->empresa       = Empresa::withCount(['usuarios', 'equipos'])->findOrFail($id);
        $this->totalUsuarios = $this->empresa->usuarios_count;
        $this->totalEquipos  = $this->empresa->equipos_count;
        $this->open          = true;
    }

    public function eliminar(): void
    {
        if (! $this->empresa) return;

        try {
            $nombre = $this->empresa->nombre;
            $this->empresa->delete(); // SoftDelete

            $this->close();
            $this->dispatch('empresaEliminada');
            $this->dispatch('toast', type: 'success', message: "Empresa «{$nombre}» eliminada.");

        } catch (\Exception $e) {
            Log::error('EmpresaDeleteModal@eliminar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al eliminar la empresa.');
            $this->close();
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['empresa', 'totalUsuarios', 'totalEquipos']);
    }

    public function render()
    {
        return view('livewire.configuracion.empresas.empresa-delete-modal');
    }
}