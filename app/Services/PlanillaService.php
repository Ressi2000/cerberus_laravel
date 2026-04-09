<?php

namespace App\Services;

use App\Models\Asignacion;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * PlanillaService
 *
 * Genera los tres tipos de planillas PDF del módulo de asignaciones.
 * Todas se generan on-demand (no se almacenan en disco).
 *
 * v2 — Cambios respecto a la versión anterior:
 *   - asignacion() y devolucion() cargan relaciones de área común
 *     (areaEmpresa, areaDepartamento, areaResponsable) además de las personales.
 *   - La detección usuario/área ocurre en la vista via $asignacion->tipoReceptor().
 *   - egreso() sin cambios — sigue siendo exclusivo para usuarios.
 */
class PlanillaService
{
    /**
     * Planilla de Asignación (DC-ST-FO-08)
     * Soporta receptor personal y área común.
     */
    public function asignacion(Asignacion $asignacion): \Barryvdh\DomPDF\PDF
    {
        $asignacion->load([
            'empresa',
            'analista',

            // ── Receptor personal ──────────────────────────────────────────
            'usuario.cargo',
            'usuario.departamento',
            'usuario.empresaNomina',
            'usuario.ubicacion',
            'usuario.jefe.cargo',

            // ── Receptor área común ────────────────────────────────────────
            'areaEmpresa',
            'areaDepartamento',
            'areaResponsable.cargo',
            'areaResponsable.departamento',

            // ── Items: solo principales con sus hijos y atributos EAV ──────
            'items' => fn ($q) => $q->whereNull('equipo_padre_id')->with([
                'equipo.categoria',
                'equipo.atributosActuales.atributo',
                'hijos.equipo.categoria',
                'hijos.equipo.atributosActuales.atributo',
            ]),

            // ── Items devueltos (para la vista saber el estado) ───────────
            'itemsDevueltos',
        ]);

        $pdf = Pdf::loadView('planillas.asignacion', [
            'asignacion' => $asignacion,
            'fecha'      => now()->format('d/m/Y'),
        ]);

        return $pdf->setPaper('letter', 'portrait');
    }

    /**
     * Planilla de Devolución (DC-ST-FO-10)
     * Soporta receptor personal y área común.
     * Incluye sección de equipos pendientes de devolución.
     */
    public function devolucion(Asignacion $asignacion): \Barryvdh\DomPDF\PDF
    {
        $asignacion->load([
            'empresa',
            'analista',

            // ── Receptor personal ──────────────────────────────────────────
            'usuario.cargo',
            'usuario.departamento',
            'usuario.empresaNomina',
            'usuario.ubicacion',
            'usuario.jefe.cargo',

            // ── Receptor área común ────────────────────────────────────────
            'areaEmpresa',
            'areaDepartamento',
            'areaResponsable.cargo',

            // ── Items devueltos — SIN whereNull: incluye periféricos devueltos ──
            // El whereNull original excluía periféricos devueltos individualmente.
            // Ahora cargamos todos y la vista los presenta con jerarquía visual.
            'itemsDevueltos' => fn ($q) => $q->with([
                'equipo.categoria',
                'equipo.atributosActuales.atributo',
                'devueltoPor',
                'padre.equipo',   // para saber a qué principal pertenecía el periférico
            ])->orderByRaw('COALESCE(equipo_padre_id, id)')->orderBy('equipo_padre_id')->orderBy('id'),

            // ── Todos los items activos (para la sección de pendientes) ───────
            // Sin whereNull: periféricos activos también son pendientes si no fueron devueltos.
            'items' => fn ($q) => $q->with([
                'equipo.categoria',
                'equipo.atributosActuales.atributo',
                'padre.equipo',
            ])->orderByRaw('COALESCE(equipo_padre_id, id)')->orderBy('equipo_padre_id')->orderBy('id'),
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
     * Solo aplica a usuarios — no a áreas comunes.
     *
     * Al generar este PDF NO se ejecuta ninguna devolución.
     * La devolución real se ejecuta desde el módulo de devolución.
     */
    public function egreso(User $usuario): \Barryvdh\DomPDF\PDF
    {
        $usuario->load([
            'cargo',
            'departamento',
            'empresaNomina',
            'ubicacion',
            'jefe.cargo',
        ]);

        // Todos los items del usuario (activos y devueltos) de todas sus asignaciones
        $todosLosItems = \App\Models\AsignacionItem::with([
            'equipo.categoria',
            'equipo.atributosActuales.atributo',
            'asignacion',
        ])
            ->whereHas('asignacion', fn ($q) => $q->where('usuario_id', $usuario->id))
            ->whereNull('equipo_padre_id')
            ->orderBy('created_at')
            ->get();

        $asignados  = $todosLosItems->filter(fn ($i) => ! $i->devuelto);
        $recibidos  = $todosLosItems->filter(fn ($i) => $i->devuelto);
        $pendientes = $asignados;   // equipos aún sin devolver = pendientes de recepción

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