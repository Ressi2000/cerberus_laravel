<?php

namespace App\Exports;
 
use Maatwebsite\Excel\Concerns\FromIterator;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
 
class EmpresasExport implements FromIterator, ShouldAutoSize
{
    protected $query;
 
    public function __construct($query)
    {
        $this->query = $query;
    }
 
    public function iterator(): \Iterator
    {
        yield [
            'ID', 'Nombre', 'RIF', 'Dirección', 'Teléfono',
            'Usuarios activos', 'Equipos activos', 'Ubicaciones activas', 'Estado',
            'Fecha creación', 'Última actualización',
        ];
 
        foreach ($this->query->cursor() as $e) {
            yield [
                $e->id,
                $e->nombre,
                $e->rif       ?? '—',
                $e->direccion ?? '—',
                $e->telefono  ?? '—',
                $e->usuarios_count   ?? 0,
                $e->equipos_count    ?? 0,
                $e->ubicaciones_count ?? 0,
                $e->activo ? 'Activa' : 'Inactiva',
                $e->created_at?->format('d/m/Y H:i') ?? '—',
                $e->updated_at?->format('d/m/Y H:i') ?? '—',
            ];
        }
    }
}
