<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtributoEquipo extends Model
{
    protected $table = 'atributos_equipos';

    protected $fillable = [
        'categoria_id',
        'nombre',
        'slug',
        'tipo',
        'requerido',
        'filtrable',
        'visible_en_tabla',
        'orden'
    ];

    public function categoria()
    {
        return $this->belongsTo(CategoriaEquipo::class, 'categoria_id');
    }
}
