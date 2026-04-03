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

    protected $casts = [
        'asignable' => 'boolean',
    ];

    public function atributos()
    {
        return $this->hasMany(AtributoEquipo::class, 'categoria_id')->orderBy('orden');
    }

    public function equipos()
    {
        return $this->hasMany(Equipo::class, 'categoria_id');
    }
}
