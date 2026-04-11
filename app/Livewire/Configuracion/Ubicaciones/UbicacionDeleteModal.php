<?php

namespace App\Livewire\Configuracion\Ubicaciones;

use App\Models\Ubicacion;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class UbicacionDeleteModal extends Component
{
    public bool      $open          = false;
    public ?Ubicacion $ubicacion    = null;
    public int       $totalUsuarios = 0;
    public int       $totalEquipos  = 0;

    #[On('openUbicacionEliminar')]
    public function abrir(int $id): void
    {
        $this->ubicacion     = Ubicacion::withCount(['usuarios', 'equipos'])->findOrFail($id);
        $this->totalUsuarios = $this->ubicacion->usuarios_count;
        $this->totalEquipos  = $this->ubicacion->equipos_count;
        $this->open          = true;
    }

    public function eliminar(): void
    {
        if (! $this->ubicacion) return;

        $totalVinculados = $this->totalUsuarios + $this->totalEquipos;

        if ($totalVinculados > 0) {
            $this->dispatch(
                'toast',
                type: 'error',
                message: "No se puede eliminar: tiene {$this->totalUsuarios} usuario(s) y {$this->totalEquipos} equipo(s) asociado(s)."
            );
            $this->close();
            return;
        }

        try {
            $nombre = $this->ubicacion->nombre;
            $this->ubicacion->delete(); // SoftDelete

            $this->close();
            $this->dispatch('ubicacionEliminada');
            $this->dispatch('toast', type: 'success', message: "Ubicación «{$nombre}» eliminada.");

        } catch (\Exception $e) {
            Log::error('UbicacionDeleteModal@eliminar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al eliminar la ubicación.');
            $this->close();
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['ubicacion', 'totalUsuarios', 'totalEquipos']);
    }

    public function render()
    {
        return view('livewire.configuracion.ubicaciones.ubicacion-delete-modal');
    }
}