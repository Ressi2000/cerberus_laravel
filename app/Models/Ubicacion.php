<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ubicacion extends Model
{
    protected $fillable = ['nombre', 'tipo', 'descripcion', 'empresa_id'];
}
