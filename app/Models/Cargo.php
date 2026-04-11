<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cargo extends Model
{
    use Auditable;

    protected $table = 'cargos';

    protected $fillable = [
        'nombre',
        'empresa_id',      // null = global
        'departamento_id',
        'activo',
    ];

    protected $casts = [
        'empresa_id'      => 'integer',
        'departamento_id' => 'integer',
        'activo'         => 'boolean',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    /** Empresa propietaria — null = cargo global */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /** Departamento al que pertenece */
    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }

    /** Usuarios que tienen este cargo */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /** Cargos sin empresa: visibles globalmente en todos los selects */
    public function scopeGlobales(Builder $query): Builder
    {
        return $query->whereNull('empresa_id');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }
 
    public function scopeInactivos(Builder $query): Builder
    {
        return $query->where('activo', false)->where('activo', true);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────
 
    public function puedeDesactivarse(): bool
    {
        return $this->usuarios()->where('estado', 'Activo')->count() === 0;
    }
}