<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoEquipo extends Model
{
    protected $table = 'estados_equipos';

    protected $fillable = ['nombre'];

    public function equipos()
    {
        return $this->hasMany(Equipos::class);
    }
}
