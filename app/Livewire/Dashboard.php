<?php

namespace App\Livewire;

use App\Models\AsignacionItem;
use App\Models\Auditoria;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\Prestamo;
use App\Models\Traslado;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Component;

class Dashboard extends Component
{
    public ?int $empresaFiltroId = null;

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Aplica el filtro de empresa (solo efectivo para el Administrador). */
    private function porEmpresa($query)
    {
        return $query->when(
            $this->empresaFiltroId,
            fn($q) => $q->where('empresa_id', $this->empresaFiltroId)
        );
    }

    public function render()
    {
        $user    = auth()->user();
        $esAdmin = $user->hasRole('Administrador');

        // Lista de empresas para el selector (solo admin la necesita)
        $empresas = $esAdmin
            ? Empresa::activas()->orderBy('nombre')->get(['id', 'nombre'])
            : collect();

        // Empresa seleccionada para mostrar en header
        $empresaSeleccionada = $this->empresaFiltroId
            ? Empresa::find($this->empresaFiltroId, ['id', 'nombre'])
            : null;

        // ── Estadísticas principales ───────────────────────────────────────────
        $totalEquipos = $this->porEmpresa(
            Equipo::visiblePara($user)->where('activo', true)
        )->count();

        $equiposAsignados = AsignacionItem::whereHas('asignacion', function ($q) use ($user) {
            $this->porEmpresa(
                $q->visiblePara($user)->whereIn('estado', ['Activa', 'Parcial'])
            );
        })->where('devuelto', false)->count();

        $prestamosActivos = $this->porEmpresa(
            Prestamo::visiblePara($user)->where('estado', 'Activo')
        )->count();

        $prestamosVencidos = $this->porEmpresa(
            Prestamo::visiblePara($user)->where('estado', 'Vencido')
        )->count();

        $trasladosMes = $this->porEmpresa(
            Traslado::visiblePara($user)
                ->whereMonth('fecha_traslado', now()->month)
                ->whereYear('fecha_traslado', now()->year)
        )->count();

        $usuariosActivos = $this->porEmpresa(
            User::visiblePara($user)->where('estado', 'Activo')
        )->count();

        // ── KPI: Edad promedio de equipos activos ──────────────────────────────
        $fechasAdquisicion = $this->porEmpresa(
            Equipo::visiblePara($user)
                ->where('activo', true)
                ->whereNotNull('fecha_adquisicion')
        )->pluck('fecha_adquisicion');

        $edadPromedioAnios = null;
        $edadPromedioDias  = null;
        if ($fechasAdquisicion->isNotEmpty()) {
            $totalDias        = $fechasAdquisicion->sum(fn($f) => now()->diffInDays(Carbon::parse($f)));
            $edadPromedioDias = $totalDias / $fechasAdquisicion->count();
            $edadPromedioAnios = round($edadPromedioDias / 365, 1);
        }

        // ── Datos para gráficas ────────────────────────────────────────────────
        $equiposBase = $this->porEmpresa(
            Equipo::visiblePara($user)
                ->where('activo', true)
                ->with('estado:id,nombre', 'categoria:id,nombre')
        )->get(['id', 'estado_id', 'categoria_id', 'empresa_id']);

        $equiposPorEstado = $equiposBase
            ->groupBy(fn($e) => $e->estado?->nombre ?? 'Sin estado')
            ->map->count()
            ->sortDesc();

        $equiposPorCategoria = $equiposBase
            ->groupBy(fn($e) => $e->categoria?->nombre ?? 'Sin categoría')
            ->map->count()
            ->sortDesc();

        // ── Préstamos vencidos (tabla alerta) ──────────────────────────────────
        $prestamosVencidosLista = $this->porEmpresa(
            Prestamo::visiblePara($user)->where('estado', 'Vencido')
        )
            ->with(['usuario:id,name', 'areaDepartamento:id,nombre'])
            ->withCount(['items as items_pendientes' => fn($q) => $q->where('devuelto', false)])
            ->orderBy('fecha_devolucion_esperada')
            ->limit(6)
            ->get();

        // ── Garantías por vencer en 30 días ───────────────────────────────────
        $garantiasPorVencer = $this->porEmpresa(
            Equipo::visiblePara($user)
                ->where('activo', true)
                ->whereNotNull('fecha_garantia_fin')
                ->whereBetween('fecha_garantia_fin', [
                    now()->toDateString(),
                    now()->addDays(30)->toDateString(),
                ])
        )
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
        $ultimosTraslados = $this->porEmpresa(
            Traslado::visiblePara($user)
        )
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
            'edadPromedioAnios'      => $edadPromedioAnios,
            'edadPromedioDias'       => $edadPromedioDias,
            'equiposPorEstado'       => $equiposPorEstado,
            'equiposPorCategoria'    => $equiposPorCategoria,
            'prestamosVencidosLista' => $prestamosVencidosLista,
            'garantiasPorVencer'     => $garantiasPorVencer,
            'actividadReciente'      => $actividadReciente,
            'ultimosTraslados'       => $ultimosTraslados,
            'empresas'               => $empresas,
            'empresaSeleccionada'    => $empresaSeleccionada,
            'esAdmin'                => $esAdmin,
        ]);
    }
}
