<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo TareaMantenimientoCatalogo
 *
 * Catálogo maestro de tareas base de mantenimiento preventivo, editable
 * solo por el Administrador. NO usa SoftDeletes: el ciclo de vida se
 * controla con `activo`, igual que CategoriaEquipo — así las tareas que ya
 * quedaron guardadas dentro de un checklist histórico no se ven afectadas
 * si luego se desactivan acá.
 */
class TareaMantenimientoCatalogo extends Model
{
    use Auditable;

    protected $table = 'tareas_mantenimiento_catalogo';

    protected $fillable = [
        'nombre',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeOrdenadas(Builder $query): Builder
    {
        return $query->orderBy('orden')->orderBy('nombre');
    }
}
