<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromIterator;

class UsuariosExport implements FromIterator
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function iterator(): \Iterator
    {
        // Encabezados
        yield [
            'ID','Nombre','Username','Ficha','Cédula','Email','Rol','Estado','Empresa','Departamento','Cargo','Ubicación','Jefe','Creado'
        ];

        foreach ($this->query->cursor() as $u) {
            yield [
                $u->id,
                $u->name,
                $u->username,
                $u->ficha,
                $u->cedula,
                $u->email,
                $u->roles->pluck('name')->join(', '),
                $u->estado,
                $u->empresa->nombre ?? '',
                $u->departamento->nombre ?? '',
                $u->cargo->nombre ?? '',
                $u->ubicacion->nombre ?? '',
                $u->jefe->name ?? '',
                $u->created_at?->format('Y-m-d H:i'),
            ];
        }
    }
}
