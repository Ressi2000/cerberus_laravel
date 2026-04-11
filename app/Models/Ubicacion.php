<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ubicacion extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'ubicaciones';

    protected $fillable = [
        'nombre',
        'descripcion',
        'empresa_id',
        'es_estado', // true = ubicación foránea (visible para todos los analistas)
    ];

    protected $casts = [
        'empresa_id' => 'integer',
        'es_estado'  => 'boolean',
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
        return $query->where('es_estado', true);
    }

    /**
     * Ubicaciones locales (es_estado = false).
     */
    public function scopeLocales(Builder $query): Builder
    {
        return $query->where('es_estado', false);
    }
}