<?php

namespace App\Notifications;

use App\Models\Asignacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AsignacionRealizadaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Asignacion $asignacion) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $asig     = $this->asignacion;
        $tipo     = $asig->usuario_id ? 'Personal' : 'Área común';
        $dest     = $asig->receptorNombre();
        $analista = $asig->analista?->name ?? '—';
        $items    = $asig->items()->count();

        return [
            'tipo'      => 'asignacion_realizada',
            'icono'     => 'assignment',
            'color'     => 'emerald',
            'titulo'    => 'Asignación realizada',
            'mensaje'   => "{$analista} registró la asignación #ASG-{$asig->id} ({$tipo}) para {$dest} — {$items} equipo(s).",
            'url'       => route('admin.asignaciones.index'),
            'meta'      => [
                'asignacion_id' => $asig->id,
                'tipo'          => $tipo,
                'destinatario'  => $dest,
                'analista'      => $analista,
                'items'         => $items,
            ],
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $asig     = $this->asignacion;
        $tipo     = $asig->usuario_id ? 'Personal' : 'Área común';
        $dest     = $asig->receptorNombre();
        $analista = $asig->analista?->name ?? '—';
        $items    = $asig->items()->count();

        return (new MailMessage)
            ->subject("Cerberus · Asignación #ASG-{$asig->id} realizada para {$dest}")
            ->view('emails.notificacion', [
                'titulo'   => 'Asignación realizada',
                'icono'    => '📋',
                'tipo'     => 'success',
                'etiqueta' => 'Asignación',
                'mensaje'  => "{$analista} ha registrado la asignación #ASG-{$asig->id} ({$tipo}) para {$dest}.",
                'detalles' => [
                    'Número'       => "ASG-{$asig->id}",
                    'Tipo'         => $tipo,
                    'Asignado a'   => $dest,
                    'Registrado por' => $analista,
                    'Equipos'      => $items,
                    'Empresa'      => $asig->empresa?->nombre ?? '—',
                    'Fecha'        => $asig->created_at?->format('d/m/Y H:i'),
                ],
                'url' => route('admin.asignaciones.index'),
            ]);
    }
}
