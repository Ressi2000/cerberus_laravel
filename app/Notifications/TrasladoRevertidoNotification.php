<?php

namespace App\Notifications;

use App\Models\Traslado;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrasladoRevertidoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Traslado $traslado,
        public User $responsable,
        public string $motivo
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $t     = $this->traslado;
        $items = $t->items()->count();
        return [
            'tipo'    => 'traslado_revertido',
            'icono'   => 'undo',
            'color'   => 'orange',
            'titulo'  => 'Traslado revertido',
            'mensaje' => "{$this->responsable->name} revirtió el traslado #{$t->numero} — {$items} equipo(s) volvieron a {$t->ubicacionOrigen?->nombre}.",
            'url'     => route('admin.traslados.index'),
            'meta'    => [
                'traslado_id'  => $t->id,
                'numero'       => $t->numero,
                'origen'       => $t->ubicacionOrigen?->nombre,
                'responsable'  => $this->responsable->name,
                'motivo'       => $this->motivo,
                'items'        => $items,
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
            ->subject("Cerberus · Traslado #{$t->numero} revertido")
            ->view('emails.notificacion', [
                'titulo'   => 'Traslado revertido',
                'icono'    => '↩️',
                'tipo'     => 'warning',
                'etiqueta' => 'Reversión',
                'mensaje'  => "{$this->responsable->name} ha revertido el traslado #{$t->numero}. Los equipos regresaron a su ubicación de origen.",
                'detalles' => [
                    'Número'         => $t->numero,
                    'Origen'         => $t->ubicacionOrigen?->nombre ?? '—',
                    'Destino'        => $t->ubicacionDestino?->nombre ?? '—',
                    'Equipos'        => $items,
                    'Revertido por'  => $this->responsable->name,
                    'Motivo'         => $this->motivo,
                    'Fecha'          => now()->format('d/m/Y H:i'),
                ],
                'url' => route('admin.traslados.index'),
            ]);
    }
}
