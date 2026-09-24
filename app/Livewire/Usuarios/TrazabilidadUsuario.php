<?php

namespace App\Livewire\Usuarios;

use App\Models\AsignacionItem;
use App\Models\Auditoria;
use App\Models\PrestamoItem;
use App\Models\User;
use App\Services\AuditoriaResolverService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Trazabilidad completa de un usuario.
 *
 * Análoga a Equipos\HistorialEquipo pero desde la perspectiva del usuario:
 * en vez de "qué le pasó a este equipo", responde "qué equipos ha tenido
 * esta persona y qué pasó con ellos" (asignaciones, préstamos, cambios en
 * sus propios datos) en una sola línea de tiempo ordenada por fecha.
 *
 * No reemplaza los historiales específicos de Asignaciones, Préstamos ni
 * Usuarios (cada uno sigue existiendo para su contexto propio) — esta es
 * la vista consolidada que junta todo para quien necesita ver el cuadro
 * completo de un usuario de una sola vez.
 *
 * Diseñada para aceptar una fuente más el día que exista el módulo de
 * Mantenimiento y Reparación: como esos registros se vinculan a la
 * asignación activa del equipo, basta con agregar un eventosMantenimientos()
 * que filtre por whereHas('asignacion', fn($q) => $q->where('usuario_id', ...))
 * y sumarlo al array $fuentes de render().
 */
class TrazabilidadUsuario extends Component
{
    use WithPagination;

    public User $usuario;

    public string $tipo        = ''; // '' = todos | asignacion | prestamo | datos
    public string $fecha_desde = '';
    public string $fecha_hasta = '';

    protected int $perPage = 20;

    public function mount(User $usuario): void
    {
        $this->authorize('view', $usuario);
        $this->usuario = $usuario->load(['empresaNomina', 'ubicacion', 'departamento', 'cargo', 'jefe']);
    }

    public function updated($property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['tipo', 'fecha_desde', 'fecha_hasta']);
        $this->resetPage();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Equipos activos ahora mismo (sección fija, siempre visible, sin paginar)
    // ─────────────────────────────────────────────────────────────────────────

    protected function equiposActivosAsignados(): Collection
    {
        return AsignacionItem::with(['equipo.categoria', 'asignacion.empresa'])
            ->whereHas('asignacion', fn($q) =>
                $q->where('usuario_id', $this->usuario->id)->where('estado', 'Activa')
            )
            ->where('devuelto', false)
            ->whereNull('equipo_padre_id')
            ->get();
    }

    protected function equiposActivosPrestados(): Collection
    {
        return PrestamoItem::with(['equipo.categoria', 'prestamo.empresa'])
            ->whereHas('prestamo', fn($q) =>
                $q->where('usuario_id', $this->usuario->id)->whereIn('estado', ['Activo', 'Vencido'])
            )
            ->where('devuelto', false)
            ->whereNull('equipo_padre_id')
            ->get();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Construcción de eventos por fuente
    // ─────────────────────────────────────────────────────────────────────────

    /** Asignaciones: un evento de entrega y, si aplica, uno de devolución, por cada equipo. */
    protected function eventosAsignaciones(): Collection
    {
        $items = AsignacionItem::with(['asignacion.analista', 'devueltoPor', 'equipo.categoria'])
            ->whereHas('asignacion', fn($q) => $q->where('usuario_id', $this->usuario->id))
            ->get();

        $eventos = collect();

        foreach ($items as $item) {
            $asignacion = $item->asignacion;
            if (! $asignacion || ! $item->equipo) continue;

            $nombreEquipo = $item->equipo->codigo_interno ?? $item->equipo->nombre ?? 'Equipo';

            $eventos->push([
                'fecha'   => $asignacion->fecha_asignacion ?? $item->created_at,
                'tipo'    => 'asignacion',
                'icono'   => 'assignment_ind',
                'color'   => 'blue',
                'titulo'  => "Asignado: {$nombreEquipo}",
                'detalle' => $item->equipo->categoria?->nombre,
                'estado'  => null,
                'usuario' => $asignacion->analista?->name ?? 'Sistema',
            ]);

            if ($item->devuelto) {
                $eventos->push([
                    'fecha'   => $item->fecha_devolucion ?? $item->updated_at,
                    'tipo'    => 'asignacion',
                    'icono'   => 'assignment_return',
                    'color'   => 'sky',
                    'titulo'  => "Devuelto: {$nombreEquipo}",
                    'detalle' => $item->observaciones_devolucion,
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
        $items = PrestamoItem::with(['prestamo.analista', 'devueltoPor', 'equipo.categoria'])
            ->whereHas('prestamo', fn($q) => $q->where('usuario_id', $this->usuario->id))
            ->get();

        $eventos = collect();

        foreach ($items as $item) {
            $prestamo = $item->prestamo;
            if (! $prestamo || ! $item->equipo) continue;

            $nombreEquipo = $item->equipo->codigo_interno ?? $item->equipo->nombre ?? 'Equipo';

            $eventos->push([
                'fecha'   => $prestamo->fecha_prestamo ?? $item->created_at,
                'tipo'    => 'prestamo',
                'icono'   => 'handshake',
                'color'   => 'amber',
                'titulo'  => "En préstamo: {$nombreEquipo}",
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
                    'titulo'  => "Devuelto (préstamo): {$nombreEquipo}",
                    'detalle' => $item->observaciones_devolucion,
                    'estado'  => null,
                    'usuario' => $item->devueltoPor?->name ?? 'Sistema',
                ]);
            }
        }

        return $eventos;
    }

    /** Cambios en los datos propios del usuario (vía auditoría general). */
    protected function eventosDatos(AuditoriaResolverService $resolver): Collection
    {
        return Auditoria::where('tabla', 'users')
            ->where('registro_id', $this->usuario->id)
            ->with('usuario')
            ->get()
            ->map(function ($a) use ($resolver) {
                if ($a->accion === 'CREAR') {
                    return [
                        'fecha'   => $a->created_at,
                        'tipo'    => 'datos',
                        'icono'   => 'person_add',
                        'color'   => 'green',
                        'titulo'  => 'Usuario registrado en el sistema',
                        'detalle' => null,
                        'estado'  => null,
                        'usuario' => $a->usuario?->name ?? 'Sistema',
                    ];
                }

                $previos = json_decode($a->valores_previos, true);
                $nuevos  = json_decode($a->valores_nuevos, true);
                $cambios = $resolver->cambiosLegibles('users', $previos, $nuevos);

                if (empty($cambios)) return null;

                $detalle = collect($cambios)
                    ->map(fn($c) => "{$c['etiqueta']}: {$c['antes']} → {$c['despues']}")
                    ->implode(' · ');

                return [
                    'fecha'   => $a->created_at,
                    'tipo'    => 'datos',
                    'icono'   => $a->accion === 'INACTIVAR' ? 'block' : ($a->accion === 'REACTIVAR' ? 'check_circle' : 'rule'),
                    'color'   => $a->accion === 'INACTIVAR' ? 'red' : ($a->accion === 'REACTIVAR' ? 'green' : 'gray'),
                    'titulo'  => 'Cambio en datos del usuario',
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
        $fuentes = [
            'asignacion' => fn() => $this->eventosAsignaciones(),
            'prestamo'   => fn() => $this->eventosPrestamos(),
            'datos'      => fn() => $this->eventosDatos($resolver),
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
        $eventosPaginados = new LengthAwarePaginator(
            $items,
            $eventos->count(),
            $this->perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('livewire.usuarios.trazabilidad-usuario', [
            'eventos'          => $eventosPaginados,
            'equiposAsignados' => $this->equiposActivosAsignados(),
            'equiposPrestados' => $this->equiposActivosPrestados(),
        ]);
    }
}
