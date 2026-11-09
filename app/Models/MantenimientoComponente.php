<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo MantenimientoComponente
 *
 * Detalle de qué componentes pide un mantenimiento/reparación. "Pendiente"
 * significa que no había stock suficiente al pedirlo — todavía no se
 * descontó nada del almacén. "Entregado" significa que ya se descontó.
 */
class MantenimientoComponente extends Model
{
    protected $table = 'mantenimiento_componentes';

    protected $fillable = [
        'mantenimiento_id',
        'componente_id',
        'cantidad_requerida',
        'estado',
        'fecha_entrega',
        'entregado_por_id',
    ];

    protected $casts = [
        'fecha_entrega' => 'date',
    ];

    public function mantenimiento()
    {
        return $this->belongsTo(Mantenimiento::class);
    }

    public function componente()
    {
        return $this->belongsTo(ComponenteAlmacen::class, 'componente_id');
    }

    public function entregadoPor()
    {
        return $this->belongsTo(User::class, 'entregado_por_id');
    }

    public function estaPendiente(): bool
    {
        return $this->estado === 'Pendiente';
    }
}
