<?php

namespace App\Livewire\Equipos;

use App\Models\AtributoEquipo;
use App\Models\CategoriaEquipo;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Ubicacion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
/*
 * Visibilidad:
 *  - Administrador → ve todos los equipos
 *  - Analista       → ve equipos de su ubicación física + foráneos
 *  - Usuario        → no puede acceder al módulo
 */

class EquiposTable extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public string $categoria_id  = '';
    public string $estado_id     = '';
    public string $ubicacion_id  = '';
    public string $activo        = '';
    public string $garantia      = '';
    public string $fecha_desde   = '';
    public string $fecha_hasta   = '';
    public int    $perPage       = 10;

    public array $filtros = [];

    // ─────────────────────────────────────────────────────────────────────────
    public function updated(string $property): void
    {
        if ($property !== 'page') $this->resetPage();
    }

    public function updatedCategoriaId(): void
    {
        $this->filtros = [];
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'categoria_id',
            'estado_id',
            'ubicacion_id',
            'activo',
            'garantia',
            'fecha_desde',
            'fecha_hasta',
            'filtros',
        ]);
        $this->resetPage();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Computed Properties
    // ─────────────────────────────────────────────────────────────────────────
    #[Computed]
    public function categorias()
    {
        return CategoriaEquipo::orderBy('nombre')->pluck('nombre', 'id');
    }

    #[Computed]
    public function estados()
    {
        return EstadoEquipo::orderBy('nombre')->pluck('nombre', 'id');
    }

    #[Computed]
    public function ubicaciones()
    {
        $user = Auth::user();

        if ($user->hasRole('Administrador')) {
            return Ubicacion::orderBy('nombre')->pluck('nombre', 'id');
        }

        // Analista: solo la ubicación de su empresa activa + foráneos
        return Ubicacion::where(function ($q) use ($user) {
            $q->where('empresa_id', $user->empresa_activa_id)
                ->orWhere('es_estado', true);
        })
            ->orderBy('nombre')
            ->pluck('nombre', 'id');
    }

    #[Computed]
    public function atributosFiltrables(): Collection
    {
        if (! $this->categoria_id) return collect();

        return AtributoEquipo::where('categoria_id', $this->categoria_id)
            ->where('filtrable', true)
            ->orderBy('orden')
            ->get();
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        return collect([
            $this->search,
            $this->categoria_id,
            $this->estado_id,
            $this->ubicacion_id,
            $this->activo,
            $this->garantia,
            $this->fecha_desde,
            $this->fecha_hasta,
        ])
            ->merge($this->filtros)
            ->filter()
            ->count();
    }

    #[Computed]
    public function filterParams(): array
    {
        return [
            'search'       => $this->search,
            'categoria_id' => $this->categoria_id,
            'estado_id'    => $this->estado_id,
            'ubicacion_id' => $this->ubicacion_id,
            'activo'       => $this->activo,
            'garantia'     => $this->garantia,
            'fecha_desde'  => $this->fecha_desde,
            'fecha_hasta'  => $this->fecha_hasta,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Render / Query
    // ─────────────────────────────────────────────────────────────────────────
    public function render()
    {
        $empresaId = Auth::user()->empresa_activa_id ?? Auth::user()->empresa_id;

        $query = Equipo::query()
            ->with([
                'categoria',
                'estado',
                'ubicacion',
                // Cargamos TODOS los atributos actuales (marca, modelo, RAM, etc.)
                // en una sola query para evitar N+1. El blade los accede por slug.
                'atributosActuales.atributo',
            ])
            ->visiblePara(Auth::user());

        // Búsqueda libre — incluye atributos EAV
        if ($this->search) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('codigo_interno', 'like', "%{$s}%")
                    ->orWhere('serial', 'like', "%{$s}%")
                    ->orWhere('nombre_maquina', 'like', "%{$s}%")
                    ->orWhereHas(
                        'atributosActuales',
                        fn($sub) =>
                        $sub->where('valor', 'like', "%{$s}%")
                    );
            });
        }

        if ($this->categoria_id)  $query->where('categoria_id', $this->categoria_id);
        if ($this->estado_id)     $query->where('estado_id',    $this->estado_id);
        if ($this->ubicacion_id)  $query->where('ubicacion_id', $this->ubicacion_id);

        if ($this->activo !== '') {
            $query->where('activo', (bool) $this->activo);
        }

        if ($this->garantia === 'vigente') {
            $query->where('fecha_garantia_fin', '>=', now()->toDateString());
        } elseif ($this->garantia === 'vencida') {
            $query->whereNotNull('fecha_garantia_fin')
                ->where('fecha_garantia_fin', '<', now()->toDateString());
        }

        if ($this->fecha_desde) $query->whereDate('fecha_adquisicion', '>=', $this->fecha_desde);
        if ($this->fecha_hasta) $query->whereDate('fecha_adquisicion', '<=', $this->fecha_hasta);

        // Filtros EAV dinámicos
        foreach ($this->filtros as $atributoId => $valor) {
            if ($valor === null || $valor === '') continue;

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
                    $sub->where('eav.valor', (int) $valor);
                } else {
                    $sub->where('eav.valor', 'like', "%{$valor}%");
                }
            });
        }

        // Stats
        $baseQuery       = clone $query;
        $total           = (clone $baseQuery)->count();
        $totalActivos    = (clone $baseQuery)->where('activo', true)->count();
        $garantiaVencida = (clone $baseQuery)
            ->whereNotNull('fecha_garantia_fin')
            ->where('fecha_garantia_fin', '<', now()->toDateString())
            ->count();
        $enMantenimiento = (clone $baseQuery)
            ->whereHas('estado', fn($q) => $q->where('nombre', 'like', '%reparaci%'))
            ->count();

        return view('livewire.equipos.equipos-table', [
            'equipos'         => $query->latest()->paginate($this->perPage),
            'total'           => $total,
            'totalActivos'    => $totalActivos,
            'garantiaVencida' => $garantiaVencida,
            'enMantenimiento' => $enMantenimiento,
        ]);
    }
}
