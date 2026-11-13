<?php

namespace App\Services;

use App\Models\AtributoEquipo;
use App\Models\ComponenteAlmacen;
use App\Models\Deposito;
use App\Models\Equipo;
use App\Models\EquipoAtributoGrupoInstancia;
use App\Models\EquipoAtributoValor;
use App\Models\Mantenimiento;
use App\Models\PiezaExtraida;
use App\Models\PiezaExtraidaMovimiento;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ExtraccionPiezaService
 * ─────────────────────────────────────────────────────────────────────────────
 * Punto único para "sacar" una pieza física de un equipo y clasificarla.
 *
 * Solo los atributos marcados `reutilizable` en su definición son piezas
 * candidatas a extraer — es justamente lo que ese flag significa: "esto es
 * una pieza física, ofrécela en el modal de rescate". Un atributo no marcado
 * (ej: Sistema Operativo, Color) nunca se extrae individualmente; se va con
 * el equipo cuando este pasa a un Depósito como un todo.
 *
 * Que el atributo esté marcado reutilizable NO decide automáticamente que la
 * pieza extraída termine en Almacén: quien extrae decide el destino real
 * (`$destino`) para esa unidad puntual, porque una pieza de un tipo
 * normalmente reutilizable puede salir rota (ej: sustituir una RAM dañada
 * durante una reparación) y debe ir a Depósito igual. El flag del atributo
 * solo filtra QUÉ se puede rescatar; la decisión de ESTA unidad la toma el
 * usuario al momento de extraer.
 *
 * Reutilizado tanto al dar de baja un equipo (Fase 5 — una llamada por cada
 * pieza que el usuario decide rescatar) como al sustituir una pieza durante
 * una reparación (Fase 6).
 */
class ExtraccionPiezaService
{
    /**
     * Lista las piezas candidatas a extraer de un equipo: valores actuales
     * (simples o instancias de grupo) cuyo atributo está marcado reutilizable.
     * Es la fuente del modal "¿cuáles componentes quieres rescatar?".
     *
     * @return Collection<int, array{
     *     tipo: string,
     *     atributo: AtributoEquipo,
     *     valor: EquipoAtributoValor|null,
     *     grupoInstancia: EquipoAtributoGrupoInstancia|null,
     *     descripcion: string,
     * }>
     */
    public function candidatos(Equipo $equipo): Collection
    {
        $simples = $equipo->atributosActuales()
            ->whereHas('atributo', fn ($q) => $q
                ->where('reutilizable', true)
                ->where('tipo', '!=', AtributoEquipo::TIPO_GROUP))
            ->with('atributo')
            ->get()
            ->map(fn (EquipoAtributoValor $valor) => [
                'tipo'           => 'simple',
                'atributo'       => $valor->atributo,
                'valor'          => $valor,
                'grupoInstancia' => null,
                'descripcion'    => $valor->atributo->nombre . ': ' . $valor->valor,
            ]);

        $grupos = $equipo->grupoInstancias()
            ->whereHas('atributo', fn ($q) => $q->where('reutilizable', true))
            ->with('atributo')
            ->get()
            ->map(fn (EquipoAtributoGrupoInstancia $instancia) => [
                'tipo'           => 'grupo',
                'atributo'       => $instancia->atributo,
                'valor'          => null,
                'grupoInstancia' => $instancia,
                'descripcion'    => $instancia->atributo->describirValor($instancia->valores),
            ]);

        return $simples->concat($grupos)
            ->sortBy(fn ($c) => $c['atributo']->orden)
            ->values();
    }

    /**
     * Extrae la pieza de un atributo simple (sin sub-campos) de un equipo.
     */
    public function extraerDeAtributoSimple(
        Equipo $equipoOrigen,
        AtributoEquipo $atributo,
        string $destino,
        User $actor,
        string $motivo,
        ?Deposito $deposito = null,
        ?Mantenimiento $mantenimiento = null,
        ?string $observaciones = null,
        ?int $piezaInstaladaId = null,
        ?string $serial = null,
    ): PiezaExtraida {
        $valorActual = EquipoAtributoValor::where('equipo_id', $equipoOrigen->id)
            ->where('atributo_id', $atributo->id)
            ->where('es_actual', true)
            ->first();

        if (! $valorActual) {
            throw new \InvalidArgumentException(
                "El equipo #{$equipoOrigen->id} no tiene un valor vigente para el atributo «{$atributo->nombre}»."
            );
        }

        return $this->extraer(
            equipoOrigen: $equipoOrigen,
            atributo: $atributo,
            valorExtraido: ['valor' => $valorActual->valor],
            destino: $destino,
            actor: $actor,
            motivo: $motivo,
            deposito: $deposito,
            mantenimiento: $mantenimiento,
            observaciones: $observaciones,
            onDesactivar: fn () => $valorActual->update(['es_actual' => false]),
            piezaInstaladaId: $piezaInstaladaId,
            serial: $serial,
        );
    }

    /**
     * Extrae una instancia puntual de un atributo tipo 'group' (ej: uno de
     * varios discos/RAM del equipo), sin afectar las demás instancias.
     */
    public function extraerDeGrupoInstancia(
        Equipo $equipoOrigen,
        EquipoAtributoGrupoInstancia $instancia,
        string $destino,
        User $actor,
        string $motivo,
        ?Deposito $deposito = null,
        ?Mantenimiento $mantenimiento = null,
        ?string $observaciones = null,
        ?int $piezaInstaladaId = null,
        ?string $serial = null,
    ): PiezaExtraida {
        if ($instancia->equipo_id !== $equipoOrigen->id) {
            throw new \InvalidArgumentException('La instancia no pertenece al equipo indicado.');
        }

        if (! $instancia->es_actual) {
            throw new \InvalidArgumentException('Esta instancia ya fue extraída o no está vigente.');
        }

        return $this->extraer(
            equipoOrigen: $equipoOrigen,
            atributo: $instancia->atributo,
            valorExtraido: $instancia->valores,
            destino: $destino,
            actor: $actor,
            motivo: $motivo,
            deposito: $deposito,
            mantenimiento: $mantenimiento,
            observaciones: $observaciones,
            onDesactivar: fn () => $instancia->update(['es_actual' => false]),
            grupoInstancia: $instancia,
            piezaInstaladaId: $piezaInstaladaId,
            serial: $serial,
        );
    }

    /**
     * Núcleo común: valida, apaga el valor de origen, clasifica el destino
     * (suma stock en Almacén o deja constancia en Depósito) y deja la traza
     * completa — todo en una sola transacción.
     */
    private function extraer(
        Equipo $equipoOrigen,
        AtributoEquipo $atributo,
        array $valorExtraido,
        string $destino,
        User $actor,
        string $motivo,
        ?Deposito $deposito,
        ?Mantenimiento $mantenimiento,
        ?string $observaciones,
        \Closure $onDesactivar,
        ?EquipoAtributoGrupoInstancia $grupoInstancia = null,
        ?int $piezaInstaladaId = null,
        ?string $serial = null,
    ): PiezaExtraida {
        if (! $atributo->reutilizable) {
            throw new \InvalidArgumentException(
                "El atributo «{$atributo->nombre}» no está marcado como reutilizable; no se puede extraer como pieza."
            );
        }

        if (! in_array($motivo, [
            PiezaExtraida::MOTIVO_BAJA_EQUIPO,
            PiezaExtraida::MOTIVO_SUSTITUCION_REPARACION,
            PiezaExtraida::MOTIVO_DESARME_MANUAL,
            PiezaExtraida::MOTIVO_SUSTITUCION_PREVENTIVO,
        ], true)) {
            throw new \InvalidArgumentException("Motivo de extracción inválido: {$motivo}");
        }

        if (! in_array($destino, [PiezaExtraida::ESTADO_EN_ALMACEN, PiezaExtraida::ESTADO_EN_DEPOSITO], true)) {
            throw new \InvalidArgumentException("Destino de extracción inválido: {$destino}");
        }

        if ($destino === PiezaExtraida::ESTADO_EN_DEPOSITO && ! $deposito) {
            throw new \InvalidArgumentException('Una pieza enviada a Depósito necesita indicar cuál depósito.');
        }

        if ($destino === PiezaExtraida::ESTADO_EN_DEPOSITO && (int) $deposito->empresa_id !== (int) $equipoOrigen->empresa_id) {
            throw new \InvalidArgumentException('El depósito debe ser de la misma empresa que el equipo.');
        }

        $vaAlmacen = $destino === PiezaExtraida::ESTADO_EN_ALMACEN;

        return DB::transaction(function () use (
            $equipoOrigen, $atributo, $valorExtraido, $vaAlmacen, $actor, $motivo,
            $deposito, $mantenimiento, $observaciones, $onDesactivar, $grupoInstancia, $piezaInstaladaId, $serial,
        ) {
            $serial = trim((string) $serial) ?: null;

            // Si lo que sale es una pieza que entró desde el almacén, es la
            // MISMA pieza física: sigue con su id, su serial y su recorrido.
            $existente = $this->piezaInstaladaQueSale($equipoOrigen, $atributo, $valorExtraido, $piezaInstaladaId);

            $onDesactivar();

            $componente = null;
            if ($vaAlmacen) {
                $componente = $this->bucketDeStock($equipoOrigen->empresa_id, $atributo, $valorExtraido, $actor);
                $componente->registrarEntrada(
                    1,
                    $actor,
                    match ($motivo) {
                        PiezaExtraida::MOTIVO_BAJA_EQUIPO            => 'Rescate por baja de equipo',
                        PiezaExtraida::MOTIVO_DESARME_MANUAL         => 'Rescate por desarme de equipo',
                        PiezaExtraida::MOTIVO_SUSTITUCION_PREVENTIVO => 'Rescate por sustitución en mantenimiento',
                        default                                      => 'Rescate por sustitución en reparación',
                    },
                );
            }

            if ($existente) {
                // Si entró sin serial y ahora se conoce, se le anota.
                if ($serial && ! $existente->identificador) {
                    $existente->identificador = $serial;
                }

                return $this->volverASacar(
                    $existente, $equipoOrigen, $atributo, $valorExtraido, $vaAlmacen, $componente,
                    $deposito, $mantenimiento, $motivo, $actor, $observaciones, $grupoInstancia,
                );
            }

            $pieza = PiezaExtraida::create([
                'empresa_id'            => $equipoOrigen->empresa_id,
                'equipo_origen_id'      => $equipoOrigen->id,
                'atributo_id'           => $atributo->id,
                'grupo_instancia_id'    => $grupoInstancia?->id,
                'valor_extraido'        => $valorExtraido,
                'reutilizable'          => $vaAlmacen,
                'condicion'             => PiezaExtraida::CONDICION_REUTILIZADO,
                'identificador'         => $serial,
                'estado'                => $vaAlmacen ? PiezaExtraida::ESTADO_EN_ALMACEN : PiezaExtraida::ESTADO_EN_DEPOSITO,
                'componente_almacen_id' => $componente?->id,
                'deposito_id'           => $vaAlmacen ? null : $deposito->id,
                'mantenimiento_id'      => $mantenimiento?->id,
                'motivo'                => $motivo,
                'extraido_por'          => $actor->id,
                'observaciones'         => $observaciones,
            ]);

            $pieza->movimientos()->create([
                'tipo'                  => PiezaExtraidaMovimiento::TIPO_EXTRACCION,
                'equipo_relacionado_id' => $equipoOrigen->id,
                'mantenimiento_id'      => $mantenimiento?->id,
                'registrado_por'        => $actor->id,
                'observaciones'         => $observaciones,
            ]);

            $pieza->movimientos()->create([
                'tipo'                    => $vaAlmacen
                    ? PiezaExtraidaMovimiento::TIPO_INGRESO_ALMACEN
                    : PiezaExtraidaMovimiento::TIPO_ENVIO_DEPOSITO,
                'deposito_relacionado_id' => $vaAlmacen ? null : $deposito->id,
                'registrado_por'          => $actor->id,
            ]);

            return $pieza;
        });
    }

    /**
     * Saca de un equipo una pieza que entró desde el almacén y que NO está
     * en su ficha (ej. una pantalla, o una RAM instalada cuando la
     * característica de la categoría todavía no estaba vinculada a la
     * familia). Sigue siendo la misma pieza: vuelve al almacén (a su
     * variante) o va a un depósito, y se agrega a su recorrido.
     */
    public function retirarPiezaInstalada(
        PiezaExtraida $pieza,
        Equipo $equipo,
        string $destino,
        User $actor,
        string $motivo,
        ?Deposito $deposito = null,
        ?Mantenimiento $mantenimiento = null,
        ?string $observaciones = null,
    ): PiezaExtraida {
        if (! in_array($destino, [PiezaExtraida::ESTADO_EN_ALMACEN, PiezaExtraida::ESTADO_EN_DEPOSITO], true)) {
            throw new \InvalidArgumentException("Destino inválido: {$destino}");
        }

        if ($destino === PiezaExtraida::ESTADO_EN_DEPOSITO
            && (! $deposito || (int) $deposito->empresa_id !== (int) $equipo->empresa_id)) {
            throw new \InvalidArgumentException('Indica un depósito de la misma empresa que el equipo.');
        }

        return DB::transaction(function () use ($pieza, $equipo, $destino, $actor, $motivo, $deposito, $mantenimiento, $observaciones) {
            $pieza = PiezaExtraida::lockForUpdate()->findOrFail($pieza->id);

            if ($pieza->estado !== PiezaExtraida::ESTADO_INSTALADA || (int) $pieza->equipo_destino_id !== (int) $equipo->id) {
                throw new \InvalidArgumentException('Esa pieza ya no está instalada en este equipo.');
            }

            $vaAlmacen  = $destino === PiezaExtraida::ESTADO_EN_ALMACEN;
            $componente = $pieza->componenteAlmacen()->withTrashed()->first();

            if ($vaAlmacen) {
                if (! $componente) {
                    throw new \InvalidArgumentException('Esta pieza ya no tiene variante en el almacén: envíala a un depósito.');
                }
                if ($componente->trashed()) {
                    $componente->restore();
                }
                if (! $componente->activo) {
                    $componente->update(['activo' => true]);
                }
                $componente->registrarEntrada(1, $actor, 'Retirada de un equipo', $observaciones);
            }

            $pieza->update([
                'equipo_origen_id'  => $equipo->id,
                'equipo_destino_id' => null,
                'reutilizable'      => $vaAlmacen,
                'condicion'         => PiezaExtraida::CONDICION_REUTILIZADO,
                'estado'            => $destino,
                'deposito_id'       => $vaAlmacen ? null : $deposito->id,
                'mantenimiento_id'  => $mantenimiento?->id,
                'motivo'            => $motivo,
                'extraido_por'      => $actor->id,
                'observaciones'     => $observaciones ?? $pieza->observaciones,
            ]);

            $pieza->movimientos()->create([
                'tipo'                  => PiezaExtraidaMovimiento::TIPO_DESINSTALACION,
                'equipo_relacionado_id' => $equipo->id,
                'mantenimiento_id'      => $mantenimiento?->id,
                'registrado_por'        => $actor->id,
                'observaciones'         => $observaciones,
            ]);

            $pieza->movimientos()->create([
                'tipo'                    => $vaAlmacen ? PiezaExtraidaMovimiento::TIPO_INGRESO_ALMACEN : PiezaExtraidaMovimiento::TIPO_ENVIO_DEPOSITO,
                'deposito_relacionado_id' => $vaAlmacen ? null : $deposito->id,
                'registrado_por'          => $actor->id,
            ]);

            return $pieza;
        });
    }

    /**
     * Piezas instaladas en este equipo que podrían ser la que sale: de la
     * misma característica (o de la misma familia) y con sus valores de
     * ficha. Si hay varias idénticas (dos RAM iguales), quien retira
     * elige cuál por su serial; si no elige, sale la que se instaló primero.
     */
    public function piezasInstaladasCoincidentes(Equipo $equipo, AtributoEquipo $atributo, array $valores)
    {
        $buscado = $this->normalizarValores($valores);

        return PiezaExtraida::where('equipo_destino_id', $equipo->id)
            ->where('estado', PiezaExtraida::ESTADO_INSTALADA)
            ->where(function ($q) use ($atributo) {
                $q->where('atributo_id', $atributo->id);
                if ($atributo->familia_id) {
                    $q->orWhereHas('atributo', fn ($a) => $a->where('familia_id', $atributo->familia_id));
                }
            })
            ->oldest('updated_at')
            ->orderBy('id')
            ->get()
            // Lo que dice la pieza tiene que seguir en la ficha del equipo (la
            // ficha puede tener datos de más si alguien la completó después).
            ->filter(function (PiezaExtraida $p) use ($buscado) {
                $propios = $this->normalizarValores($p->valor_extraido ?? []);

                return $propios !== [] && array_intersect_assoc($propios, $buscado) === $propios;
            })
            ->values();
    }

    private function piezaInstaladaQueSale(Equipo $equipo, AtributoEquipo $atributo, array $valores, ?int $piezaId): ?PiezaExtraida
    {
        $candidatas = $this->piezasInstaladasCoincidentes($equipo, $atributo, $valores);

        if ($piezaId !== null) {
            $elegida = $candidatas->firstWhere('id', $piezaId);
            if (! $elegida) {
                throw new \InvalidArgumentException('Esa pieza no está instalada en este equipo con esos valores.');
            }

            return PiezaExtraida::lockForUpdate()->find($elegida->id);
        }

        return $candidatas->isEmpty() ? null : PiezaExtraida::lockForUpdate()->find($candidatas->first()->id);
    }

    /** Valores comparables: sin vacíos, como texto y en orden de clave. */
    private function normalizarValores(array $valores): array
    {
        $limpio = collect($valores)
            ->reject(fn ($v) => $v === null || $v === '')
            ->map(fn ($v) => trim((string) $v))
            ->all();
        ksort($limpio);

        return $limpio;
    }

    /**
     * Una pieza que estaba instalada vuelve a salir de un equipo: se
     * actualiza su ubicación (almacén o depósito) y se agregan los pasos
     * "Desinstalada" e "Ingresó al almacén"/"Enviada a depósito" a su
     * recorrido. Desde ahora cuenta como usada.
     */
    private function volverASacar(
        PiezaExtraida $pieza,
        Equipo $equipo,
        AtributoEquipo $atributo,
        array $valores,
        bool $vaAlmacen,
        ?ComponenteAlmacen $componente,
        ?Deposito $deposito,
        ?Mantenimiento $mantenimiento,
        string $motivo,
        User $actor,
        ?string $observaciones,
        ?EquipoAtributoGrupoInstancia $grupoInstancia,
    ): PiezaExtraida {
        $pieza->update([
            'equipo_origen_id'      => $equipo->id,
            'equipo_destino_id'     => null,
            'atributo_id'           => $atributo->id,
            'grupo_instancia_id'    => $grupoInstancia?->id,
            'valor_extraido'        => $valores,
            'reutilizable'          => $vaAlmacen,
            'condicion'             => PiezaExtraida::CONDICION_REUTILIZADO,
            'estado'                => $vaAlmacen ? PiezaExtraida::ESTADO_EN_ALMACEN : PiezaExtraida::ESTADO_EN_DEPOSITO,
            // En depósito sigue siendo de su variante: así aparece en su historial.
            'componente_almacen_id' => $componente?->id ?? $pieza->componente_almacen_id,
            'deposito_id'           => $vaAlmacen ? null : $deposito->id,
            'mantenimiento_id'      => $mantenimiento?->id,
            'motivo'                => $motivo,
            'extraido_por'          => $actor->id,
            'observaciones'         => $observaciones ?? $pieza->observaciones,
        ]);

        $pieza->movimientos()->create([
            'tipo'                  => PiezaExtraidaMovimiento::TIPO_DESINSTALACION,
            'equipo_relacionado_id' => $equipo->id,
            'mantenimiento_id'      => $mantenimiento?->id,
            'registrado_por'        => $actor->id,
            'observaciones'         => $observaciones,
        ]);

        $pieza->movimientos()->create([
            'tipo'                    => $vaAlmacen
                ? PiezaExtraidaMovimiento::TIPO_INGRESO_ALMACEN
                : PiezaExtraidaMovimiento::TIPO_ENVIO_DEPOSITO,
            'deposito_relacionado_id' => $vaAlmacen ? null : $deposito->id,
            'registrado_por'          => $actor->id,
        ]);

        return $pieza;
    }

    /**
     * Encuentra o crea el bucket de stock en Almacén de Componentes donde
     * cae esta pieza, agrupado por nombre (atributo + valor legible) para
     * que, por ejemplo, "RAM (8GB)" y "RAM (16GB)" no se mezclen en el mismo
     * stock. Recibe el empresa_id directo (no un Equipo) porque también lo
     * usa registrarNueva() para una pieza que nunca estuvo en ningún equipo.
     */
    /**
     * Variante (componente del almacén) que corresponde a estos valores de
     * ficha: la busca o la crea. La usa también "Registrar entrada".
     */
    public function varianteDeFicha(int $empresaId, AtributoEquipo $atributo, array $valores, User $actor): ComponenteAlmacen
    {
        return $this->bucketDeStock($empresaId, $atributo, $valores, $actor);
    }

    private function bucketDeStock(int $empresaId, AtributoEquipo $atributo, array $valorExtraido, User $actor): ComponenteAlmacen
    {
        $nombre = $atributo->describirValor($valorExtraido);

        // withTrashed(): si ya existió un componente con este nombre y se
        // eliminó o desactivó, se reutiliza y se reactiva — si no, la pieza
        // quedaría sumada a un componente oculto o se crearía un duplicado.
        $componente = ComponenteAlmacen::withTrashed()
            ->where('empresa_id', $empresaId)
            ->where('nombre', $nombre)
            ->orderByRaw('deleted_at IS NOT NULL')   // primero el que no está eliminado
            ->orderByDesc('activo')
            ->first();

        if (! $componente) {
            return ComponenteAlmacen::create([
                'empresa_id'   => $empresaId,
                'familia_id'   => $atributo->familia_id,
                'nombre'       => $nombre,
                'unidad'       => 'unidad',
                'stock_actual' => 0,
                'activo'       => true,
                'creado_por'   => $actor->id,
            ]);
        }

        if ($componente->trashed()) {
            $componente->restore();
        }

        if (! $componente->activo) {
            $componente->update(['activo' => true]);
        }

        // Si la característica ya usa una familia y el componente todavía no
        // tiene, se le asigna (queda ordenado en el almacén).
        if (! $componente->familia_id && $atributo->familia_id) {
            $componente->update(['familia_id' => $atributo->familia_id]);
        }

        return $componente;
    }

    /**
     * Da de alta $cantidad unidades de una pieza NUEVA (comprada, nunca
     * estuvo en ningún equipo) que corresponde a un atributo de equipo
     * conocido (ej. RAM, disco) — a diferencia de un componente manual sin
     * atributo (ver PiezaManualService), esta SÍ queda vinculada a un
     * atributo y cae en el MISMO bucket que usaría una pieza equivalente
     * extraída de un equipo (mismo atributo + mismo valor = mismo nombre de
     * bucket vía bucketDeStock()), para que "RAM (8GB) nueva" y
     * "RAM (8GB) rescatada" nunca terminen en buckets distintos.
     *
     * @return Collection<int, PiezaExtraida>
     */
    public function registrarNueva(
        int $empresaId,
        AtributoEquipo $atributo,
        array $valorExtraido,
        User $actor,
        int $cantidad = 1,
        ?string $observaciones = null,
    ): Collection {
        if (! $atributo->reutilizable) {
            throw new \InvalidArgumentException(
                "El atributo «{$atributo->nombre}» no está marcado como reutilizable; no se puede registrar como pieza de almacén."
            );
        }

        return DB::transaction(function () use ($empresaId, $atributo, $valorExtraido, $actor, $cantidad, $observaciones) {
            $componente = $this->bucketDeStock($empresaId, $atributo, $valorExtraido, $actor);
            $componente->registrarEntrada($cantidad, $actor, 'Alta de pieza nueva', $observaciones);

            $piezas = collect();

            for ($i = 0; $i < $cantidad; $i++) {
                $pieza = PiezaExtraida::create([
                    'empresa_id'            => $empresaId,
                    'equipo_origen_id'      => null,
                    'atributo_id'           => $atributo->id,
                    'valor_extraido'        => $valorExtraido,
                    'reutilizable'          => true,
                    'condicion'             => PiezaExtraida::CONDICION_NUEVO,
                    'estado'                => PiezaExtraida::ESTADO_EN_ALMACEN,
                    'componente_almacen_id' => $componente->id,
                    'motivo'                => PiezaExtraida::MOTIVO_REGISTRO_MANUAL,
                    'extraido_por'          => $actor->id,
                    'observaciones'         => $observaciones,
                ]);

                $pieza->movimientos()->create([
                    'tipo'           => PiezaExtraidaMovimiento::TIPO_INGRESO_ALMACEN,
                    'registrado_por' => $actor->id,
                    'observaciones'  => $observaciones,
                ]);

                $piezas->push($pieza);
            }

            return $piezas;
        });
    }
}
