<?php
// ════════════════════════════════════════════════════════════════════════════
// app/Livewire/Configuracion/Estados/EstadoDeleteModal.php
// ════════════════════════════════════════════════════════════════════════════

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

    public function desactivar(): void
    {
        if (! $this->estado) return;

        if ($this->totalEquipos > 0) {
            $this->dispatch('toast', type: 'error',
                message: "No se puede desactivar: {$this->totalEquipos} equipo(s) usan este estado.");
            $this->close();
            return;
        }

        try {
            $nombre = $this->estado->nombre;
            $this->estado->update(['activo' => false]);

            $this->close();
            $this->dispatch('estadoEliminado');
            $this->dispatch('toast', type: 'success', message: "Estado «{$nombre}» desactivado.");
        } catch (\Exception $e) {
            Log::error('EstadoDeleteModal@desactivar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al desactivar el estado.');
            $this->close();
        }
    }

    #[On('reactivarEstado')]
    public function reactivar(int $id): void
    {
        try {
            $estado = EstadoEquipo::findOrFail($id);
            $estado->update(['activo' => true]);

            $this->dispatch('estadoEliminado');
            $this->dispatch('toast', type: 'success', message: "Estado «{$estado->nombre}» reactivado.");
        } catch (\Exception $e) {
            Log::error('EstadoDeleteModal@reactivar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al reactivar el estado.');
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