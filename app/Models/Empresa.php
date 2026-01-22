<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $fillable = ['nombre', 'direccion', 'telefono', 'rif', 'estado'];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
