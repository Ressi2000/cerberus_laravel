<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EstadoEquipo extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'estados_equipos';

    protected $fillable = [
        'nombre',
    ];

    // ── Constantes de estados del sistema ────────────────────────────────────
    // Permiten hacer comparaciones semánticas en lugar de strings literales:
    //   if ($equipo->estado->nombre === EstadoEquipo::DISPONIBLE)
    //   EstadoEquipo::where('nombre', EstadoEquipo::BAJA)->first()

    const DISPONIBLE    = 'Disponible';
    const ASIGNADO      = 'Asignado';
    const EN_PRESTAMO   = 'En préstamo';
    const EN_REPARACION = 'En reparación';
    const BAJA          = 'Dado de baja';
    const NO_ASIGNABLE  = 'No asignable';

    // ── Relaciones ────────────────────────────────────────────────────────────

    /** Equipos que tienen actualmente este estado */
    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class, 'estado_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /**
     * Estados que indican que el equipo está operativo
     * (disponible o asignado — no en reparación ni dado de baja).
     */
    public function scopeOperativos($query)
    {
        return $query->whereIn('nombre', [
            self::DISPONIBLE,
            self::ASIGNADO,
            self::EN_PRESTAMO,
        ]);
    }

    /** Estado que indica que el equipo no puede ser usado ni asignado */
    public function scopeInactivos($query)
    {
        return $query->whereIn('nombre', [
            self::BAJA,
            self::EN_REPARACION,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Retorna true si este estado permite asignar el equipo a un usuario */
    public function permiteAsignacion(): bool
    {
        return $this->nombre === self::DISPONIBLE;
    }

    /** Retorna true si el equipo con este estado puede prestarse */
    public function permitePrestamo(): bool
    {
        return $this->nombre === self::DISPONIBLE;
    }
}