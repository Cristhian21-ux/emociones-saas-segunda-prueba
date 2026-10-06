<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/** Planes del SaaS (precios en USD, mensual / anual). */
class PlanSeeder extends Seeder
{
    public const PLANES = [
        ['codigo' => 'gratuito', 'nombre' => 'Gratuito', 'precio_mensual' => 0, 'precio_anual' => 0, 'max_psicologos' => 2, 'max_pacientes' => 50, 'evaluaciones' => false, 'analisis_emociones' => false, 'orden' => 1],
        ['codigo' => 'vip', 'nombre' => 'VIP', 'precio_mensual' => 15, 'precio_anual' => 150, 'max_psicologos' => 10, 'max_pacientes' => 1000, 'evaluaciones' => true, 'analisis_emociones' => false, 'orden' => 2],
        ['codigo' => 'premium', 'nombre' => 'Premium', 'precio_mensual' => 70, 'precio_anual' => 700, 'max_psicologos' => 100, 'max_pacientes' => 100000, 'evaluaciones' => true, 'analisis_emociones' => true, 'orden' => 3],
    ];

    public function run(): void
    {
        foreach (self::PLANES as $plan) {
            Plan::updateOrCreate(['codigo' => $plan['codigo']], $plan);
        }
    }
}
