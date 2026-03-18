<?php

namespace App\Livewire\Equipos;

use App\Models\Equipo;
use App\Models\EstadoEquipo;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;
 
class EquipoDeleteModal extends Component
{
    public bool $open = false;
    public ?Equipo $equipo = null;
 
    #[On('openEquipoDelete')]
    public function openEquipoDelete(int $id): void
    {
        $equipo = Equipo::with(['categoria', 'estado'])->findOrFail($id);
 
        $this->authorize('delete', $equipo);
 
        $this->equipo = $equipo;
        $this->open   = true;
    }
 
    /**
     * Desactivación lógica:
     *   - activo    = false        → dado de baja lógico
     *   - estado_id = "Dado de baja" → reflejo visual en la tabla
     *
     * NO usa deleted_at. El registro permanece para auditoría.
     * La eliminación real (deleted_at) es exclusiva del Administrador.
     */
    public function desactivar(): void
    {
        if (! $this->equipo) return;
 
        $this->authorize('delete', $this->equipo);
 
        try {
            $estadoBaja = EstadoEquipo::where('nombre', 'Dado de baja')->value('id');
 
            $this->equipo->update([
                'activo'    => false,
                'estado_id' => $estadoBaja ?? $this->equipo->estado_id,
            ]);
 
            $this->close();
            $this->dispatch('equipoDesactivado');
 
            session()->flash('success', 'Equipo dado de baja correctamente.');
 
            $this->redirect(route('admin.equipos.index'), navigate: true);
 
        } catch (\Exception $e) {
            Log::error('Error desactivando equipo: ' . $e->getMessage());
            session()->flash('error', 'Ocurrió un error al dar de baja el equipo.');
            $this->close();
        }
    }
 
    public function close(): void
    {
        $this->reset(['open', 'equipo']);
    }
 
    public function render()
    {
        return view('livewire.equipos.equipo-delete-modal');
    }
}
