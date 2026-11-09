<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromIterator;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class LicenciasMicrosoftExport implements FromIterator, ShouldAutoSize
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
            'Estado',
            'Usuarios asignados',
            'Fecha creación',
            'Última actualización',
        ];

        // ── Filas ────────────────────────────────────────────────────────────
        foreach ($this->query->cursor() as $tipo) {
            yield [
                $tipo->id,
                $tipo->nombre,
                $tipo->activo ? 'Activo' : 'Inactivo',
                $tipo->usuarios_count ?? 0,
                $tipo->created_at?->format('d/m/Y H:i') ?? '—',
                $tipo->updated_at?->format('d/m/Y H:i') ?? '—',
            ];
        }
    }
}
