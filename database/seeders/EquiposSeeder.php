<?php

namespace Database\Seeders;

use App\Models\AtributoEquipo;
use App\Models\CategoriaEquipo;
use App\Models\Empresa;
use Illuminate\Support\Str;
use App\Models\EquipoAtributoValor;
use App\Models\Equipos;
use App\Models\EstadoEquipo;
use App\Models\Ubicacion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EquiposSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $empresas   = Empresa::all();
        $categorias = CategoriaEquipo::with('atributos')->get();
        $estados    = EstadoEquipo::all();
        $ubicaciones = Ubicacion::all();
        $usuarios   = User::all();

        if (
            $empresas->isEmpty() ||
            $categorias->isEmpty() ||
            $estados->isEmpty() ||
            $ubicaciones->isEmpty() ||
            $usuarios->isEmpty()
        ) {
            $this->command->warn('Faltan datos base. Seeder cancelado.');
            return;
        }

        foreach ($empresas as $empresa) {

            foreach ($categorias as $categoria) {

                $atributos = AtributoEquipo::where('categoria_id', $categoria->id)->get();

                $cantidad = 20; // Cambia a 100 si quieres carga pesada

                for ($i = 1; $i <= $cantidad; $i++) {

                    $fechaAdquisicion = Carbon::now()->subYears(rand(0,5))->subDays(rand(0,365));

                    $equipo = Equipos::create([
                        'empresa_id'          => $empresa->id,
                        'categoria_id'        => $categoria->id,
                        'estado_id'           => $estados->random()->id,
                        'ubicacion_id'        => $ubicaciones->random()->id,
                        'codigo_interno'      => strtoupper(Str::random(4)) . '-' . rand(1000,9999),
                        'serial'              => strtoupper(Str::random(10)),
                        'nombre_maquina'      => $categoria->nombre . ' ' . rand(1,200),
                        'fecha_adquisicion'   => $fechaAdquisicion,
                        'fecha_garantia_fin'  => $fechaAdquisicion->copy()->addYears(1),
                        'activo'              => true,
                        'observaciones'       => 'Equipo generado automáticamente para pruebas.'
                    ]);

                    foreach ($atributos as $atributo) {

                        EquipoAtributoValor::create([
                            'equipo_id'   => $equipo->id,
                            'atributo_id' => $atributo->id,
                            'valor'       => $this->generarValor($atributo->tipo),
                            'es_actual'   => true,
                            'creado_por'  => $usuarios->random()->id,
                        ]);
                    }
                }
            }
        }

        $this->command->info('Equipos generados correctamente.');
    }

    private function generarValor(string $tipo)
    {
        return match ($tipo) {

            'string'   => Str::random(8),

            'integer'  => rand(1, 128),

            'decimal'  => rand(100, 8000) / 10,

            'boolean'  => rand(0, 1),

            'date'     => now()->subDays(rand(1,1000))->format('Y-m-d'),

            default    => Str::random(6),
        };
    }
}
