<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrasladoItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'traslado_id',
        'equipo_id',
        'observaciones',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Relaciones
    // ─────────────────────────────────────────────────────────────────────────

    public function traslado()
    {
        return $this->belongsTo(Traslado::class);
    }

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }
}
