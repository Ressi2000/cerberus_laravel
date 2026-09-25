<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo PlanMantenimiento
 *
 * El cronograma de mantenimiento preventivo: un plan por equipo, con su
 * frecuencia y checklist. GenerarMantenimientosProgramados (comando
 * programado) revisa fecha_proximo y crea el caso en Mantenimientos con
 * antelación — el analista no tiene que acordarse de crear cada uno.
 */
class PlanMantenimiento extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'planes_mantenimiento';

    /** Días de antelación con los que se genera el caso antes de fecha_proximo. */
    const DIAS_ANTELACION_GENERACION = 7;

    protected $fillable = [
        'empresa_id',
        'equipo_id',
        'frecuencia_meses',
        'fecha_proximo',
        'checklist_plantilla',
        'activo',
        'observaciones',
        'creado_por',
    ];

    protected $casts = [
        'fecha_proximo'        => 'date',
        'checklist_plantilla'  => 'array',
        'activo'                => 'boolean',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Relaciones
    // ─────────────────────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class, 'plan_mantenimiento_id');
    }

    /** El caso "Programado"/"En proceso" que este plan ya generó y sigue abierto, si hay uno. */
    public function casoAbierto()
    {
        return $this->hasOne(Mantenimiento::class, 'plan_mantenimiento_id')
            ->whereNotIn('estado', Mantenimiento::ESTADOS_TERMINALES)
            ->latestOfMany();
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

    /** Planes activos a los que ya les toca generar el caso (dentro del margen de antelación). */
    public function scopePendientesDeGenerar(Builder $query): Builder
    {
        return $query->activos()
            ->where('fecha_proximo', '<=', now()->addDays(self::DIAS_ANTELACION_GENERACION)->toDateString());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers de negocio
    // ─────────────────────────────────────────────────────────────────────────

    public function estaVencido(): bool
    {
        return $this->fecha_proximo->isPast();
    }

    public function estaProximo(): bool
    {
        return ! $this->estaVencido()
            && $this->fecha_proximo->lte(now()->addDays(self::DIAS_ANTELACION_GENERACION));
    }

    /**
     * Recalcula fecha_proximo a partir de una fecha base (normalmente la
     * fecha de cierre del caso que este plan generó) + frecuencia_meses.
     */
    public function avanzarProximaFecha(\DateTimeInterface|string $desde): void
    {
        $this->update([
            'fecha_proximo' => \Carbon\Carbon::parse($desde)->addMonths($this->frecuencia_meses)->toDateString(),
        ]);
    }
}
