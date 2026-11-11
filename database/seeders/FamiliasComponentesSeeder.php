<?php

namespace Database\Seeders;

use App\Models\FamiliaComponente;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Familias de piezas iniciales. Por ahora solo RAM (fase 1); el resto se
 * crea desde Configuración → Familias de piezas.
 *
 * No pisa una familia que ya exista con el mismo nombre (se puede correr
 * varias veces sin perder cambios hechos desde la pantalla).
 */
class FamiliasComponentesSeeder extends Seeder
{
    public function run(): void
    {
        FamiliaComponente::firstOrCreate(
            ['nombre' => 'RAM'],
            [
                'descripcion' => 'Memoria RAM de laptops, desktops y servidores.',
                'control'     => FamiliaComponente::CONTROL_UNIDAD,
                'activo'      => true,
                'campos'      => [
                    [
                        'id'             => (string) Str::uuid(),
                        'nombre'         => 'Capacidad (GB)',
                        'tipo'           => 'integer',
                        'opciones'       => [],
                        'requerido'      => false,
                        'debe_coincidir' => false,
                    ],
                    [
                        'id'             => (string) Str::uuid(),
                        'nombre'         => 'Tipo',
                        'tipo'           => 'select',
                        'opciones'       => ['DDR', 'DDR2', 'DDR2L', 'DDR3', 'DDR3L', 'DDR4', 'DDR4L', 'DDR5', 'DDR5L'],
                        'requerido'      => false,
                        'debe_coincidir' => true,
                    ],
                    [
                        'id'             => (string) Str::uuid(),
                        'nombre'         => 'Formato',
                        'tipo'           => 'select',
                        'opciones'       => ['SO-DIMM (laptop)', 'DIMM (escritorio)'],
                        'requerido'      => false,
                        'debe_coincidir' => true,
                    ],
                ],
            ]
        );

        $this->command?->info('Familias de piezas: ' . FamiliaComponente::count());
    }
}
