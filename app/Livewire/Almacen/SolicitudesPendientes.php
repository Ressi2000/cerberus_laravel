<?php

namespace App\Livewire\Almacen;

use App\Models\ComponenteAlmacen;
use App\Models\Empresa;
use App\Models\MantenimientoComponente;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Bandeja de solicitudes pendientes del Almacén.
 *
 * Reúne en un solo lugar los componentes que los casos de mantenimiento o
 * reparación pidieron cuando no había stock ("Pendiente"). Antes había que
 * entrar caso por caso para ver qué faltaba. Desde aquí se ve qué se puede
 * entregar ya, se registra la entrada de stock que falte y se entrega, con la
 * misma lógica que el panel del caso (Mantenimiento::entregarComponentePendiente()).
 */
class SolicitudesPendientes extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'empresa')]
    public string $empresa_id = '';

    /** Solo las que ya se pueden entregar (hay stock y el caso está en trabajo). */
    public bool $solo_entregables = false;

    public function mount(): void
    {
        $this->authorize('viewAny', ComponenteAlmacen::class);
    }

    public function updated(string $property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'empresa_id', 'solo_entregables']);
        $this->resetPage();
    }

    /** Una entrada de stock registrada desde aquí puede volver entregables varias filas. */
    #[On('stockActualizado')]
    public function refrescar(): void
    {
        unset($this->solicitudes, $this->stats);
    }

    #[Computed]
    public function empresasOpciones()
    {
        return Auth::user()->hasRole('Administrador')
            ? Empresa::orderBy('nombre')->pluck('nombre', 'id')
            : collect();
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        return collect([$this->search !== '', $this->empresa_id !== '', $this->solo_entregables])->filter()->count();
    }

    /**
     * Todas las pendientes visibles con los filtros de texto/empresa, ya
     * evaluadas (¿se puede entregar? y, si no, por qué). Se evalúan en PHP
     * porque "hay stock" depende de las piezas con identidad de cada
     * componente (ver ComponenteAlmacen::unidadesParaConsumo()).
     */
    #[Computed]
    public function solicitudes()
    {
        $items = MantenimientoComponente::pendientesVisiblesPara(Auth::user())
            ->with(['componente', 'mantenimiento.equipo', 'mantenimiento.empresa'])
            ->when($this->empresa_id, fn ($q) => $q->whereHas('mantenimiento', fn ($m) => $m->where('empresa_id', $this->empresa_id)))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->whereHas('componente', fn ($c) => $c->where('nombre', 'like', "%{$this->search}%"))
                  ->orWhereHas('mantenimiento.equipo', fn ($e) => $e->where('codigo_interno', 'like', "%{$this->search}%"));
            }))
            ->oldest()
            ->get();

        // Disponible por componente, calculado una sola vez y descontando lo
        // que ya "reservan" las solicitudes más antiguas del mismo componente:
        // si hay 1 unidad y 2 casos la piden, solo la más antigua sale entregable.
        $disponible = [];

        return $items->map(function (MantenimientoComponente $item) use (&$disponible) {
            $componente = $item->componente;
            $caso       = $item->mantenimiento;

            // Por unidad cuenta todo el stock (se elige la pieza por su serial
            // al entregar, desde el caso); a granel, lo que se entrega por cantidad.
            if ($componente && ! array_key_exists($componente->id, $disponible)) {
                $disponible[$componente->id] = $componente->esAGranel()
                    ? $componente->unidadesParaConsumo()
                    : (int) $componente->stock_actual;
            }

            $hay = $componente ? $disponible[$componente->id] : 0;

            $motivo = match (true) {
                ! $componente                     => 'El componente ya no existe.',
                $hay < $item->cantidad_requerida  => "Falta stock: hay {$hay} disponible(s) de {$item->cantidad_requerida}.",
                ! $caso->permiteRegistrarTrabajo() => "El caso está «{$caso->estado}»: se entrega cuando esté en trabajo.",
                default                           => null,
            };

            if ($motivo === null) {
                $disponible[$componente->id] -= $item->cantidad_requerida;
            }

            $item->setAttribute('entregable', $motivo === null);
            // Por unidad se entrega desde el caso, eligiendo la pieza (y su ficha).
            $item->setAttribute('en_el_caso', $componente && ! $componente->esAGranel());
            $item->setAttribute('motivo_no_entregable', $motivo);

            return $item;
        });
    }

    #[Computed]
    public function stats(): array
    {
        $todas = $this->solicitudes;

        return [
            'pendientes'  => $todas->count(),
            'entregables' => $todas->where('entregable', true)->count(),
            'casos'       => $todas->pluck('mantenimiento_id')->unique()->count(),
            'componentes' => $todas->pluck('componente_id')->unique()->count(),
        ];
    }

    public function entregar(int $itemId): void
    {
        $item = MantenimientoComponente::pendientesVisiblesPara(Auth::user())->with('mantenimiento')->find($itemId);

        if (! $item) {
            $this->dispatch('toast', type: 'error', message: 'Esa solicitud ya no está pendiente.');
            $this->refrescar();
            return;
        }

        $caso = $item->mantenimiento;
        $this->authorize('update', $caso);

        try {
            if ($caso->entregarComponentePendiente($item, Auth::user())) {
                $this->dispatch('toast', type: 'success', message: "Entregado al caso #{$caso->id}.");
            } else {
                $this->dispatch('toast', type: 'error', message: 'Todavía no hay stock suficiente para entregar esta solicitud.');
            }
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        } catch (\Exception $e) {
            Log::error('SolicitudesPendientes@entregar: ' . $e->getMessage());
            $this->dispatch('toast', type: 'error', message: 'Error al entregar la solicitud.');
        }

        $this->refrescar();
    }

    public function render()
    {
        $filtradas = $this->solo_entregables
            ? $this->solicitudes->where('entregable', true)->values()
            : $this->solicitudes;

        $porPagina = 15;
        $pagina    = new \Illuminate\Pagination\LengthAwarePaginator(
            $filtradas->forPage($this->getPage(), $porPagina)->values(),
            $filtradas->count(),
            $porPagina,
            $this->getPage(),
            ['path' => route('admin.almacen.solicitudes'), 'pageName' => 'page'],
        );

        return view('livewire.almacen.solicitudes-pendientes', [
            'registros' => $pagina,
            'stats'     => $this->stats,
        ]);
    }
}
