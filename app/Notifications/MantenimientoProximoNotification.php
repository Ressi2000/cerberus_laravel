<?php

namespace App\Notifications;

use App\Models\Mantenimiento;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MantenimientoProximoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Mantenimiento $mantenimiento, public int $diasRestantes) {}

    private function cuando(): string
    {
        return match ($this->diasRestantes) {
            0       => 'hoy',
            1       => 'mañana',
            default => "en {$this->diasRestantes} días",
        };
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $m = $this->mantenimiento;
        $e = $m->equipo;

        return [
            'tipo'    => 'mantenimiento_proximo',
            'icono'   => 'build',
            'color'   => 'blue',
            'titulo'  => 'Mantenimiento preventivo ' . $this->cuando(),
            'mensaje' => "El mantenimiento preventivo de {$e?->codigo_interno} es {$this->cuando()} ({$m->proxima_fecha_programada?->format('d/m/Y')}).",
            'url'     => route('admin.mantenimientos.show', $m->id),
            'meta'    => [
                'mantenimiento_id' => $m->id,
                'equipo_id'        => $e?->id,
                'codigo_interno'   => $e?->codigo_interno,
                'fecha_programada' => $m->proxima_fecha_programada?->toDateString(),
                'dias_restantes'   => $this->diasRestantes,
            ],
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $m = $this->mantenimiento;
        $e = $m->equipo;

        return (new MailMessage)
            ->subject("Cerberus · Mantenimiento de {$e?->codigo_interno} {$this->cuando()}")
            ->view('emails.notificacion', [
                'titulo'   => 'Mantenimiento preventivo ' . $this->cuando(),
                'icono'    => '🔧',
                'tipo'     => 'warning',
                'etiqueta' => 'Mantenimiento',
                'mensaje'  => "El mantenimiento preventivo programado para \"{$e?->codigo_interno}\" es {$this->cuando()}. Se recomienda coordinarlo a tiempo.",
                'detalles' => [
                    'Equipo'              => $e?->codigo_interno ?? '—',
                    'Empresa'             => $m->empresa?->nombre ?? '—',
                    'Fecha programada'    => $m->proxima_fecha_programada?->format('d/m/Y'),
                    'Días restantes'      => $this->diasRestantes,
                ],
                'url' => route('admin.mantenimientos.show', $m->id),
            ]);
    }
}
