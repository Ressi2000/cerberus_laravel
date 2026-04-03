<?php

namespace App\Livewire\Configuracion\Estados;

use App\Models\EstadoEquipo;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class EstadoDeleteModal extends Component
{
    public bool          $open         = false;
    public ?EstadoEquipo $estado       = null;
    public int           $totalEquipos = 0;

    #[On('openEstadoEliminar')]
    public function abrir(int $id): void
    {
        $this->estado       = EstadoEquipo::withCount('equipos')->findOrFail($id);
        $this->totalEquipos = $this->estado->equipos_count;
        $this->open         = true;
    }

    public function eliminar(): void
    {
        if (! $this->estado) return;

        if ($this->totalEquipos > 0) {
            $this->dispatch(
                'toast',
                type: 'error',
                message: "No se puede eliminar: {$this->totalEquipos} equipo(s) usan este estado."
            );
            $this->close();
            return;
        }

        try {
            $nombre = $this->estado->nombre;
            $this->estado->delete();
            $this->close();
            $this->dispatch('estadoEliminado');
            $this->dispatch('toast', type: 'success', message: "Estado «{$nombre}» eliminado.");
        } catch (\Exception $e) {
            Log::error('EstadoDeleteModal@eliminar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al eliminar el estado.');
            $this->close();
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['estado', 'totalEquipos']);
    }

    public function render()
    {
        return view('livewire.configuracion.estados.estado-delete-modal');
    }
}
