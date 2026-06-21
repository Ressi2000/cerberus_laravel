<?php

namespace App\Http\Controllers;

use App\Models\Asignacion;
use App\Models\Prestamo;
use App\Models\Traslado;
use App\Services\Folio;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Página pública de verificación de planillas PDF (destino del QR impreso).
 * No requiere login: solo confirma que el documento escaneado corresponde
 * a un registro real y vigente del sistema. La URL llega firmada
 * (middleware 'signed'), así que no se puede adivinar ni alterar el id.
 */
class VerificacionController extends Controller
{
    public function show(Request $request, string $tipo, int $id): View|Response
    {
        $datos = match ($tipo) {
            'asignacion' => $this->datosAsignacion($id),
            'prestamo'   => $this->datosPrestamo($id),
            'traslado'   => $this->datosTraslado($id),
            default      => null,
        };

        if ($datos === null) {
            return response()->view('verificacion.no-encontrado', [], 404);
        }

        return view('verificacion.show', [
            'folio' => Folio::etiqueta($tipo, $id),
            ...$datos,
        ]);
    }

    private function datosAsignacion(int $id): ?array
    {
        $asignacion = Asignacion::with(['empresa', 'usuario', 'areaResponsable'])->find($id);

        if (! $asignacion) {
            return null;
        }

        $receptor = $asignacion->tipoReceptor() === 'area'
            ? $asignacion->areaResponsable?->name
            : $asignacion->usuario?->name;

        return [
            'tituloDocumento' => 'Planilla de Asignación',
            'codigoDoc'       => 'DC-ST-FO-08',
            'receptor'        => $receptor,
            'empresa'         => $asignacion->empresa?->nombre,
            'fechaDocumento'  => $asignacion->fecha_asignacion,
            'estado'          => $asignacion->estado,
            'esVigente'       => $asignacion->estaActiva(),
        ];
    }

    private function datosPrestamo(int $id): ?array
    {
        $prestamo = Prestamo::with(['empresa', 'usuario', 'areaResponsable'])->find($id);

        if (! $prestamo) {
            return null;
        }

        $receptor = $prestamo->tipoReceptor() === 'area'
            ? $prestamo->areaResponsable?->name
            : $prestamo->usuario?->name;

        return [
            'tituloDocumento' => 'Planilla de Préstamo',
            'codigoDoc'       => 'DC-ST-FO-PRE',
            'receptor'        => $receptor,
            'empresa'         => $prestamo->empresa?->nombre,
            'fechaDocumento'  => $prestamo->fecha_prestamo,
            'estado'          => $prestamo->estado,
            'esVigente'       => ! $prestamo->estaCerrado(),
        ];
    }

    private function datosTraslado(int $id): ?array
    {
        $traslado = Traslado::with(['empresa', 'recibe'])->find($id);

        if (! $traslado) {
            return null;
        }

        return [
            'tituloDocumento' => 'Planilla de Traslado',
            'codigoDoc'       => 'DC-ST-FO-11',
            'receptor'        => $traslado->recibe?->name,
            'empresa'         => $traslado->empresa?->nombre,
            'fechaDocumento'  => $traslado->fecha_traslado,
            'estado'          => $traslado->estado,
            'esVigente'       => $traslado->estado === 'activo',
        ];
    }
}
