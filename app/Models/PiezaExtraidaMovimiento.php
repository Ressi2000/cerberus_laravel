<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo PiezaExtraidaMovimiento
 *
 * Un renglón del kardex de una PiezaExtraida: qué pasó, cuándo, y con qué
 * equipo/depósito/mantenimiento se relaciona. Es lo que arma el histórico
 * de trazabilidad ("esta pieza salió de aquí, fue a almacén, luego se instaló
 * en tal equipo...").
 */
class PiezaExtraidaMovimiento extends Model
{
    use Auditable;

    protected $table = 'piezas_extraidas_movimientos';

    protected $fillable = [
        'pieza_id',
        'tipo',
        'equipo_relacionado_id',
        'deposito_relacionado_id',
        'mantenimiento_id',
        'registrado_por',
        'observaciones',
    ];

    // ── Tipos de movimiento ──────────────────────────────────────────────────
    const TIPO_EXTRACCION        = 'extraccion';
    const TIPO_INGRESO_ALMACEN   = 'ingreso_almacen';
    const TIPO_INSTALACION       = 'instalacion';
    const TIPO_DESINSTALACION    = 'desinstalacion';
    const TIPO_ENVIO_DEPOSITO    = 'envio_deposito';
    const TIPO_TRASLADO_DEPOSITO = 'traslado_deposito';

    const TIPOS = [
        self::TIPO_EXTRACCION        => 'Extraída del equipo',
        self::TIPO_INGRESO_ALMACEN   => 'Ingresó al almacén',
        self::TIPO_INSTALACION       => 'Instalada en un equipo',
        self::TIPO_DESINSTALACION    => 'Desinstalada',
        self::TIPO_ENVIO_DEPOSITO    => 'Enviada a depósito',
        self::TIPO_TRASLADO_DEPOSITO => 'Trasladada entre depósitos',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function pieza(): BelongsTo
    {
        return $this->belongsTo(PiezaExtraida::class, 'pieza_id');
    }

    public function equipoRelacionado(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_relacionado_id');
    }

    public function depositoRelacionado(): BelongsTo
    {
        return $this->belongsTo(Deposito::class, 'deposito_relacionado_id');
    }

    public function mantenimiento(): BelongsTo
    {
        return $this->belongsTo(Mantenimiento::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function labelTipo(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }
}
