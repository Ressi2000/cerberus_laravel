<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Avisa, solo dentro del sistema (campana de notificaciones), que un
 * documento quedó completamente firmado de forma digital. El PDF con las
 * firmas estampadas se ve abriendo la planilla de siempre, no se envía
 * por correo.
 */
class FirmaCompletadaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $tituloDocumento,
        public string $folio,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'tipo'    => 'firma_completada',
            'icono'   => 'task_alt',
            'color'   => 'emerald',
            'titulo'  => 'Documento firmado digitalmente',
            'mensaje' => "{$this->tituloDocumento} ({$this->folio}) quedó firmado por todas las partes.",
            'url'     => null,
            'meta'    => [
                'folio' => $this->folio,
            ],
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
