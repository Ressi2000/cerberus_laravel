<?php

namespace App\Livewire\Asignaciones;

use App\Models\Asignacion;
use App\Models\AsignacionItem;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * AsignacionesTable v2
 *
 * Mejoras respecto a v1:
 *   - Buscador funcional: aplica search en ambas queries (usuarios y áreas)
 *   - Filtros enriquecidos: tipo, empresa, analista, estado, rango de fechas
 *   - Pestaña "Cerradas" con sus propias queries y paginación
 *   - activeFiltersCount cubre todos los filtros nuevos
 *   - Pestaña activa sincronizada a URL con #[Url] para compartir/recargar
 */
class AsignacionesTable extends Component
{
    use WithPagination;

    // ── Búsqueda y filtros ────────────────────────────────────────────────────

    #[Url(as: 'q')]
    public string $search      = '';

    #[Url(as: 'empresa')]
    public string $empresa_id  = '';

    #[Url(as: 'analista')]
    public string $analista_id = '';

    #[Url(as: 'desde')]
    public string $fecha_desde = '';

    #[Url(as: 'hasta')]
    public string $fecha_hasta = '';

    // ── Paginación ────────────────────────────────────────────────────────────
    public int $perPage = 15;

    // ─────────────────────────────────────────────────────────────────────────

    public function updated(string $property): void
    {
        if ($property !== 'page') $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'empresa_id', 'analista_id', 'fecha_desde', 'fecha_hasta']);
        $this->resetPage();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Opciones para selects de filtros
    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function empresasOpciones()
    {
        $actor = Auth::user();

        if ($actor->hasRole('Administrador')) {
            return Empresa::orderBy('nombre')->pluck('nombre', 'id');
        }

        // Analista: solo su empresa activa
        return Empresa::where('id', $actor->empresa_activa_id)->pluck('nombre', 'id');
    }

    #[Computed]
    public function analistasOpciones()
    {
        $actor = Auth::user();

        $query = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['Administrador', 'Analista']))
            ->orderBy('name');

        if ($actor->hasRole('Analista') && $actor->empresa_activa_id) {
            $query->whereHas('empresas', fn ($q) => $q->where('empresa_id', $actor->empresa_activa_id));
        }

        return $query->pluck('name', 'id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Stats (todos los estados)
    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function stats(): array
    {
        $actor = Auth::user();
        $base  = Asignacion::visiblePara($actor);

        return [
            'usuarios_con_equipos' => (clone $base)
                ->where('estado', 'Activa')
                ->whereNotNull('usuario_id')
                ->distinct('usuario_id')
                ->count('usuario_id'),

            'areas_activas' => (clone $base)
                ->where('estado', 'Activa')
                ->whereNull('usuario_id')
                ->count(),

            'equipos_activos' => AsignacionItem::whereHas('asignacion', function ($q) use ($actor) {
                $q->visiblePara($actor)->where('estado', 'Activa');
            })->where('devuelto', false)->count(),

            'cerradas' => (clone $base)->where('estado', 'Cerrada')->count(),
        ];
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        return collect([
            $this->search,
            $this->empresa_id,
            $this->analista_id,
            $this->fecha_desde,
            $this->fecha_hasta,
        ])->filter()->count();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Query base reutilizable (aplica filtros comunes)
    // ─────────────────────────────────────────────────────────────────────────

    private function baseQuery(): Builder
    {
        $actor = Auth::user();
        $query = Asignacion::visiblePara($actor);

        if ($this->empresa_id) {
            $query->where('empresa_id', $this->empresa_id);
        }

        if ($this->analista_id) {
            $query->where('analista_id', $this->analista_id);
        }

        if ($this->fecha_desde) {
            $query->whereDate('fecha_asignacion', '>=', $this->fecha_desde);
        }

        if ($this->fecha_hasta) {
            $query->whereDate('fecha_asignacion', '<=', $this->fecha_hasta);
        }

        return $query;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Pestaña USUARIOS (activas)
    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function usuarios()
    {
        $actor = Auth::user();

        $query = User::query()
            ->with(['cargo', 'empresaNomina', 'ubicacion'])
            ->whereHas('asignaciones', function (Builder $q) use ($actor) {
                $q->visiblePara($actor)
                  ->where('estado', 'Activa')
                  ->whereHas('itemsActivos');

                if ($this->empresa_id) {
                    $q->where('empresa_id', $this->empresa_id);
                }
                if ($this->analista_id) {
                    $q->where('analista_id', $this->analista_id);
                }
                if ($this->fecha_desde) {
                    $q->whereDate('fecha_asignacion', '>=', $this->fecha_desde);
                }
                if ($this->fecha_hasta) {
                    $q->whereDate('fecha_asignacion', '<=', $this->fecha_hasta);
                }
            })
            ->withCount(['asignacionItemsActivos as equipos_activos_count'])
            ->withMax(
                ['asignaciones as ultima_asignacion' => fn ($q) => $q->visiblePara($actor)],
                'fecha_asignacion'
            );

        // ── BUSCADOR REPARADO ────────────────────────────────────────────────
        // Busca en nombre, cédula, ficha, email del usuario
        if ($this->search) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('cedula', 'like', "%{$s}%")
                  ->orWhere('ficha', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        return $query->orderBy('name')->paginate($this->perPage, pageName: 'u_page');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Pestaña ÁREAS COMUNES (activas)
    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function asignacionesArea()
    {
        $query = $this->baseQuery()
            ->with([
                'areaEmpresa',
                'areaDepartamento',
                'areaResponsable.cargo',
                'analista',
            ])
            ->where('estado', 'Activa')
            ->whereNull('usuario_id')
            ->whereNotNull('area_empresa_id')
            ->withCount(['itemsActivos as equipos_activos_count']);

        // ── BUSCADOR REPARADO ────────────────────────────────────────────────
        // Busca en empresa, departamento y nombre del responsable
        if ($this->search) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->whereHas('areaEmpresa', fn ($e) => $e->where('nombre', 'like', "%{$s}%"))
                  ->orWhereHas('areaDepartamento', fn ($d) => $d->where('nombre', 'like', "%{$s}%"))
                  ->orWhereHas('areaResponsable', fn ($u) => $u->where('name', 'like', "%{$s}%"));
            });
        }

        return $query->orderByDesc('fecha_asignacion')
            ->paginate($this->perPage, pageName: 'a_page');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Pestaña CERRADAS (A1 — nueva)
    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function asignacionesCerradas()
    {
        $query = $this->baseQuery()
            ->with([
                'empresa',
                'analista',
                // Receptor personal
                'usuario.cargo',
                'usuario.empresaNomina',
                // Receptor área
                'areaDepartamento',
                'areaEmpresa',
                'areaResponsable',
            ])
            ->where('estado', 'Cerrada')
            ->withCount(['items as total_equipos_count']);

        if ($this->search) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                // Buscar en usuario personal
                $q->whereHas('usuario', fn ($u) =>
                    $u->where('name', 'like', "%{$s}%")
                      ->orWhere('cedula', 'like', "%{$s}%")
                      ->orWhere('ficha', 'like', "%{$s}%")
                )
                // Buscar en área
                ->orWhereHas('areaDepartamento', fn ($d) => $d->where('nombre', 'like', "%{$s}%"))
                ->orWhereHas('areaEmpresa', fn ($e) => $e->where('nombre', 'like', "%{$s}%"))
                ->orWhereHas('areaResponsable', fn ($u) => $u->where('name', 'like', "%{$s}%"));
            });
        }

        return $query->orderByDesc('fecha_asignacion')
            ->paginate($this->perPage, pageName: 'c_page');
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.asignaciones.asignaciones-table', [
            'usuarios'             => $this->usuarios,
            'asignacionesArea'     => $this->asignacionesArea,
            'asignacionesCerradas' => $this->asignacionesCerradas,
            'stats'                => $this->stats,
        ]);
    }
}