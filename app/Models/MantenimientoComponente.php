<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo MantenimientoComponente
 *
 * Detalle de qué componentes pide un mantenimiento/reparación. "Pendiente"
 * significa que no había stock suficiente al pedirlo — todavía no se
 * descontó nada del almacén. "Entregado" significa que ya se descontó.
 * "Cancelado" significa que el caso terminó sin que llegara a haber stock.
 */
class MantenimientoComponente extends Model
{
    protected $table = 'mantenimiento_componentes';

    const ESTADO_PENDIENTE = 'Pendiente';
    const ESTADO_ENTREGADO = 'Entregado';
    /** El caso se cerró antes de que hubiera stock: ya no se va a entregar. */
    const ESTADO_CANCELADO = 'Cancelado';

    protected $fillable = [
        'mantenimiento_id',
        'componente_id',
        'pieza_id',
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

    /** Pieza exacta entregada (familias por unidad), si aplica. */
    public function pieza()
    {
        return $this->belongsTo(PiezaExtraida::class, 'pieza_id');
    }

    public function entregadoPor()
    {
        return $this->belongsTo(User::class, 'entregado_por_id');
    }

    /**
     * Solicitudes que el almacén todavía debe atender: pendientes de casos
     * abiertos que el usuario puede ver (misma regla de empresa que
     * Mantenimiento::visiblePara()).
     */
    public function scopePendientesVisiblesPara(Builder $query, User $actor): Builder
    {
        return $query->where('estado', self::ESTADO_PENDIENTE)
            ->whereHas('mantenimiento', fn ($m) => $m->visiblePara($actor)->abiertos());
    }

    public function estaPendiente(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }
}
