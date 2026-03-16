<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaEquipo extends Model
{
    protected $table = 'categorias_equipos';

    protected $fillable = [
        'nombre',
        'asignable',
        'descripcion'
    ];

    public function atributos()
    {
        return $this->hasMany(AtributoEquipo::class, 'categoria_id');
    }

    public function equipos()
    {
        return $this->hasMany(Equipos::class);
    }
}
