<?php

namespace App\Livewire\Mantenimientos;

use App\Models\ComponenteAlmacen;
use App\Models\Mantenimiento;
use App\Models\MantenimientoComponente;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class MantenimientoComponentesPanel extends Component
{
    public int $mantenimientoId;

    public bool   $formAbierto   = false;
    public string $componente_id = '';
    public ?int   $cantidad      = 1;

    // "El componente que necesito no está en el almacén" — da de alta un
    // componente nuevo (stock 0) y lo pide en el mismo paso, quedando
    // automáticamente "Pendiente" — así queda registrada la solicitud.
    public bool   $solicitarNuevo   = false;
    public string $nuevoNombre      = '';
    public string $nuevaUnidad      = 'unidad';

    public function mount(int $mantenimientoId): void
    {
        $this->mantenimientoId = $mantenimientoId;
    }

    /** El caso pudo cerrarse/avanzar desde el panel de Acciones (otro componente). */
    #[On('mantenimientoActualizado')]
    public function refrescar(): void
    {
        unset($this->mantenimiento);
    }

    #[Computed]
    public function mantenimiento(): Mantenimiento
    {
        return Mantenimiento::with(['componentes.componente', 'componentes.entregadoPor'])->findOrFail($this->mantenimientoId);
    }

    #[Computed]
    public function componentesDisponibles()
    {
        $m = $this->mantenimiento;

        return ComponenteAlmacen::activos()
            ->where('empresa_id', $m->empresa_id)
            ->orderBy('nombre')
            ->get()
            ->mapWithKeys(fn ($c) => [$c->id => "{$c->nombre} (stock: {$c->stock_actual} {$c->unidad})"]);
    }

    public function abrirForm(): void
    {
        $m = $this->mantenimiento;
        $this->authorize('update', $m);

        if (! $m->estaAbierto()) {
            $this->dispatch('toast', type: 'error', message: 'Este caso ya está cerrado.');
            return;
        }

        $this->reset(['componente_id', 'cantidad', 'solicitarNuevo', 'nuevoNombre', 'nuevaUnidad']);
        $this->cantidad = 1;
        $this->nuevaUnidad = 'unidad';
        $this->formAbierto = true;
    }

    public function cerrarForm(): void
    {
        $this->formAbierto = false;
        $this->resetValidation();
    }

    protected function rules(): array
    {
        if ($this->solicitarNuevo) {
            return [
                'nuevoNombre' => 'required|string|max:150',
                'nuevaUnidad' => 'required|string|max:30',
                'cantidad'    => 'required|integer|min:1',
            ];
        }

        return [
            'componente_id' => 'required|exists:componentes_almacen,id',
            'cantidad'      => 'required|integer|min:1',
        ];
    }

    public function pedir(): void
    {
        $m = $this->mantenimiento;
        $this->authorize('update', $m);

        $this->validate();

        try {
            if ($this->solicitarNuevo) {
                $this->authorize('create', ComponenteAlmacen::class);

                $componente = ComponenteAlmacen::create([
                    'empresa_id'   => $m->empresa_id,
                    'nombre'       => trim($this->nuevoNombre),
                    'unidad'       => trim($this->nuevaUnidad) ?: 'unidad',
                    'stock_actual' => 0,
                    'activo'       => true,
                    'creado_por'   => Auth::id(),
                ]);
            } else {
                $componente = ComponenteAlmacen::findOrFail($this->componente_id);
            }

            $item = $m->pedirComponente($componente, $this->cantidad, Auth::user());

            $msg = $item->estado === 'Entregado'
                ? "«{$componente->nombre}» entregado desde el almacén."
                : "«{$componente->nombre}» solicitado — no hay stock, queda pendiente en el almacén.";

            $this->dispatch('toast', type: $item->estado === 'Entregado' ? 'success' : 'warning', message: $msg);
            $this->dispatch('componenteRefrescar');
            unset($this->mantenimiento);
            $this->cerrarForm();
        } catch (\Exception $e) {
            Log::error('MantenimientoComponentesPanel@pedir: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al pedir el componente.');
        }
    }

    public function entregarPendiente(int $itemId): void
    {
        $m = $this->mantenimiento;
        $this->authorize('update', $m);

        $item = MantenimientoComponente::findOrFail($itemId);

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
