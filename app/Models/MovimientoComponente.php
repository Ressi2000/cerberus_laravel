<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo MovimientoComponente
 *
 * Kardex de entradas y salidas de componentes_almacen. Es un registro de
 * auditoría por sí mismo (nunca se edita ni se borra) — no lleva SoftDeletes
 * ni el trait Auditable, cada fila ya ES el rastro.
 */
class MovimientoComponente extends Model
{
    protected $table = 'movimientos_componentes';

    protected $fillable = [
        'componente_id',
        'mantenimiento_id',
        'tipo',
        'cantidad',
        'motivo',
        'registrado_por_id',
        'observaciones',
    ];

    public function componente()
    {
        return $this->belongsTo(ComponenteAlmacen::class, 'componente_id');
    }

    public function mantenimiento()
    {
        return $this->belongsTo(Mantenimiento::class);
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }
}
