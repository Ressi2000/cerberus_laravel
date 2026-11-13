<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo MonitorMedicion
 *
 * Punto de la serie de latencia/disponibilidad de un host (máx. uno por
 * minuto). Se purga pasados `monitor_red.retencion_mediciones_dias` días;
 * la disponibilidad histórica se calcula desde monitor_caidas, no desde aquí.
 */
class MonitorMedicion extends Model
{
    protected $table = 'monitor_mediciones';

    public $timestamps = false;

    protected $fillable = [
        'monitor_host_id',
        'medido_en',
        'exito',
        'latencia_ms',
    ];

    protected $casts = [
        'medido_en'   => 'datetime',
        'exito'       => 'boolean',
        'latencia_ms' => 'float',
    ];

    public function host()
    {
        return $this->belongsTo(MonitorHost::class, 'monitor_host_id')->withTrashed();
    }
}
