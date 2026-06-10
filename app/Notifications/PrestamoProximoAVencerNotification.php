<?php

namespace App\Notifications;

use App\Models\Prestamo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrestamoProximoAVencerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Prestamo $prestamo, public int $diasRestantes) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $p = $this->prestamo;
        return [
            'tipo'    => 'prestamo_proximo_vencer',
            'icono'   => 'schedule',
            'color'   => 'amber',
            'titulo'  => 'Préstamo próximo a vencer',
            'mensaje' => "El préstamo #{$p->numero} vence en {$this->diasRestantes} día(s) ({$p->fecha_devolucion_esperada?->format('d/m/Y')}).",
            'url'     => route('admin.prestamos.index'),
            'meta'    => [
                'prestamo_id'             => $p->id,
                'numero'                  => $p->numero,
                'fecha_devolucion_esperada' => $p->fecha_devolucion_esperada?->toDateString(),
                'dias_restantes'          => $this->diasRestantes,
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
        $dest = $p->tipo === 'personal'
            ? ($p->usuario?->name ?? '—')
            : ($p->areaEmpresa?->nombre ?? '—');

        return (new MailMessage)
            ->subject("Cerberus · Préstamo #{$p->numero} vence en {$this->diasRestantes} día(s)")
            ->view('emails.notificacion', [
                'titulo'   => 'Préstamo próximo a vencer',
                'icono'    => '⏰',
                'tipo'     => 'warning',
                'etiqueta' => 'Alerta',
                'mensaje'  => "El préstamo #{$p->numero} vencerá en {$this->diasRestantes} día(s). Coordine la devolución a tiempo.",
                'detalles' => [
                    'Número'               => $p->numero,
                    'Destinatario'         => $dest,
                    'Fecha devolución esp.' => $p->fecha_devolucion_esperada?->format('d/m/Y'),
                    'Días restantes'       => $this->diasRestantes,
                    'Empresa'              => $p->empresa?->nombre ?? '—',
                ],
                'url' => route('admin.prestamos.index'),
            ]);
    }
}
