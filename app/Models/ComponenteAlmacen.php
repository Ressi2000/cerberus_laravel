<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * Modelo ComponenteAlmacen
 *
 * Catálogo de componentes/repuestos en stock, propio de cada empresa (no se
 * comparte entre empresas). `stock_actual` es la cantidad vigente; el kardex
 * completo de entradas y salidas vive en movimientos_componentes — nunca se
 * edita stock_actual a mano, siempre a través de registrarEntrada()/registrarSalida().
 */
class ComponenteAlmacen extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'componentes_almacen';

    protected $fillable = [
        'empresa_id',
        'familia_id',
        'deposito_id',
        'nombre',
        'descripcion',
        'unidad',
        'stock_actual',
        'activo',
        'creado_por',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Relaciones
    // ─────────────────────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    /** Familia a la que pertenece esta variante (RAM, Discos, Bisagras…). */
    public function familia()
    {
        return $this->belongsTo(FamiliaComponente::class, 'familia_id');
    }

    /** Depósito físico donde vive este stock. */
    public function deposito()
    {
        return $this->belongsTo(Deposito::class);
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoComponente::class, 'componente_id')->latest();
    }

    public function usosEnMantenimiento()
    {
        return $this->hasMany(MantenimientoComponente::class, 'componente_id');
    }

    /** Piezas individuales rescatadas que suman a este bucket de stock. */
    public function piezasExtraidas()
    {
        return $this->hasMany(PiezaExtraida::class, 'componente_almacen_id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────────────────

    public function scopeVisiblePara(Builder $query, User $actor): Builder
    {
        if ($actor->hasRole('Administrador')) {
            return $query;
        }

        if ($actor->hasRole('Analista') && $actor->empresa_activa_id) {
            return $query->where('empresa_id', $actor->empresa_activa_id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers de negocio
    // ─────────────────────────────────────────────────────────────────────────

    /** ¿Se controla a granel (solo cantidad)? Sin familia se trata como por unidad. */
    /**
     * Ficha de esta variante ({id de campo de la familia => valor}): de una
     * de sus piezas o, si todavía no tiene, de su nombre (ver
     * FamiliaComponente::valoresDesdeNombre). Vacío si la familia no tiene
     * ficha o la variante se creó con un nombre libre.
     */
    public function valoresFicha(): array
    {
        $familia = $this->familia;
        if (! $familia?->tieneFicha()) {
            return [];
        }

        $campos  = collect($familia->campos)->pluck('id');
        $pieza   = $this->piezasExtraidas()->whereNotNull('valor_extraido')->latest('id')->first();
        $valores = collect($pieza?->valor_extraido ?? [])->only($campos)->filter(fn ($v) => $v !== null && $v !== '');

        if ($valores->isEmpty()) {
            $valores = collect($familia->valoresDesdeNombre($this->nombre));
        }

        return $valores->map(fn ($v) => (string) $v)->all();
    }

    public function esAGranel(): bool
    {
        return (bool) $this->familia?->esAGranel();
    }

    /**
     * Pone el componente en una familia. Si la familia es "a granel", las
     * piezas que estaban en el almacén pasan a contarse solo por cantidad
     * (el stock no cambia; las piezas ya usadas conservan su historial).
     * Devuelve cuántas piezas se agruparon.
     */
    public function asignarFamilia(FamiliaComponente $familia, User $actor): int
    {
        return DB::transaction(function () use ($familia, $actor) {
            $this->update(['familia_id' => $familia->id]);
            $this->setRelation('familia', $familia);

            return $familia->esAGranel() ? $this->agruparPiezasEnAlmacen($actor) : 0;
        });
    }

    /**
     * Las piezas individuales que siguen en el almacén dejan de serlo y pasan
     * a ser parte de la cantidad en stock (estado "agrupada"). stock_actual
     * no cambia: esas unidades pasan a contarse como "sin identidad".
     */
    public function agruparPiezasEnAlmacen(User $actor): int
    {
        $piezas = $this->piezasEnAlmacen()->get();

        foreach ($piezas as $pieza) {
            $pieza->update(['estado' => PiezaExtraida::ESTADO_AGRUPADA]);
            $pieza->movimientos()->create([
                'tipo'           => PiezaExtraidaMovimiento::TIPO_AGRUPADA,
                'registrado_por' => $actor->id,
                'observaciones'  => "«{$this->nombre}» pasó a una familia a granel: se cuenta solo por cantidad.",
            ]);
        }

        return $piezas->count();
    }

    /**
     * Desactiva el componente (deja de ofrecerse para pedir). No se puede si
     * todavía tiene stock — habría que darle salida primero — ni si algún
     * caso lo está esperando como "Pendiente".
     */
    public function desactivar(): void
    {
        if ($this->stock_actual > 0) {
            throw new \InvalidArgumentException(
                "«{$this->nombre}» todavía tiene {$this->stock_actual} {$this->unidad} en stock. Regístrales una salida antes de desactivarlo."
            );
        }

        $casos = $this->usosEnMantenimiento()
            ->where('estado', MantenimientoComponente::ESTADO_PENDIENTE)
            ->pluck('mantenimiento_id')
            ->unique();

        if ($casos->isNotEmpty()) {
            throw new \InvalidArgumentException(
                "«{$this->nombre}» está pendiente de entrega en el/los caso(s) #" . $casos->implode(', #') . '. No se puede desactivar.'
            );
        }

        $this->update(['activo' => false]);
    }

    public function tieneStockSuficiente(int $cantidad): bool
    {
        return $this->stock_actual >= $cantidad;
    }

    /** Registra una entrada de stock (compra, ajuste, etc.) y suma al stock actual. */
    public function registrarEntrada(int $cantidad, User $actor, ?string $motivo = null, ?string $observaciones = null): MovimientoComponente
    {
        return DB::transaction(function () use ($cantidad, $actor, $motivo, $observaciones) {
            $this->increment('stock_actual', $cantidad);

            return $this->movimientos()->create([
                'tipo'              => 'Entrada',
                'cantidad'          => $cantidad,
                'motivo'            => $motivo,
                'registrado_por_id' => $actor->id,
                'observaciones'     => $observaciones,
            ]);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Stock con y sin identidad
    //
    // stock_actual cuenta TODAS las unidades. Parte de ellas tienen identidad
    // propia (una PiezaExtraida en "en_almacen" por unidad) y el resto son
    // unidades "sin identidad" (stock anterior a la trazabilidad, o un
    // componente pedido como nuevo). Regla que se debe mantener siempre:
    //
    //     stock_actual = unidades sin identidad + piezas en almacén
    //
    // Por eso ninguna salida puede bajar el stock sin decir también qué
    // piezas con identidad salen, salvo que alcancen las unidades sin
    // identidad (ver registrarSalida()).
    // ─────────────────────────────────────────────────────────────────────────

    /** Piezas con identidad que hoy están físicamente en este almacén. */
    public function piezasEnAlmacen()
    {
        return $this->piezasExtraidas()->where('estado', PiezaExtraida::ESTADO_EN_ALMACEN);
    }

    /** Unidades del stock que no tienen una pieza individual asociada. */
    public function unidadesSinIdentidad(): int
    {
        return max(0, $this->stock_actual - $this->piezasEnAlmacen()->count());
    }

    /**
     * Unidades que se pueden entregar a un caso desde "Pedir componente":
     * las sin identidad más las piezas registradas a mano. Las piezas
     * ligadas a una característica del equipo (RAM, disco...) NO cuentan:
     * esas se instalan desde el panel de Piezas, que también actualiza las
     * características del equipo receptor.
     */
    public function unidadesParaConsumo(): int
    {
        $manuales = $this->piezasEnAlmacen()->whereNull('atributo_id')->count();

        return min($this->stock_actual, $this->unidadesSinIdentidad() + $manuales);
    }

    /**
     * Piezas registradas a mano que salen al entregar $cantidad a un caso:
     * primero se usan las unidades sin identidad y, si no alcanzan, las
     * piezas manuales más antiguas. Nunca devuelve piezas ligadas a una
     * característica del equipo (ver unidadesParaConsumo()).
     */
    public function piezasParaConsumo(int $cantidad): \Illuminate\Support\Collection
    {
        $faltan = max(0, $cantidad - $this->unidadesSinIdentidad());

        if ($faltan === 0) {
            return collect();
        }

        return $this->piezasEnAlmacen()
            ->whereNull('atributo_id')
            ->oldest()
            ->orderBy('id')
            ->limit($faltan)
            ->get();
    }

    /**
     * Registra una salida de stock (consumo en un mantenimiento, ajuste,
     * daño...). $piezas son las unidades con identidad que salen en este
     * movimiento; el resto de la cantidad tiene que salir de las unidades
     * sin identidad. Quien llama es responsable de cambiar el estado de
     * esas piezas (instalada, en depósito, retirada) dentro de la misma
     * transacción.
     *
     * Bloquea la fila mientras valida, para que dos salidas simultáneas no
     * dejen el stock por debajo de cero ni usen las mismas unidades.
     *
     * @param iterable<PiezaExtraida> $piezas
     */
    public function registrarSalida(
        int $cantidad,
        User $actor,
        ?string $motivo = null,
        ?Mantenimiento $mantenimiento = null,
        iterable $piezas = [],
        ?string $observaciones = null,
    ): MovimientoComponente {
        if ($cantidad < 1) {
            throw new \InvalidArgumentException('La cantidad de la salida debe ser al menos 1.');
        }

        return DB::transaction(function () use ($cantidad, $actor, $motivo, $mantenimiento, $piezas, $observaciones) {
            $bloqueado = static::whereKey($this->id)->lockForUpdate()->firstOrFail();
            $this->stock_actual = $bloqueado->stock_actual;

            if ($this->stock_actual < $cantidad) {
                throw new \InvalidArgumentException("No hay stock suficiente. Stock actual: {$this->stock_actual}.");
            }

            $piezas = collect($piezas);
            $ids    = $piezas->pluck('id')->unique();

            if ($ids->count() !== $piezas->count()) {
                throw new \InvalidArgumentException('Una misma pieza no puede salir dos veces en el mismo movimiento.');
            }

            if ($ids->count() > $cantidad) {
                throw new \InvalidArgumentException('Seleccionaste más piezas que la cantidad de la salida.');
            }

            $validas = $this->piezasEnAlmacen()->whereIn('id', $ids)->lockForUpdate()->count();

            if ($validas !== $ids->count()) {
                throw new \InvalidArgumentException('Alguna de las piezas elegidas no es de este componente o ya no está en el almacén.');
            }

            $sinIdentidad = $this->unidadesSinIdentidad();
            $resto        = $cantidad - $ids->count();

            if ($resto > $sinIdentidad) {
                $faltan = $resto - $sinIdentidad;
                throw new \InvalidArgumentException(
                    "Este componente tiene piezas con identidad en el almacén: indica cuáles salen (faltan {$faltan})."
                );
            }

            $this->decrement('stock_actual', $cantidad);

            return $this->movimientos()->create([
                'mantenimiento_id'  => $mantenimiento?->id,
                'tipo'              => 'Salida',
                'cantidad'          => $cantidad,
                'motivo'            => $motivo,
                'registrado_por_id' => $actor->id,
                'observaciones'     => $observaciones,
            ]);
        });
    }

    /**
     * Salida manual de stock (pérdida, corrección de inventario...) sin
     * equipo ni depósito de destino. Las piezas con identidad que salen
     * quedan "retiradas", con su movimiento en el historial — así el stock
     * y las piezas en almacén siguen cuadrando.
     *
     * @param iterable<PiezaExtraida> $piezas
     */
    public function registrarAjusteSalida(
        int $cantidad,
        User $actor,
        ?string $motivo = null,
        ?string $observaciones = null,
        iterable $piezas = [],
    ): MovimientoComponente {
        $piezas = collect($piezas);

        return DB::transaction(function () use ($cantidad, $actor, $motivo, $observaciones, $piezas) {
            $movimiento = $this->registrarSalida($cantidad, $actor, $motivo ?: 'Ajuste manual', null, $piezas, $observaciones);

            foreach ($piezas as $pieza) {
                $pieza->update(['estado' => PiezaExtraida::ESTADO_RETIRADA]);

                $pieza->movimientos()->create([
                    'tipo'           => PiezaExtraidaMovimiento::TIPO_SALIDA_AJUSTE,
                    'registrado_por' => $actor->id,
                    'observaciones'  => trim(($motivo ?: 'Ajuste manual') . ($observaciones ? " — {$observaciones}" : '')),
                ]);
            }

            return $movimiento;
        });
    }
}
