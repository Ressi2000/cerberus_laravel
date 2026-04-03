<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromIterator;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class EstadosExport implements FromIterator, ShouldAutoSize
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function iterator(): \Iterator
    {
        // ── Encabezados ──────────────────────────────────────────────────────
        yield [
            'ID',
            'Nombre',
            'Total equipos asignados',
            'Fecha creación',
            'Última actualización',
        ];

        // ── Filas ────────────────────────────────────────────────────────────
        foreach ($this->query->cursor() as $estado) {
            yield [
                $estado->id,
                $estado->nombre,
                $estado->equipos_count ?? 0,
                $estado->created_at?->format('d/m/Y H:i') ?? '—',
                $estado->updated_at?->format('d/m/Y H:i') ?? '—',
            ];
        }
    }
}