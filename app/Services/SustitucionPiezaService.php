<?php

namespace App\Services;

use App\Models\AtributoEquipo;
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
 * SustitucionPiezaService
 * ─────────────────────────────────────────────────────────────────────────────
 * Cubre el lado de INSTALAR durante una reparación: toma una pieza rescatada
 * (PiezaExtraida en estado 'en_almacen') y la pone en otro equipo, actualiza
 * el atributo correspondiente del equipo receptor, descuenta el stock de
 * Almacén y deja la traza (estado, equipo_destino_id, movimiento).
 *
 * "agregar" solo aplica a atributos tipo grupo (ej: una laptop con una RAM
 * libre a la que se le suma otra) — queda junto a lo que ya tenía. "sustituir"
 * reemplaza un valor/instancia existente (único modo posible en un atributo
 * simple, que solo admite un valor a la vez).
 *
 * El retiro de la pieza vieja/dañada que se está reemplazando usa
 * ExtraccionPiezaService (motivo sustitucion_reparacion) — ver los métodos
 * retirar()/retirarDeGrupo(), que son un simple passthrough para que la UI
 * de la reparación dependa de un solo servicio.
 */
class SustitucionPiezaService
{
    const MODO_AGREGAR   = 'agregar';
    const MODO_SUSTITUIR = 'sustituir';

    public function __construct(private ExtraccionPiezaService $extraccion)
    {
    }

    /**
     * Piezas rescatadas disponibles en Almacén que encajan en este equipo
     * (mismo atributo → misma categoría de equipo, misma empresa). Fuente
     * del selector "¿qué pieza vas a instalar?".
     */
    public function piezasDisponibles(Equipo $equipoDestino): Collection
    {
        return PiezaExtraida::with('atributo')
            ->where('empresa_id', $equipoDestino->empresa_id)
            ->where('estado', PiezaExtraida::ESTADO_EN_ALMACEN)
            ->whereHas('atributo', fn ($q) => $q->where('categoria_id', $equipoDestino->categoria_id))
            ->get();
    }

    /** Retira un valor simple del equipo (pieza vieja/dañada que se está reemplazando). */
    public function retirar(
        Equipo $equipo,
        AtributoEquipo $atributo,
        string $destino,
        User $actor,
        ?Deposito $deposito = null,
        ?Mantenimiento $mantenimiento = null,
        ?string $observaciones = null,
    ): PiezaExtraida {
        return $this->extraccion->extraerDeAtributoSimple(
            $equipo, $atributo, $destino, $actor,
            PiezaExtraida::MOTIVO_SUSTITUCION_REPARACION, $deposito, $mantenimiento, $observaciones,
        );
    }

    /** Retira una instancia de grupo del equipo (ej: una de varias RAM). */
    public function retirarDeGrupo(
        Equipo $equipo,
        EquipoAtributoGrupoInstancia $instancia,
        string $destino,
        User $actor,
        ?Deposito $deposito = null,
        ?Mantenimiento $mantenimiento = null,
        ?string $observaciones = null,
    ): PiezaExtraida {
        return $this->extraccion->extraerDeGrupoInstancia(
            $equipo, $instancia, $destino, $actor,
            PiezaExtraida::MOTIVO_SUSTITUCION_REPARACION, $deposito, $mantenimiento, $observaciones,
        );
    }

    /**
     * Instala una pieza rescatada en el equipo receptor.
     *
     * @param int|null $instanciaASustituirId Requerido cuando $modo = sustituir
     *        y el atributo es tipo 'group': cuál instancia vigente reemplaza.
     */
    public function instalar(
        PiezaExtraida $pieza,
        Equipo $equipoDestino,
        string $modo,
        User $actor,
        ?Mantenimiento $mantenimiento = null,
        ?int $instanciaASustituirId = null,
    ): void {
        if ($pieza->estado !== PiezaExtraida::ESTADO_EN_ALMACEN) {
            throw new \InvalidArgumentException('Esta pieza no está disponible en Almacén (ya fue instalada o descartada).');
        }

        $atributo = $pieza->atributo;

        if ($atributo->categoria_id !== $equipoDestino->categoria_id) {
            throw new \InvalidArgumentException("La pieza «{$atributo->nombre}» no es compatible con la categoría de este equipo.");
        }

        if (! in_array($modo, [self::MODO_AGREGAR, self::MODO_SUSTITUIR], true)) {
            throw new \InvalidArgumentException("Modo de instalación inválido: {$modo}");
        }

        if ($modo === self::MODO_AGREGAR && ! $atributo->esGrupo()) {
            throw new \InvalidArgumentException('Solo un atributo tipo grupo se puede "agregar" sin reemplazar nada.');
        }

        DB::transaction(function () use ($pieza, $equipoDestino, $modo, $actor, $mantenimiento, $instanciaASustituirId, $atributo) {
            $pieza->componenteAlmacen?->registrarSalida(
                1, $actor, "Instalación en equipo #{$equipoDestino->id}", $mantenimiento
            );

            if ($atributo->esGrupo()) {
                $this->aplicarEnGrupo($equipoDestino, $atributo->id, $pieza->valor_extraido, $modo, $instanciaASustituirId, $actor);
            } else {
                $this->aplicarEnSimple($equipoDestino, $atributo->id, $pieza->valor_extraido, $actor);
            }

            $pieza->update([
                'estado'            => PiezaExtraida::ESTADO_INSTALADA,
                'equipo_destino_id' => $equipoDestino->id,
            ]);

            $pieza->movimientos()->create([
                'tipo'                  => PiezaExtraidaMovimiento::TIPO_INSTALACION,
                'equipo_relacionado_id' => $equipoDestino->id,
                'mantenimiento_id'      => $mantenimiento?->id,
                'registrado_por'        => $actor->id,
            ]);
        });
    }

    /** Aplica el valor de la pieza sobre un atributo simple (único modo posible: sustituir el valor vigente). */
    private function aplicarEnSimple(Equipo $equipo, int $atributoId, array $valorExtraido, User $actor): void
    {
        EquipoAtributoValor::where('equipo_id', $equipo->id)
            ->where('atributo_id', $atributoId)
            ->where('es_actual', true)
            ->update(['es_actual' => false]);

        EquipoAtributoValor::create([
            'equipo_id'   => $equipo->id,
            'atributo_id' => $atributoId,
            'valor'       => $valorExtraido['valor'] ?? '',
            'es_actual'   => true,
            'creado_por'  => $actor->id,
        ]);
    }

    /**
     * Aplica el valor de la pieza sobre un atributo tipo grupo — versiona
     * TODO el conjunto de instancias vigentes (mismo patrón que
     * EditarEquipo::guardar()): las marca es_actual=false y recrea el
     * conjunto completo, agregando o sustituyendo según corresponda.
     */
    private function aplicarEnGrupo(
        Equipo $equipo,
        int $atributoId,
        array $valorExtraido,
        string $modo,
        ?int $instanciaASustituirId,
        User $actor,
    ): void {
        $instanciasActuales = EquipoAtributoGrupoInstancia::where('equipo_id', $equipo->id)
            ->where('atributo_id', $atributoId)
            ->where('es_actual', true)
            ->orderBy('orden')
            ->get();

        if ($modo === self::MODO_SUSTITUIR) {
            if (! $instanciaASustituirId || ! $instanciasActuales->contains('id', $instanciaASustituirId)) {
                throw new \InvalidArgumentException('Selecciona cuál instancia vigente reemplaza esta pieza.');
            }
        }

        $instanciasActuales->each(fn ($i) => $i->update(['es_actual' => false]));

        $orden = 0;
        foreach ($instanciasActuales as $instancia) {
            if ($modo === self::MODO_SUSTITUIR && $instancia->id === $instanciaASustituirId) {
                continue; // esta la reemplaza la pieza nueva
            }

            EquipoAtributoGrupoInstancia::create([
                'equipo_id'   => $equipo->id,
                'atributo_id' => $atributoId,
                'valores'     => $instancia->valores,
                'orden'       => $orden++,
                'es_actual'   => true,
                'creado_por'  => $actor->id,
            ]);
        }

        EquipoAtributoGrupoInstancia::create([
            'equipo_id'   => $equipo->id,
            'atributo_id' => $atributoId,
            'valores'     => $valorExtraido,
            'orden'       => $orden,
            'es_actual'   => true,
            'creado_por'  => $actor->id,
        ]);
    }
}
