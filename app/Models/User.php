<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Notifications\CerberusResetPassword;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

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

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CerberusResetPassword($token));
    }

    public function canResetPassword(): bool
    {
        return $this->estado === 'Activo';
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

    public function asignaciones()
    {
        return $this->hasMany(Asignacion::class, 'usuario_id');
    }

    // Items activos de todas sus asignaciones (para withCount en la tabla)
    public function asignacionItemsActivos()
    {
        return $this->hasManyThrough(
            AsignacionItem::class,
            Asignacion::class,
            'usuario_id',   // FK en asignaciones
            'asignacion_id' // FK en asignacion_items
        )->where('asignacion_items.devuelto', false);
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
        if ($actor->hasRole('Analista') && $actor->empresa_activa_id) {
            return $query->where(function ($q) use ($actor) {
                // Usuario en una ubicación que pertenece a la empresa activa del analista
                $q->whereHas(
                    'ubicacion',
                    fn($u) =>
                    $u->where('empresa_id', $actor->empresa_activa_id)
                )
                    // O usuario en una ubicación foránea (visible para todos los analistas)
                    ->orWhereHas(
                        'ubicacion',
                        fn($u) =>
                        $u->where('es_estado', true)
                    );
            });
        }

        return $query->whereRaw('1 = 0');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', 'Activo');
    }

    /**
     * Usuarios que pueden ser seleccionados en formularios
     */
    public function scopeSeleccionables(Builder $query): Builder
    {
        return $query
            ->where('estado', 'Activo')
            ->orderBy('name');
    }

    public function getFotoUrlAttribute()
    {
        if ($this->foto && Storage::disk('public')->exists($this->foto)) {
            return Storage::url($this->foto);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=1B263B&color=A9D6E5&size=128';
    }

    // para negar acceso a inactivos si deseas filtrar en queries, y un método para chequear el estado en login
    // protected static function booted()
    // {
    //     static::addGlobalScope('activo', function (Builder $builder) {
    //         $builder->where('estado', 'Activo');
    //     });
    // }
}
