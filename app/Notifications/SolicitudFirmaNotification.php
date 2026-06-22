<?php

namespace App\Notifications;

use App\Models\Firma;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Solicita al firmante que estampe su firma digital remota sobre una
 * planilla. El enlace incluido es una URL firmada de Laravel (expira y
 * no puede alterarse), así que no requiere que el destinatario inicie sesión.
 */
class SolicitudFirmaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Firma $firma,
        public string $tituloDocumento,
        public string $folio,
        public string $url,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'tipo'    => 'solicitud_firma',
            'icono'   => 'edit_note',
            'color'   => 'blue',
            'titulo'  => 'Firma digital solicitada',
            'mensaje' => "Se solicita tu firma digital en {$this->tituloDocumento} ({$this->folio}).",
            'url'     => $this->url,
            'meta'    => [
                'firma_id' => $this->firma->id,
                'folio'    => $this->folio,
                'rol'      => $this->firma->rol,
            ],
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Cerberus · Firma digital pendiente — {$this->folio}")
            ->view('emails.notificacion', [
                'titulo'   => 'Firma digital solicitada',
                'icono'    => '✍️',
                'tipo'     => 'info',
                'etiqueta' => 'Firma pendiente',
                'mensaje'  => "Se solicita tu firma digital en {$this->tituloDocumento} ({$this->folio}). "
                    ."El enlace es personal, vence pronto y no requiere iniciar sesión.",
                'detalles' => [
                    'Documento' => $this->tituloDocumento,
                    'Folio'     => $this->folio,
                ],
                'url' => $this->url,
            ]);
    }
}
