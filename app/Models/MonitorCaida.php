<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo MonitorCaida
 *
 * Un intervalo en el que un host no respondió. `fin` null = caída en curso.
 * Tabla de detalle: sin softDeletes ni auditoría (la escribe el monitoreo
 * automático, no una persona).
 */
class MonitorCaida extends Model
{
    protected $table = 'monitor_caidas';

    public $timestamps = false;

    protected $fillable = [
        'monitor_host_id',
        'inicio',
        'fin',
        'duracion_segundos',
    ];

    protected $casts = [
        'inicio'            => 'datetime',
        'fin'               => 'datetime',
        'duracion_segundos' => 'integer',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Relaciones
    // ─────────────────────────────────────────────────────────────────────────

    /** Incluye hosts eliminados: el historial de caídas se conserva. */
    public function host()
    {
        return $this->belongsTo(MonitorHost::class, 'monitor_host_id')->withTrashed();
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
            return $query->whereHas('host', fn ($h) => $h->where('empresa_id', $actor->empresa_activa_id));
        }

        return $query->whereRaw('1 = 0');
    }

    public function scopeEnCurso(Builder $query): Builder
    {
        return $query->whereNull('fin');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    public function enCurso(): bool
    {
        return $this->fin === null;
    }

    /** Duración en segundos (si sigue en curso, hasta ahora). */
    public function duracion(): int
    {
        return $this->duracion_segundos
            ?? max(0, (int) $this->inicio->diffInSeconds($this->fin ?? now(), true));
    }

    /** "3 d 2 h 5 min", "12 min", "40 s". */
    public static function formatearDuracion(int $segundos): string
    {
        if ($segundos < 60) {
            return $segundos . ' s';
        }

        $d = intdiv($segundos, 86400);
        $h = intdiv($segundos % 86400, 3600);
        $m = intdiv($segundos % 3600, 60);

        return collect([$d ? "{$d} d" : null, $h ? "{$h} h" : null, "{$m} min"])->filter()->implode(' ');
    }
}
