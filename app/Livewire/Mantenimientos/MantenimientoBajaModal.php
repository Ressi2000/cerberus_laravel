<?php

namespace App\Livewire\Mantenimientos;

use App\Models\Mantenimiento;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Dar de baja un equipo porque la reparación no tiene solución. Decisión
 * patrimonial e irreversible — reservada al Administrador
 * (MantenimientoPolicy::aprobarBaja). Si el equipo tenía una asignación
 * activa, se cierra en el mismo paso (Mantenimiento::marcarDadoDeBaja()).
 */
class MantenimientoBajaModal extends Component
{
    public bool $open = false;
    public ?int $mantenimientoId = null;
    public string $motivo = '';

    #[On('openMantenimientoBaja')]
    public function abrir(int $mantenimientoId): void
    {
        $m = Mantenimiento::findOrFail($mantenimientoId);
        $this->authorize('aprobarBaja', $m);

        $this->mantenimientoId = $mantenimientoId;
        $this->motivo = '';
        $this->resetValidation();
        $this->open = true;
    }

    public function confirmar(): void
    {
        $m = Mantenimiento::findOrFail($this->mantenimientoId);
        $this->authorize('aprobarBaja', $m);

        $this->validate(['motivo' => 'required|string|max:1000']);

        try {
            $m->marcarDadoDeBaja(Auth::user(), $this->motivo);
            $this->dispatch('toast', type: 'success', message: 'Equipo dado de baja.');
            $this->dispatch('mantenimientoActualizado');
            $this->close();
        } catch (\Exception $e) {
            Log::error('MantenimientoBajaModal@confirmar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al dar de baja el equipo.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['mantenimientoId', 'motivo']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.mantenimientos.mantenimiento-baja-modal');
    }
}
