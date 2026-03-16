<?php

namespace Database\Seeders;

use App\Models\AtributoEquipo;
use App\Models\CategoriaEquipo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AtributosEquiposSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedLaptop();
        $this->seedDesktop();
        $this->seedServidor();
        $this->seedImpresora();
        $this->seedSwitch();
    }

    private function seedLaptop()
    {
        $categoria = CategoriaEquipo::where('nombre', 'Laptop')->first();

        if (!$categoria) return;

        $atributos = [
            ['Marca', 'string', true, true],
            ['Modelo', 'string', true, true],
            ['Procesador', 'string', true, true],
            ['RAM (GB)', 'integer', true, true],
            ['Disco (GB)', 'integer', true, true],
            ['Tipo de Disco', 'string', false, false],
            ['Sistema Operativo', 'string', false, true],
            ['IMEI', 'string', false, false],
            ['MAC Address', 'string', false, false],
        ];

        $this->insertAtributos($categoria->id, $atributos);
    }

    private function seedDesktop()
    {
        $categoria = CategoriaEquipo::where('nombre', 'Desktop')->first();
        if (!$categoria) return;

        $atributos = [
            ['Marca', 'string', true, true],
            ['Modelo', 'string', true, true],
            ['Procesador', 'string', true, true],
            ['RAM (GB)', 'integer', true, true],
            ['Disco (GB)', 'integer', true, true],
            ['Tipo de Disco', 'string', false, false],
            ['Sistema Operativo', 'string', false, true],
            ['Tarjeta de Video', 'string', false, false],
            ['MAC Address', 'string', false, false],
        ];

        $this->insertAtributos($categoria->id, $atributos);
    }

    private function seedServidor()
    {
        $categoria = CategoriaEquipo::where('nombre', 'Servidor')->first();
        if (!$categoria) return;

        $atributos = [
            ['Marca', 'string', true, true],
            ['Modelo', 'string', true, true],
            ['Procesador', 'string', true, true],
            ['RAM (GB)', 'integer', true, true],
            ['Almacenamiento Total (TB)', 'decimal', true, true],
            ['RAID Configurado', 'string', false, false],
            ['Sistema Operativo', 'string', false, true],
            ['IP Principal', 'string', false, true],
        ];

        $this->insertAtributos($categoria->id, $atributos);
    }

    private function seedImpresora()
    {
        $categoria = CategoriaEquipo::where('nombre', 'Impresora')->first();
        if (!$categoria) return;

        $atributos = [
            ['Marca', 'string', true, true],
            ['Modelo', 'string', true, true],
            ['Tipo', 'string', true, true], // Láser, Inkjet
            ['Color', 'boolean', false, true],
            ['IP', 'string', false, true],
            ['Número de Serie del Fusor', 'string', false, false],
        ];

        $this->insertAtributos($categoria->id, $atributos);
    }

    private function seedSwitch()
    {
        $categoria = CategoriaEquipo::where('nombre', 'Switch')->first();
        if (!$categoria) return;

        $atributos = [
            ['Marca', 'string', true, true],
            ['Modelo', 'string', true, true],
            ['Cantidad de Puertos', 'integer', true, true],
            ['Administrable', 'boolean', false, true],
            ['Velocidad (Gbps)', 'decimal', false, true],
            ['IP Gestión', 'string', false, true],
        ];

        $this->insertAtributos($categoria->id, $atributos);
    }

    private function insertAtributos($categoriaId, $atributos)
    {
        $orden = 1;

        foreach ($atributos as [$nombre, $tipo, $requerido, $filtrable]) {

            AtributoEquipo::firstOrCreate(
                [
                    'categoria_id' => $categoriaId,
                    'slug' => Str::slug($nombre),
                ],
                [
                    'nombre' => $nombre,
                    'tipo' => $tipo,
                    'requerido' => $requerido,
                    'filtrable' => $filtrable,
                    'visible_en_tabla' => true,
                    'orden' => $orden++,
                ]
            );
        }
    }
}
