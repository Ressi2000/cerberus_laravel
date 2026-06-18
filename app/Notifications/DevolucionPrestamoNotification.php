<?php

namespace App\Notifications;

use App\Models\Prestamo;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DevolucionPrestamoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Prestamo $prestamo,
        public User $responsable,
        public int $cantidadDevuelta
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $prestamo = $this->prestamo;
        $dest     = $prestamo->receptorNombre();
        $tipo     = $prestamo->usuario_id ? 'Personal' : 'Área común';

        return [
            'tipo'    => 'devolucion_prestamo',
            'icono'   => 'assignment_return',
            'color'   => 'blue',
            'titulo'  => 'Devolución registrada',
            'mensaje' => "{$this->responsable->name} registró la devolución de {$this->cantidadDevuelta} equipo(s) del préstamo #PRE-{$prestamo->id} ({$tipo}) asignado a {$dest}.",
            'url'     => route('admin.prestamos.index'),
            'meta'    => [
                'prestamo_id'       => $prestamo->id,
                'tipo'              => $tipo,
                'destinatario'      => $dest,
                'responsable'       => $this->responsable->name,
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
        $prestamo = $this->prestamo;
        $dest     = $prestamo->receptorNombre();
        $tipo     = $prestamo->usuario_id ? 'Personal' : 'Área común';
        $resto    = $prestamo->itemsActivos()->count();

        return (new MailMessage)
            ->subject("Cerberus · Devolución registrada — PRE-{$prestamo->id}")
            ->view('emails.notificacion', [
                'titulo'   => 'Devolución registrada',
                'icono'    => '↩️',
                'tipo'     => 'info',
                'etiqueta' => 'Devolución',
                'mensaje'  => "{$this->responsable->name} ha registrado la devolución de {$this->cantidadDevuelta} equipo(s) del préstamo #PRE-{$prestamo->id}.",
                'detalles' => [
                    'Préstamo'          => "PRE-{$prestamo->id}",
                    'Tipo'              => $tipo,
                    'Prestado a'        => $dest,
                    'Registrado por'    => $this->responsable->name,
                    'Equipos devueltos' => $this->cantidadDevuelta,
                    'Equipos restantes' => $resto,
                    'Empresa'           => $prestamo->empresa?->nombre ?? '—',
                    'Estado'            => $prestamo->estado,
                    'Fecha'             => now()->format('d/m/Y H:i'),
                ],
                'url' => route('admin.prestamos.index'),
            ]);
    }
}
