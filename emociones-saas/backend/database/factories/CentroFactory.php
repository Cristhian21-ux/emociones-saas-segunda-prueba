<?php

namespace Database\Factories;

use App\Models\Centro;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Centro>
 */
class CentroFactory extends Factory
{
    public function definition(): array
    {
        $nombre = 'Centro '.fake()->unique()->lastName();

        return [
            'nombre' => $nombre,
            'slug' => Str::slug($nombre).'-'.Str::lower(Str::random(4)),
            'plan_id' => fn () => Plan::where('codigo', 'gratuito')->value('id'),
            'estado' => 'activo',
        ];
    }

    public function plan(string $codigo): static
    {
        return $this->state(fn () => ['plan_id' => Plan::where('codigo', $codigo)->value('id')]);
    }
}
