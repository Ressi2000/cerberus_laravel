<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo Equipo
 *
 * Representa un activo tecnológico del inventario.
 * Usa arquitectura EAV para atributos técnicos variables por categoría.
 * Tiene SoftDeletes para eliminación administrativa (solo Administrador).
 * El campo 'activo' controla la baja lógica (Analista).
 */
class Equipo extends Model
{
    use SoftDeletes;
 
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
        return $this->belongsTo(CategoriaEquipo::class);
    }
 
    /** Estado actual (Disponible, Asignado, En préstamo, etc.) */
    public function estado()
    {
        return $this->belongsTo(EstadoEquipo::class);
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
