<?php

namespace App\Asistente\Herramientas;

use App\Asistente\Herramienta;
use App\Models\AsignacionItem;
use App\Models\Mantenimiento;
use App\Models\MovimientoComponente;
use App\Models\PiezaExtraidaMovimiento;
use App\Models\PrestamoItem;
use App\Models\TrasladoItem;
use App\Models\User;

class HistorialEquipo extends Herramienta
{
    public function nombre(): string
    {
        return 'historial_equipo';
    }

    public function descripcion(): string
    {
        return 'Historial de UN equipo en orden cronológico (del más reciente al más antiguo): casos de mantenimiento '
            . '(preventivos y reparaciones, con falla y diagnóstico), asignaciones y devoluciones, préstamos, '
            . 'traslados de ubicación y piezas que se le sacaron o instalaron. Úsala para informes del equipo o '
            . 'preguntas como "¿cuántas veces se ha reparado…?" o "¿quién lo tuvo antes?".';
    }

    public function parametros(): array
    {
        return [
            'codigo' => ['type' => 'string', 'description' => 'Código interno del equipo.'],
            'tipo'   => ['type' => 'string', 'enum' => ['todo', 'mantenimiento', 'asignaciones', 'prestamos', 'traslados', 'piezas'], 'description' => 'Qué parte del historial (por defecto todo).'],
            'limite' => ['type' => 'integer', 'description' => 'Máximo de eventos (por defecto 50, máximo 200).'],
        ];
    }

    public function requeridos(): array
    {
        return ['codigo'];
    }

    public function ejecutar(User $actor, array $args): array
    {
        $equipo = $this->encontrarEquipo($actor, $args['codigo']);
        $tipo = $args['tipo'] ?? 'todo';
        $quiere = fn (string $t) => $tipo === 'todo' || $tipo === $t;
        $eventos = collect();

        if ($quiere('mantenimiento')) {
            $casos = Mantenimiento::visiblePara($actor)->where('equipo_id', $equipo->id)->with('responsable')->get();
            foreach ($casos as $c) {
                $eventos->push([
                    'fecha'   => $c->fecha_inicio ?? $c->created_at,
                    'tipo'    => $c->esCorrectivo() ? 'Reparación' : 'Mantenimiento preventivo',
                    'detalle' => trim(implode(' · ', array_filter([
                        "Caso #{$c->id} ({$c->estado})",
                        $c->falla_reportada ? "Falla: {$c->falla_reportada}" : null,
                        $c->diagnostico ? "Diagnóstico: {$c->diagnostico}" : null,
                        $c->descripcion && ! $c->falla_reportada ? $c->descripcion : null,
                        $c->responsable ? "Responsable: {$c->responsable->name}" : null,
                        $c->fecha_fin_real ? 'Terminado el ' . $this->fecha($c->fecha_fin_real) : null,
                    ]))),
                    'url'     => route('admin.mantenimientos.show', $c),
                ]);
            }
            // Repuestos a granel usados en sus casos (las piezas con serial salen en "piezas").
            $granel = MovimientoComponente::with('componente')
                ->where('tipo', 'Salida')
                ->whereHas('mantenimiento', fn ($m) => $m->visiblePara($actor)->where('equipo_id', $equipo->id))
                ->get();
            // Si la unidad usada tiene serial, ya aparece como "Instalada" con su código.
            $conSerial = PiezaExtraidaMovimiento::where('tipo', PiezaExtraidaMovimiento::TIPO_INSTALACION)
                ->where('equipo_relacionado_id', $equipo->id)->whereNotNull('mantenimiento_id')
                ->with('pieza:id,componente_almacen_id')->get()
                ->map(fn ($mv) => $mv->mantenimiento_id . '-' . $mv->pieza?->componente_almacen_id)->flip();
            foreach ($granel->reject(fn ($m) => isset($conSerial[$m->mantenimiento_id . '-' . $m->componente_id])) as $m) {
                $eventos->push([
                    'fecha'   => $m->created_at,
                    'tipo'    => 'Repuesto usado',
                    'detalle' => "{$m->cantidad} × " . ($m->componente?->nombre ?? 'repuesto') . " en el caso #{$m->mantenimiento_id}",
                ]);
            }
        }

        if ($quiere('asignaciones')) {
            $items = AsignacionItem::where('equipo_id', $equipo->id)
                ->whereHas('asignacion', fn ($a) => $a->visiblePara($actor))
                ->with('asignacion.usuario', 'asignacion.areaDepartamento', 'asignacion.areaEmpresa')->get();
            foreach ($items as $i) {
                $eventos->push([
                    'fecha'   => $i->asignacion->fecha_asignacion ?? $i->created_at,
                    'tipo'    => 'Asignación',
                    'detalle' => 'Asignado a ' . $i->asignacion->receptorNombre(),
                ]);
                if ($i->devuelto) {
                    $eventos->push([
                        'fecha'   => $i->fecha_devolucion ?? $i->updated_at,
                        'tipo'    => 'Devolución',
                        'detalle' => 'Devuelto por ' . $i->asignacion->receptorNombre() . ($i->observaciones_devolucion ? " ({$i->observaciones_devolucion})" : ''),
                    ]);
                }
            }
        }

        if ($quiere('prestamos')) {
            $items = PrestamoItem::where('equipo_id', $equipo->id)
                ->whereHas('prestamo', fn ($p) => $p->visiblePara($actor))
                ->with('prestamo.usuario', 'prestamo.areaDepartamento', 'prestamo.areaEmpresa')->get();
            foreach ($items as $i) {
                $eventos->push([
                    'fecha'   => $i->prestamo->fecha_prestamo ?? $i->created_at,
                    'tipo'    => 'Préstamo',
                    'detalle' => 'Prestado a ' . $i->prestamo->receptorNombre()
                        . ($i->prestamo->fecha_devolucion_esperada ? ' hasta el ' . $this->fecha($i->prestamo->fecha_devolucion_esperada) : ''),
                ]);
                if ($i->devuelto) {
                    $eventos->push([
                        'fecha'   => $i->fecha_devolucion ?? $i->updated_at,
                        'tipo'    => 'Devolución de préstamo',
                        'detalle' => 'Devuelto por ' . $i->prestamo->receptorNombre(),
                    ]);
                }
            }
        }

        if ($quiere('traslados')) {
            $items = TrasladoItem::where('equipo_id', $equipo->id)
                ->whereHas('traslado', fn ($t) => $t->visiblePara($actor))
                ->with('traslado.ubicacionOrigen', 'traslado.ubicacionDestino')->get();
            foreach ($items as $i) {
                $t = $i->traslado;
                $eventos->push([
                    'fecha'   => $t->fecha_traslado ?? $t->created_at,
                    'tipo'    => 'Traslado',
                    'detalle' => 'De ' . ($t->ubicacionOrigen?->nombre ?? '—') . ' a ' . ($t->ubicacionDestino?->nombre ?? '—')
                        . ($t->motivo ? " ({$t->motivo})" : '') . ($t->estado === 'revertido' ? ' — revertido' : ''),
                    'url'     => route('admin.traslados.show', $t),
                ]);
            }
        }

        if ($quiere('piezas')) {
            $movs = PiezaExtraidaMovimiento::where('equipo_relacionado_id', $equipo->id)
                ->whereIn('tipo', [PiezaExtraidaMovimiento::TIPO_EXTRACCION, PiezaExtraidaMovimiento::TIPO_INSTALACION, PiezaExtraidaMovimiento::TIPO_DESINSTALACION])
                ->whereHas('pieza', fn ($p) => $p->visiblePara($actor))
                ->with('pieza.componenteAlmacen')->get();
            foreach ($movs as $m) {
                $eventos->push([
                    'fecha'   => $m->created_at,
                    'tipo'    => $m->labelTipo(),
                    'detalle' => ($m->pieza->componenteAlmacen?->nombre ?? $m->pieza->descripcion()) . ' · ' . $m->pieza->codigo()
                        . ($m->mantenimiento_id ? " (caso #{$m->mantenimiento_id})" : ''),
                ]);
            }
        }

        $ordenados = $eventos->sortByDesc(fn ($e) => \Illuminate\Support\Carbon::parse($e['fecha'])->timestamp)->values();
        $limite = $this->limite($args, 50, 200);

        return [
            'equipo'   => $equipo->codigo_interno,
            'url'      => route('admin.equipos.show', $equipo),
            'total'    => $ordenados->count(),
            'resumen'  => $ordenados->countBy('tipo')->all(),
            'eventos'  => $ordenados->take($limite)->map(fn ($e) => ['fecha' => $this->fecha($e['fecha'])] + $e)->all(),
        ];
    }
}
