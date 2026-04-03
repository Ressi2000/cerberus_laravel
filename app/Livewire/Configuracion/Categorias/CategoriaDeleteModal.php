<?php

namespace App\Livewire\Configuracion\Categorias;

use App\Models\CategoriaEquipo;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class CategoriaDeleteModal extends Component
{
    public bool             $open         = false;
    public ?CategoriaEquipo $categoria    = null;
    public int              $totalEquipos = 0;

    #[On('openCategoriaEliminar')]
    public function abrir(int $id): void
    {
        $this->categoria    = CategoriaEquipo::withCount('equipos')->findOrFail($id);
        $this->totalEquipos = $this->categoria->equipos_count;
        $this->open         = true;
    }

    public function eliminar(): void
    {
        if (! $this->categoria) return;

        if ($this->totalEquipos > 0) {
            $this->dispatch(
                'toast',
                type: 'error',
                message: "No se puede eliminar: tiene {$this->totalEquipos} equipo(s) asociado(s)."
            );
            $this->close();
            return;
        }

        try {
            $nombre = $this->categoria->nombre;
            $this->categoria->delete();
            $this->close();
            $this->dispatch('categoriaEliminada');
            $this->dispatch('toast', type: 'success', message: "Categoría «{$nombre}» eliminada.");
        } catch (\Exception $e) {
            Log::error('CategoriaDeleteModal@eliminar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al eliminar la categoría.');
            $this->close();
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['categoria', 'totalEquipos']);
    }

    public function render()
    {
        return view('livewire.configuracion.categorias.categoria-delete-modal');
    }
}
