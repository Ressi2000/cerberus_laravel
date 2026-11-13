<?php

namespace App\Services;

use App\Models\ComponenteAlmacen;
use App\Models\Empresa;
use App\Models\PiezaExtraida;
use App\Models\PiezaExtraidaMovimiento;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * TrasladoAlmacenService
 * ─────────────────────────────────────────────────────────────────────────────
 * Pasa stock del almacén de una empresa al de otra (cada empresa tiene su
 * propio almacén). En el destino cae en la variante con el mismo nombre y
 * familia (si no existe, se crea igual).
 *
 * - Por unidad: se mueven las piezas elegidas por su serial. Siguen siendo
 *   las mismas piezas (mismo id, serial y recorrido), ahora de la otra
 *   empresa, y su recorrido dice "Trasladada a otro almacén".
 * - Unidades sin serial y familias a granel: solo cantidad.
 *
 * Ambos lados quedan en el historial de entradas y salidas de su variante.
 */
class TrasladoAlmacenService
{
    /**
     * @param iterable<PiezaExtraida> $piezas      piezas (con serial) que se trasladan
     * @param int                     $sinSerial   unidades sin serial (o cantidad, a granel)
     */
    public function trasladar(
        ComponenteAlmacen $origen,
        Empresa $empresaDestino,
        User $actor,
        iterable $piezas = [],
        int $sinSerial = 0,
        ?string $observaciones = null,
    ): ComponenteAlmacen {
        $piezas   = collect($piezas);
        $cantidad = $piezas->count() + $sinSerial;

        if ((int) $empresaDestino->id === (int) $origen->empresa_id) {
            throw new \InvalidArgumentException('Elige una empresa distinta a la de origen.');
        }

        if ($cantidad < 1) {
            throw new \InvalidArgumentException($origen->esAGranel()
                ? 'Indica cuántas trasladar.'
                : 'Marca cuáles piezas trasladar.');
        }

        if ($origen->esAGranel() && $piezas->isNotEmpty()) {
            throw new \InvalidArgumentException('A granel solo se traslada por cantidad.');
        }

        $empresaOrigen = $origen->empresa?->nombre ?? 'otra empresa';

        return DB::transaction(function () use ($origen, $empresaDestino, $actor, $piezas, $cantidad, $observaciones, $empresaOrigen) {
            // Valida stock y que las piezas sean de esta variante y estén en el almacén.
            $origen->registrarSalida($cantidad, $actor, "Traslado a {$empresaDestino->nombre}", null, $piezas, $observaciones);

            $destino = $this->varianteEnDestino($origen, $empresaDestino, $actor);
            $destino->registrarEntrada($cantidad, $actor, "Traslado desde {$empresaOrigen}", $observaciones);

            foreach ($piezas as $pieza) {
                $pieza->update([
                    'empresa_id'            => $empresaDestino->id,
                    'componente_almacen_id' => $destino->id,
                ]);

                $pieza->movimientos()->create([
                    'tipo'           => PiezaExtraidaMovimiento::TIPO_TRASLADO_ALMACEN,
                    'registrado_por' => $actor->id,
                    'observaciones'  => trim("De {$empresaOrigen} a {$empresaDestino->nombre}. " . ($observaciones ?? '')),
                ]);
            }

            return $destino;
        });
    }

    /** La misma variante (nombre y familia) en el almacén de la otra empresa; se crea si no existe. */
    private function varianteEnDestino(ComponenteAlmacen $origen, Empresa $empresaDestino, User $actor): ComponenteAlmacen
    {
        $destino = ComponenteAlmacen::withTrashed()
            ->where('empresa_id', $empresaDestino->id)
            ->where('nombre', $origen->nombre)
            ->orderByRaw('deleted_at IS NOT NULL')
            ->first();

        if (! $destino) {
            return ComponenteAlmacen::create([
                'empresa_id'   => $empresaDestino->id,
                'familia_id'   => $origen->familia_id,
                'nombre'       => $origen->nombre,
                'descripcion'  => $origen->descripcion,
                'unidad'       => $origen->unidad,
                'stock_actual' => 0,
                'activo'       => true,
                'creado_por'   => $actor->id,
            ]);
        }

        if ($destino->trashed()) {
            $destino->restore();
        }

        $destino->forceFill(['activo' => true, 'familia_id' => $destino->familia_id ?? $origen->familia_id])->save();

        return $destino;
    }
}
