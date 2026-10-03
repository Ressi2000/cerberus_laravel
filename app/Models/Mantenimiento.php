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
        'plan_mantenimiento_id',
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

    /** Plan de mantenimiento del que nació este caso, si fue generado automáticamente. */
    /**
     * withTrashed(): un plan eliminado sigue siendo el origen real de este
     * caso — LoteDetalle ya permite seguir procesando el historial de un
     * plan borrado, así que esta relación no debe perderlo solo porque el
     * plan ya no está activo (ver mantenimientos-table.blade.php, que
     * agrupa por plan y necesita poder mostrar/enlazar el lote aunque el
     * plan esté eliminado).
     */
    public function planMantenimiento()
    {
        return $this->belongsTo(PlanMantenimiento::class, 'plan_mantenimiento_id')->withTrashed();
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

    /** Piezas extraídas de un equipo durante esta reparación (sustitución). */
    public function piezasExtraidas()
    {
        return $this->hasMany(PiezaExtraida::class);
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

    /**
     * ¿Se puede registrar trabajo ahora (componentes, piezas, observaciones)?
     * Preventivo: mientras el caso esté abierto, igual que siempre. Correctivo:
     * solo durante "En reparación" — en Reportado/Diagnosticado todavía no hay
     * nada que registrar, y en Reparado/Cerrado ya se congeló lo hecho.
     */
    public function permiteRegistrarTrabajo(): bool
    {
        if (! $this->estaAbierto()) {
            return false;
        }

        return $this->esPreventivo() || $this->estado === 'En reparación';
    }

    /** Estados válidos según el tipo de esta intervención (para armar el select). */
    public function estadosDisponibles(): array
    {
        return $this->esPreventivo() ? self::ESTADOS_PREVENTIVO : self::ESTADOS_CORRECTIVO;
    }

    /**
     * ¿El equipo de este caso sigue dentro de su periodo de garantía?
     * Solo informativo — no bloquea ni cambia nada, se usa para avisar al
     * analista al crear el caso (Preventivo o Correctivo).
     */
    public function equipoEnGarantia(): bool
    {
        $fin = $this->equipo?->fecha_garantia_fin;

        return $fin !== null && $fin->isFuture();
    }

    /**
     * Bloquea el equipo: guarda su estado actual (para poder restaurarlo tal
     * cual al cerrar) y lo pasa a "En mantenimiento" o "En reparación" según
     * el tipo. NO toca ninguna asignación activa.
     *
     * Correctivo: se bloquea al CREAR el caso — el equipo ya falló, no tiene
     * sentido que siga disponible para asignar/prestar mientras se diagnostica.
     *
     * Preventivo: se bloquea recién al pasar a "En proceso" (avanzarEstado()),
     * no al crear el caso — un mantenimiento "Programado" con días de
     * antelación (generado por el cronograma o creado a mano) no debe dejar
     * el equipo inutilizable antes de que alguien lo esté trabajando de
     * verdad.
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

    /** ¿Este caso llegó a bloquear el equipo? (false para un Preventivo cancelado desde "Programado"). */
    public function estaBloqueado(): bool
    {
        return $this->estado_equipo_anterior_id !== null;
    }

    /**
     * Restaura el equipo al estado que tenía antes de bloquearse (ej. vuelve
     * a "Asignado"). No hace nada si el caso nunca llegó a bloquear el
     * equipo (ej. un Preventivo cancelado directo desde "Programado", antes
     * de pasar por "En proceso") — de lo contrario forzaría el equipo a
     * "Disponible" aunque en realidad seguía asignado normalmente.
     */
    public function liberarEquipo(): void
    {
        $equipo = $this->equipo;

        if (! $equipo || ! $this->estaBloqueado()) {
            return;
        }

        $equipo->update(['estado_id' => $this->estado_equipo_anterior_id]);
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

    /**
     * Avanza al siguiente estado "simple" de la secuencia. Para Preventivo,
     * el paso Programado -> En proceso es el que bloquea el equipo (ver
     * bloquearEquipo()); para Correctivo el equipo ya estaba bloqueado
     * desde la creación, así que los pasos intermedios no tocan nada más.
     */
    public function avanzarEstado(): bool
    {
        $siguiente = self::SIGUIENTE_ESTADO_SIMPLE[$this->estado] ?? null;

        if (! $siguiente) {
            return false;
        }

        $bloquearAhora = $this->esPreventivo() && $this->estado === 'Programado' && $siguiente === 'En proceso';

        $this->update(['estado' => $siguiente]);

        if ($bloquearAhora) {
            $this->bloquearEquipo();
        }

        return true;
    }

    /**
     * Cierra un mantenimiento preventivo como completado. Si el caso nació
     * de un plan de mantenimiento (cronograma), el plan revisa si con este
     * cierre ya se completó TODO el lote que generó (puede haber más
     * equipos de la misma categoría/empresa con su caso todavía abierto) y,
     * si es así, avanza solo a la siguiente fecha — así el ciclo se repite
     * sin que nadie tenga que acordarse de crear el próximo.
     */
    public function completar(): void
    {
        DB::transaction(function () {
            $fechaCierre = now()->toDateString();
            $this->update(['estado' => 'Completado', 'fecha_fin_real' => $fechaCierre]);
            $this->liberarEquipo();

            $this->planMantenimiento?->avanzarSiLoteCompleto();
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

    /**
     * Reabre un caso Cerrado — solo si el problema sigue siendo el mismo
     * diagnóstico; si es otra falla, corresponde abrir un caso nuevo, no
     * reabrir este. Vuelve a "En reparación" y re-bloquea el equipo
     * directamente (bloquearEquipo() no sirve acá: se niega a correr dos
     * veces sobre el mismo caso). El motivo queda anotado en observaciones
     * para no perder por qué se reabrió.
     */
    public function reabrir(User $actor, string $motivo): void
    {
        if (! ($this->esCorrectivo() && $this->estado === 'Cerrado')) {
            throw new \InvalidArgumentException('Solo se puede reabrir una reparación que esté Cerrada.');
        }

        DB::transaction(function () use ($actor, $motivo) {
            $equipo = $this->equipo;

            if ($equipo && $this->estado_equipo_anterior_id) {
                $estadoReparacion = EstadoEquipo::where('nombre', EstadoEquipo::EN_REPARACION)->value('id');
                $equipo->update(['estado_id' => $estadoReparacion ?? $equipo->estado_id]);
            }

            $nota = 'Reabierto el ' . now()->format('d/m/Y') . " por {$actor->name}: {$motivo}";

            $this->update([
                'estado'         => 'En reparación',
                'fecha_fin_real' => null,
                'observaciones'  => trim(($this->observaciones ? $this->observaciones . "\n\n" : '') . $nota),
            ]);
        });
    }

    /** Estados de Correctivo a los que se puede retroceder (excluye los terminales — ahí corresponde reabrir()). */
    const ESTADOS_RETROCEDIBLES_CORRECTIVO = ['Reportado', 'Diagnosticado', 'En reparación', 'Reparado'];

    /** Estados de Preventivo a los que se puede retroceder (excluye Completado/Cancelado). */
    const ESTADOS_RETROCEDIBLES_PREVENTIVO = ['Programado', 'En proceso'];

    /** Estados retrocedibles según el tipo de esta intervención. */
    public function estadosRetrocedibles(): array
    {
        return $this->esCorrectivo() ? self::ESTADOS_RETROCEDIBLES_CORRECTIVO : self::ESTADOS_RETROCEDIBLES_PREVENTIVO;
    }

    /**
     * Retrocede el caso a un estado anterior dentro del flujo normal (por si
     * faltó algo). No aplica a los estados terminales (Completado, Cerrado,
     * Dado de baja, Cancelado) — de Cerrado se sale con reabrir(), que
     * además re-bloquea el equipo.
     *
     * Correctivo: el bloqueo del equipo no cambia entre estos estados (se
     * bloquea una sola vez, al crear el caso), así que retroceder acá no
     * tiene efectos secundarios sobre el equipo.
     *
     * Preventivo: "En proceso" -> "Programado" deshace el bloqueo que hizo
     * avanzarEstado() al entrar a "En proceso" — se libera el equipo y se
     * limpia estado_equipo_anterior_id para que, si el caso vuelve a
     * avanzar, bloquearEquipo() pueda tomar el estado actual del equipo de
     * nuevo (no corre dos veces si ya está seteado).
     */
    public function retrocederA(string $estado): void
    {
        $lista   = $this->estadosRetrocedibles();
        $actual  = array_search($this->estado, $lista, true);
        $destino = array_search($estado, $lista, true);

        if ($destino === false) {
            throw new \InvalidArgumentException('Ese estado no es válido para retroceder. Si el caso ya está cerrado, usa "Reabrir".');
        }

        if ($actual === false || $destino >= $actual) {
            throw new \InvalidArgumentException('Solo se puede retroceder a un estado anterior al actual.');
        }

        DB::transaction(function () use ($estado) {
            $this->update(['estado' => $estado]);

            if ($this->esPreventivo() && $estado === 'Programado' && $this->estaBloqueado()) {
                $this->liberarEquipo();
                $this->update(['estado_equipo_anterior_id' => null]);
            }
        });
    }

    /**
     * Elimina el caso (soft delete, queda en BD para auditoría). Si el caso
     * seguía abierto y bloqueando el equipo, lo libera antes — no puede
     * quedar un equipo "En reparación" para siempre por un caso borrado.
     */
    public function eliminarCaso(): void
    {
        DB::transaction(function () {
            if ($this->estaAbierto() && $this->estaBloqueado()) {
                $this->liberarEquipo();
            }

            $this->delete();

            // Si este caso era el último pendiente del lote de su plan, con
            // él borrado el lote puede haber quedado completo — igual que
            // hacen completar()/cancelar(), hay que revisarlo para que el
            // plan no se quede esperando para siempre.
            $this->planMantenimiento?->avanzarSiLoteCompleto();
        });
    }

    /** Cancela un mantenimiento preventivo que no llegó a ejecutarse. */
    public function cancelar(): void
    {
        DB::transaction(function () {
            $this->update(['estado' => 'Cancelado', 'fecha_fin_real' => now()->toDateString()]);
            $this->liberarEquipo();

            $this->planMantenimiento?->avanzarSiLoteCompleto();
        });
    }

    /**
     * Da de baja el equipo porque la reparación no tiene solución.
     * Distinto de completar(): es permanente, requiere aprobación de un
     * Administrador, y si el equipo tenía una asignación activa la cierra
     * en el mismo paso (motivo "Equipo dado de baja") — no puede quedar
     * "asignado" en el sistema un equipo que ya no está operativo.
     *
     * Antes de archivar el equipo en el depósito indicado, extrae las
     * piezas que el usuario decidió rescatar (cada una con su propio
     * destino Almacén/Depósito) — ver ObsolescenciaService.
     *
     * @param array<int, array{tipo:string, atributo_id:int, grupo_instancia_id:?int, destino:string}> $piezas
     */
    public function marcarDadoDeBaja(User $aprobador, string $motivo, Deposito $deposito, array $piezas = []): void
    {
        DB::transaction(function () use ($aprobador, $motivo, $deposito, $piezas) {
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

            $obsolescencia = app(\App\Services\ObsolescenciaService::class);
            $obsolescencia->extraerPiezas($this->equipo, $piezas, $aprobador, $deposito, mantenimiento: $this);
            $obsolescencia->archivarEquipo($this->equipo, $deposito);
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
