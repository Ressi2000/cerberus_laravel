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
            'Disponible',
            'Asignado',
            'En préstamo',
            'En reparación',
            'Dado de baja',
            'No asignable'
        ];

        foreach ($estados as $estado) {
            \App\Models\EstadoEquipo::firstOrCreate([
                'nombre' => $estado
            ]);
        }
    }
}
