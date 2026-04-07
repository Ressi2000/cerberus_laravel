<?php

namespace App\Livewire\Asignaciones;

use App\Models\Asignacion;
use App\Models\AsignacionItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * AsignacionesTable — con pestañas Usuarios / Áreas comunes
 *
 * La pestaña activa se maneja con Alpine (sin Livewire) para no
 * generar requests al servidor al cambiar de tab. Cada pestaña
 * tiene su propia paginación independiente.
 */
class AsignacionesTable extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search     = '';
    public int    $perPage    = 15;

    public function updated(string $property): void
    {
        if ($property !== 'page') $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search']);
        $this->resetPage();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Stats unificadas (ambas pestañas)
    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function stats(): array
    {
        $actor = Auth::user();
        $base  = Asignacion::visiblePara($actor);

        $usuariosConEquipos = (clone $base)
            ->where('estado', 'Activa')
            ->whereNotNull('usuario_id')
            ->distinct('usuario_id')
            ->count('usuario_id');

        $areasActivas = (clone $base)
            ->where('estado', 'Activa')
            ->whereNull('usuario_id')
            ->count();

        $equiposActivos = AsignacionItem::whereHas('asignacion', function ($q) use ($actor) {
            $q->visiblePara($actor)->where('estado', 'Activa');
        })->where('devuelto', false)->count();

        $cerradas = (clone $base)->where('estado', 'Cerrada')->count();

        return [
            'usuarios_con_equipos' => $usuariosConEquipos,
            'areas_activas'        => $areasActivas,
            'equipos_activos'      => $equiposActivos,
            'cerradas'             => $cerradas,
        ];
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        return collect([$this->search])->filter()->count();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Query — Pestaña USUARIOS
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
            })
            ->withCount([
                'asignacionItemsActivos as equipos_activos_count',
            ])
            ->withMax(
                ['asignaciones as ultima_asignacion' => function ($q) use ($actor) {
                    $q->visiblePara($actor);
                }],
                'fecha_asignacion'
            );

        if ($this->search) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('cedula', 'like', "%{$s}%")
                  ->orWhere('ficha', 'like', "%{$s}%");
            });
        }

        return $query->orderBy('name')->paginate($this->perPage, pageName: 'u_page');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Query — Pestaña ÁREAS COMUNES
    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function asignacionesArea()
    {
        $actor = Auth::user();

        $query = Asignacion::with([
            'areaEmpresa',
            'areaDepartamento',
            'areaResponsable.cargo',
            'analista',
            'itemsActivos.equipo.categoria',
        ])
            ->visiblePara($actor)
            ->where('estado', 'Activa')
            ->whereNull('usuario_id')        // sin usuario = área común
            ->whereNotNull('area_empresa_id') // con empresa de área

            ->withCount([
                'itemsActivos as equipos_activos_count',
            ]);

        if ($this->search) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->whereHas('areaEmpresa', fn($e) => $e->where('nombre', 'like', "%{$s}%"))
                  ->orWhereHas('areaDepartamento', fn($d) => $d->where('nombre', 'like', "%{$s}%"))
                  ->orWhereHas('areaResponsable', fn($u) => $u->where('name', 'like', "%{$s}%"));
            });
        }

        return $query->orderByDesc('fecha_asignacion')
            ->paginate($this->perPage, pageName: 'a_page');
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.asignaciones.asignaciones-table', [
            'usuarios'         => $this->usuarios,
            'asignacionesArea' => $this->asignacionesArea,
            'stats'            => $this->stats,
        ]);
    }
}