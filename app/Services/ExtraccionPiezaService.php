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
    ): PiezaExtraida {
        if (! $atributo->reutilizable) {
            throw new \InvalidArgumentException(
                "El atributo «{$atributo->nombre}» no está marcado como reutilizable; no se puede extraer como pieza."
            );
        }

        if (! in_array($motivo, [PiezaExtraida::MOTIVO_BAJA_EQUIPO, PiezaExtraida::MOTIVO_SUSTITUCION_REPARACION], true)) {
            throw new \InvalidArgumentException("Motivo de extracción inválido: {$motivo}");
        }

        if (! in_array($destino, [PiezaExtraida::ESTADO_EN_ALMACEN, PiezaExtraida::ESTADO_EN_DEPOSITO], true)) {
            throw new \InvalidArgumentException("Destino de extracción inválido: {$destino}");
        }

        if ($destino === PiezaExtraida::ESTADO_EN_DEPOSITO && ! $deposito) {
            throw new \InvalidArgumentException('Una pieza enviada a Depósito necesita indicar cuál depósito.');
        }

        $vaAlmacen = $destino === PiezaExtraida::ESTADO_EN_ALMACEN;

        return DB::transaction(function () use (
            $equipoOrigen, $atributo, $valorExtraido, $vaAlmacen, $actor, $motivo,
            $deposito, $mantenimiento, $observaciones, $onDesactivar, $grupoInstancia,
        ) {
            $onDesactivar();

            $componente = null;
            if ($vaAlmacen) {
                $componente = $this->bucketDeStock($equipoOrigen, $atributo, $valorExtraido, $actor);
                $componente->registrarEntrada(
                    1,
                    $actor,
                    $motivo === PiezaExtraida::MOTIVO_BAJA_EQUIPO
                        ? 'Rescate por baja de equipo'
                        : 'Rescate por sustitución en reparación',
                );
            }

            $pieza = PiezaExtraida::create([
                'empresa_id'            => $equipoOrigen->empresa_id,
                'equipo_origen_id'      => $equipoOrigen->id,
                'atributo_id'           => $atributo->id,
                'grupo_instancia_id'    => $grupoInstancia?->id,
                'valor_extraido'        => $valorExtraido,
                'reutilizable'          => $vaAlmacen,
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
     * Encuentra o crea el bucket de stock en Almacén de Componentes donde
     * cae esta pieza, agrupado por nombre (atributo + valor legible) para
     * que, por ejemplo, "RAM (8GB)" y "RAM (16GB)" no se mezclen en el mismo
     * stock.
     */
    private function bucketDeStock(Equipo $equipoOrigen, AtributoEquipo $atributo, array $valorExtraido, User $actor): ComponenteAlmacen
    {
        $nombre = $atributo->describirValor($valorExtraido);

        return ComponenteAlmacen::firstOrCreate(
            [
                'empresa_id' => $equipoOrigen->empresa_id,
                'nombre'     => $nombre,
            ],
            [
                'unidad'       => 'unidad',
                'stock_actual' => 0,
                'activo'       => true,
                'creado_por'   => $actor->id,
            ]
        );
    }
}
