<?php

namespace App\Services;

use App\Models\Asignacion;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * PlanillaService
 *
 * Genera los tres tipos de planillas PDF del módulo de asignaciones.
 * Todas se generan on-demand (no se almacenan en disco).
 *
 * Planilla de Asignación → lista equipos entregados en una asignación
 * Planilla de Devolución → lista equipos devueltos de una asignación
 * Planilla de Egreso     → todos los equipos del usuario (activos y devueltos)
 *                          para el proceso de offboarding / salida de RRHH
 *
 * Uso:
 *   app(PlanillaService::class)->asignacion($asignacion)->download('archivo.pdf');
 *   app(PlanillaService::class)->egreso($usuario)->stream();
 */
class PlanillaService
{
    /**
     * Planilla de Asignación (DC-ST-FO-08)
     * Muestra todos los equipos entregados en la asignación,
     * con sus atributos EAV (solo visible_en_tabla = true) y periféricos.
     */
    public function asignacion(Asignacion $asignacion): \Barryvdh\DomPDF\PDF
    {
        $asignacion->load([
            'empresa',
            'usuario.cargo',
            'usuario.departamento',
            'usuario.empresaNomina',
            'usuario.ubicacion',
            'usuario.jefe',
            'analista',
            // Solo items principales con sus hijos y atributos EAV
            'items' => fn($q) => $q->whereNull('equipo_padre_id')->with([
                'equipo.categoria',
                'equipo.atributosActuales' => fn($q) => $q->whereHas('atributo', fn($a) => $a->where('visible_en_tabla', true)),
                'equipo.atributosActuales.atributo',
                'hijos.equipo.categoria',
                'hijos.equipo.atributosActuales.atributo',
            ]),
        ]);

        $pdf = Pdf::loadView('planillas.asignacion', [
            'asignacion' => $asignacion,
            'fecha'      => now()->format('d/m/Y'),
        ]);

        return $pdf->setPaper('letter', 'portrait');
    }

    /**
     * Planilla de Devolución
     * Muestra solo los equipos devueltos de la asignación,
     * con fecha de devolución y quién la registró.
     */
    public function devolucion(Asignacion $asignacion): \Barryvdh\DomPDF\PDF
    {
        $asignacion->load([
            'empresa',
            'usuario.cargo',
            'usuario.departamento',
            'usuario.empresaNomina',
            'usuario.ubicacion',
            'usuario.jefe',
            'analista',
            'itemsDevueltos' => fn($q) => $q->whereNull('equipo_padre_id')->with([
                'equipo.categoria',
                'equipo.atributosActuales' => fn($q) => $q->whereHas('atributo', fn($a) => $a->where('visible_en_tabla', true)),
                'equipo.atributosActuales.atributo',
                'hijos.equipo.categoria',
                'devueltoPor',
            ]),
        ]);

        $pdf = Pdf::loadView('planillas.devolucion', [
            'asignacion' => $asignacion,
            'fecha'      => now()->format('d/m/Y'),
        ]);

        return $pdf->setPaper('letter', 'portrait');
    }

    /**
     * Planilla de Egreso (DC-ST-FO-09)
     * Vista completa de todos los equipos del usuario para offboarding.
     * Muestra: asignados, recibidos (devueltos), pendientes.
     * Al generar este PDF NO se ejecuta ninguna devolución — es solo el documento.
     * La devolución real se ejecuta desde el módulo de devolución.
     */
    public function egreso(User $usuario): \Barryvdh\DomPDF\PDF
    {
        $usuario->load([
            'cargo',
            'departamento',
            'empresaNomina',
            'ubicacion',
            'jefe',
        ]);

        // Todos los items del usuario (activos y devueltos) de todas sus asignaciones
        $todosLosItems = \App\Models\AsignacionItem::with([
            'equipo.categoria',
            'asignacion',
        ])
            ->whereHas('asignacion', fn($q) => $q->where('usuario_id', $usuario->id))
            ->orderBy('created_at')
            ->get();

        $asignados   = $todosLosItems->filter(fn($i) => !$i->devuelto);
        $recibidos   = $todosLosItems->filter(fn($i) => $i->devuelto);
        $pendientes  = $asignados; // equipos aún sin devolver = pendientes de recepción

        $pdf = Pdf::loadView('planillas.egreso', [
            'usuario'    => $usuario,
            'asignados'  => $asignados,
            'recibidos'  => $recibidos,
            'pendientes' => $pendientes,
            'fecha'      => now()->format('d/m/Y'),
        ]);

        return $pdf->setPaper('letter', 'portrait');
    }
}