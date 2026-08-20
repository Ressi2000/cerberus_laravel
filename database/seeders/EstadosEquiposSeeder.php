<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EstadosEquiposSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $estados = [
            'Disponible'    => '#22C55E',
            'Asignado'      => '#3B82F6',
            'En préstamo'   => '#F59E0B',
            'En reparación' => '#F97316',
            'Dado de baja'  => '#EF4444',
            'No asignable'  => '#64748B',
        ];

        foreach ($estados as $nombre => $color) {
            \App\Models\EstadoEquipo::firstOrCreate(
                ['nombre' => $nombre],
                ['color' => $color]
            );
        }
    }
}
