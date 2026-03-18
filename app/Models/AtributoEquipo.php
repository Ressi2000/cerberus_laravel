<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtributoEquipo extends Model
{
    // CRÍTICO: Eloquent inferiría "atributo_equipos" sin esto
    protected $table = 'atributos_equipos';
 
    protected $fillable = [
        'categoria_id',
        'nombre',
        'slug',
        'tipo',
        'requerido',
        'filtrable',
        'visible_en_tabla',
        'orden',
        'opciones', // JSON para tipo 'select' si lo necesitas en el futuro
    ];
 
    protected $casts = [
        'requerido'         => 'boolean',
        'filtrable'         => 'boolean',
        'visible_en_tabla'  => 'boolean',
        'opciones'          => 'array',
    ];
 
    // Relaciones
    public function categoria()
    {
        return $this->belongsTo(CategoriaEquipo::class, 'categoria_id');
    }
 
    public function valores()
    {
        return $this->hasMany(EquipoAtributoValor::class, 'atributo_id');
    }
 
    public function valoresActuales()
    {
        return $this->hasMany(EquipoAtributoValor::class, 'atributo_id')
                    ->where('es_actual', true);
    }
}
