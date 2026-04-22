<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

/**
 * Modelo Equipo
 *
 * Representa un activo tecnológico del inventario.
 * Usa arquitectura EAV para atributos técnicos variables por categoría.
 * Tiene SoftDeletes para eliminación administrativa (solo Administrador).
 * El campo 'activo' controla la baja lógica (Analista).
 * 
 * La clave es que empresa_id en el equipo es la empresa propietaria (nómina del activo), 
 * pero ubicacion_id es donde está físicamente, y es ese campo el que determina la 
 * visibilidad del analista — exactamente igual al principio rector de Cerberus para usuarios.
 */
class Equipo extends Model
{
    use Auditable, SoftDeletes;

    protected $fillable = [
        'empresa_id',
        'categoria_id',
        'estado_id',
        'ubicacion_id',
        'codigo_interno',
        'serial',
        'nombre_maquina',
        'fecha_adquisicion',
        'fecha_garantia_fin',
        'activo',
        'observaciones',
        'creado_por',         // FK al usuario que registró el equipo
    ];

    protected $casts = [
        'fecha_adquisicion' => 'date',
        'fecha_garantia_fin' => 'date',
    ];

    // ── Regla de negocio: la categoría no puede cambiar tras la creación ─────
    protected static function booted(): void
    {
        static::updating(function ($equipo) {
            if ($equipo->isDirty('categoria_id')) {
                throw new \Exception('La categoría de un equipo no puede modificarse.');
            }
        });
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // Relaciones
    // ─────────────────────────────────────────────────────────────────────────

    /** Categoría del equipo (Laptop, Desktop, Servidor, etc.) */
    public function categoria()
    {
        return $this->belongsTo(CategoriaEquipo::class, 'categoria_id');
    }

    /** Estado actual (Disponible, Asignado, En préstamo, etc.) */
    public function estado()
    {
        return $this->belongsTo(EstadoEquipo::class, 'estado_id');
    }

    /** Empresa propietaria del equipo */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    /** Ubicación física actual del equipo */
    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class);
    }

    public function scopeVisiblePara(Builder $query, User $actor): Builder
    {
        if ($actor->hasRole('Administrador')) {
            return $query;
        }

        if ($actor->hasRole('Analista') && $actor->empresa_activa_id) {
            return $query->where(function ($q) use ($actor) {
                // Equipo en una ubicación que pertenece a la empresa activa del analista
                $q->whereHas(
                    'ubicacion',
                    fn($u) =>
                    $u->where('empresa_id', $actor->empresa_activa_id)
                )
                    // O equipo en una ubicación foránea (visible para todos los analistas)
                    ->orWhereHas(
                        'ubicacion',
                        fn($u) =>
                        $u->where('es_estado', true)
                    );
            });
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Usuario que registró el equipo en el sistema.
     * FK: creado_por → users.id
     */
    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Valores de atributos EAV actuales (es_actual = true).
     * Son los valores vigentes de características técnicas.
     */
    public function atributosActuales()
    {
        return $this->hasMany(EquipoAtributoValor::class)
            ->where('es_actual', true);
    }

    /**
     * Historial completo de valores EAV (actuales e históricos).
     * Útil para la vista de historial de cambios.
     */
    public function atributosHistorico()
    {
        return $this->hasMany(EquipoAtributoValor::class);
    }
}
