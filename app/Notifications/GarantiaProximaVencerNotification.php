<?php

namespace App\Notifications;

use App\Models\Equipo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GarantiaProximaVencerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Equipo $equipo, public int $diasRestantes) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $e = $this->equipo;
        return [
            'tipo'    => 'garantia_proxima_vencer',
            'icono'   => 'verified_user',
            'color'   => 'amber',
            'titulo'  => 'Garantía próxima a vencer',
            'mensaje' => "La garantía de {$e->nombre} vence en {$this->diasRestantes} día(s) ({$e->fecha_garantia_fin?->format('d/m/Y')}).",
            'url'     => route('admin.equipos.show', $e->id),
            'meta'    => [
                'equipo_id'         => $e->id,
                'equipo_nombre'     => $e->nombre,
                'codigo_interno'    => $e->codigo_interno,
                'fecha_garantia_fin'=> $e->fecha_garantia_fin?->toDateString(),
                'dias_restantes'    => $this->diasRestantes,
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
            ->subject("Cerberus · Garantía de {$e->nombre} vence en {$this->diasRestantes} día(s)")
            ->view('emails.notificacion', [
                'titulo'   => 'Garantía próxima a vencer',
                'icono'    => '🛡️',
                'tipo'     => 'warning',
                'etiqueta' => 'Garantía',
                'mensaje'  => "La garantía del equipo \"{$e->nombre}\" vencerá en {$this->diasRestantes} día(s). Considere renovarla o tomar medidas preventivas.",
                'detalles' => [
                    'Equipo'             => $e->nombre,
                    'Código interno'     => $e->codigo_interno ?? '—',
                    'Empresa'            => $e->empresa?->nombre ?? '—',
                    'Vencimiento garantía' => $e->fecha_garantia_fin?->format('d/m/Y'),
                    'Días restantes'     => $this->diasRestantes,
                ],
                'url' => route('admin.equipos.show', $e->id),
            ]);
    }
}
