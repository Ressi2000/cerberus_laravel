<?php

namespace App\Services;

use App\Models\Asignacion;
use App\Models\Prestamo;
use App\Models\Traslado;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Resuelve, para un tipo de planilla ('asignacion'|'prestamo'|'traslado'),
 * el modelo y quién debe firmar cada rol aplicable.
 *
 * Los roles firmables están limitados a los espacios de firma realmente
 * impresos en cada planilla: asignación y préstamo solo imprimen receptor
 * (el analista firma en persona), mientras que traslado imprime además a
 * quien autoriza ('jefe').
 */
class FirmaResolver
{
    /**
     * Roles remotamente firmables por tipo. El analista/técnico que entrega
     * firma en persona en el momento (no aplica firma remota); en asignación
     * y préstamo solo el receptor firma a distancia. En traslado, tanto quien
     * recibe como quien autoriza pueden estar en ubicaciones distintas.
     */
    public static function rolesAplicables(string $tipo): array
    {
        return match ($tipo) {
            'traslado' => ['receptor', 'jefe'],
            default    => ['receptor'],
        };
    }

    public static function modelo(string $tipo, int $id): ?Model
    {
        return match ($tipo) {
            'asignacion' => Asignacion::with(['usuario', 'areaResponsable'])->find($id),
            'prestamo'   => Prestamo::with(['usuario', 'areaResponsable'])->find($id),
            'traslado'   => Traslado::with(['recibe', 'autoriza'])->find($id),
            default      => null,
        };
    }

    /**
     * @return array{receptor: ?User, jefe: ?User}
     */
    public static function firmantes(string $tipo, Model $documento): array
    {
        if ($tipo === 'traslado') {
            return [
                'receptor' => $documento->recibe,
                'jefe'     => $documento->autoriza,
            ];
        }

        // asignacion / prestamo: receptor personal o área común. No hay rol 'jefe'.
        return [
            'receptor' => $documento->usuario ?? $documento->areaResponsable,
            'jefe'     => null,
        ];
    }

    public static function tituloDocumento(string $tipo): string
    {
        return match ($tipo) {
            'asignacion' => 'Planilla de Asignación',
            'prestamo'   => 'Planilla de Préstamo',
            'traslado'   => 'Planilla de Traslado',
            default      => throw new \InvalidArgumentException("Tipo de firma desconocido: {$tipo}"),
        };
    }
}
