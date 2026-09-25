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
 * El cronograma de mantenimiento preventivo: un plan por CATEGORÍA +
 * EMPRESA (ej. "todas las laptops de Empresa Test cada 6 meses"), no por
 * equipo individual — es un evento masivo. GenerarMantenimientosProgramados
 * (comando programado) revisa fecha_proximo y crea, con antelación, un caso
 * "Programado" por cada equipo activo de esa categoría/empresa — el
 * analista no tiene que armar el lote a mano.
 *
 * fecha_proximo solo avanza cuando TODOS los casos del lote generado
 * llegan a un estado terminal (ver Mantenimiento::completar()/cancelar()),
 * y avanza desde la fecha programada del lote (no desde el cierre real),
 * para mantener una cadencia fija de calendario.
 */
class PlanMantenimiento extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'planes_mantenimiento';

    /** Días de antelación con los que se genera el lote antes de fecha_proximo. */
    const DIAS_ANTELACION_GENERACION = 7;

    protected $fillable = [
        'empresa_id',
        'categoria_id',
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

    public function categoria()
    {
        return $this->belongsTo(CategoriaEquipo::class, 'categoria_id');
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class, 'plan_mantenimiento_id');
    }

    /** Casos que este plan ya generó y siguen abiertos (el lote en curso). */
    public function casosAbiertos()
    {
        return $this->mantenimientos()->whereNotIn('estado', Mantenimiento::ESTADOS_TERMINALES);
    }

    /** Equipos activos alcanzados por este plan (misma categoría + empresa). */
    public function equiposAlcanzados()
    {
        return Equipo::where('empresa_id', $this->empresa_id)
            ->where('categoria_id', $this->categoria_id)
            ->where('activo', true);
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

    /** Planes activos a los que ya les toca generar el lote (dentro del margen de antelación). */
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

    /** Casos generados para el lote que corresponde a la fecha_proximo actual. */
    public function casosDelCicloActual()
    {
        return $this->mantenimientos()->where('proxima_fecha_programada', $this->fecha_proximo);
    }

    /** ¿Ya se generó el lote de esta fecha_proximo? */
    public function loteGenerado(): bool
    {
        return $this->casosDelCicloActual()->exists();
    }

    /**
     * Progreso del lote en curso: [completados, total]. Null si el lote de
     * la fecha_proximo actual todavía no se generó.
     */
    public function progresoLoteActual(): ?array
    {
        $casos = $this->casosDelCicloActual()->get();

        if ($casos->isEmpty()) {
            return null;
        }

        $completados = $casos->whereIn('estado', Mantenimiento::ESTADOS_TERMINALES)->count();

        return ['completados' => $completados, 'total' => $casos->count()];
    }

    /** ¿Todos los casos del lote en curso llegaron a un estado terminal? */
    public function loteCompleto(): bool
    {
        $progreso = $this->progresoLoteActual();

        return $progreso !== null && $progreso['completados'] === $progreso['total'];
    }

    /**
     * Recalcula fecha_proximo a partir de una fecha base (por defecto, la
     * propia fecha_proximo actual, para mantener una cadencia fija de
     * calendario en vez de ir corriéndose según cuándo se cierre cada lote)
     * + frecuencia_meses.
     */
    public function avanzarProximaFecha(\DateTimeInterface|string|null $desde = null): void
    {
        $base = $desde ? \Carbon\Carbon::parse($desde) : $this->fecha_proximo->copy();

        $this->update([
            'fecha_proximo' => $base->addMonths($this->frecuencia_meses)->toDateString(),
        ]);
    }

    /**
     * Si el lote en curso ya está completo, avanza el plan a la próxima
     * fecha. Se llama al cerrar cada caso individual del lote (ver
     * Mantenimiento::completar()/cancelar()) — el plan solo avanza cuando
     * el ÚLTIMO caso pendiente del lote se cierra.
     */
    public function avanzarSiLoteCompleto(): void
    {
        if ($this->loteCompleto()) {
            $this->avanzarProximaFecha();
        }
    }
}
