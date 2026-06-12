<?php

namespace App\Notifications;

use App\Models\Asignacion;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DevolucionAsignacionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Asignacion $asignacion,
        public User $responsable,
        public int $cantidadDevuelta
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $asig  = $this->asignacion;
        $dest  = $asig->receptorNombre();
        $tipo  = $asig->usuario_id ? 'Personal' : 'Área común';

        return [
            'tipo'    => 'devolucion_asignacion',
            'icono'   => 'assignment_return',
            'color'   => 'blue',
            'titulo'  => 'Devolución registrada',
            'mensaje' => "{$this->responsable->name} registró la devolución de {$this->cantidadDevuelta} equipo(s) de la asignación #ASG-{$asig->id} ({$tipo}) asignada a {$dest}.",
            'url'     => route('admin.asignaciones.index'),
            'meta'    => [
                'asignacion_id'    => $asig->id,
                'tipo'             => $tipo,
                'destinatario'     => $dest,
                'responsable'      => $this->responsable->name,
                'cantidad_devuelta' => $this->cantidadDevuelta,
            ],
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $asig  = $this->asignacion;
        $dest  = $asig->receptorNombre();
        $tipo  = $asig->usuario_id ? 'Personal' : 'Área común';
        $resto = $asig->itemsActivos()->count();

        return (new MailMessage)
            ->subject("Cerberus · Devolución registrada — ASG-{$asig->id}")
            ->view('emails.notificacion', [
                'titulo'   => 'Devolución registrada',
                'icono'    => '↩️',
                'tipo'     => 'info',
                'etiqueta' => 'Devolución',
                'mensaje'  => "{$this->responsable->name} ha registrado la devolución de {$this->cantidadDevuelta} equipo(s) de la asignación #ASG-{$asig->id}.",
                'detalles' => [
                    'Asignación'       => "ASG-{$asig->id}",
                    'Tipo'             => $tipo,
                    'Asignado a'       => $dest,
                    'Registrado por'   => $this->responsable->name,
                    'Equipos devueltos' => $this->cantidadDevuelta,
                    'Equipos restantes' => $resto,
                    'Empresa'          => $asig->empresa?->nombre ?? '—',
                    'Estado'           => $asig->estado,
                    'Fecha'            => now()->format('d/m/Y H:i'),
                ],
                'url' => route('admin.asignaciones.index'),
            ]);
    }
}
