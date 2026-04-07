<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo Asignacion — v2
 *
 * Estados simplificados:
 *   Activa  → tiene al menos un equipo no devuelto
 *   Cerrada → todos los equipos fueron devueltos
 *
 * Tipo de receptor (mutuamente excluyentes):
 *   Personal    → usuario_id está poblado
 *   Área común  → area_empresa_id + area_departamento_id + area_responsable_id
 */
class Asignacion extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'asignaciones';
 
    protected $fillable = [
        'empresa_id',
        // Receptor personal
        'usuario_id',
        // Receptor área común
        'area_empresa_id',
        'area_departamento_id',
        'area_responsable_id',
        // Registro
        'analista_id',
        'fecha_asignacion',
        'estado',
        'observaciones',
    ];
 
    protected $casts = [
        'fecha_asignacion' => 'date',
    ];
 
    // ─────────────────────────────────────────────────────────────────────────
    // Relaciones
    // ─────────────────────────────────────────────────────────────────────────
 
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
 
    /** Usuario receptor (asignación personal) */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
 
    /** Empresa del área (asignación a área común) */
    public function areaEmpresa()
    {
        return $this->belongsTo(Empresa::class, 'area_empresa_id');
    }
 
    /** Departamento del área (asignación a área común) */
    public function areaDepartamento()
    {
        return $this->belongsTo(Departamento::class, 'area_departamento_id');
    }
 
    /** Responsable del área (asignación a área común) */
    public function areaResponsable()
    {
        return $this->belongsTo(User::class, 'area_responsable_id');
    }
 
    public function analista()
    {
        return $this->belongsTo(User::class, 'analista_id');
    }
 
    public function items()
    {
        return $this->hasMany(AsignacionItem::class);
    }
 
    public function itemsActivos()
    {
        return $this->hasMany(AsignacionItem::class)->where('devuelto', false);
    }
 
    public function itemsDevueltos()
    {
        return $this->hasMany(AsignacionItem::class)->where('devuelto', true);
    }

    public function ubicacionDestino()
    {
        // Para asignaciones personales, se muestra la ubicación del usuario.
        // Para asignaciones a áreas comunes, se muestra la ubicación de la empresa del área.
        return $this->usuario?->ubicacion ?? $this->areaEmpresa?->ubicacion;
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
 
    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('estado', 'Activa');
    }
 
    public function scopeCerradas(Builder $query): Builder
    {
        return $query->where('estado', 'Cerrada');
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // Helpers de negocio
    // ─────────────────────────────────────────────────────────────────────────
 
    /** Tipo de receptor: 'personal' | 'area' */
    public function tipoReceptor(): string
    {
        return $this->usuario_id ? 'personal' : 'area';
    }
 
    /**
     * Nombre legible del receptor para mostrar en tablas y planillas.
     */
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
     * Recalcula el estado basado en los items activos.
     * Lógica binaria: Activa si quedan items, Cerrada si no quedan.
     */
    public function recalcularEstado(): void
    {
        $tieneActivos = $this->items()->where('devuelto', false)->exists();
        $nuevoEstado  = $tieneActivos ? 'Activa' : 'Cerrada';
 
        if ($this->estado !== $nuevoEstado) {
            $this->update(['estado' => $nuevoEstado]);
        }
    }
 
    public function estaActiva(): bool
    {
        return $this->estado === 'Activa';
    }
}
