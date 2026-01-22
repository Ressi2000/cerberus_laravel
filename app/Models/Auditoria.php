<?php

namespace App\Models;

use App\Helper\AuditDiff;
use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    public $timestamps = false;

    protected $table = 'auditoria';

    protected $fillable = [
        'usuario_id',
        'tabla',
        'registro_id',
        'accion',
        'valores_previos',
        'valores_nuevos',
        'created_at'
    ];

    protected $ignoredAuditFields = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
        'remember_token',
        'empresa_activa_id',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function scopeVisiblePara($query, User $user)
    {
        if ($user->hasRole('Administrador')) {
            return $query;
        }

        if ($user->hasRole('Analista')) {
            return $query->where('usuario_id', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function getCambiosAttribute(): array
    {
        return AuditDiff::diff(
            $this->valores_previos,
            $this->valores_nuevos
        );
    }
}
