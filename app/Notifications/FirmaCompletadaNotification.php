<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifica que un documento quedó completamente firmado de forma digital
 * y adjunta el PDF final con las firmas ya estampadas.
 */
class FirmaCompletadaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $tituloDocumento,
        public string $folio,
        public string $pdfBinario,
        public string $nombreArchivo,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
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

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Cerberus · {$this->tituloDocumento} firmado — {$this->folio}")
            ->view('emails.notificacion', [
                'titulo'   => 'Documento firmado digitalmente',
                'icono'    => '✅',
                'tipo'     => 'success',
                'etiqueta' => 'Firma completa',
                'mensaje'  => "{$this->tituloDocumento} ({$this->folio}) ha sido firmado digitalmente por todas las partes. Se adjunta el PDF final.",
                'detalles' => [
                    'Documento' => $this->tituloDocumento,
                    'Folio'     => $this->folio,
                ],
                'url' => null,
            ])
            ->attachData($this->pdfBinario, $this->nombreArchivo, [
                'mime' => 'application/pdf',
            ]);
    }
}
