<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Deposito
 *
 * Lugar de guardado: equipos dados de baja + componentes descartados/
 * dañados. Siempre de una empresa; el Administrador crea los que hagan
 * falta (Principal, Tóxicos, etc.). NO usa SoftDeletes — mismo criterio
 * que CategoriaEquipo, ver docblock de la migración.
 */
class Deposito extends Model
{
    use Auditable;

    protected $table = 'depositos';

    protected $fillable = [
        'empresa_id',
        'ubicacion_id',
        'nombre',
        'descripcion',
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

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class);
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /** Equipos dados de baja guardados en este depósito. */
    public function equipos()
    {
        return $this->hasMany(Equipo::class);
    }

    /** Stock de componentes que terminó descartado en este depósito. */
    public function componentesAlmacen()
    {
        return $this->hasMany(ComponenteAlmacen::class);
    }

    /** Piezas individuales descartadas (no reutilizables) guardadas directamente en este depósito. */
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

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }
}
