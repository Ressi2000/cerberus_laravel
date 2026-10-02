<?php

namespace App\Livewire\Mantenimientos;

use App\Models\Mantenimiento;
use Illuminate\Support\Facades\Auth;
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

    // Reabrir un caso Cerrado — ver Mantenimiento::reabrir().
    public bool   $reabrirAbierto   = false;
    public string $motivoReapertura = '';

    // Retroceder a un estado anterior — ver Mantenimiento::retrocederA().
    public bool   $retrocederAbierto = false;
    public string $estadoRetroceso   = '';

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
            'mantenimientoOrigen', 'reparacionesOriginadas', 'evidencias.subidoPor',
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

    /**
     * Formulario de diagnóstico — solo mientras el caso está "Reportado".
     * Al guardar, avanza automáticamente a "Diagnosticado" (ya no hay nada
     * más que llenar ahí, solo se decide el siguiente paso).
     */
    public function guardarDiagnostico(): void
    {
        $m = $this->mantenimiento;
        $this->authorize('update', $m);

        if ($m->estado !== 'Reportado') {
            $this->dispatch('toast', type: 'error', message: 'El diagnóstico de este caso ya fue guardado.');
            return;
        }

        $this->validate([
            'diagnostico' => 'required|string|max:2000',
            'causa_raiz'  => 'nullable|string|max:2000',
            'costo'       => 'nullable|numeric|min:0',
        ], [
            'diagnostico.required' => 'Describe el diagnóstico antes de continuar.',
        ]);

        $m->update([
            'diagnostico' => $this->diagnostico,
            'causa_raiz'  => $this->causa_raiz ?: null,
            'costo'       => $this->costo,
        ]);

        $m->avanzarEstado(); // Reportado -> Diagnosticado

        $this->dispatch('toast', type: 'success', message: 'Diagnóstico guardado. Caso pasó a «Diagnosticado».');
        $this->notificarActualizacion();
    }

    /**
     * Observaciones de seguimiento — solo mientras se puede registrar
     * trabajo (Correctivo: "En reparación"; Preventivo: todo el tiempo que
     * esté abierto, sin cambios respecto a como ya funcionaba).
     */
    public function guardarObservaciones(): void
    {
        $m = $this->mantenimiento;
        $this->authorize('update', $m);

        if (! $m->permiteRegistrarTrabajo()) {
            $this->dispatch('toast', type: 'error', message: 'No se pueden editar las observaciones en este estado.');
            return;
        }

        $this->validate(['observaciones' => 'nullable|string|max:2000']);

        $m->update(['observaciones' => $this->observaciones ?: null]);

        $this->dispatch('toast', type: 'success', message: 'Observaciones guardadas.');
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

        if ($m->esCorrectivo() && $m->evidencias->where('tipo', 'Después')->isEmpty()) {
            $this->dispatch('toast', type: 'error', message: 'Sube la foto "Después" antes de cerrar el caso.');
            return;
        }

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

    // ── Reabrir (desde Cerrado) ──────────────────────────────────────────────

    public function abrirReabrir(): void
    {
        $this->authorize('update', $this->mantenimiento);
        $this->motivoReapertura = '';
        $this->resetValidation();
        $this->reabrirAbierto = true;
    }

    public function cerrarReabrir(): void
    {
        $this->reabrirAbierto = false;
        $this->resetValidation();
    }

    public function confirmarReabrir(): void
    {
        $m = $this->mantenimiento;
        $this->authorize('update', $m);

        $this->validate([
            'motivoReapertura' => 'required|string|max:1000',
        ], [
            'motivoReapertura.required' => 'Indica por qué se reabre el caso.',
        ]);

        try {
            $m->reabrir(Auth::user(), $this->motivoReapertura);
            $this->dispatch('toast', type: 'success', message: 'Caso reabierto. Volvió a «En reparación».');
            $this->cerrarReabrir();
            $this->notificarActualizacion();
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    // ── Retroceder a un estado anterior ──────────────────────────────────────

    public function abrirRetroceder(): void
    {
        $this->authorize('update', $this->mantenimiento);
        $this->estadoRetroceso = '';
        $this->resetValidation();
        $this->retrocederAbierto = true;
    }

    public function cerrarRetroceder(): void
    {
        $this->retrocederAbierto = false;
        $this->resetValidation();
    }

    /** Estados anteriores al actual a los que se puede retroceder (para el select). */
    #[Computed]
    public function estadosRetrocedibles(): array
    {
        $m = $this->mantenimiento;
        $actual = array_search($m->estado, Mantenimiento::ESTADOS_RETROCEDIBLES_CORRECTIVO, true);

        if ($actual === false) {
            return [];
        }

        return array_slice(Mantenimiento::ESTADOS_RETROCEDIBLES_CORRECTIVO, 0, $actual);
    }

    public function confirmarRetroceder(): void
    {
        $m = $this->mantenimiento;
        $this->authorize('update', $m);

        $this->validate([
            'estadoRetroceso' => 'required|in:' . implode(',', $this->estadosRetrocedibles ?: ['__ninguno__']),
        ], [
            'estadoRetroceso.required' => 'Selecciona a qué estado retroceder.',
            'estadoRetroceso.in'       => 'Ese estado no es válido para retroceder.',
        ]);

        try {
            $m->retrocederA($this->estadoRetroceso);
            $this->dispatch('toast', type: 'success', message: "Caso retrocedido a «{$this->estadoRetroceso}».");
            $this->cerrarRetroceder();
            $this->notificarActualizacion();
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    // ── Eliminar caso (Administrador) ────────────────────────────────────────

    public function eliminar(): void
    {
        $m = $this->mantenimiento;
        $this->authorize('delete', $m);

        $m->eliminarCaso();

        $this->dispatch('toast', type: 'success', message: 'Caso eliminado.');
        $this->redirect(route('admin.mantenimientos.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.mantenimientos.mantenimiento-detalle');
    }
}
