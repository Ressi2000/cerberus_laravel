<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * Modelo ComponenteAlmacen
 *
 * Catálogo de componentes/repuestos en stock, propio de cada empresa (no se
 * comparte entre empresas). `stock_actual` es la cantidad vigente; el kardex
 * completo de entradas y salidas vive en movimientos_componentes — nunca se
 * edita stock_actual a mano, siempre a través de registrarEntrada()/registrarSalida().
 */
class ComponenteAlmacen extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'componentes_almacen';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'descripcion',
        'unidad',
        'stock_actual',
        'activo',
        'creado_por',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

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

    public function movimientos()
    {
        return $this->hasMany(MovimientoComponente::class, 'componente_id')->latest();
    }

    public function usosEnMantenimiento()
    {
        return $this->hasMany(MantenimientoComponente::class, 'componente_id');
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

    public function tieneStockSuficiente(int $cantidad): bool
    {
        return $this->stock_actual >= $cantidad;
    }

    /** Registra una entrada de stock (compra, ajuste, etc.) y suma al stock actual. */
    public function registrarEntrada(int $cantidad, User $actor, ?string $motivo = null, ?string $observaciones = null): MovimientoComponente
    {
        return DB::transaction(function () use ($cantidad, $actor, $motivo, $observaciones) {
            $this->increment('stock_actual', $cantidad);

            return $this->movimientos()->create([
                'tipo'              => 'Entrada',
                'cantidad'          => $cantidad,
                'motivo'            => $motivo,
                'registrado_por_id' => $actor->id,
                'observaciones'     => $observaciones,
            ]);
        });
    }

    /** Registra una salida de stock (consumo en un mantenimiento, ajuste, etc.). */
    public function registrarSalida(int $cantidad, User $actor, ?string $motivo = null, ?Mantenimiento $mantenimiento = null): MovimientoComponente
    {
        return DB::transaction(function () use ($cantidad, $actor, $motivo, $mantenimiento) {
            $this->decrement('stock_actual', $cantidad);

            return $this->movimientos()->create([
                'mantenimiento_id'  => $mantenimiento?->id,
                'tipo'              => 'Salida',
                'cantidad'          => $cantidad,
                'motivo'            => $motivo,
                'registrado_por_id' => $actor->id,
            ]);
        });
    }
}
