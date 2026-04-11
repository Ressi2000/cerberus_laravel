<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Empresa extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'empresas';

    protected $fillable = [
        'nombre',
        'rif',
        'direccion',
        'telefono',
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
    // Con SoftDeletes, Eloquent filtra deleted_at automáticamente en todos
    // los queries. Para ver eliminadas usar: Empresa::withTrashed()->...
}