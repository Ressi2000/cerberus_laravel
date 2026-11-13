<?php

namespace App\Livewire\Mantenimientos;

use App\Models\Mantenimiento;
use App\Models\MantenimientoComponente;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Panel "Usado del almacén" de un caso: lo que se le entregó (con la pieza
 * exacta, si es por unidad) y lo que quedó pendiente por falta de stock.
 *
 * Para usar algo nuevo del almacén se abre UsarDelAlmacenModal, la única
 * forma de hacerlo (reemplaza a "Pedir componente" e "Instalar pieza").
 */
class MantenimientoComponentesPanel extends Component
{
    // Locked: el id no se puede cambiar desde el navegador.
    #[Locked]
    public int $mantenimientoId;

    public function mount(int $mantenimientoId): void
    {
        $this->mantenimientoId = $mantenimientoId;
    }

    /** El caso pudo cambiar desde otro panel o desde "Usar del almacén". */
    #[On('mantenimientoActualizado')]
    #[On('componenteRefrescar')]
    public function refrescar(): void
    {
        unset($this->mantenimiento);
    }

    #[Computed]
    public function mantenimiento(): Mantenimiento
    {
        return Mantenimiento::with(['componentes.componente.familia', 'componentes.pieza.deposito', 'componentes.pieza.equipoDestino', 'componentes.entregadoPor'])
            ->findOrFail($this->mantenimientoId);
    }

    public function abrirUsar(): void
    {
        $m = $this->mantenimiento;
        $this->authorize('update', $m);

        if (! $m->permiteRegistrarTrabajo()) {
            $this->dispatch('toast', type: 'error', message: 'Solo se puede usar algo del almacén mientras el caso está en trabajo.');
            return;
        }

        $this->dispatch('openUsarDelAlmacen', mantenimientoId: $m->id);
    }

    public function entregarPendiente(int $itemId): void
    {
        $m = $this->mantenimiento;
        $this->authorize('update', $m);

        if (! $m->permiteRegistrarTrabajo()) {
            $this->dispatch('toast', type: 'error', message: 'No se pueden entregar componentes en este estado.');
            return;
        }

        // Solo ítems de ESTE caso: el id llega desde el navegador.
        $item = MantenimientoComponente::where('mantenimiento_id', $m->id)->find($itemId);

        if (! $item) {
            $this->dispatch('toast', type: 'error', message: 'Ese componente no pertenece a este caso.');
            return;
        }

        // Por unidad: se elige la pieza por su serial (y se actualiza la
        // ficha del equipo si corresponde) en "Usar del almacén".
        if ($item->componente && ! $item->componente->esAGranel()) {
            $this->dispatch('openUsarDelAlmacen', mantenimientoId: $m->id, pendienteId: $item->id);
            return;
        }

        if ($m->entregarComponentePendiente($item, Auth::user())) {
            $this->dispatch('toast', type: 'success', message: 'Componente entregado desde el almacén.');
        } else {
            $this->dispatch('toast', type: 'error', message: 'Todavía no hay stock suficiente para entregar este componente.');
        }

        $this->dispatch('componenteRefrescar');
        unset($this->mantenimiento);
    }

    public function render()
    {
        return view('livewire.mantenimientos.mantenimiento-componentes-panel');
    }
}
