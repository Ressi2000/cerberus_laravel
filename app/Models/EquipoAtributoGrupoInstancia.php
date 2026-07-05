<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipoAtributoGrupoInstancia extends Model
{
    protected $table = 'equipo_atributo_grupo_instancias';

    protected $fillable = [
        'equipo_id',
        'atributo_id',
        'valores', // JSON: {sub_campo_id => valor}
        'orden',
        'es_actual',
        'creado_por',
    ];

    protected $casts = [
        'valores'   => 'array',
        'orden'     => 'integer',
        'es_actual' => 'boolean',
    ];

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function atributo(): BelongsTo
    {
        return $this->belongsTo(AtributoEquipo::class, 'atributo_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}
