<?php

namespace App\Listeners;

use App\Models\Auditoria;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogLogout
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        Auditoria::create([
            'usuario_id' => $event->user->id,
            'tabla' => 'users',
            'registro_id' => $event->user->id,
            'accion' => 'LOGOUT',
            'valores_previos' => null,
            'valores_nuevos' => json_encode([
                'ip' => request()->ip(),
            ]),
            'created_at' => now(),
        ]);
    }
}
