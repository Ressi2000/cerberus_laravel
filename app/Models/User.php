<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Builder;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'empresa_id', //nomina
        'empresa_activa_id', //contexto analista
        'email',
        'ficha',
        'cedula',
        'departamento_id',
        'cargo_id',
        'ubicacion_id', //visibilidad
        'telefono',
        'jefe_id',
        'foto',
        'password',
        'estado',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    public function cargo()
    {
        return $this->belongsTo(Cargo::class, 'cargo_id');
    }

    public function jefe()
    {
        return $this->belongsTo(User::class, 'jefe_id');
    }

    public function subordinados()
    {
        return $this->hasMany(User::class, 'jefe_id');
    }

    public function empresaNomina()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function empresasAsignadas()
    {
        return $this->belongsToMany(Empresa::class, 'empresa_user')->withTimestamps();
    }

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_id');
    }

    public function empresaActiva()
    {
        return $this->belongsTo(Empresa::class, 'empresa_activa_id');
    }

    // Scope para filtrar usuarios visibles para el actor dado
    public function scopeVisiblePara(Builder $query, User $actor): Builder
    {
        // Admin ve todo
        if ($actor->hasRole('Administrador')) {
            return $query;
        }

        // Usuario normal: solo él mismo
        if ($actor->hasRole('Usuario')) {
            return $query->whereKey($actor->id);
        }

        // Analista
        if ($actor->hasRole('Analista')) {
            return $query->where(function ($q) use ($actor) {
                $q->where('ubicacion_id', $actor->empresa_activa_id)
                  ->orWhereHas('ubicacion', fn ($u) => $u->where('es_estado', true));
            });
        }

        return $query->whereRaw('1 = 0');
    }
}
