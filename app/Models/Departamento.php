<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\Auditable;


class Departamento extends Model
{
    use Auditable;

    protected $table = 'departamentos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'empresa_id',   // null = global (visible para todas las empresas)
        'activo',
    ];

    protected $casts = [
        'empresa_id' => 'integer',
        'activo'     => 'boolean',

    ];
 
    // ── Relaciones ─────────────────────────────────────────────────────────

    /** Empresa propietaria (null = global) */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /** Cargos que pertenecen a este departamento */
    public function cargos(): HasMany
    {
        return $this->hasMany(Cargo::class);
    }

    /** Usuarios asignados a este departamento */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }
 
    // ── Scopes ─────────────────────────────────────────────────────────────

    /**
     * Scope: Departamentos sin empresa: visibles globalmente en todos los selects
     */
    public function scopeGlobales(Builder $query): Builder
    {
        return $query->whereNull('empresa_id')->where('activo', true);
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeInactivos(Builder $query): Builder
    {
        return $query->where('activo', false);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    public function puedeDesactivarse(): bool
    {
        return $this->usuarios()->where('estado', 'Activo')->count() === 0;
    }
}
