<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromIterator;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;

class UsuariosExport implements FromIterator, ShouldAutoSize
{
    protected $query;
 
    public function __construct($query)
    {
        $this->query = $query;
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // Encabezados + filas
    // ─────────────────────────────────────────────────────────────────────────
    public function iterator(): \Iterator
    {
        // ── FILA 1: Encabezados ──────────────────────────────────────────────
        yield [
            // Identificación
            'ID',
            'Nombre completo',
            'Username',
            'Cédula',
            'Ficha',
            'Email',
            'Teléfono',
 
            // Laboral
            'Empresa (nómina)',
            'Empresa activa',
            'Departamento',
            'Cargo',
            'Ubicación',
            'Es foráneo',
            'Jefe directo',
 
            // Acceso
            'Rol(es)',
            'Estado',
 
            // Empresas asignadas
            'Empresas asignadas',
 
            // Sistema
            'Fecha creación',
            'Última actualización',
        ];
 
        // ── FILAS DE DATOS ───────────────────────────────────────────────────
        // Usamos cursor() para no cargar todo en memoria (eficiente para muchos usuarios)
        foreach ($this->query->cursor() as $u) {
 
            // Empresas asignadas — lista separada por comas
            $empresasAsignadas = $u->empresasAsignadas
                ->pluck('nombre')
                ->join(', ');
 
            // Ubicación y flag foráneo
            $ubicacionNombre = $u->ubicacion?->nombre ?? '—';
            $esFora          = $u->ubicacion?->es_estado ? 'Sí' : 'No';
 
            yield [
                // Identificación
                $u->id,
                $u->name,
                $u->username,
                $u->cedula,
                $u->ficha     ?? '—',
                $u->email,
                $u->telefono  ?? '—',
 
                // Laboral
                $u->empresaNomina?->nombre  ?? '—',
                $u->empresaActiva?->nombre  ?? '—',
                $u->departamento?->nombre   ?? '—',
                $u->cargo?->nombre          ?? '—',
                $ubicacionNombre,
                $esFora,
                $u->jefe?->name             ?? '—',
 
                // Acceso
                $u->roles->pluck('name')->join(', ') ?: '—',
                $u->estado,
 
                // Empresas asignadas
                $empresasAsignadas ?: '—',
 
                // Sistema
                $u->created_at?->format('d/m/Y H:i') ?? '—',
                $u->updated_at?->format('d/m/Y H:i') ?? '—',
            ];
        }
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // Estilos: encabezado con fondo azul Cerberus + texto blanco + negrita
    // ─────────────────────────────────────────────────────────────────────────
    // public function styles(Worksheet $sheet): array
    // {
    //     return [
    //         // Fila 1 = encabezados
    //         1 => [
    //             'font' => [
    //                 'bold'  => true,
    //                 'color' => ['argb' => 'FFFFFFFF'], // blanco
    //             ],
    //             'fill' => [
    //                 'fillType'   => Fill::FILL_SOLID,
    //                 'startColor' => ['argb' => 'FF1E40AF'], // azul Cerberus
    //             ],
    //         ],
    //     ];
    // }
}
