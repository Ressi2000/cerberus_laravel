<?php

namespace App\Livewire\Mantenimientos;

use App\Models\ComponenteAlmacen;
use App\Models\Mantenimiento;
use App\Models\MantenimientoComponente;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class MantenimientoComponentesPanel extends Component
{
    // Locked: el id no se puede cambiar desde el navegador.
    #[Locked]
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
    public string $nuevaFamiliaId   = '';

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

    /** Familias activas, para dar de alta un componente nuevo ya clasificado. */
    #[Computed]
    public function familiasOpciones()
    {
        return \App\Models\FamiliaComponente::activas()->orderBy('nombre')->pluck('nombre', 'id');
    }

    #[Computed]
    public function componentesDisponibles()
    {
        $m = $this->mantenimiento;

        // Las piezas ligadas a una característica del equipo (RAM, disco...)
        // no se entregan por aquí — se instalan desde el panel de Piezas —,
        // así que no cuentan como disponibles para pedir.
        return ComponenteAlmacen::activos()
            ->where('empresa_id', $m->empresa_id)
            ->withCount(['piezasExtraidas as piezas_con_caracteristica' => fn ($q) => $q
                ->where('estado', \App\Models\PiezaExtraida::ESTADO_EN_ALMACEN)
                ->whereNotNull('atributo_id'),
            ])
            ->orderBy('nombre')
            ->get()
            ->mapWithKeys(function ($c) {
                $disponibles = max(0, $c->stock_actual - $c->piezas_con_caracteristica);
                $texto = "{$c->nombre} (disponible: {$disponibles} {$c->unidad})";

                if ($c->piezas_con_caracteristica > 0) {
                    $texto .= " — {$c->piezas_con_caracteristica} se instalan desde Piezas";
                }

                return [$c->id => $texto];
            });
    }

    public function abrirForm(): void
    {
        $m = $this->mantenimiento;
        $this->authorize('update', $m);

        if (! $m->permiteRegistrarTrabajo()) {
            $this->dispatch('toast', type: 'error', message: 'Este caso ya está cerrado.');
            return;
        }

        $this->reset(['componente_id', 'cantidad', 'solicitarNuevo', 'nuevoNombre', 'nuevaUnidad', 'nuevaFamiliaId']);
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
                'nuevaFamiliaId' => 'required|exists:familias_componentes,id',
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

        // Se revisa aquí y no solo en abrirForm(): pedir() se puede invocar
        // directamente desde el navegador sin pasar por el formulario.
        if (! $m->permiteRegistrarTrabajo()) {
            $this->dispatch('toast', type: 'error', message: 'No se pueden pedir componentes en el estado actual del caso.');
            return;
        }

        $this->validate();

        try {
            if ($this->solicitarNuevo) {
                $this->authorize('create', ComponenteAlmacen::class);

                $componente = ComponenteAlmacen::create([
                    'empresa_id'   => $m->empresa_id,
                    'familia_id'   => (int) $this->nuevaFamiliaId,
                    'nombre'       => trim($this->nuevoNombre),
                    'unidad'       => trim($this->nuevaUnidad) ?: 'unidad',
                    'stock_actual' => 0,
                    'activo'       => true,
                    'creado_por'   => Auth::id(),
                ]);
            } else {
                // Solo componentes activos de la empresa del caso.
                $componente = ComponenteAlmacen::activos()
                    ->where('empresa_id', $m->empresa_id)
                    ->find($this->componente_id);

                if (! $componente) {
                    $this->addError('componente_id', 'Ese componente no está disponible para este caso.');
                    return;
                }
            }

            $item = $m->pedirComponente($componente, $this->cantidad, Auth::user());

            $msg = $item->estado === 'Entregado'
                ? "«{$componente->nombre}» entregado desde el almacén."
                : ($componente->stock_actual >= $this->cantidad
                    ? "«{$componente->nombre}» quedó pendiente: las unidades en almacén son piezas ligadas a una característica del equipo — instálalas desde el panel de Piezas."
                    : "«{$componente->nombre}» solicitado — no hay stock, queda pendiente en el almacén.");

            $this->dispatch('toast', type: $item->estado === 'Entregado' ? 'success' : 'warning', message: $msg);
            $this->dispatch('componenteRefrescar');
            unset($this->mantenimiento);
            $this->cerrarForm();
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        } catch (\Exception $e) {
            Log::error('MantenimientoComponentesPanel@pedir: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al pedir el componente.');
        }
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
