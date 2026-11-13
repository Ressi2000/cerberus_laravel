<?php

namespace App\Services;

use App\Models\ComponenteAlmacen;
use App\Models\Empresa;
use App\Models\PiezaExtraida;
use App\Models\PiezaExtraidaMovimiento;
use App\Models\TrasladoAlmacen;
use App\Models\User;
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
 *   empresa, y su recorrido dice "Trasladada al almacén de otra empresa".
 * - Unidades sin serial y familias a granel: solo cantidad.
 *
 * Queda un TrasladoAlmacen con el motivo (obligatorio) y lo que se movió,
 * que es la base de la "Autorización de Traslado" en PDF; las dos salidas
 * y entradas de stock quedan ligadas a él.
 */
class TrasladoAlmacenService
{
    /** Largo mínimo del motivo: que diga para qué se traslada, no solo "traslado". */
    const MOTIVO_MINIMO = 10;

    /**
     * @param iterable<PiezaExtraida> $piezas      piezas (con serial) que se trasladan
     * @param int                     $sinSerial   unidades sin serial (o cantidad, a granel)
     */
    public function trasladar(
        ComponenteAlmacen $origen,
        Empresa $empresaDestino,
        User $actor,
        string $motivo,
        iterable $piezas = [],
        int $sinSerial = 0,
    ): TrasladoAlmacen {
        $piezas   = collect($piezas);
        $cantidad = $piezas->count() + $sinSerial;
        $motivo   = trim($motivo);

        if (mb_strlen($motivo) < self::MOTIVO_MINIMO) {
            throw new \InvalidArgumentException('Escribe el motivo del traslado (para qué se necesita).');
        }

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

        $origen->loadMissing(['empresa', 'familia']);
        $empresaOrigen = $origen->empresa?->nombre ?? 'otra empresa';

        return DB::transaction(function () use ($origen, $empresaDestino, $actor, $motivo, $piezas, $sinSerial, $cantidad, $empresaOrigen) {
            // Valida stock y que las piezas sean de esta variante y estén en el almacén.
            $salida = $origen->registrarSalida($cantidad, $actor, "Traslado a {$empresaDestino->nombre}", null, $piezas, $motivo);

            $destino = $this->varianteEnDestino($origen, $empresaDestino, $actor);
            $entrada = $destino->registrarEntrada($cantidad, $actor, "Traslado desde {$empresaOrigen}", $motivo);

            $traslado = TrasladoAlmacen::create([
                'empresa_origen_id'     => $origen->empresa_id,
                'empresa_destino_id'    => $empresaDestino->id,
                'componente_origen_id'  => $origen->id,
                'componente_destino_id' => $destino->id,
                'variante'              => $origen->nombre,
                'familia'               => $origen->familia?->nombre,
                'cantidad'              => $cantidad,
                'detalle'               => [
                    'piezas'     => $piezas->map(fn (PiezaExtraida $p) => [
                        'id'        => $p->id,
                        'codigo'    => $p->identificador ?: "PIEZA-{$p->id}",
                        'condicion' => $p->labelCondicion(),
                    ])->values()->all(),
                    'sin_serial' => $sinSerial,
                    'unidad'     => $origen->unidad,
                ],
                'motivo'                => $motivo,
                'registrado_por'        => $actor->id,
            ]);

            $salida->update(['traslado_almacen_id' => $traslado->id]);
            $entrada->update(['traslado_almacen_id' => $traslado->id]);

            foreach ($piezas as $pieza) {
                $pieza->update([
                    'empresa_id'            => $empresaDestino->id,
                    'componente_almacen_id' => $destino->id,
                ]);

                $pieza->movimientos()->create([
                    'tipo'           => PiezaExtraidaMovimiento::TIPO_TRASLADO_ALMACEN,
                    'registrado_por' => $actor->id,
                    'observaciones'  => "De {$empresaOrigen} a {$empresaDestino->nombre} (autorización {$traslado->folio()}): {$motivo}",
                ]);
            }

            return $traslado;
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
