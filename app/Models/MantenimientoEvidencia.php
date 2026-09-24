<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Modelo MantenimientoEvidencia
 *
 * Galería de fotos de un mantenimiento/reparación (antes/durante/después).
 */
class MantenimientoEvidencia extends Model
{
    protected $table = 'mantenimiento_evidencias';

    protected $fillable = [
        'mantenimiento_id',
        'ruta_archivo',
        'tipo',
        'subido_por_id',
    ];

    public function mantenimiento()
    {
        return $this->belongsTo(Mantenimiento::class);
    }

    public function subidoPor()
    {
        return $this->belongsTo(User::class, 'subido_por_id');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->ruta_archivo);
    }
}
