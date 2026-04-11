<?php

namespace App\Exports;


use Maatwebsite\Excel\Concerns\FromIterator;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CargosExport implements FromIterator, ShouldAutoSize
{
    protected $query;
 
    public function __construct($query)
    {
        $this->query = $query;
    }
 
    public function iterator(): \Iterator
    {
        yield [
            'ID', 'Nombre', 'Empresa', 'Departamento',
            'Usuarios activos', 'Estado',
            'Fecha creación', 'Última actualización',
        ];
 
        foreach ($this->query->cursor() as $c) {
            yield [
                $c->id,
                $c->nombre,
                $c->empresa?->nombre     ?? '—',
                $c->departamento?->nombre ?? '—',
                $c->usuarios_count ?? 0,
                $c->activo ? 'Activo' : 'Inactivo',
                $c->created_at?->format('d/m/Y H:i') ?? '—',
                $c->updated_at?->format('d/m/Y H:i') ?? '—',
            ];
        }
    }
}
