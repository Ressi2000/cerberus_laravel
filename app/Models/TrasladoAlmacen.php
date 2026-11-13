<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un traslado de stock entre los almacenes de dos empresas, con su motivo.
 * Es la base de la "Autorización de Traslado" (PDF). Ver TrasladoAlmacenService.
 */
class TrasladoAlmacen extends Model
{
    use Auditable;

    protected $table = 'traslados_almacen';

    protected $fillable = [
        'empresa_origen_id',
        'empresa_destino_id',
        'componente_origen_id',
        'componente_destino_id',
        'variante',
        'familia',
        'cantidad',
        'detalle',
        'motivo',
        'registrado_por',
    ];

    protected $casts = [
        'detalle'  => 'array',
        'cantidad' => 'integer',
    ];

    public function empresaOrigen(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_origen_id');
    }

    public function empresaDestino(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_destino_id');
    }

    public function componenteOrigen(): BelongsTo
    {
        return $this->belongsTo(ComponenteAlmacen::class, 'componente_origen_id');
    }

    public function componenteDestino(): BelongsTo
    {
        return $this->belongsTo(ComponenteAlmacen::class, 'componente_destino_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    /** Folio impreso en la autorización (ej. TA-000012). */
    public function folio(): string
    {
        return \App\Services\Folio::etiqueta('traslado_almacen', $this->id);
    }

    /** Lo puede ver el Administrador, o un Analista de cualquiera de las dos empresas. */
    public function visiblePara(User $user): bool
    {
        return $user->hasRole('Administrador')
            || ($user->hasRole('Analista') && in_array((int) $user->empresa_activa_id, [(int) $this->empresa_origen_id, (int) $this->empresa_destino_id], true));
    }
}
