<?php

namespace App\Notifications;

use App\Models\Traslado;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrasladoCreadoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Traslado $traslado) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $t     = $this->traslado;
        $items = $t->items()->count();
        return [
            'tipo'    => 'traslado_creado',
            'icono'   => 'local_shipping',
            'color'   => 'blue',
            'titulo'  => '¡Equipos en camino!',
            'mensaje' => "El traslado #{$t->numero} fue registrado — {$items} equipo(s) van camino a {$t->ubicacionDestino?->nombre}.",
            'url'     => route('admin.traslados.index'),
            'meta'    => [
                'traslado_id' => $t->id,
                'numero'      => $t->numero,
                'destino'     => $t->ubicacionDestino?->nombre,
                'items'       => $items,
            ],
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $t     = $this->traslado;
        $items = $t->items()->count();
        return (new MailMessage)
            ->subject("Cerberus · Traslado #{$t->numero} registrado")
            ->view('emails.notificacion', [
                'titulo'   => '¡Equipos en camino hacia ti!',
                'icono'    => '🚚',
                'tipo'     => 'info',
                'etiqueta' => 'Traslado',
                'mensaje'  => "{$items} equipo(s) del traslado #{$t->numero} están en camino a {$t->ubicacionDestino?->nombre ?? 'tu ubicación'}. ¡Prepárate para recibirlos!",
                'detalles' => [
                    'Número'  => $t->numero,
                    'Destino' => $t->ubicacionDestino?->nombre ?? '—',
                    'Empresa' => $t->empresa?->nombre ?? '—',
                    'Equipos' => $items,
                    'Fecha'   => $t->created_at?->format('d/m/Y H:i'),
                ],
                'url' => route('admin.traslados.index'),
            ]);
    }
}
