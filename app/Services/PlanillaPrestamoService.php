<?php

namespace App\Services;

use App\Models\Prestamo;
use Barryvdh\DomPDF\Facade\Pdf;

class PlanillaPrestamoService
{
    /**
     * Planilla de Préstamo de Activos Tecnológicos
     */
    public function prestamo(Prestamo $prestamo): \Barryvdh\DomPDF\PDF
    {
        $prestamo->load([
            'empresa',
            'analista',
            'usuario.cargo',
            'usuario.departamento',
            'usuario.empresaNomina',
            'usuario.ubicacion',
            'usuario.jefe.cargo',
            'areaEmpresa',
            'areaDepartamento',
            'areaResponsable.cargo',
            'areaResponsable.departamento',
            'itemsActivos' => fn ($q) => $q->with([
                'equipo.categoria',
                'equipo.atributosActuales.atributo',
                'padre.equipo',
            ])->orderByRaw('COALESCE(equipo_padre_id, id)')->orderBy('equipo_padre_id')->orderBy('id'),
        ]);

        $pdf = Pdf::loadView('planillas.prestamo', [
            'prestamo' => $prestamo,
            'fecha'    => now()->format('d/m/Y'),
            ...PlanillaVerificacion::datos('prestamo', $prestamo->id),
        ]);

        return $pdf->setPaper('letter', 'portrait');
    }

    /**
     * Planilla de Devolución de Préstamo
     */
    public function devolucion(Prestamo $prestamo): \Barryvdh\DomPDF\PDF
    {
        $prestamo->load([
            'empresa',
            'analista',
            'usuario.cargo',
            'usuario.departamento',
            'usuario.empresaNomina',
            'usuario.ubicacion',
            'usuario.jefe.cargo',
            'areaEmpresa',
            'areaDepartamento',
            'areaResponsable.cargo',
            'itemsDevueltos' => fn ($q) => $q->with([
                'equipo.categoria',
                'equipo.atributosActuales.atributo',
                'devueltoPor',
                'padre.equipo',
            ])->orderByRaw('COALESCE(equipo_padre_id, id)')->orderBy('equipo_padre_id')->orderBy('id'),
            'items' => fn ($q) => $q->with([
                'equipo.categoria',
                'equipo.atributosActuales.atributo',
                'padre.equipo',
            ])->orderByRaw('COALESCE(equipo_padre_id, id)')->orderBy('equipo_padre_id')->orderBy('id'),
        ]);

        $pdf = Pdf::loadView('planillas.prestamo-devolucion', [
            'prestamo' => $prestamo,
            'fecha'    => now()->format('d/m/Y'),
            ...PlanillaVerificacion::datos('prestamo', $prestamo->id),
        ]);

        return $pdf->setPaper('letter', 'portrait');
    }
}
