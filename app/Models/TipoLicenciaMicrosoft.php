<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo TipoLicenciaMicrosoft
 *
 * Tabla maestra — Registro de Licencias de Microsoft asignadas a cada
 * usuario (Premium, Standard, Basic, Outlook simple, No tiene).
 * Control de ciclo de vida mediante el campo `activo`.
 */
class TipoLicenciaMicrosoft extends Model
{
    use Auditable, SoftDeletes;

    protected $table = 'tipos_licencia_microsoft';

    protected $fillable = [
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    /** Usuarios que tienen asignado este tipo de licencia */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'tipo_licencia_microsoft_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeInactivos(Builder $query): Builder
    {
        return $query->where('activo', false);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function puedeDesactivarse(): bool
    {
        return $this->usuarios()->count() === 0;
    }
}
