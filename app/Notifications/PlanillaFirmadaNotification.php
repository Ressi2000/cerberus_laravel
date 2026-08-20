<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirma por correo, con la planilla ya firmada por ambas partes
 * adjunta en PDF, que el receptor recibió sus equipos.
 *
 * Se dispara únicamente desde FirmaService::completar() cuando queda
 * firmada una asignación o préstamo, y se envía solo al receptor —
 * no al analista ni a su jefe directo.
 */
class PlanillaFirmadaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $tipo, // 'asignacion' | 'prestamo'
        public string $folio,
        public int $totalItems,
        public ?string $empresaNombre,
        public string $pdfContenidoBase64,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $titulo = $this->tipo === 'prestamo' ? 'Préstamo' : 'Asignación';
        $accion = $this->tipo === 'prestamo' ? 'préstamo' : 'asignación';

        return (new MailMessage)
            ->subject("Cerberus · {$titulo} {$this->folio} confirmada y firmada")
            ->view('emails.notificacion', [
                'titulo'   => "{$titulo} confirmada",
                'icono'    => '✅',
                'tipo'     => 'success',
                'etiqueta' => $titulo,
                'mensaje'  => "Hola {$notifiable->name}, se ha confirmado tu {$accion} de {$this->totalItems} equipo(s). "
                    . 'Adjunta encontrarás la planilla firmada digitalmente por ambas partes.',
                'detalles' => [
                    'Folio'   => $this->folio,
                    'Equipos' => $this->totalItems,
                    'Empresa' => $this->empresaNombre ?? '—',
                    'Fecha'   => now()->format('d/m/Y H:i'),
                ],
            ])
            ->attachData(base64_decode($this->pdfContenidoBase64), "{$this->folio}.pdf", [
                'mime' => 'application/pdf',
            ]);
    }
}
