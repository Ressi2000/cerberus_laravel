<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Una firma digital simple (trazo en canvas) sobre un documento
 * (Asignacion, Prestamo o Traslado), asociada a un rol ('receptor' o 'jefe').
 *
 * No reemplaza la firma manuscrita en papel — es un mecanismo alternativo,
 * con valor probatorio de firma simple (no certificada).
 */
class Firma extends Model
{
    protected $table = 'firmas';

    protected $fillable = [
        'firmable_type',
        'firmable_id',
        'rol',
        'user_id',
        'estado',
        'imagen',
        'ip',
        'user_agent',
        'firmado_at',
    ];

    protected $casts = [
        'firmado_at' => 'datetime',
    ];

    public function firmable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function estaFirmada(): bool
    {
        return $this->estado === 'firmado';
    }
}
