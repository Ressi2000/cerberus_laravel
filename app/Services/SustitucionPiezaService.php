<?php

namespace App\Services;

use App\Models\AtributoEquipo;
use App\Models\Deposito;
use App\Models\Equipo;
use App\Models\EquipoAtributoGrupoInstancia;
use App\Models\EquipoAtributoValor;
use App\Models\Mantenimiento;
use App\Models\MantenimientoComponente;
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
     * Piezas rescatadas disponibles en Almacén que le corresponden a este equipo
     * (mismo atributo → misma categoría de equipo, misma empresa). Fuente
     * del selector "¿qué pieza vas a instalar?".
     */
    public function piezasDisponibles(Equipo $equipoDestino): Collection
    {
        // Familias que usa la categoría del equipo: una RAM de la familia RAM
        // le sirve venga de la categoría que venga (ver atributoDestino()).
        $familias = AtributoEquipo::where('categoria_id', $equipoDestino->categoria_id)
            ->whereNotNull('familia_id')
            ->pluck('familia_id');

        return PiezaExtraida::with(['atributo', 'equipoOrigen'])
            ->where('empresa_id', $equipoDestino->empresa_id)
            ->where('estado', PiezaExtraida::ESTADO_EN_ALMACEN)
            ->whereHas('atributo', fn ($q) => $q
                ->where('categoria_id', $equipoDestino->categoria_id)
                ->when($familias->isNotEmpty(), fn ($q) => $q->orWhereIn('familia_id', $familias)))
            ->get();
    }

    /**
     * Característica del equipo destino donde se instala la pieza:
     *  - Si la pieza es de una familia, la característica de la categoría
     *    del equipo que usa esa misma familia (la RAM de un Desktop se instala
     *    en la "RAM" de una Laptop si ambas usan la familia RAM).
     *  - Si no, la misma característica de la pieza, solo en su categoría.
     * Null si al equipo no le corresponde esa pieza.
     */
    public function atributoDestino(PiezaExtraida $pieza, Equipo $equipoDestino): ?AtributoEquipo
    {
        $atributo = $pieza->atributo;

        if (! $atributo) {
            return null;
        }

        if ($atributo->usaFamilia()) {
            return AtributoEquipo::where('categoria_id', $equipoDestino->categoria_id)
                ->where('familia_id', $atributo->familia_id)
                ->first();
        }

        return (int) $atributo->categoria_id === (int) $equipoDestino->categoria_id ? $atributo : null;
    }

    /** Retira un valor simple del equipo (pieza vieja/dañada que se está reemplazando, o que se mejora). */
    public function retirar(
        Equipo $equipo,
        AtributoEquipo $atributo,
        string $destino,
        User $actor,
        ?Deposito $deposito = null,
        ?Mantenimiento $mantenimiento = null,
        ?string $observaciones = null,
        ?int $piezaInstaladaId = null,
        ?string $serial = null,
    ): PiezaExtraida {
        return $this->extraccion->extraerDeAtributoSimple(
            $equipo, $atributo, $destino, $actor,
            $this->motivoSegunMantenimiento($mantenimiento), $deposito, $mantenimiento, $observaciones, $piezaInstaladaId, $serial,
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
        ?int $piezaInstaladaId = null,
        ?string $serial = null,
    ): PiezaExtraida {
        return $this->extraccion->extraerDeGrupoInstancia(
            $equipo, $instancia, $destino, $actor,
            $this->motivoSegunMantenimiento($mantenimiento), $deposito, $mantenimiento, $observaciones, $piezaInstaladaId, $serial,
        );
    }

    /** Retira del equipo una pieza del almacén que no está en su ficha (ver ExtraccionPiezaService::retirarPiezaInstalada). */
    public function retirarPieza(
        PiezaExtraida $pieza,
        Equipo $equipo,
        string $destino,
        User $actor,
        ?Deposito $deposito = null,
        ?Mantenimiento $mantenimiento = null,
        ?string $observaciones = null,
    ): PiezaExtraida {
        return $this->extraccion->retirarPiezaInstalada(
            $pieza, $equipo, $destino, $actor,
            $this->motivoSegunMantenimiento($mantenimiento), $deposito, $mantenimiento, $observaciones,
        );
    }

    /**
     * Las piezas del almacén instaladas en el equipo que NO están en su
     * ficha, y por eso no salen al retirar una característica:
     * - sin característica, o cuya familia no está vinculada en esta categoría;
     * - o que la categoría sí usa, pero ninguna entrada actual de la ficha la
     *   refleja (ej. se instaló antes de vincular la familia).
     * Con varias iguales, cada entrada de la ficha "cubre" una sola pieza.
     */
    public function piezasFueraDeFicha(Equipo $equipo)
    {
        $piezas = PiezaExtraida::where('equipo_destino_id', $equipo->id)
            ->where('estado', PiezaExtraida::ESTADO_INSTALADA)
            ->with(['atributo', 'componenteAlmacen'])
            ->orderBy('id')
            ->get();

        $fichaPorAtributo = [];   // atributo_id => [valores normalizados de cada entrada vigente]
        $fuera = collect();

        foreach ($piezas as $pieza) {
            $destino = $this->atributoDestino($pieza, $equipo);

            if (! $destino) {
                $fuera->push($pieza);
                continue;
            }

            $fichaPorAtributo[$destino->id] ??= $this->entradasVigentes($equipo, $destino);
            $propios = $this->normalizar($pieza->valor_extraido ?? []);

            $i = collect($fichaPorAtributo[$destino->id])
                ->search(fn ($entrada) => $propios !== [] && array_intersect_assoc($propios, $entrada) === $propios);

            if ($i === false) {
                $fuera->push($pieza);
            } else {
                unset($fichaPorAtributo[$destino->id][$i]); // esa entrada ya cubre a esta pieza
            }
        }

        return $fuera->values();
    }

    /** Valores vigentes de una característica en el equipo (una entrada por instancia). */
    private function entradasVigentes(Equipo $equipo, AtributoEquipo $atributo): array
    {
        if ($atributo->esGrupo()) {
            return EquipoAtributoGrupoInstancia::where('equipo_id', $equipo->id)
                ->where('atributo_id', $atributo->id)
                ->where('es_actual', true)
                ->pluck('valores')
                ->map(fn ($v) => $this->normalizar(is_array($v) ? $v : (json_decode((string) $v, true) ?: [])))
                ->all();
        }

        $valor = EquipoAtributoValor::where('equipo_id', $equipo->id)
            ->where('atributo_id', $atributo->id)
            ->where('es_actual', true)
            ->value('valor');

        return $valor === null ? [] : [$this->normalizar(['valor' => $valor])];
    }

    private function normalizar(array $valores): array
    {
        $limpio = collect($valores)
            ->reject(fn ($v) => $v === null || $v === '')
            ->map(fn ($v) => trim((string) $v))
            ->all();
        ksort($limpio);

        return $limpio;
    }

    /**
     * Mismo retiro de pieza, pero el motivo que queda en el historial
     * depende del tipo de caso — un Preventivo no es una reparación, así
     * que no debe decir "Sustitución en reparación" (ver mensaje de la
     * usuaria: subir la RAM porque el equipo está lento es un mantenimiento,
     * no algo que se rompió).
     */
    private function motivoSegunMantenimiento(?Mantenimiento $mantenimiento): string
    {
        return $mantenimiento?->esPreventivo()
            ? PiezaExtraida::MOTIVO_SUSTITUCION_PREVENTIVO
            : PiezaExtraida::MOTIVO_SUSTITUCION_REPARACION;
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
        string $destinoPiezaSaliente = PiezaExtraida::ESTADO_EN_ALMACEN,
        ?Deposito $depositoPiezaSaliente = null,
        ?string $serialSaliente = null,
        ?MantenimientoComponente $pendiente = null,
    ): void {
        if ($pieza->estado !== PiezaExtraida::ESTADO_EN_ALMACEN) {
            throw new \InvalidArgumentException('Esta pieza no está disponible en Almacén (ya fue instalada o descartada).');
        }

        if ((int) $pieza->empresa_id !== (int) $equipoDestino->empresa_id) {
            throw new \InvalidArgumentException('La pieza y el equipo deben ser de la misma empresa.');
        }

        $atributo = $pieza->atributo;

        // Las piezas registradas a mano en Almacén (sin atributo) no saben
        // qué característica del equipo reemplazan: no se instalan por aquí.
        if (! $atributo) {
            throw new \InvalidArgumentException('Esta pieza no está ligada a una característica del equipo; no se puede instalar desde aquí.');
        }

        // Desde aquí, $atributo es la característica DEL EQUIPO destino
        // (puede ser otra categoría que la de origen si comparten familia).
        $atributo = $this->atributoDestino($pieza, $equipoDestino);

        if (! $atributo) {
            throw new \InvalidArgumentException("La pieza «{$pieza->atributo->nombre}» no corresponde a ninguna característica de la categoría de este equipo.");
        }

        if (! in_array($modo, [self::MODO_AGREGAR, self::MODO_SUSTITUIR], true)) {
            throw new \InvalidArgumentException("Modo de instalación inválido: {$modo}");
        }

        if ($modo === self::MODO_AGREGAR && ! $atributo->esGrupo()) {
            throw new \InvalidArgumentException('Solo un atributo tipo grupo se puede "agregar" sin reemplazar nada.');
        }

        DB::transaction(function () use ($pieza, $equipoDestino, $modo, $actor, $mantenimiento, $instanciaASustituirId, $atributo, $destinoPiezaSaliente, $depositoPiezaSaliente, $serialSaliente, $pendiente) {
            if (! $pieza->componenteAlmacen) {
                throw new \InvalidArgumentException('Esta pieza no está asociada a ningún componente del almacén.');
            }

            // Sale ESTA pieza (no una unidad cualquiera): registrarSalida()
            // bloquea la pieza y verifica que siga en el almacén, así dos
            // instalaciones simultáneas no pueden usar la misma unidad.
            $pieza->componenteAlmacen->registrarSalida(
                1, $actor, "Instalación en equipo #{$equipoDestino->id}", $mantenimiento, [$pieza]
            );

            // Al sustituir, la pieza que SALE del equipo no se pierde: se
            // registra igual que con "Retirar" (Almacén si está buena,
            // Depósito si está dañada), con su propio historial.
            if ($modo === self::MODO_SUSTITUIR) {
                $this->retirarPiezaSaliente(
                    $equipoDestino, $atributo, $instanciaASustituirId, $destinoPiezaSaliente,
                    $depositoPiezaSaliente, $actor, $mantenimiento,
                    $serialSaliente,
                );
            }

            if ($atributo->esGrupo()) {
                // La instancia sustituida ya salió del equipo: la nueva se agrega.
                $this->aplicarEnGrupo($equipoDestino, $atributo->id, $pieza->valor_extraido, self::MODO_AGREGAR, null, $actor);
            } else {
                $this->aplicarEnSimple($equipoDestino, $atributo->id, $pieza->valor_extraido, $actor);
            }

            $pieza->update([
                'estado'            => PiezaExtraida::ESTADO_INSTALADA,
                'equipo_destino_id' => $equipoDestino->id,
            ]);

            // Queda también en "lo usado del almacén" del caso, con la pieza
            // exacta (y si venía de una solicitud pendiente, la cierra).
            $mantenimiento?->anotarUsoDePieza($pieza, $actor, $pendiente);

            $pieza->movimientos()->create([
                'tipo'                  => PiezaExtraidaMovimiento::TIPO_INSTALACION,
                'equipo_relacionado_id' => $equipoDestino->id,
                'mantenimiento_id'      => $mantenimiento?->id,
                'registrado_por'        => $actor->id,
            ]);
        });
    }

    /**
     * Saca del equipo la pieza que se está reemplazando y la deja trazada.
     * Atributo de grupo: la instancia elegida (obligatoria). Atributo simple:
     * el valor vigente, si el equipo tiene uno (si no tenía, no sale nada).
     */
    private function retirarPiezaSaliente(
        Equipo $equipo,
        AtributoEquipo $atributo,
        ?int $instanciaId,
        string $destino,
        ?Deposito $deposito,
        User $actor,
        ?Mantenimiento $mantenimiento,
        ?string $serial = null,
    ): void {
        if ($atributo->esGrupo()) {
            $instancia = $instanciaId
                ? EquipoAtributoGrupoInstancia::where('equipo_id', $equipo->id)
                    ->where('atributo_id', $atributo->id)
                    ->where('es_actual', true)
                    ->find($instanciaId)
                : null;

            if (! $instancia) {
                throw new \InvalidArgumentException('Selecciona cuál instancia vigente reemplaza esta pieza.');
            }

            $this->retirarDeGrupo($equipo, $instancia, $destino, $actor, $deposito, $mantenimiento, 'Sustituida por otra pieza.', serial: $serial);

            return;
        }

        $tieneValor = EquipoAtributoValor::where('equipo_id', $equipo->id)
            ->where('atributo_id', $atributo->id)
            ->where('es_actual', true)
            ->exists();

        if ($tieneValor) {
            $this->retirar($equipo, $atributo, $destino, $actor, $deposito, $mantenimiento, 'Sustituida por otra pieza.', serial: $serial);
        }
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
