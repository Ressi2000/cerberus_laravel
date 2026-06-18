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
use Livewire\Attributes\On;
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
        return CategoriaEquipo::activos()->orderBy('nombre')->pluck('nombre', 'id');
    }

    #[Computed]
    public function estados()
    {
        return EstadoEquipo::activos()->orderBy('nombre')->pluck('nombre', 'id');
    }

    #[Computed]
    public function ubicaciones()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->hasRole('Administrador')) {
            return Ubicacion::where('activo', true)->orderBy('es_estado')->orderBy('nombre')->pluck('nombre', 'id');
        }

        // Analista: solo la ubicación de su empresa activa + foráneos
        return Ubicacion::where('activo', true)->where(function ($q) use ($user) {
            $q->where('empresa_id', $user->empresa_activa_id)
                ->orWhere('es_estado', true);
        })
            ->orderBy('es_estado')->orderBy('nombre')
            ->pluck('nombre', 'id');
    }

    #[On('equipoActualizado')]
    public function onEquipoActualizado(): void { }

    #[Computed]
    public function atributosFiltrables(): Collection
    {
        if (! $this->categoria_id) return collect();

        return AtributoEquipo::where('categoria_id', $this->categoria_id)
            ->filtrables()
            ->orderBy('orden')
            ->get();
    }

    #[Computed]
    public function atributosVisibles(): Collection
    {
        if (! $this->categoria_id) return collect();

        return AtributoEquipo::where('categoria_id', $this->categoria_id)
            ->where('visible_en_tabla', true)
            ->where('tipo', '!=', AtributoEquipo::TIPO_FILE)
            ->orderBy('orden')
            ->get();
    }

    /**
     * Columnas finales a renderizar: una por atributo simple, o una por
     * sub-campo cuando el atributo es de tipo 'group' (cada instancia se
     * concatena en la celda, ver render() de la tabla).
     */
    #[Computed]
    public function columnasVisibles(): Collection
    {
        $columnas = collect();

        foreach ($this->atributosVisibles as $attr) {
            if ($attr->tipo === AtributoEquipo::TIPO_GROUP) {
                foreach ($attr->sub_campos ?? [] as $sub) {
                    $columnas->push([
                        'key'         => "attr_{$attr->id}_sub_{$sub['id']}",
                        'label'       => "{$attr->nombre} - {$sub['nombre']}",
                        'atributo_id' => $attr->id,
                        'tipo'        => $sub['tipo'],
                        'sub_id'      => $sub['id'],
                    ]);
                }
                continue;
            }

            $columnas->push([
                'key'         => "attr_{$attr->id}",
                'label'       => $attr->nombre,
                'atributo_id' => $attr->id,
                'tipo'        => $attr->tipo,
                'sub_id'      => null,
            ]);
        }

        return $columnas;
    }

    #[Computed]
    public function headers(): array
    {
        if (! $this->categoria_id) {
            return ['Código', 'Categoría', 'Estado', 'Ubicación', 'Condición', 'Acciones'];
        }

        $eav = $this->columnasVisibles
            ->map(fn($c) => ['label' => $c['label'], 'key' => $c['key']])
            ->toArray();

        return array_merge(
            ['Código', 'Estado', 'Ubicación', 'Condición'],
            $eav,
            ['Acciones']
        );
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

        $query = Equipo::query()
            ->with([
                'categoria',
                'estado',
                'ubicacion',
                'atributosActuales.atributo',
                'grupoInstancias', // para atributos tipo 'group'
            ])
            ->visiblePara(Auth::user());

        // Búsqueda libre — incluye atributos EAV
        if ($this->search) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('codigo_interno', 'like', "%{$s}%")
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

        // Filtros EAV dinámicos (atributos simples + sub-campos de grupo)
        foreach ($this->filtros as $clave => $valor) {
            if ($valor === null || $valor === '') continue;

            // Sub-campo de un atributo tipo 'group': clave = "{atributoId}_sub_{subId}"
            if (str_contains((string) $clave, '_sub_')) {
                [$atributoId, $subId] = array_pad(explode('_sub_', $clave, 2), 2, null);

                $atributo = $this->atributosFiltrables->firstWhere('id', (int) $atributoId);
                $subCampo = collect($atributo?->sub_campos ?? [])->firstWhere('id', $subId);

                $query->whereExists(function ($sub) use ($atributoId, $subId, $valor, $subCampo) {
                    $sub->selectRaw(1)
                        ->from('equipo_atributo_grupo_instancias as egi')
                        ->whereColumn('egi.equipo_id', 'equipos.id')
                        ->where('egi.atributo_id', $atributoId);

                    // Sintaxis "columna->clave" de Eloquent: agnóstica de motor de BD
                    // (MySQL/SQLite/Postgres), a diferencia de JSON_EXTRACT/JSON_UNQUOTE crudo.
                    $columna = "egi.valores->{$subId}";

                    if ($subCampo && in_array($subCampo['tipo'], ['integer', 'decimal'])) {
                        $sub->where($columna, $valor);
                    } elseif ($subCampo && $subCampo['tipo'] === 'boolean') {
                        $sub->where($columna, (int) $valor);
                    } else {
                        $sub->where($columna, 'like', "%{$valor}%");
                    }
                });

                continue;
            }

            $atributoId = $clave;
            $atributo   = $this->atributosFiltrables->firstWhere('id', $atributoId);

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
        $baseQuery        = clone $query;
        $total            = (clone $baseQuery)->count();
        $totalActivos     = (clone $baseQuery)->where('activo', true)->count();
        $garantiaVencida  = (clone $baseQuery)
            ->whereNotNull('fecha_garantia_fin')
            ->where('fecha_garantia_fin', '<', now()->toDateString())
            ->count();
        $garantiaProxima  = (clone $baseQuery)
            ->whereNotNull('fecha_garantia_fin')
            ->where('fecha_garantia_fin', '>=', now()->toDateString())
            ->where('fecha_garantia_fin', '<=', now()->addDays(30)->toDateString())
            ->count();
        $enMantenimiento  = (clone $baseQuery)
            ->whereHas('estado', fn($q) => $q->where('nombre', 'like', '%reparaci%'))
            ->count();

        return view('livewire.equipos.equipos-table', [
            'equipos'         => $query->latest()->paginate($this->perPage),
            'total'           => $total,
            'totalActivos'    => $totalActivos,
            'garantiaVencida' => $garantiaVencida,
            'garantiaProxima' => $garantiaProxima,
            'enMantenimiento' => $enMantenimiento,
        ]);
    }
}
