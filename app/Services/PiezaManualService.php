<?php

namespace App\Services;

use App\Models\ComponenteAlmacen;
use App\Models\Equipo;
use App\Models\PiezaExtraida;
use App\Models\PiezaExtraidaMovimiento;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * PiezaManualService
 * ─────────────────────────────────────────────────────────────────────────────
 * Da de alta piezas individuales que NO nacen de un atributo de equipo (ver
 * ExtraccionPiezaService para esas) — componentes que se registran a mano en
 * Almacén porque no tienen un atributo EAV que los represente (bisagras,
 * carcasas, tornillería...). Cada unidad igual queda con su propia identidad
 * en PiezaExtraida: si es nueva (comprada) o reutilizada (y de qué equipo
 * salió), y un identificador para distinguirla de otras unidades idénticas.
 */
class PiezaManualService
{
    /**
     * @return Collection<int, PiezaExtraida>
     */
    public function registrar(
        ComponenteAlmacen $componente,
        int $cantidad,
        string $condicion,
        User $actor,
        ?Equipo $equipoOrigen = null,
        ?string $identificador = null,
        ?string $observaciones = null,
    ): Collection {
        if (! in_array($condicion, [PiezaExtraida::CONDICION_NUEVO, PiezaExtraida::CONDICION_REUTILIZADO], true)) {
            throw new \InvalidArgumentException("Condición inválida: {$condicion}");
        }

        if ($condicion === PiezaExtraida::CONDICION_REUTILIZADO && ! $equipoOrigen) {
            throw new \InvalidArgumentException('Indica de qué equipo salió esta pieza reutilizada.');
        }

        if ($condicion === PiezaExtraida::CONDICION_NUEVO && $equipoOrigen) {
            throw new \InvalidArgumentException('Una pieza nueva (comprada) no puede tener un equipo de origen.');
        }

        return DB::transaction(function () use ($componente, $cantidad, $condicion, $actor, $equipoOrigen, $identificador, $observaciones) {
            $piezas = collect();

            for ($i = 0; $i < $cantidad; $i++) {
                // Un identificador escrito a mano solo tiene sentido para una
                // unidad puntual — si se dan de alta varias a la vez, cada una
                // necesita el suyo propio, así que se autogenera siempre.
                $idUnidad = ($cantidad === 1 && $identificador)
                    ? $identificador
                    : $this->generarIdentificador($componente);

                $pieza = PiezaExtraida::create([
                    'empresa_id'            => $componente->empresa_id,
                    'equipo_origen_id'      => $equipoOrigen?->id,
                    'atributo_id'           => null,
                    'valor_extraido'        => null,
                    'reutilizable'          => true,
                    'condicion'             => $condicion,
                    'identificador'         => $idUnidad,
                    'estado'                => PiezaExtraida::ESTADO_EN_ALMACEN,
                    'componente_almacen_id' => $componente->id,
                    'motivo'                => PiezaExtraida::MOTIVO_REGISTRO_MANUAL,
                    'extraido_por'          => $actor->id,
                    'observaciones'         => $observaciones,
                ]);

                $pieza->movimientos()->create([
                    'tipo'                  => PiezaExtraidaMovimiento::TIPO_INGRESO_ALMACEN,
                    'registrado_por'        => $actor->id,
                    'observaciones'         => $observaciones,
                ]);

                // Si viene de un equipo, deja también el evento de "salió de
                // aquí" — mismo tipo que usa ExtraccionPiezaService, para que
                // aparezca igual en el historial de ESE equipo.
                if ($equipoOrigen) {
                    $pieza->movimientos()->create([
                        'tipo'                  => PiezaExtraidaMovimiento::TIPO_EXTRACCION,
                        'equipo_relacionado_id' => $equipoOrigen->id,
                        'registrado_por'        => $actor->id,
                        'observaciones'         => $observaciones,
                    ]);
                }

                $piezas->push($pieza);
            }

            $componente->registrarEntrada(
                $cantidad,
                $actor,
                $condicion === PiezaExtraida::CONDICION_NUEVO ? 'Alta de stock nuevo' : 'Alta de stock reutilizado',
                $observaciones,
            );

            return $piezas;
        });
    }

    /** "AUTO-{componente}-{secuencia}" — simple, estable, siempre único por bucket. */
    private function generarIdentificador(ComponenteAlmacen $componente): string
    {
        $n = PiezaExtraida::where('componente_almacen_id', $componente->id)->count() + 1;

        return 'AUTO-' . $componente->id . '-' . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    }
}
