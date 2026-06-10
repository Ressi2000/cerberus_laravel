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
        $asig = $this->asignacion;
        $tipo = $asig->tipo === 'personal' ? 'Personal' : 'Área común';
        $dest = $asig->tipo === 'personal'
            ? ($asig->usuario?->name ?? '—')
            : ($asig->areaEmpresa?->nombre ?? '—');

        return [
            'tipo'      => 'asignacion_realizada',
            'icono'     => 'assignment',
            'color'     => 'emerald',
            'titulo'    => 'Asignación realizada',
            'mensaje'   => "Se registró la asignación #{$asig->numero} ({$tipo}) para {$dest}.",
            'url'       => route('admin.asignaciones.index'),
            'meta'      => [
                'asignacion_id' => $asig->id,
                'numero'        => $asig->numero,
                'tipo'          => $tipo,
                'destinatario'  => $dest,
                'items'         => $asig->items()->count(),
            ],
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $asig = $this->asignacion;
        $dest = $asig->tipo === 'personal'
            ? ($asig->usuario?->name ?? '—')
            : ($asig->areaEmpresa?->nombre ?? '—');

        return (new MailMessage)
            ->subject("Cerberus · Asignación #{$asig->numero} realizada")
            ->view('emails.notificacion', [
                'titulo'   => 'Asignación realizada',
                'icono'    => '📋',
                'tipo'     => 'success',
                'etiqueta' => 'Asignación',
                'mensaje'  => "Se ha registrado correctamente la asignación #{$asig->numero} en el sistema Cerberus.",
                'detalles' => [
                    'Número'       => $asig->numero,
                    'Tipo'         => $asig->tipo === 'personal' ? 'Personal' : 'Área común',
                    'Destinatario' => $dest,
                    'Equipos'      => $asig->items()->count(),
                    'Empresa'      => $asig->empresa?->nombre ?? '—',
                    'Fecha'        => $asig->created_at?->format('d/m/Y H:i'),
                ],
                'url' => route('admin.asignaciones.index'),
            ]);
    }
}
