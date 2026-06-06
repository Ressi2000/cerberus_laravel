<?php

namespace App\Livewire\Prestamos;

use App\Models\Empresa;
use App\Models\Prestamo;
use App\Models\PrestamoItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PrestamosTable extends Component
{
    use WithPagination;

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

    #[Url(as: 'tab')]
    public string $tabActiva   = 'usuarios';

    public int $perPage = 15;

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
    // Opciones de filtros
    // ─────────────────────────────────────────────────────────────────────────

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
    public function analistasOpciones()
    {
        return User::whereHas('roles', fn ($q) => $q->whereIn('name', ['Administrador', 'Analista']))
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Stats
    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function stats(): array
    {
        $actor = Auth::user();
        $base  = Prestamo::visiblePara($actor);

        return [
            'usuarios_con_prestamos' => (clone $base)
                ->whereIn('estado', ['Activo', 'Vencido'])
                ->whereNotNull('usuario_id')
                ->distinct('usuario_id')
                ->count('usuario_id'),

            'areas_activas' => (clone $base)
                ->whereIn('estado', ['Activo', 'Vencido'])
                ->whereNull('usuario_id')
                ->count(),

            'equipos_en_prestamo' => PrestamoItem::whereHas('prestamo', function ($q) use ($actor) {
                $q->visiblePara($actor)->whereIn('estado', ['Activo', 'Vencido']);
            })->where('devuelto', false)->count(),

            'vencidos' => (clone $base)->where('estado', 'Vencido')->count(),

            'cerrados' => (clone $base)->where('estado', 'Cerrado')->count(),
        ];
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        return collect([$this->search, $this->empresa_id, $this->analista_id, $this->fecha_desde, $this->fecha_hasta])
            ->filter()->count();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Query base
    // ─────────────────────────────────────────────────────────────────────────

    private function baseQuery(): Builder
    {
        $actor = Auth::user();
        $query = Prestamo::visiblePara($actor);

        if ($this->empresa_id)  $query->where('empresa_id',  $this->empresa_id);
        if ($this->analista_id) $query->where('analista_id', $this->analista_id);
        if ($this->fecha_desde) $query->whereDate('fecha_prestamo', '>=', $this->fecha_desde);
        if ($this->fecha_hasta) $query->whereDate('fecha_prestamo', '<=', $this->fecha_hasta);

        return $query;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Pestaña USUARIOS
    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function usuarios()
    {
        $actor = Auth::user();

        $query = User::query()
            ->with(['cargo', 'empresaNomina', 'ubicacion'])
            ->whereHas('prestamos', function (Builder $q) use ($actor) {
                $q->visiblePara($actor)
                  ->whereIn('estado', ['Activo', 'Vencido']);

                if ($this->empresa_id)  $q->where('empresa_id',  $this->empresa_id);
                if ($this->analista_id) $q->where('analista_id', $this->analista_id);
                if ($this->fecha_desde) $q->whereDate('fecha_prestamo', '>=', $this->fecha_desde);
                if ($this->fecha_hasta) $q->whereDate('fecha_prestamo', '<=', $this->fecha_hasta);
            })
            ->withCount(['prestamoItemsActivos as equipos_prestados_count'])
            ->withMax(
                ['prestamos as ultimo_prestamo' => fn ($q) => $q->visiblePara($actor)],
                'fecha_prestamo'
            );

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
    // Pestaña ÁREAS COMUNES
    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function prestamosArea()
    {
        $query = $this->baseQuery()
            ->with(['areaEmpresa', 'areaDepartamento', 'areaResponsable.cargo', 'analista'])
            ->whereIn('estado', ['Activo', 'Vencido'])
            ->whereNull('usuario_id')
            ->withCount(['itemsActivos as equipos_activos_count']);

        if ($this->search) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->whereHas('areaEmpresa',      fn ($e) => $e->where('nombre', 'like', "%{$s}%"))
                  ->orWhereHas('areaDepartamento', fn ($d) => $d->where('nombre', 'like', "%{$s}%"))
                  ->orWhereHas('areaResponsable',  fn ($u) => $u->where('name',   'like', "%{$s}%"));
            });
        }

        return $query->orderByDesc('fecha_prestamo')->paginate($this->perPage, pageName: 'a_page');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Pestaña CERRADOS
    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function prestamosCerrados()
    {
        $query = $this->baseQuery()
            ->with([
                'empresa', 'analista',
                'usuario.cargo', 'usuario.empresaNomina',
                'areaDepartamento', 'areaEmpresa', 'areaResponsable',
            ])
            ->where('estado', 'Cerrado')
            ->withCount(['items as total_equipos_count']);

        if ($this->search) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->whereHas('usuario', fn ($u) =>
                    $u->where('name', 'like', "%{$s}%")
                      ->orWhere('cedula', 'like', "%{$s}%")
                      ->orWhere('ficha',  'like', "%{$s}%")
                )
                ->orWhereHas('areaDepartamento', fn ($d) => $d->where('nombre', 'like', "%{$s}%"))
                ->orWhereHas('areaEmpresa',      fn ($e) => $e->where('nombre', 'like', "%{$s}%"))
                ->orWhereHas('areaResponsable',  fn ($u) => $u->where('name',   'like', "%{$s}%"));
            });
        }

        return $query->orderByDesc('fecha_prestamo')->paginate($this->perPage, pageName: 'c_page');
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.prestamos.prestamos-table', [
            'usuarios'         => $this->usuarios,
            'prestamosArea'    => $this->prestamosArea,
            'prestamosCerrados' => $this->prestamosCerrados,
            'stats'            => $this->stats,
        ]);
    }
}
