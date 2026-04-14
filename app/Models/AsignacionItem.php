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

    // ─────────────────────────────────────────────────────────────────────────
    // Lógica de vinculación padre-hijo post-asignación
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Vincula este item como periférico de otro item principal.
     *
     * Permite asociar un periférico (teclado, mouse, monitor, etc.) que llegó
     * en una asignación distinta a un equipo principal (laptop, desktop) que
     * ya existe en CUALQUIER asignación activa del mismo receptor.
     *
     * Reglas de negocio validadas:
     *   1. El item padre debe ser principal (equipo_padre_id === null).
     *   2. El item padre debe estar activo (devuelto = false).
     *   3. El item padre debe tener el mismo receptor que este item
     *      (mismo usuario_id o misma combinación área).
     *   4. Este item no puede ser padre de sí mismo.
     *   5. Este item no debe estar devuelto.
     *
     * @param  AsignacionItem  $itemPadre  El item principal al que se vincula.
     * @throws \InvalidArgumentException   Si alguna regla de negocio falla.
     */
    public function vincularAPadre(AsignacionItem $itemPadre): void
    {
        // ── Regla 4: no apuntarse a sí mismo ────────────────────────────────
        if ($this->id === $itemPadre->id) {
            throw new \InvalidArgumentException(
                'Un item no puede ser periférico de sí mismo.'
            );
        }

        // ── Regla 5: este item no debe estar devuelto ────────────────────────
        if ($this->devuelto) {
            throw new \InvalidArgumentException(
                'No se puede vincular un item que ya fue devuelto.'
            );
        }

        // ── Regla 1: el padre debe ser principal ─────────────────────────────
        if (! $itemPadre->esPrincipal()) {
            throw new \InvalidArgumentException(
                'Solo se puede vincular a un equipo principal. No se permiten periféricos de periféricos.'
            );
        }

        // ── Regla 2: el padre debe estar activo ──────────────────────────────
        if ($itemPadre->devuelto) {
            throw new \InvalidArgumentException(
                'No se puede vincular a un equipo que ya fue devuelto.'
            );
        }

        // ── Regla 3: mismo receptor ───────────────────────────────────────────
        // Cargamos las asignaciones con sus datos de receptor para comparar.
        $asignacionPropia  = $this->asignacion;
        $asignacionPadre   = $itemPadre->asignacion;

        if (! $this->mismoReceptor($asignacionPropia, $asignacionPadre)) {
            throw new \InvalidArgumentException(
                'El equipo principal pertenece a un receptor diferente. Solo puedes vincular periféricos al mismo usuario o área.'
            );
        }

        // ── Todo válido: actualizar el vínculo ────────────────────────────────
        DB::transaction(function () use ($itemPadre) {
            $this->update(['equipo_padre_id' => $itemPadre->id]);
        });
    }

    /**
     * Desvincula este item de su padre, convirtiéndolo en principal.
     *
     * Útil cuando un periférico cambia de equipo principal o se quiere
     * dejar libre sin devolverlo.
     *
     * @throws \InvalidArgumentException Si el item ya es principal o está devuelto.
     */
    public function desvincularDePadre(): void
    {
        if ($this->esPrincipal()) {
            throw new \InvalidArgumentException(
                'Este item ya es un equipo principal, no tiene padre que desvincular.'
            );
        }

        if ($this->devuelto) {
            throw new \InvalidArgumentException(
                'No se puede modificar un item que ya fue devuelto.'
            );
        }

        DB::transaction(function () {
            $this->update(['equipo_padre_id' => null]);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper privado: comparación de receptores entre dos asignaciones
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Compara si dos asignaciones tienen el mismo receptor.
     *
     * Receptor personal : compara usuario_id.
     * Receptor área      : compara area_empresa_id + area_departamento_id.
     * Tipos mixtos       : siempre false (personal ≠ área).
     */
    private function mismoReceptor(Asignacion $a, Asignacion $b): bool
    {
        // Ambas personales → mismo usuario
        if ($a->tipoReceptor() === 'personal' && $b->tipoReceptor() === 'personal') {
            return $a->usuario_id === $b->usuario_id;
        }

        // Ambas de área → misma empresa + mismo departamento
        if ($a->tipoReceptor() === 'area' && $b->tipoReceptor() === 'area') {
            return $a->area_empresa_id    === $b->area_empresa_id
                && $a->area_departamento_id === $b->area_departamento_id;
        }

        // Tipos mixtos → nunca son el mismo receptor
        return false;
    }
}
