<?php

namespace App\Livewire;

use App\Models\AsignacionItem;
use App\Models\Auditoria;
use App\Models\Equipo;
use App\Models\Prestamo;
use App\Models\Traslado;
use App\Models\User;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();

        // ── Estadísticas principales ───────────────────────────────────────────
        $totalEquipos = Equipo::visiblePara($user)->where('activo', true)->count();

        $equiposAsignados = AsignacionItem::whereHas('asignacion', function ($q) use ($user) {
            $q->visiblePara($user)->whereIn('estado', ['Activa', 'Parcial']);
        })->where('devuelto', false)->count();

        $prestamosActivos  = Prestamo::visiblePara($user)->where('estado', 'Activo')->count();
        $prestamosVencidos = Prestamo::visiblePara($user)->where('estado', 'Vencido')->count();

        $trasladosMes = Traslado::visiblePara($user)
            ->whereMonth('fecha_traslado', now()->month)
            ->whereYear('fecha_traslado', now()->year)
            ->count();

        $usuariosActivos = User::visiblePara($user)->where('estado', 'Activo')->count();

        // ── Datos para gráficas ────────────────────────────────────────────────
        $equiposBase = Equipo::visiblePara($user)
            ->where('activo', true)
            ->with('estado:id,nombre', 'categoria:id,nombre')
            ->get(['id', 'estado_id', 'categoria_id']);

        $equiposPorEstado = $equiposBase
            ->groupBy(fn($e) => $e->estado?->nombre ?? 'Sin estado')
            ->map->count()
            ->sortDesc();

        $equiposPorCategoria = $equiposBase
            ->groupBy(fn($e) => $e->categoria?->nombre ?? 'Sin categoría')
            ->map->count()
            ->sortDesc();

        // ── Préstamos vencidos (tabla alerta) ──────────────────────────────────
        $prestamosVencidosLista = Prestamo::visiblePara($user)
            ->where('estado', 'Vencido')
            ->with(['usuario:id,name', 'areaDepartamento:id,nombre'])
            ->withCount(['items as items_pendientes' => fn($q) => $q->where('devuelto', false)])
            ->orderBy('fecha_devolucion_esperada')
            ->limit(6)
            ->get();

        // ── Garantías por vencer en 30 días ───────────────────────────────────
        $garantiasPorVencer = Equipo::visiblePara($user)
            ->where('activo', true)
            ->whereNotNull('fecha_garantia_fin')
            ->whereBetween('fecha_garantia_fin', [
                now()->toDateString(),
                now()->addDays(30)->toDateString(),
            ])
            ->with('categoria:id,nombre')
            ->orderBy('fecha_garantia_fin')
            ->limit(6)
            ->get();

        // ── Actividad reciente ─────────────────────────────────────────────────
        $actividadReciente = Auditoria::visiblePara($user)
            ->with('usuario:id,name,foto')
            ->orderByDesc('created_at')
            ->limit(12)
            ->get();

        // ── Últimos traslados ──────────────────────────────────────────────────
        $ultimosTraslados = Traslado::visiblePara($user)
            ->with(['ubicacionOrigen:id,nombre', 'ubicacionDestino:id,nombre'])
            ->withCount('items')
            ->orderByDesc('fecha_traslado')
            ->limit(5)
            ->get();

        return view('livewire.dashboard', [
            'totalEquipos'           => $totalEquipos,
            'equiposAsignados'       => $equiposAsignados,
            'prestamosActivos'       => $prestamosActivos,
            'prestamosVencidos'      => $prestamosVencidos,
            'trasladosMes'           => $trasladosMes,
            'usuariosActivos'        => $usuariosActivos,
            'equiposPorEstado'       => $equiposPorEstado,
            'equiposPorCategoria'    => $equiposPorCategoria,
            'prestamosVencidosLista' => $prestamosVencidosLista,
            'garantiasPorVencer'     => $garantiasPorVencer,
            'actividadReciente'      => $actividadReciente,
            'ultimosTraslados'       => $ultimosTraslados,
        ]);
    }
}
