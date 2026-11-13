<?php

namespace Database\Seeders;

use App\Models\FamiliaComponente;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Familias de piezas iniciales: RAM y Discos (con ficha técnica) y "Otros" (para lo
 * que no encaje en ninguna). El resto se crea desde Configuración →
 * Familias de piezas.
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
                    ],
                    [
                        'id'             => (string) Str::uuid(),
                        'nombre'         => 'Tipo',
                        'tipo'           => 'select',
                        'opciones'       => ['DDR', 'DDR2', 'DDR2L', 'DDR3', 'DDR3L', 'DDR4', 'DDR4L', 'DDR5', 'DDR5L'],
                        'requerido'      => false,
                    ],
                    [
                        'id'             => (string) Str::uuid(),
                        'nombre'         => 'Formato',
                        'tipo'           => 'select',
                        'opciones'       => ['SO-DIMM (laptop)', 'DIMM (escritorio)'],
                        'requerido'      => false,
                    ],
                ],
            ]
        );

        // Discos: el tipo ya dice la forma física y la conexión (lo que hay
        // que saber para ponerlo en un equipo), en una sola lista.
        FamiliaComponente::firstOrCreate(
            ['nombre' => 'Discos'],
            [
                'descripcion' => 'Discos duros y de estado sólido de laptops, desktops y servidores.',
                'control'     => FamiliaComponente::CONTROL_UNIDAD,
                'activo'      => true,
                'campos'      => [
                    [
                        'id'        => (string) Str::uuid(),
                        'nombre'    => 'Capacidad (GB)',
                        'tipo'      => 'integer',
                        'opciones'  => [],
                        'requerido' => false,
                    ],
                    [
                        'id'        => (string) Str::uuid(),
                        'nombre'    => 'Tipo',
                        'tipo'      => 'select',
                        'opciones'  => ['HDD 2.5"', 'HDD 3.5"', 'SSD 2.5" SATA', 'M.2 SATA', 'M.2 NVMe', 'mSATA'],
                        'requerido' => false,
                    ],
                ],
            ]
        );

        // Red de seguridad: lo que no encaja en ninguna familia. Si con el
        // tiempo se juntan cosas parecidas, se crea su familia y se pasan.
        FamiliaComponente::firstOrCreate(
            ['nombre' => 'Otros'],
            [
                'descripcion' => 'Piezas que todavía no tienen una familia propia.',
                'control'     => FamiliaComponente::CONTROL_UNIDAD,
                'activo'      => true,
                'campos'      => [],
            ]
        );

        $this->command?->info('Familias de piezas: ' . FamiliaComponente::count());
    }
}
