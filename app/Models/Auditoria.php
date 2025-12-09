<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    public $timestamps = false;
    protected $table = 'auditoria';
    protected $fillable = [
        'usuario_id','tabla','registro_id','accion',
        'valores_previos','valores_nuevos','created_at'
    ];
}
