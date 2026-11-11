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
        array $seriales = [],
    ): Collection {
        if ($componente->esAGranel()) {
            throw new \InvalidArgumentException("«{$componente->nombre}» es de una familia a granel: se registra solo la cantidad, sin piezas individuales.");
        }

        if (! in_array($condicion, [PiezaExtraida::CONDICION_NUEVO, PiezaExtraida::CONDICION_REUTILIZADO], true)) {
            throw new \InvalidArgumentException("Condición inválida: {$condicion}");
        }

        if ($condicion === PiezaExtraida::CONDICION_REUTILIZADO && ! $equipoOrigen) {
            throw new \InvalidArgumentException('Indica de qué equipo salió esta pieza reutilizada.');
        }

        if ($condicion === PiezaExtraida::CONDICION_NUEVO && $equipoOrigen) {
            throw new \InvalidArgumentException('Una pieza nueva (comprada) no puede tener un equipo de origen.');
        }

        // Seriales: uno por unidad, en orden. Las unidades sin serial reciben
        // un código interno AUTO-…. $identificador es la forma antigua (una sola unidad).
        $seriales = collect($seriales)
            ->when($identificador !== null, fn ($c) => $c->prepend($identificador))
            ->map(fn ($s) => trim((string) $s))
            ->filter()
            ->values();

        if ($seriales->count() > $cantidad) {
            throw new \InvalidArgumentException("Escribiste {$seriales->count()} seriales para {$cantidad} unidad(es).");
        }

        if ($seriales->duplicates()->isNotEmpty()) {
            throw new \InvalidArgumentException('Hay seriales repetidos: ' . $seriales->duplicates()->unique()->implode(', ') . '.');
        }

        $existentes = PiezaExtraida::where('componente_almacen_id', $componente->id)->whereIn('identificador', $seriales)->pluck('identificador');
        if ($existentes->isNotEmpty()) {
            throw new \InvalidArgumentException('Ya hay piezas con estos seriales en este componente: ' . $existentes->implode(', ') . '.');
        }

        if ($equipoOrigen && (int) $equipoOrigen->empresa_id !== (int) $componente->empresa_id) {
            throw new \InvalidArgumentException('El equipo de origen debe ser de la misma empresa que el componente.');
        }

        return DB::transaction(function () use ($componente, $cantidad, $condicion, $actor, $equipoOrigen, $seriales, $observaciones) {
            $piezas = collect();

            for ($i = 0; $i < $cantidad; $i++) {
                // El serial escrito para esta unidad, o un código interno si no tiene.
                $idUnidad = $seriales[$i] ?? $this->generarIdentificador($componente);

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

    /**
     * "AUTO-{componente}-{secuencia}": toma el número AUTO más alto que ya
     * tenga el componente y le suma 1. Contar las piezas (como antes) repetía
     * números si alguna se movía a otro componente o si se escribía a mano
     * un identificador. El índice único (componente, identificador) es la
     * última barrera ante dos altas simultáneas.
     */
    private function generarIdentificador(ComponenteAlmacen $componente): string
    {
        $prefijo = 'AUTO-' . $componente->id . '-';

        $ultimo = PiezaExtraida::where('componente_almacen_id', $componente->id)
            ->where('identificador', 'like', $prefijo . '%')
            ->pluck('identificador')
            ->map(fn ($id) => (int) substr($id, strlen($prefijo)))
            ->max() ?? 0;

        return $prefijo . str_pad((string) ($ultimo + 1), 3, '0', STR_PAD_LEFT);
    }
}
