<?php

namespace App\Notifications;

use App\Models\Equipo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EquipoReparacionExtendidaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Equipo $equipo, public int $diasEnReparacion) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $e = $this->equipo;
        return [
            'tipo'    => 'equipo_reparacion_extendida',
            'icono'   => 'build',
            'color'   => 'orange',
            'titulo'  => 'Equipo en reparación extendida',
            'mensaje' => "{$e->nombre} lleva {$this->diasEnReparacion} días en estado «En reparación».",
            'url'     => route('admin.equipos.show', $e->id),
            'meta'    => [
                'equipo_id'        => $e->id,
                'equipo_nombre'    => $e->nombre,
                'codigo_interno'   => $e->codigo_interno,
                'dias_reparacion'  => $this->diasEnReparacion,
            ],
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $e = $this->equipo;
        return (new MailMessage)
            ->subject("Cerberus · {$e->nombre} lleva {$this->diasEnReparacion} días en reparación")
            ->view('emails.notificacion', [
                'titulo'   => 'Equipo en reparación extendida',
                'icono'    => '🔧',
                'tipo'     => 'warning',
                'etiqueta' => 'Reparación',
                'mensaje'  => "El equipo \"{$e->nombre}\" lleva {$this->diasEnReparacion} días consecutivos en estado «En reparación». Se recomienda revisar el estado del proceso.",
                'detalles' => [
                    'Equipo'         => $e->nombre,
                    'Código interno' => $e->codigo_interno ?? '—',
                    'Empresa'        => $e->empresa?->nombre ?? '—',
                    'Días en reparación' => $this->diasEnReparacion,
                ],
                'url' => route('admin.equipos.show', $e->id),
            ]);
    }
}
