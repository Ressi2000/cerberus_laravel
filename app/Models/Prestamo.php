<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo Prestamo
 *
 * Estados:
 *   Activo  → tiene al menos un equipo no devuelto y no ha vencido
 *   Vencido → fecha_devolucion_esperada < hoy y aún hay items activos
 *   Cerrado → todos los equipos fueron devueltos
 *
 * Tipo de receptor (mutuamente excluyentes):
 *   Personal   → usuario_id está poblado
 *   Área común → area_empresa_id + area_departamento_id + area_responsable_id
 */
class Prestamo extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'prestamos';

    protected $fillable = [
        'empresa_id',
        'analista_id',
        // Receptor personal
        'usuario_id',
        // Receptor área común
        'area_empresa_id',
        'area_departamento_id',
        'area_responsable_id',
        // Fechas y estado
        'fecha_prestamo',
        'fecha_devolucion_esperada',
        'fecha_devolucion_real',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_prestamo'            => 'date',
        'fecha_devolucion_esperada' => 'date',
        'fecha_devolucion_real'     => 'date',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Relaciones
    // ─────────────────────────────────────────────────────────────────────────

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function analista()
    {
        return $this->belongsTo(User::class, 'analista_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function areaEmpresa()
    {
        return $this->belongsTo(Empresa::class, 'area_empresa_id');
    }

    public function areaDepartamento()
    {
        return $this->belongsTo(Departamento::class, 'area_departamento_id');
    }

    public function areaResponsable()
    {
        return $this->belongsTo(User::class, 'area_responsable_id');
    }

    public function items()
    {
        return $this->hasMany(PrestamoItem::class);
    }

    public function itemsActivos()
    {
        return $this->hasMany(PrestamoItem::class)->where('devuelto', false);
    }

    public function itemsDevueltos()
    {
        return $this->hasMany(PrestamoItem::class)->where('devuelto', true);
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
        return $query->where('estado', 'Activo');
    }

    public function scopeVencidos(Builder $query): Builder
    {
        return $query->where('estado', 'Vencido');
    }

    public function scopeCerrados(Builder $query): Builder
    {
        return $query->where('estado', 'Cerrado');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers de negocio
    // ─────────────────────────────────────────────────────────────────────────

    public function tipoReceptor(): string
    {
        return $this->usuario_id ? 'personal' : 'area';
    }

    public function receptorNombre(): string
    {
        if ($this->usuario) {
            return $this->usuario->name;
        }

        $partes = array_filter([
            $this->areaDepartamento?->nombre,
            $this->areaEmpresa?->nombre,
        ]);

        return implode(' — ', $partes) ?: '—';
    }

    /**
     * Recalcula el estado basado en items activos y fecha de vencimiento.
     *   Cerrado → no quedan items activos
     *   Vencido → quedan items activos y la fecha esperada ya pasó
     *   Activo  → quedan items activos y no ha vencido (o no hay fecha)
     */
    public function recalcularEstado(): void
    {
        $tieneActivos = $this->items()->where('devuelto', false)->exists();

        if (! $tieneActivos) {
            $nuevoEstado = 'Cerrado';
            $fechaReal   = $this->fecha_devolucion_real ?? now()->toDateString();
        } else {
            $vencido     = $this->fecha_devolucion_esperada
                && $this->fecha_devolucion_esperada->isPast()
                && ! $this->fecha_devolucion_esperada->isToday();
            $nuevoEstado = $vencido ? 'Vencido' : 'Activo';
            $fechaReal   = null;
        }

        $this->update([
            'estado'                => $nuevoEstado,
            'fecha_devolucion_real' => $fechaReal,
        ]);
    }

    /**
     * Renueva la fecha de devolución esperada y reactiva si estaba Vencido.
     */
    public function renovar(string $nuevaFecha): void
    {
        $updates = ['fecha_devolucion_esperada' => $nuevaFecha];

        if ($this->estado === 'Vencido') {
            $updates['estado'] = 'Activo';
        }

        $this->update($updates);
    }

    public function estaActivo(): bool
    {
        return in_array($this->estado, ['Activo', 'Vencido']);
    }

    public function estaVencido(): bool
    {
        return $this->estado === 'Vencido';
    }

    public function estaCerrado(): bool
    {
        return $this->estado === 'Cerrado';
    }

    /**
     * Marca como 'Vencido' todo préstamo Activo cuya fecha esperada ya pasó.
     * El estado solo se recalculaba antes al registrar una devolución, por lo
     * que un préstamo vencido sin movimiento quedaba indefinidamente "Activo".
     */
    public static function marcarVencidosAutomaticamente(): int
    {
        return static::where('estado', 'Activo')
            ->whereNotNull('fecha_devolucion_esperada')
            ->where('fecha_devolucion_esperada', '<', now()->startOfDay())
            ->update(['estado' => 'Vencido']);
    }
}
