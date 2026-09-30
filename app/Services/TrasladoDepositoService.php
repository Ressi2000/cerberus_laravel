<?php

namespace App\Services;

use App\Models\Deposito;
use App\Models\Equipo;
use App\Models\PiezaExtraida;
use App\Models\PiezaExtraidaMovimiento;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * TrasladoDepositoService
 * ─────────────────────────────────────────────────────────────────────────────
 * Mueve lo que ya está archivado en un Depósito hacia otro de la misma
 * empresa — un equipo dado de baja, o una pieza descartada (estado
 * 'en_deposito'). No aplica a piezas en Almacén (esas se instalan o se dan
 * de baja, ver SustitucionPiezaService/DescarteComponenteService).
 */
class TrasladoDepositoService
{
    /** Traslada un equipo archivado de un depósito a otro. */
    public function trasladarEquipo(Equipo $equipo, Deposito $depositoDestino, User $actor): void
    {
        if (! $equipo->deposito_id) {
            throw new \InvalidArgumentException('Este equipo no está archivado en ningún depósito.');
        }

        if ((int) $equipo->deposito_id === $depositoDestino->id) {
            throw new \InvalidArgumentException('El equipo ya está en ese depósito.');
        }

        if ($equipo->empresa_id !== $depositoDestino->empresa_id) {
            throw new \InvalidArgumentException('El depósito destino debe ser de la misma empresa que el equipo.');
        }

        // El cambio queda registrado solo: Equipo usa el trait Auditable,
        // así que este update ya aparece en el timeline de la ficha del equipo.
        $equipo->update(['deposito_id' => $depositoDestino->id]);
    }

    /** Traslada una pieza descartada de un depósito a otro. */
    public function trasladarPieza(PiezaExtraida $pieza, Deposito $depositoDestino, User $actor): void
    {
        if ($pieza->estado !== PiezaExtraida::ESTADO_EN_DEPOSITO) {
            throw new \InvalidArgumentException('Esta pieza no está archivada en ningún depósito.');
        }

        if ((int) $pieza->deposito_id === $depositoDestino->id) {
            throw new \InvalidArgumentException('La pieza ya está en ese depósito.');
        }

        if ($pieza->empresa_id !== $depositoDestino->empresa_id) {
            throw new \InvalidArgumentException('El depósito destino debe ser de la misma empresa que la pieza.');
        }

        DB::transaction(function () use ($pieza, $depositoDestino, $actor) {
            $pieza->update(['deposito_id' => $depositoDestino->id]);

            $pieza->movimientos()->create([
                'tipo'                    => PiezaExtraidaMovimiento::TIPO_TRASLADO_DEPOSITO,
                'deposito_relacionado_id' => $depositoDestino->id,
                'registrado_por'          => $actor->id,
            ]);
        });
    }
}
