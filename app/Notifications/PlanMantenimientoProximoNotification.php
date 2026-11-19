<?php

namespace App\Notifications;

use App\Models\PlanMantenimiento;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso de que se acerca un mantenimiento preventivo del cronograma (un
 * lote: todos los equipos de una categoría/empresa). Se manda UNA vez por
 * plan —no una por equipo— 14, 7 y 1 día(s) antes.
 */
class PlanMantenimientoProximoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public PlanMantenimiento $plan, public int $diasRestantes, public int $equipos) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    private function cuando(): string
    {
        return match ($this->diasRestantes) {
            0       => 'hoy',
            1       => 'mañana',
            default => "en {$this->diasRestantes} días",
        };
    }

    private function nombre(): string
    {
        $p = $this->plan;

        return trim(($p->categoria?->nombre ?? 'Equipos') . ($p->departamento ? " · {$p->departamento->nombre}" : '') . ' — ' . ($p->empresa?->nombre ?? ''), ' —');
    }

    public function toDatabase(object $notifiable): array
    {
        $p = $this->plan;

        return [
            'tipo'    => 'plan_mantenimiento_proximo',
            'icono'   => 'event',
            'color'   => $this->diasRestantes <= 1 ? 'amber' : 'blue',
            'titulo'  => 'Mantenimiento preventivo ' . $this->cuando(),
            'mensaje' => "Mantenimiento de {$this->nombre()} ({$this->equipos} equipo(s)) programado para el {$p->fecha_proximo->format('d/m/Y')}.",
            'url'     => route('admin.cronograma.lotes.show', $p->id),
            'meta'    => [
                'plan_id'          => $p->id,
                'fecha_programada' => $p->fecha_proximo->toDateString(),
                'dias_restantes'   => $this->diasRestantes,
                'equipos'          => $this->equipos,
            ],
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $p = $this->plan;

        return (new MailMessage)
            ->subject("Cerberus · Mantenimiento preventivo {$this->cuando()}: {$this->nombre()}")
            ->view('emails.notificacion', [
                'titulo'   => 'Mantenimiento preventivo ' . $this->cuando(),
                'icono'    => '🗓️',
                'tipo'     => $this->diasRestantes <= 1 ? 'warning' : 'info',
                'etiqueta' => 'Cronograma',
                'mensaje'  => "Se acerca el mantenimiento preventivo de {$this->nombre()}. Coordina con los usuarios para tener los equipos disponibles ese día.",
                'detalles' => array_filter([
                    'Equipos'          => $p->categoria?->nombre,
                    'Departamento'     => $p->departamento?->nombre,
                    'Empresa'          => $p->empresa?->nombre,
                    'Cantidad'         => "{$this->equipos} equipo(s)",
                    'Fecha programada' => $p->fecha_proximo->format('d/m/Y'),
                    'Responsable'      => $p->responsable?->name,
                ]),
                'url' => route('admin.cronograma.lotes.show', $p->id),
            ]);
    }
}
