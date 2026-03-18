<?php

namespace App\Livewire\Equipos;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Illuminate\Support\Collection;
use App\Models\Equipo;
use App\Models\CategoriaEquipo;
use App\Models\EstadoEquipo;
use App\Models\AtributoEquipo;
use Illuminate\Support\Facades\Auth;

class EquiposTable extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public $search = '';

    public $categoria_id = '';
    public $estado_id = '';
    public $perPage = 10;

    public Collection $atributosFiltrables;
    public $filtros = [];

    public function mount()
    {
        $this->atributosFiltrables = collect();
    }

    public function updated($property)
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function updatedCategoriaId()
    {
        $this->cargarAtributosFiltrables();
    }

    private function cargarAtributosFiltrables()
    {
        if (!$this->categoria_id) {
            $this->atributosFiltrables = collect();
            $this->filtros = [];
            return;
        }

        $this->atributosFiltrables = AtributoEquipo::where('categoria_id', $this->categoria_id)
            ->where('filtrable', true)
            ->orderBy('orden')
            ->get();

        foreach ($this->atributosFiltrables as $atributo) {
            if (!isset($this->filtros[$atributo->id])) {
                $this->filtros[$atributo->id] = '';
            }
        }
    }

    public function resetFilters()
    {
        $this->reset([
            'search',
            'categoria_id',
            'estado_id',
            'filtros'
        ]);

        $this->atributosFiltrables = collect();
        $this->resetPage();
    }

    public function getActiveFiltersCountProperty()
    {
        return collect([
            $this->search,
            $this->categoria_id,
            $this->estado_id,
        ])
        ->merge($this->filtros)
        ->filter()
        ->count();
    }

    public function render()
    {
        $empresaId = Auth::user()->empresa_id;

        $query = Equipo::query()
            ->with(['categoria', 'estado'])
            ->where('empresa_id', $empresaId);

        // 🔎 búsqueda
        if ($this->search) {
            $query->where('codigo_interno', 'like', "%{$this->search}%");
        }

        // 📂 filtros base
        if ($this->categoria_id) {
            $query->where('categoria_id', $this->categoria_id);
        }

        if ($this->estado_id) {
            $query->where('estado_id', $this->estado_id);
        }

        // 🔥 filtros dinámicos EAV
        foreach ($this->filtros as $atributoId => $valor) {

            if ($valor === null || $valor === '') {
                continue;
            }

            $atributo = $this->atributosFiltrables->firstWhere('id', $atributoId);

            $query->whereExists(function ($sub) use ($atributoId, $valor, $atributo) {
                $sub->selectRaw(1)
                    ->from('equipo_atributo_valores as eav')
                    ->whereColumn('eav.equipo_id', 'equipos.id')
                    ->where('eav.atributo_id', $atributoId)
                    ->where('eav.es_actual', true);

                if ($atributo && in_array($atributo->tipo, ['integer', 'decimal'])) {
                    $sub->where('eav.valor', $valor);
                } elseif ($atributo && $atributo->tipo === 'boolean') {
                    $sub->where('eav.valor', $valor ? 1 : 0);
                } else {
                    $sub->where('eav.valor', 'like', "%{$valor}%");
                }
            });
        }

        // 📊 stats
        $baseQuery = clone $query;

        $total = (clone $baseQuery)->count();

        $activos = (clone $baseQuery)
            ->whereHas('estado', fn($q) => $q->where('nombre', 'Activo'))
            ->count();

        $mantenimiento = (clone $baseQuery)
            ->whereHas('estado', fn($q) => $q->where('nombre', 'Mantenimiento'))
            ->count();

        $baja = (clone $baseQuery)
            ->whereHas('estado', fn($q) => $q->where('nombre', 'Baja'))
            ->count();

        return view('livewire.equipos.equipos-table', [
            'equipos' => $query->paginate($this->perPage),
            'categorias' => CategoriaEquipo::pluck('nombre', 'id'),
            'estados' => EstadoEquipo::pluck('nombre', 'id'),
            'total' => $total,
            'activos' => $activos,
            'mantenimiento' => $mantenimiento,
            'baja' => $baja,
        ]);
    }
}
