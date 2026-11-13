<?php

namespace App\Notifications;

use App\Models\MonitorCaida;
use App\Models\MonitorHost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Caída o recuperación de un host del Monitor de red.
 * La dispara MonitorRedService solo en el cambio de estado confirmado
 * (no en cada revisión), así que no se repite mientras el host siga caído.
 */
class MonitorHostEstadoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public MonitorHost $host,
        public bool $recuperado,
        public ?int $duracionSegundos = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $h = $this->host;

        return [
            'tipo'    => $this->recuperado ? 'monitor_red_recuperado' : 'monitor_red_caida',
            'icono'   => $this->recuperado ? 'wifi' : 'wifi_off',
            'color'   => $this->recuperado ? 'emerald' : 'red',
            'titulo'  => $this->recuperado ? "Recuperado: {$h->nombre}" : "Caído: {$h->nombre}",
            'mensaje' => $this->mensaje(),
            'url'     => route('admin.monitor-red.hosts'),
            'meta'    => [
                'monitor_host_id'   => $h->id,
                'host'              => $h->host,
                'recuperado'        => $this->recuperado,
                'duracion_segundos' => $this->duracionSegundos,
            ],
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $h = $this->host;

        return (new MailMessage)
            ->subject($this->recuperado
                ? "✅ Cerberus · {$h->nombre} recuperado"
                : "🔴 Cerberus · {$h->nombre} sin respuesta")
            ->view('emails.notificacion', [
                'titulo'   => $this->recuperado ? 'Host recuperado' : 'Host caído',
                'icono'    => $this->recuperado ? '✅' : '🔴',
                'tipo'     => $this->recuperado ? 'success' : 'danger',
                'etiqueta' => $this->recuperado ? 'Recuperado' : 'Urgente',
                'mensaje'  => $this->mensaje(),
                'detalles' => array_filter([
                    'Host'       => $h->nombre,
                    'IP / DNS'   => $h->host,
                    'Tipo'       => $h->tipo,
                    'Proveedor'  => $h->proveedor ?: null,
                    'Empresa'    => $h->empresa?->nombre ?? '—',
                    'Desde'      => $h->estado_desde?->format('d/m/Y H:i:s'),
                    'Duración'   => $this->duracionSegundos !== null ? MonitorCaida::formatearDuracion($this->duracionSegundos) : null,
                ]),
                'url' => route('admin.monitor-red.hosts'),
            ]);
    }

    private function mensaje(): string
    {
        $h = $this->host;

        if ($this->recuperado) {
            $dur = $this->duracionSegundos !== null ? ' tras ' . MonitorCaida::formatearDuracion($this->duracionSegundos) . ' sin servicio' : '';
            return "{$h->nombre} ({$h->host}) vuelve a responder{$dur}.";
        }

        return "{$h->nombre} ({$h->host}) no responde desde las {$h->estado_desde?->format('H:i:s')}.";
    }
}
