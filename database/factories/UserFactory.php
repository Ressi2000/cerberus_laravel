<?php

namespace Database\Factories;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Empresa de nómina (obligatoria). Usa una existente o crea una.
            'empresa_id' => fn () => Empresa::query()->value('id')
                ?? Empresa::create(['nombre' => 'Empresa de prueba'])->id,
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'cedula' => 'V-' . fake()->unique()->numberBetween(1000000, 39999999),
            'email' => fake()->unique()->safeEmail(),
            'estado' => 'Activo',
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
