<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Empresa extends Model
{
    use Auditable;

    protected $table = 'empresas';

    protected $fillable = [
        'nombre',
        'rif',
        'direccion',
        'telefono',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];


    // ── Relaciones ────────────────────────────────────────────────────────────

    /** Usuarios de nómina de esta empresa */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Usuarios analistas asignados a esta empresa (pivot empresa_user) */
    public function analistas(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'empresa_user');
    }

    /** Departamentos de esta empresa */
    public function departamentos(): HasMany
    {
        return $this->hasMany(Departamento::class);
    }

    /** Cargos de esta empresa */
    public function cargos(): HasMany
    {
        return $this->hasMany(Cargo::class);
    }

    /** Ubicaciones físicas de esta empresa */
    public function ubicaciones(): HasMany
    {
        return $this->hasMany(Ubicacion::class);
    }

    /** Equipos registrados en esta empresa */
    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────
    /** Solo empresas activas — usar en selects y formularios */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /** Solo empresas inactivas — usar en panel de configuración */
    public function scopeInactivas(Builder $query): Builder
    {
        return $query->where('activo', false);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    public function puedeDesactivarse(): bool
    {
        $tieneEquipos  = $this->equipos()->where('activo', true)->exists();
        $tieneUsuarios = $this->usuarios()->where('estado', 'Activo')->exists();
 
        return ! $tieneEquipos && ! $tieneUsuarios;
    }
}