<?php

namespace App\Notifications;

use App\Models\Prestamo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrestamoVencidoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Prestamo $prestamo) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $p = $this->prestamo;
        $dias = now()->diffInDays($p->fecha_devolucion_esperada);
        return [
            'tipo'    => 'prestamo_vencido',
            'icono'   => 'warning',
            'color'   => 'red',
            'titulo'  => 'Préstamo vencido',
            'mensaje' => "El préstamo #{$p->numero} venció hace {$dias} día(s). Devolución esperada: {$p->fecha_devolucion_esperada?->format('d/m/Y')}.",
            'url'     => route('admin.prestamos.index'),
            'meta'    => [
                'prestamo_id'             => $p->id,
                'numero'                  => $p->numero,
                'fecha_devolucion_esperada' => $p->fecha_devolucion_esperada?->toDateString(),
                'dias_vencido'            => $dias,
            ],
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $p = $this->prestamo;
        $dias = now()->diffInDays($p->fecha_devolucion_esperada);
        $dest = $p->tipo === 'personal'
            ? ($p->usuario?->name ?? '—')
            : ($p->areaEmpresa?->nombre ?? '—');

        return (new MailMessage)
            ->subject("⚠️ Cerberus · Préstamo #{$p->numero} VENCIDO")
            ->view('emails.notificacion', [
                'titulo'   => 'Préstamo vencido',
                'icono'    => '⚠️',
                'tipo'     => 'danger',
                'etiqueta' => 'Urgente',
                'mensaje'  => "El préstamo #{$p->numero} ha superado su fecha de devolución esperada hace {$dias} día(s). Se requiere acción inmediata.",
                'detalles' => [
                    'Número'               => $p->numero,
                    'Destinatario'         => $dest,
                    'Fecha devolución esp.' => $p->fecha_devolucion_esperada?->format('d/m/Y'),
                    'Días vencido'         => $dias,
                    'Empresa'              => $p->empresa?->nombre ?? '—',
                ],
                'url' => route('admin.prestamos.index'),
            ]);
    }
}
