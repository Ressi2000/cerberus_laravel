<?php

namespace App\Livewire\Prestamos;

use App\Models\Prestamo;
use App\Models\PrestamoItem;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * HistorialUsuario (Préstamos)
 *
 * Análogo a App\Livewire\Asignaciones\HistorialUsuario, adaptado a los
 * 3 estados de Prestamo (Activo | Vencido | Cerrado) en vez de los 2
 * de Asignacion (Activa | Cerrada).
 */
class HistorialUsuario extends Component
{
    use WithPagination;

    public int $usuarioId;

    // ── Filtros del timeline ──────────────────────────────────────────────────
    public string $filtroEstado = 'todos';   // 'todos' | 'activo' | 'vencido' | 'cerrado'
    public string $filtroAnio   = '';
    public int    $perPage      = 5;

    // ─────────────────────────────────────────────────────────────────────────

    public function mount(User $usuario): void
    {
        $this->authorize('viewAny', Prestamo::class);

        /** @var User $actor */
        $actor = Auth::user();

        if ($actor->hasRole('Analista') && $actor->empresa_activa_id) {
            $visible = Prestamo::where('usuario_id', $usuario->id)
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

    #[Computed]
    public function usuario(): User
    {
        return User::with(['cargo', 'departamento', 'empresaNomina', 'ubicacion', 'jefe'])
            ->findOrFail($this->usuarioId);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Stats del usuario
    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function statsUsuario(): array
    {
        $totalPrestamos = Prestamo::where('usuario_id', $this->usuarioId)->count();

        $equiposActivos = PrestamoItem::whereHas('prestamo', fn ($q) =>
            $q->where('usuario_id', $this->usuarioId)->whereIn('estado', ['Activo', 'Vencido'])
        )->where('devuelto', false)->whereNull('equipo_padre_id')->count();

        $vencidos = Prestamo::where('usuario_id', $this->usuarioId)
            ->where('estado', 'Vencido')
            ->count();

        $ultimaFecha = Prestamo::where('usuario_id', $this->usuarioId)
            ->max('fecha_prestamo');

        return [
            'total_prestamos'  => $totalPrestamos,
            'equipos_activos'  => $equiposActivos,
            'vencidos'         => $vencidos,
            'ultimo_prestamo'  => $ultimaFecha
                ? \Carbon\Carbon::parse($ultimaFecha)->format('d/m/Y')
                : '—',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function aniosDisponibles(): array
    {
        return Prestamo::where('usuario_id', $this->usuarioId)
            ->selectRaw('YEAR(fecha_prestamo) as anio')
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
        return PrestamoItem::with([
            'equipo.categoria',
            'equipo.atributosActuales.atributo',
            'prestamo',
            'prestamo.empresa',
            'hijos.equipo.categoria',
        ])
            ->whereHas('prestamo', fn ($q) =>
                $q->where('usuario_id', $this->usuarioId)->whereIn('estado', ['Activo', 'Vencido'])
            )
            ->where('devuelto', false)
            ->whereNull('equipo_padre_id')
            ->orderBy('created_at')
            ->get();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Timeline de préstamos — paginado y filtrable
    // ─────────────────────────────────────────────────────────────────────────

    #[Computed]
    public function prestamos()
    {
        $query = Prestamo::with([
            'analista',
            'empresa',
            'items' => fn ($q) => $q->with([
                'equipo.categoria',
                'devueltoPor',
                'hijos.equipo.categoria',
                'hijos.devueltoPor',
            ])->orderByRaw('COALESCE(equipo_padre_id, id)')->orderBy('equipo_padre_id')->orderBy('id'),
            'itemsDevueltos',
        ])
            ->where('usuario_id', $this->usuarioId);

        if ($this->filtroEstado !== 'todos') {
            $query->where('estado', ucfirst($this->filtroEstado));
        }

        if ($this->filtroAnio) {
            $query->whereYear('fecha_prestamo', $this->filtroAnio);
        }

        return $query->orderByDesc('fecha_prestamo')
            ->paginate($this->perPage);
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.prestamos.historial-usuario');
    }
}
