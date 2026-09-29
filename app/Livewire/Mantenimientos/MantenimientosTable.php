<?php

namespace App\Livewire\Mantenimientos;

use App\Models\Empresa;
use App\Models\Mantenimiento;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Listado reactivo de Mantenimientos y Reparaciones (un solo listado para
 * ambos tipos, filtrable por tipo/estado/empresa).
 */
class MantenimientosTable extends Component
{
    use WithPagination;

    #[Url(as: 'empresa')]
    public string $empresa_id = '';

    public string $tipo             = '';
    public string $estado           = '';
    public bool   $mostrar_cerrados = false;
    public string $search           = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Mantenimiento::class);

        $actor = Auth::user();
        if (! $actor->hasRole('Administrador')) {
            $this->empresa_id = (string) ($actor->empresa_activa_id ?? '');
        }
    }

    public function updated(string $property): void
    {
        if ($property !== 'page') $this->resetPage();
    }

    public function resetFilters(): void
    {
        $actor = Auth::user();
        $this->reset(['search', 'tipo', 'estado', 'mostrar_cerrados']);
        $this->empresa_id = $actor->hasRole('Administrador') ? '' : (string) ($actor->empresa_activa_id ?? '');
        $this->resetPage();
    }

    #[Computed]
    public function empresasOpciones()
    {
        $actor = Auth::user();

        if ($actor->hasRole('Administrador')) {
            return Empresa::orderBy('nombre')->pluck('nombre', 'id');
        }

        return Empresa::where('id', $actor->empresa_activa_id)->pluck('nombre', 'id');
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        $actor = Auth::user();

        return collect([
            $this->search !== '',
            $this->estado !== '',
            $this->tipo !== '',
            $this->mostrar_cerrados,
            $actor->hasRole('Administrador') && $this->empresa_id !== '',
        ])->filter()->count();
    }

    private function baseQuery()
    {
        return Mantenimiento::visiblePara(Auth::user());
    }

    #[Computed]
    public function totalAbiertos(): int
    {
        return $this->baseQuery()->abiertos()->count();
    }

    #[Computed]
    public function totalEsperandoComponente(): int
    {
        return $this->baseQuery()->abiertos()->where('esperando_componente', true)->count();
    }

    #[Computed]
    public function totalCorrectivos(): int
    {
        return $this->baseQuery()->correctivos()->abiertos()->count();
    }

    #[Computed]
    public function totalPreventivos(): int
    {
        return $this->baseQuery()->preventivos()->abiertos()->count();
    }

    /**
     * Query base para las pestañas de estado y para el listado: todos los
     * filtros MENOS `estado` — así cada pestaña puede mostrar cuántos casos
     * le tocan sin heredar la pestaña actualmente seleccionada.
     */
    private function queryFiltrada()
    {
        return Mantenimiento::visiblePara(Auth::user())
            ->when($this->tipo, fn ($q) => $q->where('tipo', $this->tipo))
            ->when($this->empresa_id, fn ($q) => $q->where('empresa_id', $this->empresa_id))
            ->when($this->search, fn ($q) => $q->whereHas('equipo', fn ($q) =>
                $q->where('codigo_interno', 'like', "%{$this->search}%")
            ));
    }

    /**
     * Cantidad de casos por estado (respetando tipo/empresa/búsqueda, pero
     * no la pestaña de estado en sí, ni el toggle de "mostrar cerrados" —
     * cada pestaña necesita ver su propio conteo real). Alimenta las
     * pestañas del listado para no tener que adivinar "¿cuál es mi caso?"
     * en una tabla plana con todo mezclado.
     */
    #[Computed]
    public function conteosPorEstado(): array
    {
        return $this->queryFiltrada()
            ->select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->all();
    }

    #[Computed]
    public function estadosDisponibles(): array
    {
        return collect(Mantenimiento::ESTADOS_PREVENTIVO)
            ->merge(Mantenimiento::ESTADOS_CORRECTIVO)
            ->unique()
            ->values()
            ->all();
    }

    #[Computed]
    public function mantenimientos()
    {
        return $this->queryFiltrada()
            ->with(['equipo.categoria', 'empresa', 'responsable'])
            // Una pestaña de estado específica ya deja bien claro qué se
            // quiere ver (incluidos los terminales, ej. "Completado"); el
            // toggle "mostrar cerrados" solo aplica a la pestaña "Todos".
            ->when(! $this->estado && ! $this->mostrar_cerrados, fn ($q) => $q->abiertos())
            ->when($this->estado, fn ($q) => $q->where('estado', $this->estado))
            ->latest('fecha_inicio')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.mantenimientos.mantenimientos-table');
    }
}
