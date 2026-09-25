<?php

namespace App\Livewire\Mantenimientos;

use App\Models\Mantenimiento;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class MantenimientoDetalle extends Component
{
    public int $mantenimientoId;

    public string $diagnostico = '';
    public string $causa_raiz  = '';
    public string $observaciones = '';
    public ?float $costo = null;

    public function mount(int $mantenimientoId): void
    {
        $mantenimiento = Mantenimiento::findOrFail($mantenimientoId);
        $this->authorize('view', $mantenimiento);

        $this->mantenimientoId = $mantenimientoId;
        $this->diagnostico     = $mantenimiento->diagnostico ?? '';
        $this->causa_raiz      = $mantenimiento->causa_raiz ?? '';
        $this->observaciones   = $mantenimiento->observaciones ?? '';
        $this->costo           = $mantenimiento->costo ? (float) $mantenimiento->costo : null;
    }

    #[Computed]
    public function mantenimiento(): Mantenimiento
    {
        return Mantenimiento::with([
            'equipo.categoria', 'equipo.empresa', 'empresa', 'asignacion.usuario',
            'reportadoPor', 'responsable', 'ubicacionTaller', 'aprobadoPor',
            'mantenimientoOrigen', 'evidencias.subidoPor',
        ])->findOrFail($this->mantenimientoId);
    }

    #[On('componenteRefrescar')]
    #[On('evidenciaSubida')]
    #[On('mantenimientoActualizado')]
    public function refrescar(): void
    {
        unset($this->mantenimiento);
    }

    /**
     * Tras cualquier acción que cambie el estado del caso (avanzar, cerrar,
     * completar, cancelar...), hay que avisarle a los paneles hermanos
     * (Componentes, Evidencias) — son otros componentes Livewire con su
     * propia caché, no se enteran solos de que el caso ya no está abierto.
     */
    private function notificarActualizacion(): void
    {
        unset($this->mantenimiento);
        $this->dispatch('mantenimientoActualizado');
    }

    public function guardarDiagnostico(): void
    {
        $m = $this->mantenimiento;
        $this->authorize('update', $m);

        if (! $m->estaAbierto()) {
            $this->dispatch('toast', type: 'error', message: 'Este caso ya está cerrado, no se puede editar el diagnóstico.');
            return;
        }

        $this->validate([
            'diagnostico'   => 'nullable|string|max:2000',
            'causa_raiz'    => 'nullable|string|max:2000',
            'observaciones' => 'nullable|string|max:2000',
            'costo'         => 'nullable|numeric|min:0',
        ]);

        $m->update([
            'diagnostico'   => $this->diagnostico ?: null,
            'causa_raiz'    => $this->causa_raiz ?: null,
            'observaciones' => $this->observaciones ?: null,
            'costo'         => $this->costo,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Diagnóstico guardado.');
        $this->refrescar();
    }

    public function avanzarEstado(): void
    {
        $m = $this->mantenimiento;
        $this->authorize('update', $m);

        if ($m->avanzarEstado()) {
            $this->dispatch('toast', type: 'success', message: "Caso avanzado a «{$m->fresh()->estado}».");
        }

        $this->notificarActualizacion();
    }

    public function marcarReparado(): void
    {
        $m = $this->mantenimiento;
        $this->authorize('update', $m);
        $m->marcarReparado();
        $this->dispatch('toast', type: 'success', message: 'Marcado como reparado.');
        $this->notificarActualizacion();
    }

    public function cerrar(): void
    {
        $m = $this->mantenimiento;
        $this->authorize('update', $m);
        $m->cerrar();
        $this->dispatch('toast', type: 'success', message: 'Caso cerrado. El equipo fue liberado.');
        $this->notificarActualizacion();
    }

    public function completar(): void
    {
        $m = $this->mantenimiento;
        $this->authorize('update', $m);
        $m->completar();
        $this->dispatch('toast', type: 'success', message: 'Mantenimiento completado. El equipo fue liberado.');
        $this->notificarActualizacion();
    }

    public function toggleChecklistItem(int $index): void
    {
        $m = $this->mantenimiento;
        $this->authorize('update', $m);

        if (! $m->estaAbierto()) return;

        $checklist = $m->checklist ?? [];
        if (! isset($checklist[$index])) return;

        $checklist[$index]['hecho'] = ! ($checklist[$index]['hecho'] ?? false);
        $m->update(['checklist' => $checklist]);

        $this->refrescar();
    }

    public function cancelar(): void
    {
        $m = $this->mantenimiento;
        $this->authorize('update', $m);

        try {
            $m->cancelar();
            $this->dispatch('toast', type: 'success', message: 'Mantenimiento cancelado.');
        } catch (\Exception $e) {
            Log::error('MantenimientoDetalle@cancelar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al cancelar.');
        }

        $this->notificarActualizacion();
    }

    public function render()
    {
        return view('livewire.mantenimientos.mantenimiento-detalle');
    }
}
