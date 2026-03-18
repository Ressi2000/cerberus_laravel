<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipoAtributoValor extends Model
{
    protected $table = 'equipo_atributo_valores';

    protected $fillable = [
        'equipo_id',
        'atributo_id',
        'valor',
        'es_actual',
        'creado_por'
    ];

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function atributo()
    {
        return $this->belongsTo(AtributoEquipo::class, 'atributo_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}
