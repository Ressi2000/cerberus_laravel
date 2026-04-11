<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\Auditable;


class Departamento extends Model
{
    use SoftDeletes, Auditable;
 
    protected $table = 'departamentos';
 
    protected $fillable = [
        'nombre',
        'descripcion',
        'empresa_id',   // null = global (visible para todas las empresas)
    ];
 
    protected $casts = [
        'empresa_id' => 'integer',
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
        return $query->whereNull('empresa_id');
    }

}
