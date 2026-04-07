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
 */
class AsignacionItem extends Model
{
    use HasFactory, Auditable;
 
    protected $fillable = [
        'asignacion_id',
        'equipo_id',
        'equipo_padre_id',      // null = principal, int = periférico de ese item
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
 
    /** Items hijos (periféricos vinculados a este equipo) */
    public function hijos()
    {
        return $this->hasMany(AsignacionItem::class, 'equipo_padre_id');
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
 
    // ─────────────────────────────────────────────────────────────────────────
    // Lógica de devolución
    // ─────────────────────────────────────────────────────────────────────────
 
    /**
     * Registra la devolución de este item en una transacción atómica:
     *   1. Marca el item como devuelto
     *   2. Si tiene hijos activos, los devuelve también (cascade lógico)
     *   3. Devuelve el equipo a estado "Disponible"
     *   4. Devuelve los equipos de los hijos a "Disponible"
     *   5. Recalcula el estado de la asignación padre
     */
    public function registrarDevolucion(?string $observaciones = null): void
    {
        DB::transaction(function () use ($observaciones) {
            $estadoDisponible = EstadoEquipo::where('nombre', 'Disponible')->value('id');
            $analista         = Auth::id();
 
            // 1. Marcar este item
            $this->update([
                'devuelto'                 => true,
                'fecha_devolucion'         => now()->toDateString(),
                'devuelto_por_id'          => $analista,
                'observaciones_devolucion' => $observaciones,
            ]);
 
            // 2. Liberar el equipo
            if ($estadoDisponible) {
                $this->equipo()->update(['estado_id' => $estadoDisponible]);
            }
 
            // 3. Devolver hijos activos en cascada
            $this->hijos()->where('devuelto', false)->each(function ($hijo) use ($estadoDisponible, $analista) {
                $hijo->update([
                    'devuelto'         => true,
                    'fecha_devolucion' => now()->toDateString(),
                    'devuelto_por_id'  => $analista,
                ]);
 
                if ($estadoDisponible) {
                    $hijo->equipo()->update(['estado_id' => $estadoDisponible]);
                }
            });
 
            // 4. Recalcular estado de la asignación padre
            $this->asignacion->recalcularEstado();
        });
    }
}