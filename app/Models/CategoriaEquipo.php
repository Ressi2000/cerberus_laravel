<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo CategoriaEquipo
 *
 * Tabla maestra — NO usa SoftDeletes.
 * El ciclo de vida se controla con el campo `activo` (boolean):
 *   - true  → categoría visible y usable en el sistema
 *   - false → categoría desactivada (oculta en selects, visible en config con badge)
 *
 * Ventajas sobre SoftDeletes:
 *   - El unique(nombre) funciona correctamente: al "eliminar" no se ocupa el nombre.
 *   - Si se intenta crear una categoría inactiva existente, se reactiva en lugar de duplicar.
 *   - Los equipos históricos que usaban la categoría mantienen la referencia intacta.
 */
class CategoriaEquipo extends Model
{
    use Auditable;

    protected $table = 'categorias_equipos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'asignable',
        'activo',
    ];

    protected $casts = [
        'asignable' => 'boolean',
        'activo'    => 'boolean',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────────────────

    /** Solo categorías activas — usar en selects y formularios */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /** Solo categorías inactivas — usar en panel de configuración */
    public function scopeInactivos(Builder $query): Builder
    {
        return $query->where('activo', false);
    }

    /** Solo las asignables a usuarios (para el módulo de asignaciones) */
    public function scopeAsignables(Builder $query): Builder
    {
        return $query->where('asignable', true)->where('activo', true);
    }

     /** Solo categorías no asignables (áreas comunes, etc.) */
    public function scopeNoAsignables($query)
    {
        return $query->where('asignable', false)->where('activo', true);
    }

    /** Solo categorías que tienen al menos un atributo EAV configurado */
    public function scopeConAtributos($query)
    {
        return $query->has('atributos')->where('activo', true);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Relaciones
    // ─────────────────────────────────────────────────────────────────────────

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class, 'categoria_id');
    }

    public function atributos(): HasMany
    {
        return $this->hasMany(AtributoEquipo::class, 'categoria_id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /** Indica si la categoría puede ser desactivada (no tiene equipos activos) */
    public function puedeDesactivarse(): bool
    {
        return $this->equipos()->where('activo', true)->count() === 0;
    }
}