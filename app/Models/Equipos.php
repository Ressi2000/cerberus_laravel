<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipos extends Model
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
        'observaciones'
    ];

    protected static function booted()
    {
        static::updating(function ($equipo) {
            if ($equipo->isDirty('categoria_id')) {
                throw new \Exception('La categoría no puede modificarse.');
            }
        });
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaEquipo::class);
    }

    public function estado()
    {
        return $this->belongsTo(EstadoEquipo::class);
    }

    public function atributosActuales()
    {
        return $this->hasMany(EquipoAtributoValor::class)
                    ->where('es_actual', true);
    }

    public function atributosHistorico()
    {
        return $this->hasMany(EquipoAtributoValor::class);
    }
}
