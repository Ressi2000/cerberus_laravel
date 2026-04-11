<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoriaEquipo extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'categorias_equipos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'asignable',
    ];

    protected $casts = [
        'asignable' => 'boolean',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    /**
     * Atributos EAV de esta categoría, ordenados por su campo 'orden'.
     * No se pasa FK explícita porque Laravel la infiere correctamente
     * (categoria_id) desde el nombre del modelo.
     */
    public function atributos(): HasMany
    {
        return $this->hasMany(AtributoEquipo::class, 'categoria_id')->orderBy('orden');
    }

    /** Equipos que pertenecen a esta categoría */
    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class, 'categoria_id', 'id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /** Solo categorías asignables a usuarios */
    public function scopeAsignables($query)
    {
        return $query->where('asignable', true);
    }

    /** Solo categorías no asignables (áreas comunes, etc.) */
    public function scopeNoAsignables($query)
    {
        return $query->where('asignable', false);
    }

    /** Solo categorías que tienen al menos un atributo EAV configurado */
    public function scopeConAtributos($query)
    {
        return $query->has('atributos');
    }
}