<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Modelo PrestamoItem
 *
 * Representa un equipo dentro de un préstamo.
 * Soporta jerarquía padre-hijo (equipo_padre_id null = principal).
 *
 * registrarDevolucion() no hace cascada: cada item se devuelve
 * de forma independiente. Si se devuelve un principal con hijos
 * activos, estos se promueven a principales automáticamente.
 */
class PrestamoItem extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'prestamo_id',
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

    public function prestamo()
    {
        return $this->belongsTo(Prestamo::class);
    }

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function padre()
    {
        return $this->belongsTo(PrestamoItem::class, 'equipo_padre_id');
    }

    public function hijos()
    {
        return $this->hasMany(PrestamoItem::class, 'equipo_padre_id');
    }

    public function hijosActivos()
    {
        return $this->hasMany(PrestamoItem::class, 'equipo_padre_id')
            ->where('devuelto', false);
    }

    public function devueltoPor()
    {
        return $this->belongsTo(User::class, 'devuelto_por_id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    public function esPrincipal(): bool
    {
        return $this->equipo_padre_id === null;
    }

    public function esPeriférico(): bool
    {
        return $this->equipo_padre_id !== null;
    }

    public function tieneHijosActivos(): bool
    {
        return $this->hijosActivos()->exists();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Lógica de devolución (sin cascada, con promoción de huérfanos)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Registra la devolución de este item únicamente.
     *
     * 1. Marca el item como devuelto.
     * 2. Devuelve el equipo a "Disponible".
     * 3. Si era principal, promueve hijos activos a principales.
     * 4. Recalcula el estado del préstamo.
     */
    public function registrarDevolucion(?string $observaciones = null): void
    {
        DB::transaction(function () use ($observaciones) {

            $estadoDisponible = EstadoEquipo::where('nombre', 'Disponible')->value('id');

            $this->update([
                'devuelto'                 => true,
                'fecha_devolucion'         => now()->toDateString(),
                'devuelto_por_id'          => Auth::id(),
                'observaciones_devolucion' => $observaciones,
            ]);

            if ($estadoDisponible && $this->equipo_id) {
                Equipo::where('id', $this->equipo_id)
                    ->update(['estado_id' => $estadoDisponible]);
            }

            if ($this->esPrincipal()) {
                $this->hijosActivos()->update(['equipo_padre_id' => null]);
            }

            $this->prestamo->recalcularEstado();
        });
    }
}
