<?php

namespace App\Models;

use App\Traits\Auditable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo MonitorHost
 *
 * Un host (IP o nombre DNS) cuya disponibilidad se revisa por ping, con un
 * puerto TCP opcional de respaldo para equipos que bloquean ICMP.
 *
 * Los campos de configuración (nombre, host, tipo...) se editan desde el
 * módulo y quedan auditados. Los campos de "último estado" (estado,
 * latencia_ms, fallos_consecutivos...) cambian cada minuto: se escriben con
 * saveQuietly() desde registrarResultado() para no inundar la auditoría.
 */
class MonitorHost extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    public const ESTADO_PENDIENTE = 'Pendiente';
    public const ESTADO_ACTIVO    = 'Activo';
    public const ESTADO_CAIDO     = 'Caido';

    /** Resultado de registrarResultado() cuando el host acaba de caer / recuperarse. */
    public const EVENTO_CAIDA      = 'caida';
    public const EVENTO_RECUPERADO = 'recuperado';

    protected $table = 'monitor_hosts';

    protected $fillable = [
        'empresa_id',
        'equipo_id',
        'enlace_id',
        'nombre',
        'host',
        'tipo',
        'proveedor',
        'puerto_tcp',
        'pausado',
        'observaciones',
        'creado_por',
    ];

    protected $casts = [
        'pausado'             => 'boolean',
        'puerto_tcp'          => 'integer',
        'latencia_ms'         => 'float',
        'fallos_consecutivos' => 'integer',
        'estado_desde'        => 'datetime',
        'primer_fallo_en'     => 'datetime',
        'ultima_revision'     => 'datetime',
    ];

    protected static function booted(): void
    {
        // Si cambia el destino que se revisa (host/puerto) o se pausa el
        // monitoreo, el estado anterior deja de ser válido: se cierra la
        // caída abierta y el host vuelve a "Pendiente" hasta la próxima revisión.
        static::updated(function (MonitorHost $host) {
            if ($host->wasChanged(['host', 'puerto_tcp']) || ($host->wasChanged('pausado') && $host->pausado)) {
                $host->reiniciarEstado();
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

    /** Equipo del inventario al que corresponde este host (opcional). */
    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    /** Enlace (proveedor de internet de la localidad) al que pertenece, si aplica. */
    public function enlace()
    {
        return $this->belongsTo(MonitorEnlace::class, 'enlace_id');
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function caidas()
    {
        return $this->hasMany(MonitorCaida::class);
    }

    public function caidaAbierta()
    {
        return $this->hasOne(MonitorCaida::class)->whereNull('fin')->latestOfMany('inicio');
    }

    public function mediciones()
    {
        return $this->hasMany(MonitorMedicion::class);
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

    /** Hosts que el scheduler debe revisar. */
    public function scopeMonitoreables(Builder $query): Builder
    {
        return $query->where('pausado', false);
    }

    public function scopeCaidos(Builder $query): Builder
    {
        return $query->where('pausado', false)->where('estado', self::ESTADO_CAIDO);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers de negocio
    // ─────────────────────────────────────────────────────────────────────────

    /** IP v4/v6 o nombre DNS sin espacios ni caracteres de shell. */
    public static function hostValido(?string $host): bool
    {
        $host = trim((string) $host);

        if ($host === '' || strlen($host) > 255) {
            return false;
        }

        return filter_var($host, FILTER_VALIDATE_IP) !== false
            || preg_match('/^[a-z0-9]([a-z0-9.\-]*[a-z0-9])?$/i', $host) === 1;
    }

    public function estaCaido(): bool
    {
        return ! $this->pausado && $this->estado === self::ESTADO_CAIDO;
    }

    public function estaActivo(): bool
    {
        return ! $this->pausado && $this->estado === self::ESTADO_ACTIVO;
    }

    /** Etiqueta legible del estado (la BD guarda "Caido" sin tilde). */
    public function etiquetaEstado(): string
    {
        if ($this->pausado) {
            return 'Pausado';
        }

        return match ($this->estado) {
            self::ESTADO_ACTIVO => 'Activo',
            self::ESTADO_CAIDO  => 'Caído',
            default             => 'Pendiente',
        };
    }

    /**
     * Aplica el resultado de una revisión.
     *
     * Un host solo se declara "Caído" tras `$fallosParaCaida` revisiones
     * fallidas seguidas, y la caída se fecha en el PRIMER fallo (no en la
     * confirmación), así la duración y la disponibilidad son exactas sin
     * generar alertas por un paquete perdido.
     *
     * Devuelve EVENTO_CAIDA si un host que estaba Activo acaba de caer,
     * EVENTO_RECUPERADO si estaba Caído y volvió, o null si no hay nada
     * que notificar.
     */
    public function registrarResultado(bool $ok, ?float $latenciaMs, string $metodo, CarbonInterface $ahora, int $fallosParaCaida): ?string
    {
        $evento        = null;
        $estadoPrevio  = $this->estado;
        $revisionPrevia = $this->ultima_revision;

        $this->metodo          = $metodo;
        $this->ultima_revision = $ahora;

        if ($ok) {
            $this->latencia_ms         = $latenciaMs;
            $this->fallos_consecutivos = 0;
            $this->primer_fallo_en     = null;

            if ($estadoPrevio !== self::ESTADO_ACTIVO) {
                $this->estado       = self::ESTADO_ACTIVO;
                $this->estado_desde = $ahora;

                if ($this->cerrarCaidasAbiertas($ahora) > 0 && $estadoPrevio === self::ESTADO_CAIDO) {
                    $evento = self::EVENTO_RECUPERADO;
                }
            }
        } else {
            $this->latencia_ms = null;
            $this->fallos_consecutivos++;
            $this->primer_fallo_en ??= $ahora;

            if ($estadoPrevio !== self::ESTADO_CAIDO && $this->fallos_consecutivos >= max(1, $fallosParaCaida)) {
                $this->estado       = self::ESTADO_CAIDO;
                $this->estado_desde = $this->primer_fallo_en;

                if (! $this->caidas()->whereNull('fin')->exists()) {
                    $this->caidas()->create(['inicio' => $this->primer_fallo_en]);
                }

                // Un host nuevo que nunca respondió no "se cayó": se registra
                // la caída pero no se alerta (suele ser un error de configuración).
                if ($estadoPrevio === self::ESTADO_ACTIVO) {
                    $evento = self::EVENTO_CAIDA;
                }
            }
        }

        $this->saveQuietly();

        // Medición para las gráficas: como máximo una por minuto por host
        // (la revisión manual no duplica puntos), o siempre que cambie el estado.
        if ($evento || $estadoPrevio !== $this->estado || ! $revisionPrevia || $revisionPrevia->diffInSeconds($ahora, true) >= 50) {
            $this->mediciones()->create([
                'medido_en'   => $ahora,
                'exito'       => $ok,
                'latencia_ms' => $ok ? $latenciaMs : null,
            ]);
        }

        return $evento;
    }

    /** Vuelve el host a "Pendiente" y cierra su caída abierta, sin auditar. */
    public function reiniciarEstado(): void
    {
        $this->cerrarCaidasAbiertas(now());

        $this->forceFill([
            'estado'              => self::ESTADO_PENDIENTE,
            'estado_desde'        => null,
            'latencia_ms'         => null,
            'metodo'              => null,
            'fallos_consecutivos' => 0,
            'primer_fallo_en'     => null,
        ])->saveQuietly();
    }

    private function cerrarCaidasAbiertas(CarbonInterface $fin): int
    {
        $abiertas = $this->caidas()->whereNull('fin')->get();

        foreach ($abiertas as $caida) {
            $caida->update([
                'fin'               => $fin,
                'duracion_segundos' => max(0, (int) $caida->inicio->diffInSeconds($fin, true)),
            ]);
        }

        return $abiertas->count();
    }
}
