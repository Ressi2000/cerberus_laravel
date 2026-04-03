<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromIterator;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CategoriasExport implements FromIterator, ShouldAutoSize
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
            'Descripción',
            'Asignable',
            'Total atributos',
            'Total equipos',
            'Fecha creación',
            'Última actualización',
        ];

        // ── Filas ────────────────────────────────────────────────────────────
        foreach ($this->query->cursor() as $categoria) {
            yield [
                $categoria->id,
                $categoria->nombre,
                $categoria->descripcion ?? '—',
                $categoria->asignable ? 'Sí' : 'No',
                $categoria->atributos_count ?? 0,
                $categoria->equipos_count   ?? 0,
                $categoria->created_at?->format('d/m/Y H:i') ?? '—',
                $categoria->updated_at?->format('d/m/Y H:i') ?? '—',
            ];
        }
    }
}