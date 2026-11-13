<?php

namespace App\Livewire\MonitorRed\Concerns;

use App\Models\MonitorHost;
use Illuminate\Support\Collection;

/**
 * Compara el estado de los hosts entre un refresco (wire:poll) y el
 * siguiente, y avisa al navegador de los que cayeron o se recuperaron
 * para el sonido / notificación de escritorio opcional.
 *
 * La alerta "oficial" (campana + correo) la envía el scheduler aunque nadie
 * tenga la página abierta; esto es solo el aviso inmediato en pantalla.
 */
trait DetectaCambiosDeEstado
{
    /** @var array<int, string> id => estado del refresco anterior */
    public array $estadosPrevios = [];

    /** @param Collection<int, MonitorHost> $hosts */
    protected function detectarCambios(Collection $hosts): void
    {
        $cambios = [];

        foreach ($hosts as $h) {
            $actual = $h->pausado ? 'Pausado' : $h->estado;
            $previo = $this->estadosPrevios[$h->id] ?? null;

            if ($previo === MonitorHost::ESTADO_ACTIVO && $actual === MonitorHost::ESTADO_CAIDO) {
                $cambios[] = ['nombre' => $h->nombre, 'host' => $h->host, 'recuperado' => false];
            } elseif ($previo === MonitorHost::ESTADO_CAIDO && $actual === MonitorHost::ESTADO_ACTIVO) {
                $cambios[] = ['nombre' => $h->nombre, 'host' => $h->host, 'recuperado' => true];
            }

            $this->estadosPrevios[$h->id] = $actual;
        }

        if ($cambios) {
            $this->dispatch('monitor-red-cambios', cambios: $cambios);
        }
    }
}
