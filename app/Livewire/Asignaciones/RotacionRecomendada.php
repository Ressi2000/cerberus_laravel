<?php

namespace App\Livewire\Asignaciones;

use App\Models\AsignacionItem;
use App\Models\CategoriaEquipo;
use App\Models\Empresa;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Listado central de asignaciones que superan el periodo de rotación
 * recomendado configurado por categoría.
 *
 * IMPORTANTE: esto NO es una alerta de vida útil ni de estado físico del
 * equipo. Mide cuánto tiempo lleva un mismo receptor con un mismo equipo,
 * para que Gerencia pueda evaluar si conviene rotarlo — nada más.
 *
 * No se puede resolver con un simple where() en SQL porque el umbral
 * depende de la categoría de cada equipo y el cálculo vive en
 * AsignacionItem::superaRotacionRecomendada(); se trae el universo de
 * items activos con política de rotación configurada y se filtra/pagina
 * en PHP, igual que Dashboard.
 */
class RotacionRecomendada extends Component
{
    use WithPagination;

    public string $empresaId  = '';
    public string $categoriaId = '';

    public function updated($property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function resetFiltros(): void
    {
        $this->reset(['empresaId', 'categoriaId']);
        $this->resetPage();
    }

    #[Computed]
    public function empresas()
    {
        return Empresa::activas()->orderBy('nombre')->get(['id', 'nombre']);
    }

    #[Computed]
    public function categorias()
    {
        return CategoriaEquipo::activos()->whereNotNull('meses_rotacion_asignacion')->orderBy('nombre')->get(['id', 'nombre']);
    }

    #[Computed]
    public function itemsPendientes()
    {
        $actor = Auth::user();

        $items = AsignacionItem::with(['equipo.categoria', 'asignacion.usuario', 'asignacion.empresa'])
            ->whereHas('asignacion', function ($q) use ($actor) {
                $q->visiblePara($actor)->where('estado', 'Activa')
                    ->when($this->empresaId, fn($qq) => $qq->where('empresa_id', $this->empresaId));
            })
            ->where('devuelto', false)
            ->whereNull('equipo_padre_id')
            ->whereHas('equipo.categoria', function ($q) {
                $q->whereNotNull('meses_rotacion_asignacion')
                    ->when($this->categoriaId, fn($qq) => $qq->where('id', $this->categoriaId));
            })
            ->get()
            ->filter(fn($item) => $item->superaRotacionRecomendada())
            ->sortByDesc(fn($item) => $item->mesesConReceptor())
            ->values();

        return $items;
    }

    public function render()
    {
        $todos = $this->itemsPendientes();
        $page  = $this->getPage();
        $perPage = 15;

        $paginados = new \Illuminate\Pagination\LengthAwarePaginator(
            $todos->forPage($page, $perPage)->values(),
            $todos->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('livewire.asignaciones.rotacion-recomendada', [
            'items' => $paginados,
        ]);
    }
}
