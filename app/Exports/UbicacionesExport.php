<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromIterator;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class UbicacionesExport implements FromIterator, ShouldAutoSize
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function iterator(): \Iterator
    {
        yield [
            'ID',
            'Nombre',
            'Descripción',
            'Empresa',
            'Tipo',
            'Usuarios activos',
            'Equipos activos',
            'Estado',
            'Fecha creación',
            'Última actualización',
        ];

        foreach ($this->query->cursor() as $u) {
            yield [
                $u->id,
                $u->nombre,
                $u->descripcion ?? '—',
                $u->empresa?->nombre ?? '—',
                $u->es_estado ? 'Foránea' : 'Local',
                $u->usuarios_count ?? 0,
                $u->equipos_count  ?? 0,
                $u->activo ? 'Activa' : 'Inactiva',
                $u->created_at?->format('d/m/Y H:i') ?? '—',
                $u->updated_at?->format('d/m/Y H:i') ?? '—',
            ];
        }
    }
}
