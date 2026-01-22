<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromIterator;

class AuditoriaExport implements FromIterator
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function iterator(): \Iterator
    {
        // ENCABEZADOS
        yield [
            'Fecha',
            'Usuario',
            'Tabla',
            'Acción',
            'Campo',
            'Valor Anterior',
            'Valor Nuevo',
        ];

        foreach ($this->query->cursor() as $audit) {

            $cambios = $audit->cambios;

            // Si no hubo cambios (login, logout, etc.)
            if (empty($cambios)) {
                yield [
                    Carbon::parse($audit->created_at)->format('Y-m-d H:i'),
                    $audit->usuario?->name ?? 'Sistema',
                    $audit->tabla,
                    $audit->accion,
                    '—',
                    '—',
                    '—',
                ];
                continue;
            }

            foreach ($cambios as $campo => $values) {
                yield [
                    Carbon::parse($audit->created_at)->format('Y-m-d H:i'),
                    $audit->usuario?->name ?? 'Sistema',
                    $audit->tabla,
                    $audit->accion,
                    $campo,
                    is_array($values['before'])
                        ? json_encode($values['before'], JSON_UNESCAPED_UNICODE)
                        : $values['before'],
                    is_array($values['after'])
                        ? json_encode($values['after'], JSON_UNESCAPED_UNICODE)
                        : $values['after'],
                ];
            }
        }
    }
}
