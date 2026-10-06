<?php

namespace Database\Factories;

use App\Models\Centro;
use App\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Paciente>
 */
class PacienteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'centro_id' => Centro::factory(),
            'nombres' => fake('es_PE')->firstName(),
            'apellidos' => fake('es_PE')->lastName().' '.fake('es_PE')->lastName(),
            'dni' => (string) fake()->unique()->numberBetween(10000000, 99999999),
            'fecha_nacimiento' => fake()->dateTimeBetween('-60 years', '-12 years')->format('Y-m-d'),
            'telefono' => '9'.fake()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
        ];
    }
}
