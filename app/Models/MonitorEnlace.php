<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo MonitorEnlace
 *
 * Una conexión a internet de una localidad (empresa): proveedor, puerta de
 * enlace, IP pública y plan contratado. Sirve para:
 *   - Monitorear la disponibilidad de CADA enlace (su IP pública como host),
 *     aunque no sea el que está en uso en ese momento.
 *   - Atribuir cada test de velocidad al enlace por el que salió (detectado
 *     por IP pública / ASN, o elegido a mano) y compararlo con el plan.
 */
class MonitorEnlace extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    public const TIPOS = ['Fibra', 'Cobre / ADSL', 'Cable', 'Radioenlace', 'Satelital', '4G / LTE', 'Dedicado', 'Otro'];

    protected $table = 'monitor_enlaces';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'proveedor',
        'tipo_conexion',
        'gateway',
        'ip_publica',
        'asn',
        'bajada_contratada_mbps',
        'subida_contratada_mbps',
        'principal',
        'con_cuota',
        'activo',
        'observaciones',
        'creado_por',
    ];

    protected $casts = [
        'bajada_contratada_mbps' => 'float',
        'subida_contratada_mbps' => 'float',
        'principal'              => 'boolean',
        'con_cuota'              => 'boolean',
        'activo'                 => 'boolean',
    ];

    protected static function booted(): void
    {
        // Solo un enlace principal por empresa.
        static::saved(function (MonitorEnlace $enlace) {
            if ($enlace->principal && ($enlace->wasRecentlyCreated || $enlace->wasChanged('principal'))) {
                static::where('empresa_id', $enlace->empresa_id)
                    ->where('id', '!=', $enlace->id)
                    ->where('principal', true)
                    ->get()
                    ->each(fn ($otro) => $otro->update(['principal' => false]));
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Relaciones
    // ─────────────────────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function hosts()
    {
        return $this->hasMany(MonitorHost::class, 'enlace_id');
    }

    public function pruebas()
    {
        return $this->hasMany(MonitorPruebaVelocidad::class, 'enlace_id');
    }

    public function ultimaPrueba()
    {
        return $this->hasOne(MonitorPruebaVelocidad::class, 'enlace_id')->latestOfMany();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────────────────

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

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers de negocio
    // ─────────────────────────────────────────────────────────────────────────

    public static function normalizarAsn(?string $asn): ?string
    {
        $digitos = preg_replace('/\D/', '', (string) $asn);

        return $digitos === '' ? null : $digitos;
    }

    /**
     * ¿Por qué enlace de la empresa salió una conexión con esta IP pública / ASN?
     * Primero por IP pública exacta; si no, por ASN, pero solo cuando un único
     * enlace de la empresa tiene ese ASN (dos líneas del mismo ISP son ambiguas).
     *
     * @return array{0: ?MonitorEnlace, 1: ?string}  [enlace, 'ip' | 'asn' | null]
     */
    public static function detectar(int $empresaId, ?string $ip, ?string $asn): array
    {
        $enlaces = static::where('empresa_id', $empresaId)->activos()->get();

        if ($ip && ($porIp = $enlaces->firstWhere('ip_publica', $ip))) {
            return [$porIp, 'ip'];
        }

        $asn = static::normalizarAsn($asn);
        if ($asn) {
            $porAsn = $enlaces->filter(fn ($e) => static::normalizarAsn($e->asn) === $asn);
            if ($porAsn->count() === 1) {
                return [$porAsn->first(), 'asn'];
            }
        }

        return [null, null];
    }

    /** Host que monitorea la IP pública de este enlace (requiere `hosts` cargado). */
    public function hostWan(): ?MonitorHost
    {
        return $this->ip_publica ? $this->hosts->firstWhere('host', $this->ip_publica) : null;
    }

    /** % de la velocidad contratada que alcanzó un valor medido (null si no hay plan). */
    public function porcentajeDelPlan(?float $medido, string $sentido = 'bajada'): ?float
    {
        $plan = $sentido === 'subida' ? $this->subida_contratada_mbps : $this->bajada_contratada_mbps;

        return $plan && $medido !== null ? round($medido / $plan * 100, 1) : null;
    }

    /**
     * Crea (o reutiliza) el host que monitorea la IP pública de este enlace,
     * para saber si el enlace está arriba aunque no sea el que está en uso.
     */
    public function asegurarHostWan(User $actor): MonitorHost
    {
        $host = MonitorHost::where('empresa_id', $this->empresa_id)
            ->where('host', $this->ip_publica)
            ->first();

        if ($host) {
            if ($host->enlace_id !== $this->id) {
                $host->update(['enlace_id' => $this->id]);
            }

            return $host;
        }

        return MonitorHost::create([
            'empresa_id' => $this->empresa_id,
            'enlace_id'  => $this->id,
            'nombre'     => mb_substr("WAN {$this->nombre}", 0, 100),
            'host'       => $this->ip_publica,
            'tipo'       => 'Enlace ISP',
            'proveedor'  => mb_substr($this->proveedor, 0, 60),
            'creado_por' => $actor->id,
        ]);
    }
}
