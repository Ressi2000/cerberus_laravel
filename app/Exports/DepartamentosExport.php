<?php

namespace App\Exports;
 
use Maatwebsite\Excel\Concerns\FromIterator;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
 
class DepartamentosExport implements FromIterator, ShouldAutoSize
{
    protected $query;
 
    public function __construct($query)
    {
        $this->query = $query;
    }
 
    public function iterator(): \Iterator
    {
        yield [
            'ID', 'Nombre', 'Descripción', 'Empresa',
            'Cargos activos', 'Usuarios activos', 'Estado',
            'Fecha creación', 'Última actualización',
        ];
 
        foreach ($this->query->cursor() as $d) {
            yield [
                $d->id,
                $d->nombre,
                $d->descripcion       ?? '—',
                $d->empresa?->nombre  ?? '—',
                $d->cargos_count   ?? 0,
                $d->usuarios_count ?? 0,
                $d->activo ? 'Activo' : 'Inactivo',
                $d->created_at?->format('d/m/Y H:i') ?? '—',
                $d->updated_at?->format('d/m/Y H:i') ?? '—',
            ];
        }
    }
}
