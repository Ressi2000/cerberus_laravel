<?php
// ════════════════════════════════════════════════════════════════════════════
// app/Livewire/Configuracion/Ubicaciones/UbicacionDeleteModal.php
// ════════════════════════════════════════════════════════════════════════════

namespace App\Livewire\Configuracion\Ubicaciones;

use App\Models\Ubicacion;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class UbicacionDeleteModal extends Component
{
    public bool       $open          = false;
    public ?Ubicacion $ubicacion     = null;
    public int        $totalUsuarios = 0;
    public int        $totalEquipos  = 0;

    #[On('openUbicacionEliminar')]
    public function abrir(int $id): void
    {
        $this->ubicacion = Ubicacion::withCount([
            'usuarios' => fn($q) => $q->where('estado', 'Activo'),
            'equipos'  => fn($q) => $q->where('activo', true),
        ])->findOrFail($id);

        $this->totalUsuarios = $this->ubicacion->usuarios_count;
        $this->totalEquipos  = $this->ubicacion->equipos_count;
        $this->open          = true;
    }

    public function desactivar(): void
    {
        if (! $this->ubicacion) return;

        $totalVinculados = $this->totalUsuarios + $this->totalEquipos;

        if ($totalVinculados > 0) {
            $partes = array_filter([
                $this->totalUsuarios > 0 ? "{$this->totalUsuarios} usuario(s)" : null,
                $this->totalEquipos  > 0 ? "{$this->totalEquipos} equipo(s)"   : null,
            ]);
            $this->dispatch('toast', type: 'error',
                message: 'No se puede desactivar: tiene ' . implode(' y ', $partes) . ' activo(s).');
            $this->close();
            return;
        }

        try {
            $nombre = $this->ubicacion->nombre;
            $this->ubicacion->update(['activo' => false]);

            $this->close();
            $this->dispatch('ubicacionEliminada');
            $this->dispatch('toast', type: 'success', message: "Ubicación «{$nombre}» desactivada.");
        } catch (\Exception $e) {
            Log::error('UbicacionDeleteModal@desactivar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al desactivar la ubicación.');
            $this->close();
        }
    }

    #[On('reactivarUbicacion')]
    public function reactivar(int $id): void
    {
        try {
            $ubicacion = Ubicacion::findOrFail($id);
            $ubicacion->update(['activo' => true]);

            $this->dispatch('ubicacionEliminada');
            $this->dispatch('toast', type: 'success', message: "Ubicación «{$ubicacion->nombre}» reactivada.");
        } catch (\Exception $e) {
            Log::error('UbicacionDeleteModal@reactivar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al reactivar la ubicación.');
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