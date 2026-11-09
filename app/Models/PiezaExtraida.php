<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo PiezaExtraida
 *
 * Identidad individual de una pieza física extraída de un equipo (baja o
 * sustitución en reparación). Ver docblock de la migración para el porqué
 * de separarla de ComponenteAlmacen (stock agregado, sin identidad propia).
 */
class PiezaExtraida extends Model
{
    use Auditable;

    protected $table = 'piezas_extraidas';

    protected $fillable = [
        'empresa_id',
        'equipo_origen_id',
        'atributo_id',
        'grupo_instancia_id',
        'valor_extraido',
        'reutilizable',
        'condicion',
        'identificador',
        'estado',
        'componente_almacen_id',
        'deposito_id',
        'equipo_destino_id',
        'mantenimiento_id',
        'motivo',
        'extraido_por',
        'observaciones',
    ];

    protected $casts = [
        'valor_extraido' => 'array',
        'reutilizable'   => 'boolean',
    ];

    // ── Estados ───────────────────────────────────────────────────────────────
    const ESTADO_EN_ALMACEN = 'en_almacen';
    const ESTADO_INSTALADA  = 'instalada';
    const ESTADO_EN_DEPOSITO = 'en_deposito';

    const ESTADOS = [
        self::ESTADO_EN_ALMACEN  => 'En almacén',
        self::ESTADO_INSTALADA   => 'Instalada',
        self::ESTADO_EN_DEPOSITO => 'En depósito',
    ];

    // ── Motivos de extracción ────────────────────────────────────────────────
    const MOTIVO_BAJA_EQUIPO            = 'baja_equipo';
    const MOTIVO_SUSTITUCION_REPARACION = 'sustitucion_reparacion';
    /** Pieza sacada de un equipo que sigue activo — no pasó por baja ni por una reparación. */
    const MOTIVO_DESARME_MANUAL         = 'desarme_manual';
    /** Pieza dada de alta a mano en Almacén — no vino de ningún atributo de equipo. */
    const MOTIVO_REGISTRO_MANUAL        = 'registro_manual';
    /** Retiro/instalación de pieza durante un Preventivo (ej. subir la RAM) — no es una reparación. */
    const MOTIVO_SUSTITUCION_PREVENTIVO = 'sustitucion_preventivo';

    const MOTIVOS = [
        self::MOTIVO_BAJA_EQUIPO            => 'Baja de equipo',
        self::MOTIVO_SUSTITUCION_REPARACION => 'Sustitución en reparación',
        self::MOTIVO_DESARME_MANUAL         => 'Desarme manual (equipo operativo)',
        self::MOTIVO_REGISTRO_MANUAL        => 'Registro manual en Almacén',
        self::MOTIVO_SUSTITUCION_PREVENTIVO => 'Sustitución/mejora en mantenimiento preventivo',
    ];

    // ── Condición de la pieza ────────────────────────────────────────────────
    const CONDICION_NUEVO       = 'nuevo';
    const CONDICION_REUTILIZADO = 'reutilizado';

    const CONDICIONES = [
        self::CONDICION_NUEVO       => 'Nuevo',
        self::CONDICION_REUTILIZADO => 'Reutilizado',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /** Equipo del que se extrajo la pieza. */
    public function equipoOrigen(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_origen_id');
    }

    /** Atributo que describe esta pieza (ej: "RAM", "Disco duro"). */
    public function atributo(): BelongsTo
    {
        return $this->belongsTo(AtributoEquipo::class, 'atributo_id');
    }

    /** Instancia de grupo de origen, si el atributo es tipo 'group' (ej: uno de varios discos). */
    public function grupoInstancia(): BelongsTo
    {
        return $this->belongsTo(EquipoAtributoGrupoInstancia::class, 'grupo_instancia_id');
    }

    /** Bucket de stock en el que quedó sumada, si es reutilizable y está en almacén. */
    public function componenteAlmacen(): BelongsTo
    {
        return $this->belongsTo(ComponenteAlmacen::class, 'componente_almacen_id');
    }

    /** Depósito donde quedó guardada, si fue descartada o trasladada allí. */
    public function deposito(): BelongsTo
    {
        return $this->belongsTo(Deposito::class);
    }

    /** Equipo en el que quedó instalada, si se reutilizó en otro equipo. */
    public function equipoDestino(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_destino_id');
    }

    /** Mantenimiento/reparación durante el cual se extrajo la pieza, si aplica. */
    public function mantenimiento(): BelongsTo
    {
        return $this->belongsTo(Mantenimiento::class);
    }

    public function extraidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'extraido_por');
    }

    /** Historial completo de movimientos de esta pieza (kardex). */
    public function movimientos(): HasMany
    {
        return $this->hasMany(PiezaExtraidaMovimiento::class, 'pieza_id')->latest();
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

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

    public function scopeDeEquipo(Builder $query, int $equipoId): Builder
    {
        return $query->where('equipo_origen_id', $equipoId);
    }

    public function scopeEnAlmacen(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_EN_ALMACEN);
    }

    public function scopeInstaladas(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_INSTALADA);
    }

    public function scopeEnDeposito(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_EN_DEPOSITO);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Nombre legible de la pieza. Si nació de un atributo EAV, usa el
     * atributo; si es un registro manual (sin atributo), cae al nombre del
     * bucket de Almacén al que pertenece — y si tiene identificador propio,
     * lo agrega para distinguirla de otras unidades idénticas.
     */
    public function nombre(): string
    {
        $base = $this->atributo?->nombre ?? $this->componenteAlmacen?->nombre ?? 'Pieza';

        return $this->identificador ? "{$base} ({$this->identificador})" : $base;
    }

    public function esManual(): bool
    {
        return $this->atributo_id === null;
    }

    public function labelCondicion(): string
    {
        return self::CONDICIONES[$this->condicion] ?? $this->condicion;
    }

    public function labelEstado(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }
}
