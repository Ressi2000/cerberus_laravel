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
 * Las piezas con identidad que se indiquen (unidades que siguen en
 * 'en_almacen') quedan formalmente retiradas con toda su traza — pasan a
 * 'en_deposito' en el depósito indicado. Si el componente tiene piezas con
 * identidad y la cantidad supera sus unidades sin identidad, es obligatorio
 * indicar cuáles se dañaron, para que stock y piezas sigan cuadrando.
 */
class DescarteComponenteService
{
    /**
     * @param iterable<PiezaExtraida> $piezas Unidades con identidad que se
     *        dañaron. Si el componente tiene piezas en almacén y la cantidad
     *        supera sus unidades sin identidad, hay que indicar cuáles son —
     *        ver ComponenteAlmacen::registrarSalida().
     */
    public function marcarDanado(
        ComponenteAlmacen $componente,
        int $cantidad,
        Deposito $deposito,
        User $actor,
        iterable $piezas = [],
        ?string $observaciones = null,
    ): void {
        if ((int) $componente->empresa_id !== (int) $deposito->empresa_id) {
            throw new \InvalidArgumentException('El depósito debe ser de la misma empresa que el componente.');
        }

        $piezas = collect($piezas);

        DB::transaction(function () use ($componente, $cantidad, $deposito, $actor, $piezas, $observaciones) {
            // Valida stock, que las piezas sean de este componente y estén en
            // el almacén, y que la cantidad cuadre con las piezas elegidas.
            $componente->registrarSalida($cantidad, $actor, 'Dañado — dado de baja', null, $piezas, $observaciones);

            foreach ($piezas as $pieza) {
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
