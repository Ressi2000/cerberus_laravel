<?php

namespace App\Traits;

use App\Models\Auditoria;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait Auditable
{
    protected $auditExclude = [
        'remember_token',
    ];

    protected static function bootAuditable()
    {
        static::created(function ($model) {
            $model->registrarAuditoria('CREAR');
        });

        static::updated(function ($model) {
            $model->registrarAuditoria('EDITAR');
        });

        static::deleted(function ($model) {
            $model->registrarAuditoria('ELIMINAR');
        });
    }

    public function registrarAuditoria($accion)
    {
        // Evitar auditoría recursiva
        if ($this->getTable() === (new Auditoria())->getTable()) {
            return;
        }

        try {
            Auditoria::create([
                'usuario_id' => Auth::id() ?? null,
                'tabla' => $this->getTable(),
                'registro_id' => $this->getKey(),
                'accion' => $accion,
                'valores_previos' => json_encode($this->getOriginal()),
                'valores_nuevos' => json_encode($this->getAttributes()),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error auditoria: ' . $e->getMessage());
        }
    }
}
