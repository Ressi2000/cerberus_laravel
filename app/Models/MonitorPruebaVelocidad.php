<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo MonitorPruebaVelocidad
 *
 * Resultado de un test de velocidad de internet. Lo mide el NAVEGADOR de
 * quien lo ejecuta (contra speed.cloudflare.com), por lo que refleja la
 * conexión de la sede desde la que se abrió la página, no la del servidor.
 */
class MonitorPruebaVelocidad extends Model
{
    protected $table = 'monitor_pruebas_velocidad';

    protected $fillable = [
        'empresa_id',
        'usuario_id',
        'enlace_id',
        'enlace_asignado_por',
        'bajada_mbps',
        'subida_mbps',
        'ping_ms',
        'jitter_ms',
        'isp',
        'asn',
        'ip_publica',
        'ciudad',
        'pais',
    ];

    protected $casts = [
        'bajada_mbps' => 'float',
        'subida_mbps' => 'float',
        'ping_ms'     => 'float',
        'jitter_ms'   => 'float',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    /** Enlace por el que salió el test (detectado por IP/ASN o elegido a mano). */
    public function enlace()
    {
        return $this->belongsTo(MonitorEnlace::class, 'enlace_id')->withTrashed();
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function scopeVisiblePara(Builder $query, User $actor): Builder
    {
        if ($actor->hasRole('Administrador')) {
            return $query;
        }

        if ($actor->hasRole('Analista') && $actor->empresa_activa_id) {
            return $query->where('empresa_id', $actor->empresa_activa_id);
        }

        return $query->whereRaw('1 = 0');
    }

    /** Mismo proveedor: por ASN si ambos lo tienen, si no por nombre normalizado. */
    public function mismoProveedorQue(?string $isp, ?string $asn): bool
    {
        $a = preg_replace('/\D/', '', (string) $this->asn);
        $b = preg_replace('/\D/', '', (string) $asn);

        if ($a !== '' && $b !== '') {
            return $a === $b;
        }

        $norm = fn ($s) => preg_replace('/[^a-z0-9]/', '', mb_strtolower((string) $s));

        return $norm($this->isp) === $norm($isp);
    }
}
