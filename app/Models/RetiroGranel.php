<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Salida de algo A GRANEL de donde estaba: devolución por error en un caso,
 * retiro de un equipo, o daño estando en el almacén. Ver RetiroGranelService.
 */
class RetiroGranel extends Model
{
    use Auditable;

    protected $table = 'retiros_granel';

    const TIPO_DEVOLUCION = 'devolucion';
    const TIPO_RETIRO     = 'retiro';
    const TIPO_DANADO     = 'danado';

    const TIPOS = [
        self::TIPO_DEVOLUCION => 'Devuelto (corrección)',
        self::TIPO_RETIRO     => 'Retirado del equipo',
        self::TIPO_DANADO     => 'Dañado en el almacén',
    ];

    const DESTINO_ALMACEN  = 'almacen';
    const DESTINO_DEPOSITO = 'deposito';
    const DESTINO_DESECHO  = 'desecho';

    const DESTINOS = [
        self::DESTINO_ALMACEN  => 'Almacén',
        self::DESTINO_DEPOSITO => 'Depósito',
        self::DESTINO_DESECHO  => 'Desechado',
    ];

    protected $fillable = [
        'empresa_id', 'componente_id', 'variante', 'tipo', 'cantidad', 'destino', 'deposito_id',
        'equipo_id', 'mantenimiento_id', 'mantenimiento_componente_id', 'movimiento_componente_id',
        'motivo', 'registrado_por',
    ];

    protected $casts = ['cantidad' => 'integer'];

    public function componente(): BelongsTo { return $this->belongsTo(ComponenteAlmacen::class, 'componente_id'); }
    public function deposito(): BelongsTo { return $this->belongsTo(Deposito::class); }
    public function equipo(): BelongsTo { return $this->belongsTo(Equipo::class); }
    public function mantenimiento(): BelongsTo { return $this->belongsTo(Mantenimiento::class); }
    public function registradoPor(): BelongsTo { return $this->belongsTo(User::class, 'registrado_por'); }

    public function labelTipo(): string { return self::TIPOS[$this->tipo] ?? $this->tipo; }

    public function labelDestino(): string
    {
        return $this->destino === self::DESTINO_DEPOSITO && $this->deposito
            ? "Depósito {$this->deposito->nombre}"
            : (self::DESTINOS[$this->destino] ?? $this->destino);
    }
}
