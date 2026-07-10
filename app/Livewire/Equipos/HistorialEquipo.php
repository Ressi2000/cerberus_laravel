<?php

namespace App\Livewire\Equipos;

use App\Models\Asignacion;
use App\Models\AsignacionItem;
use App\Models\Auditoria;
use App\Models\AtributoEquipo;
use App\Models\Equipo;
use App\Models\Prestamo;
use App\Models\PrestamoItem;
use App\Models\TrasladoItem;
use App\Services\AuditoriaResolverService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Timeline consolidado del historial de un equipo.
 *
 * Mezcla, en una sola línea de tiempo ordenada por fecha:
 *  - Cambios en características técnicas (EAV)
 *  - Asignaciones (entrega y devolución)
 *  - Traslados
 *  - Préstamos (entrega y devolución)
 *  - Cambios en características estáticas del equipo (estado, ubicación,
 *    empresa, categoría, activo/baja) vía la tabla de auditoría general.
 *
 * Cada fuente vive en una tabla distinta, así que no se puede paginar con
 * un único query: se construyen todos los eventos del equipo en memoria,
 * se ordenan, y se pagina manualmente sobre la colección resultante. Para
 * el volumen de eventos de un equipo individual esto es perfectamente
 * razonable y evita una unión SQL frágil entre 5 tablas heterogéneas.
 */
class HistorialEquipo extends Component
{
    use WithPagination;

    public Equipo $equipo;

    public string $atributo_id = '';
    public string $tipo        = ''; // '' = todos | tecnico | asignacion | traslado | prestamo | estado
    public string $fecha_desde = '';
    public string $fecha_hasta = '';

    protected int $perPage = 20;

    public function mount(Equipo $equipo): void
    {
        $this->authorize('view', $equipo);
        $this->equipo = $equipo->load(['categoria', 'estado', 'ubicacion', 'empresa']);
    }

    public function updated($property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['atributo_id', 'tipo', 'fecha_desde', 'fecha_hasta']);
        $this->resetPage();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Construcción de eventos por fuente
    // ─────────────────────────────────────────────────────────────────────────

    /** Cambios de atributos EAV (características técnicas). */
    protected function eventosTecnicos(): Collection
    {
        return $this->equipo->atributosHistorico()
            ->with(['atributo', 'usuario'])
            ->when($this->atributo_id, fn($q) => $q->where('atributo_id', $this->atributo_id))
            ->get()
            ->map(fn($v) => [
                'fecha'   => $v->created_at,
                'tipo'    => 'tecnico',
                'icono'   => 'memory',
                'color'   => 'indigo',
                'titulo'  => $v->atributo?->nombre ?? 'Atributo eliminado',
                'detalle' => ($v->atributo?->tipo === 'boolean')
                    ? ($v->valor ? 'Sí' : 'No')
                    : (string) $v->valor,
                'estado'  => $v->es_actual ? 'Vigente' : 'Histórico',
                'usuario' => $v->usuario?->name ?? 'Sistema',
            ]);
    }

    /**
     * Cambios en atributos tipo 'group' (multi-instancia: RAM, discos, etc.).
     * Cada guardado que modifica el conjunto genera una "foto" completa nueva
     * (mismas reglas de es_actual que los atributos simples), así que se
     * agrupan las instancias creadas juntas (mismo atributo + mismo instante)
     * en un solo evento de línea de tiempo.
     */
    protected function eventosGrupos(): Collection
    {
        return $this->equipo->grupoInstanciasHistorico()
            ->with(['atributo', 'usuario'])
            ->when($this->atributo_id, fn($q) => $q->where('atributo_id', $this->atributo_id))
            ->get()
            ->groupBy(fn($i) => $i->atributo_id . '|' . $i->created_at?->format('Y-m-d H:i:s'))
            ->map(function ($instancias) {
                $primera  = $instancias->first();
                $atributo = $primera->atributo;

                $detalle = $instancias
                    ->map(fn($i) => collect($i->valores)
                        ->map(function ($valor, $subId) use ($atributo) {
                            $sub    = collect($atributo?->sub_campos ?? [])->firstWhere('id', $subId);
                            $nombre = $sub['nombre'] ?? $subId;
                            return "{$nombre}: {$valor}";
                        })
                        ->implode(', ')
                    )
                    ->implode(' · ');

                return [
                    'fecha'   => $primera->created_at,
                    'tipo'    => 'tecnico',
                    'icono'   => 'memory',
                    'color'   => 'indigo',
                    'titulo'  => $atributo?->nombre ?? 'Atributo eliminado',
                    'detalle' => $detalle ?: null,
                    'estado'  => $primera->es_actual ? 'Vigente' : 'Histórico',
                    'usuario' => $primera->usuario?->name ?? 'Sistema',
                ];
            })
            ->values();
    }

    /** Asignaciones: un evento de entrega y, si aplica, uno de devolución. */
    protected function eventosAsignaciones(): Collection
    {
        $items = AsignacionItem::with(['asignacion.usuario', 'asignacion.areaDepartamento', 'asignacion.areaEmpresa', 'asignacion.analista', 'devueltoPor'])
            ->where('equipo_id', $this->equipo->id)
            ->get();

        $eventos = collect();

        foreach ($items as $item) {
            $asignacion = $item->asignacion;
            if (! $asignacion) continue;

            $eventos->push([
                'fecha'   => $asignacion->fecha_asignacion ?? $item->created_at,
                'tipo'    => 'asignacion',
                'icono'   => 'assignment_ind',
                'color'   => 'blue',
                'titulo'  => 'Asignado a ' . $asignacion->receptorNombre(),
                'detalle' => $asignacion->observaciones ?: null,
                'estado'  => null,
                'usuario' => $asignacion->analista?->name ?? 'Sistema',
            ]);

            if ($item->devuelto) {
                $eventos->push([
                    'fecha'   => $item->fecha_devolucion ?? $item->updated_at,
                    'tipo'    => 'asignacion',
                    'icono'   => 'assignment_return',
                    'color'   => 'sky',
                    'titulo'  => 'Devolución de asignación — ' . $asignacion->receptorNombre(),
                    'detalle' => $item->observaciones_devolucion ?: null,
                    'estado'  => null,
                    'usuario' => $item->devueltoPor?->name ?? 'Sistema',
                ]);
            }
        }

        return $eventos;
    }

    /** Préstamos: entrega y devolución, igual que asignaciones. */
    protected function eventosPrestamos(): Collection
    {
        $items = PrestamoItem::with(['prestamo.usuario', 'prestamo.areaDepartamento', 'prestamo.areaEmpresa', 'prestamo.analista', 'devueltoPor'])
            ->where('equipo_id', $this->equipo->id)
            ->get();

        $eventos = collect();

        foreach ($items as $item) {
            $prestamo = $item->prestamo;
            if (! $prestamo) continue;

            $eventos->push([
                'fecha'   => $prestamo->fecha_prestamo ?? $item->created_at,
                'tipo'    => 'prestamo',
                'icono'   => 'handshake',
                'color'   => 'amber',
                'titulo'  => 'En préstamo a ' . $prestamo->receptorNombre(),
                'detalle' => $prestamo->fecha_devolucion_esperada
                    ? 'Devolución esperada: ' . $prestamo->fecha_devolucion_esperada->format('d/m/Y')
                    : null,
                'estado'  => null,
                'usuario' => $prestamo->analista?->name ?? 'Sistema',
            ]);

            if ($item->devuelto) {
                $eventos->push([
                    'fecha'   => $item->fecha_devolucion ?? $item->updated_at,
                    'tipo'    => 'prestamo',
                    'icono'   => 'undo',
                    'color'   => 'yellow',
                    'titulo'  => 'Devolución de préstamo — ' . $prestamo->receptorNombre(),
                    'detalle' => $item->observaciones_devolucion ?: null,
                    'estado'  => null,
                    'usuario' => $item->devueltoPor?->name ?? 'Sistema',
                ]);
            }
        }

        return $eventos;
    }

    /** Traslados de ubicación física. */
    protected function eventosTraslados(): Collection
    {
        return TrasladoItem::with([
            'traslado.ubicacionOrigen',
            'traslado.ubicacionDestino',
            'traslado.recibe',
            'traslado.realizadoPor',
        ])
            ->where('equipo_id', $this->equipo->id)
            ->get()
            ->map(fn($item) => [
                'fecha'   => $item->traslado?->fecha_traslado ?? $item->created_at,
                'tipo'    => 'traslado',
                'icono'   => 'local_shipping',
                'color'   => 'slate',
                'titulo'  => ($item->traslado?->ubicacionOrigen?->nombre ?? '—')
                    . ' → ' . ($item->traslado?->ubicacionDestino?->nombre ?? '—'),
                'detalle' => $item->traslado?->numero ? "Traslado #{$item->traslado->numero}" : null,
                'estado'  => null,
                'usuario' => $item->traslado?->realizadoPor?->name ?? 'Sistema',
            ]);
    }

    /** Cambios en características estáticas del equipo (vía auditoría general). */
    protected function eventosEstaticos(AuditoriaResolverService $resolver): Collection
    {
        return Auditoria::where('tabla', 'equipos')
            ->where('registro_id', $this->equipo->id)
            ->with('usuario')
            ->get()
            ->map(function ($a) use ($resolver) {
                if ($a->accion === 'CREAR') {
                    return [
                        'fecha'   => $a->created_at,
                        'tipo'    => 'estado',
                        'icono'   => 'add_circle',
                        'color'   => 'green',
                        'titulo'  => 'Equipo registrado en el sistema',
                        'detalle' => null,
                        'estado'  => null,
                        'usuario' => $a->usuario?->name ?? 'Sistema',
                    ];
                }

                $previos = json_decode($a->valores_previos, true);
                $nuevos  = json_decode($a->valores_nuevos, true);
                $cambios = $resolver->cambiosLegibles('equipos', $previos, $nuevos);

                if (empty($cambios)) return null;

                $detalle = collect($cambios)
                    ->map(fn($c) => "{$c['etiqueta']}: {$c['antes']} → {$c['despues']}")
                    ->implode(' · ');

                return [
                    'fecha'   => $a->created_at,
                    'tipo'    => 'estado',
                    'icono'   => $a->accion === 'INACTIVAR' ? 'block' : ($a->accion === 'REACTIVAR' ? 'check_circle' : 'rule'),
                    'color'   => $a->accion === 'INACTIVAR' ? 'red' : ($a->accion === 'REACTIVAR' ? 'green' : 'gray'),
                    'titulo'  => 'Cambio en datos del equipo',
                    'detalle' => $detalle,
                    'estado'  => null,
                    'usuario' => $a->usuario?->name ?? 'Sistema',
                ];
            })
            ->filter()
            ->values();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Render
    // ─────────────────────────────────────────────────────────────────────────

    public function render(AuditoriaResolverService $resolver)
    {
        $atributos = AtributoEquipo::where('categoria_id', $this->equipo->categoria_id)
            ->orderBy('orden')
            ->pluck('nombre', 'id');

        $fuentes = [
            'tecnico'    => fn() => $this->eventosTecnicos()->merge($this->eventosGrupos()),
            'asignacion' => fn() => $this->eventosAsignaciones(),
            'traslado'   => fn() => $this->eventosTraslados(),
            'prestamo'   => fn() => $this->eventosPrestamos(),
            'estado'     => fn() => $this->eventosEstaticos($resolver),
        ];

        $eventos = collect();
        foreach ($fuentes as $clave => $builder) {
            if ($this->tipo && $this->tipo !== $clave) continue;
            $eventos = $eventos->merge($builder());
        }

        $eventos = $eventos
            ->when($this->fecha_desde, fn($c) => $c->filter(
                fn($e) => $e['fecha'] && $e['fecha']->toDateString() >= $this->fecha_desde
            ))
            ->when($this->fecha_hasta, fn($c) => $c->filter(
                fn($e) => $e['fecha'] && $e['fecha']->toDateString() <= $this->fecha_hasta
            ))
            ->sortByDesc(fn($e) => $e['fecha'])
            ->values();

        $page    = $this->getPage();
        $items   = $eventos->forPage($page, $this->perPage)->values();
        $eventos = new LengthAwarePaginator(
            $items,
            $eventos->count(),
            $this->perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('livewire.equipos.historial-equipo', [
            'eventos'   => $eventos,
            'atributos' => $atributos,
        ]);
    }
}
