<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Una pregunta hecha a Cerberus Bot y cómo la respondió. */
class AsistenteConsulta extends Model
{
    protected $table = 'asistente_consultas';

    protected $fillable = [
        'user_id', 'empresa_id', 'pregunta', 'respuesta', 'herramientas',
        'sin_respuesta', 'error', 'modelo', 'duracion_ms', 'tiempos',
    ];

    protected $casts = [
        'herramientas'  => 'array',
        'tiempos'       => 'array',
        'sin_respuesta' => 'boolean',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
