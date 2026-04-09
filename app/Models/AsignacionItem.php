<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Modelo AsignacionItem — v2
 *
 * Representa un equipo dentro de una asignación.
 * Soporta vinculación padre-hijo para periféricos:
 *   equipo_padre_id null    → equipo principal (laptop, desktop, etc.)
 *   equipo_padre_id = N     → periférico del item con id N
 *
 * ── CAMBIOS v2 ──────────────────────────────────────────────────────────────
 *
 * registrarDevolucion() ya NO hace cascada automática sobre los hijos.
 * Cada item (principal o periférico) se devuelve de forma independiente.
 *
 * Cuando se devuelve un item PRINCIPAL que aún tiene hijos activos:
 *   → Los hijos se promueven automáticamente a principales (Opción A):
 *     su equipo_padre_id se pone a null, quedan asignados al mismo usuario/área.
 *   → El analista puede luego devolver esos periféricos individualmente
 *     o re-vincularlos a otro equipo principal desde la UI de devolución.
 *
 * La UI de devolución (DevolverAsignacion / DevolverUsuario) muestra
 * TODOS los items activos (principales Y periféricos) con checkbox propio,
 * permitiendo devoluciones granulares en cualquier combinación.
 * ────────────────────────────────────────────────────────────────────────────
 */
class AsignacionItem extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'asignacion_id',
        'equipo_id',
        'equipo_padre_id',
        'devuelto',
        'fecha_devolucion',
        'devuelto_por_id',
        'observaciones_devolucion',
    ];

    protected $casts = [
        'devuelto'         => 'boolean',
        'fecha_devolucion' => 'date',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Relaciones
    // ─────────────────────────────────────────────────────────────────────────

    public function asignacion()
    {
        return $this->belongsTo(Asignacion::class);
    }

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    /** Item padre (el equipo principal al que este periférico está vinculado) */
    public function padre()
    {
        return $this->belongsTo(AsignacionItem::class, 'equipo_padre_id');
    }

    /** Items hijos activos (periféricos vinculados a este equipo) */
    public function hijos()
    {
        return $this->hasMany(AsignacionItem::class, 'equipo_padre_id');
    }

    /** Solo hijos que aún no han sido devueltos */
    public function hijosActivos()
    {
        return $this->hasMany(AsignacionItem::class, 'equipo_padre_id')
            ->where('devuelto', false);
    }

    public function devueltoPor()
    {
        return $this->belongsTo(User::class, 'devuelto_por_id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    public function esPeriférico(): bool
    {
        return $this->equipo_padre_id !== null;
    }

    public function esPrincipal(): bool
    {
        return $this->equipo_padre_id === null;
    }

    /**
     * Indica si este item principal tiene periféricos activos todavía asignados.
     * Usado por la UI para mostrar el aviso de "periféricos que quedarán libres".
     */
    public function tieneHijosActivos(): bool
    {
        return $this->hijosActivos()->exists();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Lógica de devolución — v2 (sin cascada, con promoción de huérfanos)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Registra la devolución de ESTE item únicamente.
     *
     * Comportamiento:
     *   1. Marca este item como devuelto (fecha, quién lo devuelve, observación).
     *   2. Devuelve el equipo a estado "Disponible".
     *   3. Si este item era PRINCIPAL y tenía hijos activos:
     *        → Los promueve a principales (equipo_padre_id = null).
     *        → No los devuelve — quedan asignados al usuario/área.
     *        → El analista puede devolverlos en cualquier momento.
     *   4. Si este item era PERIFÉRICO:
     *        → Solo devuelve este periférico, sin tocar al padre.
     *   5. Recalcula el estado de la asignación.
     *
     * NO hace cascada. Cada item se devuelve solo cuando el analista
     * lo selecciona explícitamente en la UI.
     *
     * @param string|null $observaciones Observaciones opcionales del analista.
     */
    public function registrarDevolucion(?string $observaciones = null): void
    {
        DB::transaction(function () use ($observaciones) {

            $estadoDisponible = EstadoEquipo::where('nombre', 'Disponible')->value('id');
            $analista         = Auth::id();

            // ── 1. Marcar este item como devuelto ────────────────────────────
            $this->update([
                'devuelto'                 => true,
                'fecha_devolucion'         => now()->toDateString(),
                'devuelto_por_id'          => $analista,
                'observaciones_devolucion' => $observaciones,
            ]);

            // ── 2. Liberar el equipo a "Disponible" ──────────────────────────
            if ($estadoDisponible && $this->equipo_id) {
                Equipo::where('id', $this->equipo_id)
                    ->update(['estado_id' => $estadoDisponible]);
            }

            // ── 3. Opción A: promover hijos activos a principales ────────────
            //    Solo aplica si este item era principal (no tiene padre propio).
            //    Si era periférico, no tiene hijos que promover.
            if ($this->esPrincipal()) {
                // Desvincula todos los hijos activos (pone equipo_padre_id = null).
                // nullOnDelete en la migración garantiza integridad referencial,
                // pero lo hacemos explícito aquí para que quede auditado.
                $this->hijosActivos()->update(['equipo_padre_id' => null]);
            }

            // ── 4. Recalcular estado de la asignación ────────────────────────
            //    Si todos los items quedaron devueltos → Cerrada.
            //    Si aún quedan activos (incluyendo los hijos promovidos) → Activa.
            $this->asignacion->recalcularEstado();
        });
    }
}