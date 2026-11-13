<?php

namespace App\Notifications;

use App\Models\MantenimientoComponente;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Un caso de reparación/mantenimiento pidió algo que no hay en el almacén
 * (quedó como solicitud pendiente). Va a la campanita de los
 * Administradores, con enlace a la bandeja de solicitudes.
 */
class SolicitudPiezaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public MantenimientoComponente $solicitud) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        $s    = $this->solicitud->loadMissing(['componente', 'mantenimiento.equipo', 'mantenimiento.empresa']);
        $caso = $s->mantenimiento;

        return [
            'tipo'    => 'solicitud_pieza',
            'icono'   => 'inventory_2',
            'color'   => 'purple',
            'titulo'  => 'Solicitud de pieza',
            'mensaje' => "El caso #{$caso->id} ({$caso->equipo?->codigo_interno}) necesita {$s->cantidad_requerida} × "
                . ($s->componente?->nombre ?? 'una pieza') . ' y no hay en el almacén de ' . ($caso->empresa?->nombre ?? 'la empresa') . '.',
            'url'     => route('admin.almacen.solicitudes'),
            'meta'    => [
                'solicitud_id'     => $s->id,
                'mantenimiento_id' => $caso->id,
                'componente_id'    => $s->componente_id,
                'cantidad'         => $s->cantidad_requerida,
            ],
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
