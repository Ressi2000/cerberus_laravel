<?php

namespace App\Livewire\Cronograma;

use App\Models\Mantenimiento;
use App\Models\PlanMantenimiento;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Vista de "Lote": todos los casos que un mismo plan generó en su ciclo más
 * reciente, para procesarlos en conjunto (selección múltiple + avanzar o
 * completar en bloque) sin perder la granularidad de cada equipo — cada
 * caso sigue siendo su propio Mantenimiento, con su checklist/evidencia/
 * bloqueo independiente. Si uno de los equipos del lote tiene un problema,
 * se reporta puntualmente desde su fila (ver ReportarProblemaModal) sin
 * afectar al resto.
 */
class LoteDetalle extends Component
{
    public int $planId;

    /** IDs de casos marcados con checkbox para las acciones masivas. */
    public array $seleccionados = [];

    public function mount(int $planId): void
    {
        $plan = PlanMantenimiento::findOrFail($planId);
        $this->authorize('view', $plan);

        $this->planId = $planId;
    }

    #[On('mantenimientoActualizado')]
    #[On('planGuardado')]
    public function refrescar(): void
    {
        unset($this->plan, $this->casos);
        $this->seleccionados = [];
    }

    #[Computed]
    public function plan(): PlanMantenimiento
    {
        return PlanMantenimiento::with(['categoria', 'empresa'])->findOrFail($this->planId);
    }

    /** Los casos del ciclo más reciente que este plan generó (el lote a procesar). */
    #[Computed]
    public function casos()
    {
        $fechaCiclo = $this->plan->mantenimientos()->max('proxima_fecha_programada');

        if (! $fechaCiclo) {
            return collect();
        }

        return $this->plan->mantenimientos()
            ->where('proxima_fecha_programada', $fechaCiclo)
            ->with('equipo.categoria')
            ->orderBy('id')
            ->get();
    }

    #[Computed]
    public function idsProgramados(): array
    {
        return $this->casos->where('estado', 'Programado')->pluck('id')->all();
    }

    #[Computed]
    public function idsEnProceso(): array
    {
        return $this->casos->where('estado', 'En proceso')->pluck('id')->all();
    }

    public function toggleTodos(bool $marcar): void
    {
        $this->seleccionados = $marcar
            ? $this->casos->pluck('id')->map(fn ($id) => (string) $id)->all()
            : [];
    }

    public function avanzarSeleccionados(): void
    {
        $ids = array_intersect($this->seleccionados, array_map('strval', $this->idsProgramados));

        if (empty($ids)) {
            $this->dispatch('toast', type: 'error', message: 'Selecciona casos "Programado" para avanzar.');
            return;
        }

        try {
            $casos = Mantenimiento::whereIn('id', $ids)->get();
            foreach ($casos as $caso) {
                $this->authorize('update', $caso);
                $caso->avanzarEstado();
            }

            $this->dispatch('toast', type: 'success', message: count($casos) . ' caso(s) avanzado(s) a «En proceso».');
        } catch (\Exception $e) {
            Log::error('LoteDetalle@avanzarSeleccionados: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al avanzar los casos seleccionados.');
        }

        $this->refrescar();
    }

    public function completarSeleccionados(): void
    {
        $ids = array_intersect($this->seleccionados, array_map('strval', $this->idsEnProceso));

        if (empty($ids)) {
            $this->dispatch('toast', type: 'error', message: 'Selecciona casos "En proceso" para completar.');
            return;
        }

        try {
            $casos = Mantenimiento::whereIn('id', $ids)->get();
            foreach ($casos as $caso) {
                $this->authorize('update', $caso);
                $caso->completar();
            }

            $this->dispatch('toast', type: 'success', message: count($casos) . ' caso(s) completado(s).');
        } catch (\Exception $e) {
            Log::error('LoteDetalle@completarSeleccionados: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al completar los casos seleccionados.');
        }

        $this->refrescar();
    }

    public function render()
    {
        return view('livewire.cronograma.lote-detalle');
    }
}
