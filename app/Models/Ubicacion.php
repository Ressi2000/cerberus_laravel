<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ubicacion extends Model
{
    use Auditable;

    protected $table = 'ubicaciones';

    protected $fillable = [
        'nombre',
        'descripcion',
        'empresa_id',
        'es_estado', // true = ubicación foránea (visible para todos los analistas)
        'activo',
    ];

    protected $casts = [
        'empresa_id' => 'integer',
        'es_estado'  => 'boolean',
        'activo'    => 'boolean',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    /** Empresa a la que pertenece esta ubicación */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /** Usuarios cuya ubicación física es esta */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Equipos físicamente en esta ubicación */
    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /**
     * Ubicaciones foráneas (es_estado = true).
     * Visibles para todos los analistas sin importar empresa_activa_id.
     */
    public function scopeForaneas(Builder $query): Builder
    {
        return $query->where('es_estado', true)->where('activo', true);
    }

    /**
     * Ubicaciones locales (es_estado = false).
     */
    public function scopeLocales(Builder $query): Builder
    {
        return $query->where('es_estado', false)->where('activo', true);
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
        $tieneEquipos  = $this->equipos()->where('activo', true)->exists();
        $tieneUsuarios = $this->usuarios()->where('estado', 'Activo')->exists();
 
        return ! $tieneEquipos && ! $tieneUsuarios;
    }
}