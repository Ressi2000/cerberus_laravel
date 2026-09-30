<?php

namespace App\Services;

use App\Models\ComponenteAlmacen;
use App\Models\Deposito;
use App\Models\PiezaExtraida;
use App\Models\PiezaExtraidaMovimiento;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * DescarteComponenteService
 * ─────────────────────────────────────────────────────────────────────────────
 * Da de baja stock del Almacén de Componentes porque resultó dañado estando
 * guardado — el caso que describías: "un componente registrado en el
 * almacén también podría dañarse, entonces lo tienen que pasar al depósito".
 *
 * Siempre descuenta el stock agregado (ComponenteAlmacen::registrarSalida).
 * Si además se indica una pieza trazada específica (una de las que
 * ExtraccionPiezaService rescató y sigue en 'en_almacen'), esa unidad
 * puntual queda formalmente retirada con toda su traza — pasa a
 * 'en_deposito' en el depósito indicado — en vez de perderse en el conteo
 * agregado. Solo tiene sentido vincular una pieza cuando la cantidad es 1:
 * un registro trazado no puede representar varias unidades a la vez.
 */
class DescarteComponenteService
{
    public function marcarDanado(
        ComponenteAlmacen $componente,
        int $cantidad,
        Deposito $deposito,
        User $actor,
        ?PiezaExtraida $pieza = null,
        ?string $observaciones = null,
    ): void {
        if ($pieza && $cantidad !== 1) {
            throw new \InvalidArgumentException('Una pieza trazada específica solo se puede vincular cuando la cantidad es 1.');
        }

        if (! $componente->tieneStockSuficiente($cantidad)) {
            throw new \InvalidArgumentException("No hay stock suficiente. Stock actual: {$componente->stock_actual}.");
        }

        if ($pieza && ($pieza->componente_almacen_id !== $componente->id || $pieza->estado !== PiezaExtraida::ESTADO_EN_ALMACEN)) {
            throw new \InvalidArgumentException('Esa pieza no corresponde a este componente o ya no está en Almacén.');
        }

        if ($componente->empresa_id !== $deposito->empresa_id) {
            throw new \InvalidArgumentException('El depósito debe ser de la misma empresa que el componente.');
        }

        DB::transaction(function () use ($componente, $cantidad, $deposito, $actor, $pieza, $observaciones) {
            $componente->registrarSalida($cantidad, $actor, 'Dañado — dado de baja');

            if ($pieza) {
                $pieza->update([
                    'estado'       => PiezaExtraida::ESTADO_EN_DEPOSITO,
                    'reutilizable' => false,
                    'deposito_id'  => $deposito->id,
                ]);

                $pieza->movimientos()->create([
                    'tipo'                    => PiezaExtraidaMovimiento::TIPO_ENVIO_DEPOSITO,
                    'deposito_relacionado_id' => $deposito->id,
                    'registrado_por'          => $actor->id,
                    'observaciones'           => $observaciones ?: 'Encontrada dañada estando en el Almacén.',
                ]);
            }
        });
    }
}
