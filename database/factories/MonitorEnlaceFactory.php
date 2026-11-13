<?php

namespace Database\Factories;

use App\Models\Empresa;
use App\Models\MonitorEnlace;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonitorEnlace>
 */
class MonitorEnlaceFactory extends Factory
{
    protected $model = MonitorEnlace::class;

    public function definition(): array
    {
        $empresa = Empresa::inRandomOrder()->first() ?? Empresa::create(['nombre' => 'Empresa ' . $this->faker->unique()->numberBetween(1, 99999), 'activo' => true]);
        $creador = User::inRandomOrder()->first() ?? User::factory()->create();

        return [
            'empresa_id'             => $empresa->id,
            'nombre'                 => $this->faker->randomElement(['Fibra principal', 'Respaldo', 'Enlace dedicado']) . ' ' . $this->faker->unique()->numberBetween(1, 999),
            'proveedor'              => $this->faker->randomElement(['ISP Uno', 'ISP Dos', 'ISP Tres']),
            'tipo_conexion'          => 'Fibra',
            'gateway'                => '192.168.' . $this->faker->numberBetween(0, 254) . '.1',
            'ip_publica'             => null,
            'asn'                    => (string) $this->faker->numberBetween(1000, 65000),
            'bajada_contratada_mbps' => 100,
            'subida_contratada_mbps' => 50,
            'principal'              => false,
            'con_cuota'              => false,
            'activo'                 => true,
            'creado_por'             => $creador->id,
        ];
    }

    public function principal(): static
    {
        return $this->state(['principal' => true]);
    }

    public function conCuota(): static
    {
        return $this->state(['tipo_conexion' => '4G / LTE', 'con_cuota' => true]);
    }
}
