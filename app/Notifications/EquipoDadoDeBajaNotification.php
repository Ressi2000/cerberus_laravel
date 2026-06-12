<?php

namespace App\Notifications;

use App\Models\Equipo;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EquipoDadoDeBajaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Equipo $equipo,
        public User $responsable
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $e = $this->equipo;
        return [
            'tipo'    => 'equipo_dado_de_baja',
            'icono'   => 'delete_forever',
            'color'   => 'red',
            'titulo'  => 'Equipo dado de baja',
            'mensaje' => "{$this->responsable->name} dio de baja el equipo «{$e->nombre}» ({$e->codigo_interno}).",
            'url'     => route('admin.equipos.show', $e->id),
            'meta'    => [
                'equipo_id'      => $e->id,
                'equipo_nombre'  => $e->nombre,
                'codigo_interno' => $e->codigo_interno,
                'responsable'    => $this->responsable->name,
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
            ->subject("Cerberus · Equipo «{$e->nombre}» dado de baja")
            ->view('emails.notificacion', [
                'titulo'   => 'Equipo dado de baja',
                'icono'    => '🗑️',
                'tipo'     => 'danger',
                'etiqueta' => 'Baja',
                'mensaje'  => "{$this->responsable->name} ha dado de baja el equipo «{$e->nombre}». El registro permanece disponible para auditoría.",
                'detalles' => [
                    'Equipo'         => $e->nombre,
                    'Código interno' => $e->codigo_interno ?? '—',
                    'Empresa'        => $e->empresa?->nombre ?? '—',
                    'Dado de baja por' => $this->responsable->name,
                    'Fecha'          => now()->format('d/m/Y H:i'),
                ],
                'url' => route('admin.equipos.show', $e->id),
            ]);
    }
}
