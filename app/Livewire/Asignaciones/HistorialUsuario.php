<?php

namespace App\Livewire\Asignaciones;

use App\Models\Asignacion;
use App\Models\AsignacionItem;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * HistorialUsuario v2
 *
 * Mejoras respecto a v1 (punto D del plan):
 *   - Filtro por estado ('todas' | 'activa' | 'cerrada')
 *   - Filtro por año (dropdown con años disponibles)
 *   - Paginación del timeline: perPage configurable (default 5)
 *   - Stats del usuario al inicio: equipos activos, total asignaciones, última fecha
 *   - Las asignaciones cerradas se muestran colapsadas en la vista via Alpine x-collapse
 *
 * La lógica de colapso (cerradas ocultas por defecto) vive en la vista Blade:
 * el componente solo pagina y filtra; Alpine gestiona el accordion sin round-trip.
 */
class HistorialUsuario extends Component
{
    use WithPagination;
 
    public int $usuarioId;
 
    // ── Filtros del timeline ──────────────────────────────────────────────────
    public string $filtroEstado = 'todas';   // 'todas' | 'activa' | 'cerrada'
    public string $filtroAnio   = '';        // '' | '2024' | '2025' etc.
    public int    $perPage      = 5;
 
    // ─────────────────────────────────────────────────────────────────────────
 
    public function mount(User $usuario): void
    {
        $this->authorize('viewAny', Asignacion::class);
 
        /** @var User $actor */
        $actor = Auth::user();
 
        if ($actor->hasRole('Analista') && $actor->empresa_activa_id) {
            $visible = Asignacion::where('usuario_id', $usuario->id)
                ->where('empresa_id', $actor->empresa_activa_id)
                ->exists();
            abort_unless($visible, 403);
        }
 
        $this->usuarioId = $usuario->id;
    }
 
    public function updated(string $property): void
    {
        if ($property !== 'page') $this->resetPage();
    }
 
    public function resetFiltros(): void
    {
        $this->reset(['filtroEstado', 'filtroAnio']);
        $this->resetPage();
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // Datos del usuario
    // ─────────────────────────────────────────────────────────────────────────
 
    #[Computed]
    public function usuario(): User
    {
        return User::with(['cargo', 'departamento', 'empresaNomina', 'ubicacion', 'jefe'])
            ->findOrFail($this->usuarioId);
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // Stats del usuario (D — nuevas)
    // ─────────────────────────────────────────────────────────────────────────
 
    #[Computed]
    public function statsUsuario(): array
    {
        $totalAsignaciones = Asignacion::where('usuario_id', $this->usuarioId)->count();
 
        $equiposActivos = AsignacionItem::whereHas('asignacion', fn ($q) =>
            $q->where('usuario_id', $this->usuarioId)->where('estado', 'Activa')
        )->where('devuelto', false)->whereNull('equipo_padre_id')->count();
 
        $ultimaFecha = Asignacion::where('usuario_id', $this->usuarioId)
            ->max('fecha_asignacion');
 
        return [
            'total_asignaciones' => $totalAsignaciones,
            'equipos_activos'    => $equiposActivos,
            'ultima_asignacion'  => $ultimaFecha
                ? \Carbon\Carbon::parse($ultimaFecha)->format('d/m/Y')
                : '—',
        ];
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // Años disponibles para el filtro (D)
    // ─────────────────────────────────────────────────────────────────────────
 
    #[Computed]
    public function aniosDisponibles(): array
    {
        return Asignacion::where('usuario_id', $this->usuarioId)
            ->selectRaw('YEAR(fecha_asignacion) as anio')
            ->distinct()
            ->orderByDesc('anio')
            ->pluck('anio')
            ->toArray();
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // Equipos activos (sección 1 — sin paginar, siempre visible)
    // ─────────────────────────────────────────────────────────────────────────
 
    #[Computed]
    public function equiposActivos()
    {
        return AsignacionItem::with([
            'equipo.categoria',
            'equipo.atributosActuales.atributo',
            'asignacion',
            'asignacion.empresa',
            'hijos.equipo.categoria',
        ])
            ->whereHas('asignacion', fn ($q) =>
                $q->where('usuario_id', $this->usuarioId)->where('estado', 'Activa')
            )
            ->where('devuelto', false)
            ->whereNull('equipo_padre_id')
            ->orderBy('created_at')
            ->get();
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // Timeline de asignaciones — paginado y filtrable (D)
    // ─────────────────────────────────────────────────────────────────────────
 
    #[Computed]
    public function asignaciones()
    {
        $query = Asignacion::with([
            'analista',
            'empresa',
            // Sin whereNull: cargamos TODOS los items (principales y periféricos).
            // La vista los agrupa y muestra la jerarquía correctamente.
            'items' => fn ($q) => $q->with([
                'equipo.categoria',
                'devueltoPor',
                'hijos.equipo.categoria',
                'hijos.devueltoPor',
            ])->orderByRaw('COALESCE(equipo_padre_id, id)')->orderBy('equipo_padre_id')->orderBy('id'),
            'itemsDevueltos',
        ])
            ->where('usuario_id', $this->usuarioId);
 
        // Filtro por estado
        if ($this->filtroEstado !== 'todas') {
            $query->where('estado', ucfirst($this->filtroEstado));
        }
 
        // Filtro por año
        if ($this->filtroAnio) {
            $query->whereYear('fecha_asignacion', $this->filtroAnio);
        }
 
        return $query->orderByDesc('fecha_asignacion')
            ->paginate($this->perPage);
    }
 
    // ─────────────────────────────────────────────────────────────────────────
 
    public function render()
    {
        return view('livewire.asignaciones.historial-usuario');
    }
}