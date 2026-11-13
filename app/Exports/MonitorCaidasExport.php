<?php

namespace App\Exports;

use App\Models\MonitorCaida;
use Maatwebsite\Excel\Concerns\FromIterator;

/** Historial de caídas del Monitor de red (respeta los filtros de la tabla). */
class MonitorCaidasExport implements FromIterator
{
    public function __construct(protected $query) {}

    public function iterator(): \Iterator
    {
        yield ['Host', 'IP / DNS', 'Tipo', 'Proveedor', 'Empresa', 'Inicio', 'Recuperado', 'Duración (s)', 'Duración'];

        foreach ($this->query->cursor() as $caida) {
            /** @var MonitorCaida $caida */
            $h = $caida->host;

            yield [
                $h?->nombre ?? '—',
                $h?->host ?? '—',
                $h?->tipo ?? '—',
                $h?->proveedor ?: '—',
                $h?->empresa?->nombre ?? '—',
                $caida->inicio->format('Y-m-d H:i:s'),
                $caida->fin?->format('Y-m-d H:i:s') ?? 'En curso',
                $caida->duracion(),
                MonitorCaida::formatearDuracion($caida->duracion()),
            ];
        }
    }
}
