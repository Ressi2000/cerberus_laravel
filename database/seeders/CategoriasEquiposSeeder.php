<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoriasEquiposSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = [
            ['nombre' => 'Laptop', 'asignable' => true],
            ['nombre' => 'Desktop', 'asignable' => true],
            ['nombre' => 'Servidor', 'asignable' => false],
            ['nombre' => 'Impresora', 'asignable' => false],
            ['nombre' => 'Switch', 'asignable' => false],
        ];

        foreach ($categorias as $categoria) {
            \App\Models\CategoriaEquipo::firstOrCreate($categoria);
        }
    }
}
