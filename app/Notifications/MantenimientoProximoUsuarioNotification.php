<?php

namespace App\Notifications;

use App\Models\Mantenimiento;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso por correo al usuario que tiene el equipo asignado, una semana antes
 * de un mantenimiento preventivo programado — para que lo tenga listo el
 * día de la jornada. Distinta de MantenimientoProximoNotification, que
 * avisa a analistas/administradores (gestión), no al dueño del equipo.
 *
 * Solo correo: un usuario común no necesariamente entra al panel de
 * Cerberus a ver notificaciones in-app.
 */
class MantenimientoProximoUsuarioNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Mantenimiento $mantenimiento, public int $diasRestantes) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $m = $this->mantenimiento;
        $e = $m->equipo;

        return (new MailMessage)
            ->subject("Cerberus · Tu equipo {$e?->codigo_interno} tiene mantenimiento programado")
            ->view('emails.notificacion', [
                'titulo'   => 'Mantenimiento preventivo programado',
                'icono'    => '🔧',
                'tipo'     => 'info',
                'etiqueta' => 'Mantenimiento',
                'mensaje'  => "En {$this->diasRestantes} día(s) está programado el mantenimiento preventivo de tu equipo \"{$e?->codigo_interno}\". Por favor ten el equipo disponible para entregarlo ese día.",
                'detalles' => [
                    'Equipo'           => $e?->codigo_interno ?? '—',
                    'Fecha programada' => $m->proxima_fecha_programada?->format('d/m/Y'),
                ],
            ]);
    }
}
