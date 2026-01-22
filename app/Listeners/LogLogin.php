<?php

namespace App\Listeners;

use App\Models\Auditoria;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogLogin
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
   public function handle(Login $event): void
    {
        Auditoria::create([
            'usuario_id' => $event->user->id,
            'tabla' => 'users',
            'registro_id' => $event->user->id,
            'accion' => 'LOGIN',
            'valores_previos' => null,
            'valores_nuevos' => json_encode([
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]),
            'created_at' => now(),
        ]);
    }
}
