<?php

namespace App\Notifications;

use App\Models\AsignacionItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerta de permanencia de asignación, NO de vida útil/obsolescencia del
 * equipo: se dispara cuando un mismo receptor lleva más tiempo con un
 * mismo equipo que el periodo de rotación recomendado configurado en la
 * categoría (Configuración → Categorías). Es una sugerencia de revisión,
 * nunca una señal de que el equipo esté dañado o desactualizado.
 */
class RotacionAsignacionRecomendadaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public AsignacionItem $item, public int $mesesConReceptor, public int $mesesRecomendados) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $item     = $this->item;
        $equipo   = $item->equipo;
        $usuario  = $item->asignacion?->usuario;

        return [
            'tipo'    => 'rotacion_asignacion_recomendada',
            'icono'   => 'autorenew',
            'color'   => 'orange',
            'titulo'  => 'Rotación de asignación recomendada',
            'mensaje' => "{$usuario?->name} lleva {$this->mesesConReceptor} mes(es) con {$equipo?->codigo_interno} (recomendado: {$this->mesesRecomendados}m). Se sugiere evaluar una rotación.",
            'url'     => $usuario ? route('admin.usuarios.trazabilidad', $usuario->id) : route('admin.asignaciones.rotacion-recomendada'),
            'meta'    => [
                'asignacion_item_id'  => $item->id,
                'usuario_id'          => $usuario?->id,
                'equipo_id'           => $equipo?->id,
                'codigo_interno'      => $equipo?->codigo_interno,
                'meses_con_receptor'  => $this->mesesConReceptor,
                'meses_recomendados'  => $this->mesesRecomendados,
            ],
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $item    = $this->item;
        $equipo  = $item->equipo;
        $usuario = $item->asignacion?->usuario;

        return (new MailMessage)
            ->subject("Cerberus · Rotación de asignación recomendada — {$equipo?->codigo_interno}")
            ->view('emails.notificacion', [
                'titulo'   => 'Rotación de asignación recomendada',
                'icono'    => '🔄',
                'tipo'     => 'warning',
                'etiqueta' => 'Rotación',
                'mensaje'  => "{$usuario?->name} lleva {$this->mesesConReceptor} mes(es) con el equipo \"{$equipo?->codigo_interno}\", superando el periodo de rotación recomendado ({$this->mesesRecomendados} meses) para su categoría. Esto no significa que el equipo esté dañado — es una sugerencia para evaluar si conviene rotarlo.",
                'detalles' => [
                    'Receptor'            => $usuario?->name ?? '—',
                    'Equipo'              => $equipo?->codigo_interno ?? '—',
                    'Categoría'           => $equipo?->categoria?->nombre ?? '—',
                    'Meses con el equipo' => $this->mesesConReceptor,
                    'Rotación recomendada' => "{$this->mesesRecomendados} meses",
                ],
                'url' => $usuario ? route('admin.usuarios.trazabilidad', $usuario->id) : route('admin.asignaciones.rotacion-recomendada'),
            ]);
    }
}
