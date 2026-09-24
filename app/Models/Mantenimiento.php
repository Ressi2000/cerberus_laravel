<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * Modelo Mantenimiento
 *
 * Una sola tabla para las dos intervenciones técnicas sobre un equipo —
 * distinguidas por el campo `tipo` — en vez de dos módulos separados:
 *
 *   Preventivo (Mantenimiento): Programado -> En proceso -> Completado | Cancelado
 *   Correctivo (Reparación):    Reportado -> Diagnosticado -> En reparación
 *                                -> Reparado -> Cerrado
 *                                (o Dado de baja, que reemplaza Reparado->Cerrado
 *                                 cuando el equipo no tiene arreglo)
 *
 * `esperando_componente` NO es un estado del flujo: es una bandera informativa
 * que se prende sola mientras haya algún componente pendiente de stock en
 * mantenimiento_componentes, sin interrumpir el flujo de estados de arriba.
 */
class Mantenimiento extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'mantenimientos';

    const TIPO_PREVENTIVO = 'Preventivo';
    const TIPO_CORRECTIVO = 'Correctivo';

    const ESTADOS_PREVENTIVO = ['Programado', 'En proceso', 'Completado', 'Cancelado'];
    const ESTADOS_CORRECTIVO = ['Reportado', 'Diagnosticado', 'En reparación', 'Reparado', 'Cerrado', 'Dado de baja'];

    /** Estados que cierran el caso y liberan al equipo del bloqueo. */
    const ESTADOS_TERMINALES = ['Completado', 'Cerrado', 'Dado de baja', 'Cancelado'];

    protected $fillable = [
        'empresa_id',
        'equipo_id',
        'asignacion_id',
        'estado_equipo_anterior_id',
        'tipo',
        'estado',
        'esperando_componente',
        'reportado_por_id',
        'responsable_id',
        'proveedor_externo',
        'fecha_inicio',
        'fecha_fin_estimada',
        'fecha_fin_real',
        'costo',
        'ubicacion_taller_id',
        'descripcion',
        'observaciones',
        'frecuencia_meses',
        'proxima_fecha_programada',
        'checklist',
        'falla_reportada',
        'diagnostico',
        'causa_raiz',
        'en_garantia',
        'mantenimiento_origen_id',
        'motivo_baja',
        'fecha_baja',
        'aprobado_por_id',
    ];

    protected $casts = [
        'fecha_inicio'              => 'date',
        'fecha_fin_estimada'        => 'date',
        'fecha_fin_real'            => 'date',
        'proxima_fecha_programada'  => 'date',
        'fecha_baja'                => 'date',
        'costo'                     => 'decimal:2',
        'esperando_componente'      => 'boolean',
        'en_garantia'               => 'boolean',
        'checklist'                 => 'array',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Relaciones
    // ─────────────────────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function asignacion()
    {
        return $this->belongsTo(Asignacion::class);
    }

    public function estadoEquipoAnterior()
    {
        return $this->belongsTo(EstadoEquipo::class, 'estado_equipo_anterior_id');
    }

    public function reportadoPor()
    {
        return $this->belongsTo(User::class, 'reportado_por_id');
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function ubicacionTaller()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_taller_id');
    }

    public function aprobadoPor()
    {
        return $this->belongsTo(User::class, 'aprobado_por_id');
    }

    /** Mantenimiento preventivo que detectó la falla y originó esta reparación, si aplica. */
    public function mantenimientoOrigen()
    {
        return $this->belongsTo(Mantenimiento::class, 'mantenimiento_origen_id');
    }

    /** Reparaciones que esta intervención (preventiva) originó al detectar una falla. */
    public function reparacionesOriginadas()
    {
        return $this->hasMany(Mantenimiento::class, 'mantenimiento_origen_id');
    }

    public function evidencias()
    {
        return $this->hasMany(MantenimientoEvidencia::class);
    }

    public function componentes()
    {
        return $this->hasMany(MantenimientoComponente::class);
    }

    public function componentesPendientes()
    {
        return $this->hasMany(MantenimientoComponente::class)->where('estado', 'Pendiente');
    }

    public function movimientosComponentes()
    {
        return $this->hasMany(MovimientoComponente::class);
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

    public function scopePreventivos(Builder $query): Builder
    {
        return $query->where('tipo', self::TIPO_PREVENTIVO);
    }

    public function scopeCorrectivos(Builder $query): Builder
    {
        return $query->where('tipo', self::TIPO_CORRECTIVO);
    }

    /** Casos que siguen abiertos (no llegaron a un estado terminal). */
    public function scopeAbiertos(Builder $query): Builder
    {
        return $query->whereNotIn('estado', self::ESTADOS_TERMINALES);
    }

    /** Vista "En Reparación" del sidebar: reparaciones correctivas activas. */
    public function scopeEnReparacionActiva(Builder $query): Builder
    {
        return $query->correctivos()->abiertos();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers de negocio
    // ─────────────────────────────────────────────────────────────────────────

    public function estaAbierto(): bool
    {
        return ! in_array($this->estado, self::ESTADOS_TERMINALES, true);
    }

    public function esPreventivo(): bool
    {
        return $this->tipo === self::TIPO_PREVENTIVO;
    }

    public function esCorrectivo(): bool
    {
        return $this->tipo === self::TIPO_CORRECTIVO;
    }

    /** Estados válidos según el tipo de esta intervención (para armar el select). */
    public function estadosDisponibles(): array
    {
        return $this->esPreventivo() ? self::ESTADOS_PREVENTIVO : self::ESTADOS_CORRECTIVO;
    }

    /**
     * Bloquea el equipo al iniciar el caso: guarda su estado actual (para
     * poder restaurarlo tal cual al cerrar) y lo pasa a "En mantenimiento"
     * o "En reparación" según el tipo. NO toca ninguna asignación activa.
     */
    public function bloquearEquipo(): void
    {
        $equipo = $this->equipo;

        if (! $equipo || $this->estado_equipo_anterior_id) {
            return;
        }

        $nombreBloqueo = $this->esPreventivo() ? EstadoEquipo::EN_MANTENIMIENTO : EstadoEquipo::EN_REPARACION;
        $estadoBloqueo = EstadoEquipo::where('nombre', $nombreBloqueo)->value('id');

        $this->update(['estado_equipo_anterior_id' => $equipo->estado_id]);

        if ($estadoBloqueo) {
            $equipo->update(['estado_id' => $estadoBloqueo]);
        }
    }

    /** Restaura el equipo al estado que tenía antes de bloquearse (ej. vuelve a "Asignado"). */
    public function liberarEquipo(): void
    {
        $equipo = $this->equipo;

        if (! $equipo) {
            return;
        }

        $estadoDestino = $this->estado_equipo_anterior_id
            ?? EstadoEquipo::where('nombre', EstadoEquipo::DISPONIBLE)->value('id');

        if ($estadoDestino) {
            $equipo->update(['estado_id' => $estadoDestino]);
        }
    }

    /**
     * Próximo estado en la secuencia "simple" del flujo (sin efectos sobre el
     * equipo): Programado->En proceso, Reportado->Diagnosticado->En reparación.
     * Los estados que SÍ liberan o afectan al equipo (Completado, Reparado,
     * Cerrado, Cancelado, Dado de baja) se alcanzan con sus propios métodos.
     */
    const SIGUIENTE_ESTADO_SIMPLE = [
        'Programado'    => 'En proceso',
        'Reportado'     => 'Diagnosticado',
        'Diagnosticado' => 'En reparación',
    ];

    /** Avanza al siguiente estado "simple" de la secuencia, sin tocar el equipo. */
    public function avanzarEstado(): bool
    {
        $siguiente = self::SIGUIENTE_ESTADO_SIMPLE[$this->estado] ?? null;

        if (! $siguiente) {
            return false;
        }

        $this->update(['estado' => $siguiente]);

        return true;
    }

    /** Cierra un mantenimiento preventivo como completado. */
    public function completar(): void
    {
        DB::transaction(function () {
            $this->update(['estado' => 'Completado', 'fecha_fin_real' => now()->toDateString()]);
            $this->liberarEquipo();
        });
    }

    /** Marca una reparación como técnicamente resuelta. El equipo sigue bloqueado hasta cerrar(). */
    public function marcarReparado(): void
    {
        $this->update(['estado' => 'Reparado']);
    }

    /** Cierra definitivamente una reparación ya marcada como Reparada y libera el equipo. */
    public function cerrar(): void
    {
        DB::transaction(function () {
            $this->update(['estado' => 'Cerrado', 'fecha_fin_real' => now()->toDateString()]);
            $this->liberarEquipo();
        });
    }

    /** Cancela un mantenimiento preventivo que no llegó a ejecutarse. */
    public function cancelar(): void
    {
        DB::transaction(function () {
            $this->update(['estado' => 'Cancelado', 'fecha_fin_real' => now()->toDateString()]);
            $this->liberarEquipo();
        });
    }

    /**
     * Da de baja el equipo porque la reparación no tiene solución.
     * Distinto de completar(): es permanente, requiere aprobación de un
     * Administrador, y si el equipo tenía una asignación activa la cierra
     * en el mismo paso (motivo "Equipo dado de baja") — no puede quedar
     * "asignado" en el sistema un equipo que ya no está operativo.
     */
    public function marcarDadoDeBaja(User $aprobador, string $motivo): void
    {
        DB::transaction(function () use ($aprobador, $motivo) {
            $this->update([
                'estado'         => 'Dado de baja',
                'motivo_baja'    => $motivo,
                'fecha_baja'     => now()->toDateString(),
                'fecha_fin_real' => now()->toDateString(),
                'aprobado_por_id' => $aprobador->id,
            ]);

            $itemActivo = AsignacionItem::where('equipo_id', $this->equipo_id)
                ->where('devuelto', false)
                ->first();

            if ($itemActivo) {
                $itemActivo->registrarDevolucion('Equipo dado de baja');
            }

            $estadoBaja = EstadoEquipo::where('nombre', EstadoEquipo::BAJA)->value('id');

            $this->equipo?->update([
                'activo'    => false,
                'estado_id' => $estadoBaja ?? $this->equipo->estado_id,
            ]);
        });
    }

    /**
     * Pide un componente del almacén para este caso. Si hay stock suficiente
     * lo descuenta de una vez; si no alcanza, queda "Pendiente" sin tocar el
     * stock y prende la bandera esperando_componente.
     */
    public function pedirComponente(ComponenteAlmacen $componente, int $cantidad, User $actor): MantenimientoComponente
    {
        return DB::transaction(function () use ($componente, $cantidad, $actor) {
            $hayStock = $componente->stock_actual >= $cantidad;

            $item = $this->componentes()->create([
                'componente_id'      => $componente->id,
                'cantidad_requerida' => $cantidad,
                'estado'             => $hayStock ? 'Entregado' : 'Pendiente',
                'fecha_entrega'      => $hayStock ? now()->toDateString() : null,
                'entregado_por_id'   => $hayStock ? $actor->id : null,
            ]);

            if ($hayStock) {
                $componente->registrarSalida($cantidad, $actor, 'Uso en mantenimiento', $this);
            }

            $this->recalcularEsperandoComponente();

            return $item;
        });
    }

    /** Descuenta del almacén un componente que había quedado pendiente por falta de stock. */
    public function entregarComponentePendiente(MantenimientoComponente $item, User $actor): bool
    {
        if ($item->estado !== 'Pendiente') {
            return false;
        }

        $componente = $item->componente;

        if (! $componente || $componente->stock_actual < $item->cantidad_requerida) {
            return false;
        }

        DB::transaction(function () use ($item, $componente, $actor) {
            $componente->registrarSalida($item->cantidad_requerida, $actor, 'Uso en mantenimiento', $this);

            $item->update([
                'estado'           => 'Entregado',
                'fecha_entrega'    => now()->toDateString(),
                'entregado_por_id' => $actor->id,
            ]);

            $this->recalcularEsperandoComponente();
        });

        return true;
    }

    public function recalcularEsperandoComponente(): void
    {
        $esperando = $this->componentesPendientes()->exists();

        if ($this->esperando_componente !== $esperando) {
            $this->update(['esperando_componente' => $esperando]);
        }
    }
}
