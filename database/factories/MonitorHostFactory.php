<?php

namespace Database\Factories;

use App\Models\Empresa;
use App\Models\MonitorHost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonitorHost>
 */
class MonitorHostFactory extends Factory
{
    protected $model = MonitorHost::class;

    public function definition(): array
    {
        $empresa = Empresa::inRandomOrder()->first() ?? Empresa::create(['nombre' => 'Empresa ' . $this->faker->unique()->numberBetween(1, 99999), 'activo' => true]);
        $creador = User::inRandomOrder()->first() ?? User::factory()->create();

        return [
            'empresa_id' => $empresa->id,
            'nombre'     => $this->faker->randomElement(['Router', 'Switch', 'Servidor', 'Firewall']) . ' ' . $this->faker->unique()->numberBetween(1, 9999),
            'host'       => $this->faker->unique()->localIpv4(),
            'tipo'       => $this->faker->randomElement(['Router', 'Switch', 'Servidor', 'Firewall', 'Access Point']),
            'proveedor'  => $this->faker->optional(0.6)->randomElement(['ISP 1', 'ISP 2', 'Enlace dedicado']),
            'puerto_tcp' => $this->faker->optional(0.3)->randomElement([22, 80, 443, 3389]),
            'pausado'    => false,
            'creado_por' => $creador->id,
        ];
    }

    public function activo(): static
    {
        return $this->state(fn () => [
            'estado' => MonitorHost::ESTADO_ACTIVO, 'estado_desde' => now()->subHour(),
            'latencia_ms' => 1.5, 'metodo' => 'ping', 'ultima_revision' => now()->subMinute(),
        ]);
    }

    public function caido(): static
    {
        return $this->state(fn () => [
            'estado' => MonitorHost::ESTADO_CAIDO, 'estado_desde' => now()->subMinutes(10),
            'fallos_consecutivos' => 10, 'metodo' => 'ping', 'ultima_revision' => now()->subMinute(),
        ]);
    }

    public function pausado(): static
    {
        return $this->state(fn () => ['pausado' => true]);
    }
}
