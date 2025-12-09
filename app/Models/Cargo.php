<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    protected $fillable = ['nombre', 'empresa_id', 'departamento_id'];
    
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
