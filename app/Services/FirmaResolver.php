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
 * Los roles firmables coinciden con los espacios de firma realmente
 * impresos en cada planilla: asignación y préstamo imprimen analista y
 * receptor; traslado imprime además a quien autoriza ('jefe').
 */
class FirmaResolver
{
    /**
     * Roles remotamente firmables por tipo, vía el enlace expuesto en la
     * página de verificación (destino del QR impreso).
     */
    public static function rolesAplicables(string $tipo): array
    {
        return match ($tipo) {
            'traslado' => ['analista', 'receptor', 'jefe'],
            default    => ['analista', 'receptor'],
        };
    }

    public static function modelo(string $tipo, int $id): ?Model
    {
        return match ($tipo) {
            'asignacion' => Asignacion::with(['analista', 'usuario', 'areaResponsable'])->find($id),
            'prestamo'   => Prestamo::with(['analista', 'usuario', 'areaResponsable'])->find($id),
            'traslado'   => Traslado::with(['realizadoPor', 'recibe', 'autoriza'])->find($id),
            default      => null,
        };
    }

    /**
     * @return array{analista: ?User, receptor: ?User, jefe: ?User}
     */
    public static function firmantes(string $tipo, Model $documento): array
    {
        if ($tipo === 'traslado') {
            return [
                'analista' => $documento->realizadoPor,
                'receptor' => $documento->recibe,
                'jefe'     => $documento->autoriza,
            ];
        }

        // asignacion / prestamo: receptor personal o área común. No hay rol 'jefe'.
        return [
            'analista' => $documento->analista,
            'receptor' => $documento->usuario ?? $documento->areaResponsable,
            'jefe'     => null,
        ];
    }

    public static function etiquetaRol(string $rol): string
    {
        return match ($rol) {
            'analista' => 'Analista',
            'receptor' => 'Receptor',
            'jefe'     => 'Autoriza',
            default    => ucfirst($rol),
        };
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
