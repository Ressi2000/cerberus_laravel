<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Traslado extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'traslados';

    protected $fillable = [
        'empresa_id',
        'numero',
        'ubicacion_origen_id',
        'ubicacion_destino_id',
        'motivo',
        'recibe_id',
        'autoriza_id',
        'realizado_por_id',
        'fecha_traslado',
        'observaciones',
        'estado',
        'motivo_reversion',
        'revertido_por_id',
        'revertido_at',
    ];

    protected $casts = [
        'fecha_traslado' => 'date',
        'revertido_at'   => 'datetime',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Relaciones
    // ─────────────────────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function ubicacionOrigen()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_origen_id');
    }

    public function ubicacionDestino()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_destino_id');
    }

    public function recibe()
    {
        return $this->belongsTo(User::class, 'recibe_id');
    }

    public function autoriza()
    {
        return $this->belongsTo(User::class, 'autoriza_id');
    }

    public function realizadoPor()
    {
        return $this->belongsTo(User::class, 'realizado_por_id');
    }

    public function items()
    {
        return $this->hasMany(TrasladoItem::class);
    }

    public function revertidoPor()
    {
        return $this->belongsTo(User::class, 'revertido_por_id');
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

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Genera el próximo número correlativo para una empresa.
     * Formato: TRA-YYYY-NNN (ej. TRA-2026-001)
     */
    public static function generarNumero(int $empresaId): string
    {
        $year = now()->year;

        $ultimo = static::withTrashed()
            ->where('empresa_id', $empresaId)
            ->whereYear('created_at', $year)
            ->count();

        return sprintf('TRA-%d-%03d', $year, $ultimo + 1);
    }
}
