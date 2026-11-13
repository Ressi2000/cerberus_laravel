<?php

namespace App\Livewire;

use App\Models\AsignacionItem;
use App\Models\Auditoria;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\MantenimientoComponente;
use App\Models\PiezaExtraida;
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

    /**
     * Taller y almacén: lo que conviene mirar cada día (reparaciones
     * abiertas, casos frenados por falta de piezas) y lo que ayuda a decidir
     * (cuánto tarda una reparación, qué equipos vuelven una y otra vez, qué
     * se gasta más del almacén). Respeta el filtro de empresa.
     */
    private function tallerYAlmacen(User $user): array
    {
        $casos = fn () => $this->porEmpresa(Mantenimiento::visiblePara($user));

        // Tiempo de reparación: correctivos cerrados en los últimos 90 días.
        $cerrados = $casos()->correctivos()->where('estado', 'Cerrado')
            ->whereNotNull('fecha_fin_real')
            ->where('fecha_fin_real', '>=', now()->subDays(90)->toDateString())
            ->get(['fecha_inicio', 'fecha_fin_real']);
        $diasPromedio = $cerrados->isEmpty() ? null
            : round($cerrados->avg(fn ($m) => $m->fecha_inicio->diffInDays($m->fecha_fin_real)), 1);

        // Casos frenados porque falta algo del almacén, el que más espera primero.
        $esperando = $casos()->abiertos()->where('esperando_componente', true)
            ->with(['equipo:id,codigo_interno', 'componentesPendientes.componente:id,nombre'])
            ->get()
            ->map(function (Mantenimiento $m) {
                $desde = $m->componentesPendientes->min('created_at');
                $m->setAttribute('dias_esperando', $desde ? (int) $desde->diffInDays(now()) : 0);

                return $m;
            })
            ->sortByDesc('dias_esperando')
            ->values();

        // Equipos que vuelven al taller: 2 o más reparaciones en 12 meses.
        $reincidentes = $casos()->correctivos()
            ->where('fecha_inicio', '>=', now()->subYear()->toDateString())
            ->selectRaw('equipo_id, count(*) as veces, max(fecha_inicio) as ultima')
            ->groupBy('equipo_id')
            ->having('veces', '>=', 2)
            ->orderByDesc('veces')
            ->limit(5)
            ->get();
        $equiposReincidentes = Equipo::with('categoria:id,nombre')
            ->whereIn('id', $reincidentes->pluck('equipo_id'))
            ->get(['id', 'codigo_interno', 'categoria_id'])
            ->keyBy('id');

        // Lo más usado del almacén en 90 días, por familia.
        $usado = MantenimientoComponente::where('estado', MantenimientoComponente::ESTADO_ENTREGADO)
            ->where('fecha_entrega', '>=', now()->subDays(90)->toDateString())
            ->whereHas('mantenimiento', fn ($q) => $this->porEmpresa($q->visiblePara($user)))
            ->with('componente.familia:id,nombre')
            ->get(['componente_id', 'cantidad_requerida'])
            ->groupBy(fn ($i) => $i->componente?->familia?->nombre ?? 'Sin familia')
            ->map(fn ($g) => (int) $g->sum('cantidad_requerida'))
            ->sortDesc()
            ->take(5);

        return [
            'reparacionesAbiertas' => $casos()->correctivos()->abiertos()->count(),
            'preventivosAbiertos'  => $casos()->preventivos()->abiertos()->count(),
            'casosEsperando'       => $esperando,
            'diasPromedio'         => $diasPromedio,
            'cerrados90'           => $cerrados->count(),
            'reincidentes'         => $reincidentes->map(fn ($r) => (object) [
                'equipo' => $equiposReincidentes[$r->equipo_id] ?? null,
                'veces'  => (int) $r->veces,
                'ultima' => Carbon::parse($r->ultima),
            ])->filter(fn ($r) => $r->equipo)->values(),
            'usadoAlmacen'         => $usado,
            'piezasEnAlmacen'      => $this->porEmpresa(PiezaExtraida::visiblePara($user)->enAlmacen())->count(),
            'solicitudesPendientes'=> MantenimientoComponente::pendientesVisiblesPara($user)
                ->when($this->empresaFiltroId, fn ($q) => $q->whereHas('mantenimiento', fn ($m) => $m->where('empresa_id', $this->empresaFiltroId)))
                ->count(),
        ];
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

        // ── Rotación de asignaciones recomendada ────────────────────────────────
        // NO es vida útil/obsolescencia del equipo: es cuánto tiempo lleva la
        // misma persona con el mismo equipo, comparado contra el periodo de
        // rotación configurado por categoría (Configuración → Categorías).
        $rotacionRecomendada = AsignacionItem::with(['equipo.categoria', 'asignacion.usuario', 'asignacion.empresa'])
            ->whereHas('asignacion', function ($q) use ($user) {
                $q->visiblePara($user)->where('estado', 'Activa')
                    ->when($this->empresaFiltroId, fn($qq) => $qq->where('empresa_id', $this->empresaFiltroId));
            })
            ->where('devuelto', false)
            ->whereNull('equipo_padre_id')
            ->whereHas('equipo.categoria', fn($q) => $q->whereNotNull('meses_rotacion_asignacion'))
            ->get()
            ->filter(fn($item) => $item->superaRotacionRecomendada())
            ->sortByDesc(fn($item) => $item->mesesConReceptor())
            ->values();

        $rotacionRecomendadaLista = $rotacionRecomendada->take(6);

        // ── Actividad reciente ─────────────────────────────────────────────────
        $actividadReciente = Auditoria::visiblePara($user)
            ->with('usuario:id,name,foto')
            ->orderByDesc('created_at')
            ->limit(12)
            ->get();

        // ── Mantenimiento preventivo: equipos que necesitan atención ahora ─────
        // El Administrador ve el calendario completo del cronograma (componente
        // aparte). El Analista ve algo más simple y accionable: los equipos que
        // YA tienen un caso preventivo abierto (Programado o En proceso).
        $equiposPendientesMantenimiento = $esAdmin ? collect() : $this->porEmpresa(
            Mantenimiento::visiblePara($user)->preventivos()->abiertos()
        )
            ->with('equipo.categoria:id,nombre')
            ->orderBy('proxima_fecha_programada')
            ->limit(6)
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

        // Despachar datos del donut al JS luego de cada render
        // (disparado DESPUÉS de que el DOM fue actualizado por Livewire)
        $this->dispatch('cerberus:chart-estados',
            labels: $equiposPorEstado->keys()->values()->all(),
            values: $equiposPorEstado->values()->all(),
        );

        $taller = $user->hasAnyRole(['Administrador', 'Analista']) ? $this->tallerYAlmacen($user) : null;

        return view('livewire.dashboard', [
            'taller'                 => $taller,
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
            'rotacionRecomendadaLista'  => $rotacionRecomendadaLista,
            'rotacionRecomendadaCount'  => $rotacionRecomendada->count(),
            'actividadReciente'      => $actividadReciente,
            'ultimosTraslados'       => $ultimosTraslados,
            'equiposPendientesMantenimiento' => $equiposPendientesMantenimiento,
            'empresas'               => $empresas,
            'empresaSeleccionada'    => $empresaSeleccionada,
            'esAdmin'                => $esAdmin,
        ]);
    }
}
