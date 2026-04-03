<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromIterator;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class AtributosExport implements FromIterator, ShouldAutoSize
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
            'Categoría',
            'Nombre',
            'Slug',
            'Tipo',
            'Requerido',
            'Filtrable',
            'Visible en tabla',
            'Orden',
            'Opciones (para tipo Lista)',
            'Total valores en equipos',
            'Fecha creación',
            'Última actualización',
        ];

        // ── Filas ────────────────────────────────────────────────────────────
        foreach ($this->query->cursor() as $atributo) {

            // Serializar opciones JSON como lista separada por " | "
            $opciones = '';
            if ($atributo->tipo === 'select' && ! empty($atributo->opciones)) {
                $opciones = implode(' | ', $atributo->opciones);
            }

            yield [
                $atributo->id,
                $atributo->categoria?->nombre    ?? '—',
                $atributo->nombre,
                $atributo->slug,
                $atributo->tipo,
                $atributo->requerido        ? 'Sí' : 'No',
                $atributo->filtrable        ? 'Sí' : 'No',
                $atributo->visible_en_tabla ? 'Sí' : 'No',
                $atributo->orden,
                $opciones ?: '—',
                $atributo->valores_count ?? 0,
                $atributo->created_at?->format('d/m/Y H:i') ?? '—',
                $atributo->updated_at?->format('d/m/Y H:i') ?? '—',
            ];
        }
    }
}