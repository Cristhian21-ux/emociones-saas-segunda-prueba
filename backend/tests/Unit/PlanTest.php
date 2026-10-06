<?php

namespace Tests\Unit;

use App\Models\Plan;
use App\Services\LimitesPlan;
use Tests\TestCase;

class PlanTest extends TestCase
{
    public function test_existen_tres_planes_ordenados(): void
    {
        $this->assertSame(['gratuito', 'vip', 'premium'], Plan::orderBy('orden')->pluck('codigo')->all());
    }

    public function test_solo_premium_incluye_analisis_de_emociones(): void
    {
        $this->assertFalse(Plan::where('codigo', 'vip')->first()->permite('analisis_emociones'));
        $this->assertTrue(Plan::where('codigo', 'premium')->first()->permite('analisis_emociones'));
    }

    public function test_precio_segun_ciclo(): void
    {
        $vip = Plan::where('codigo', 'vip')->first();

        $this->assertSame(15.0, $vip->precio('mensual'));
        $this->assertSame(150.0, $vip->precio('anual'));
    }

    public function test_plan_gratuito_limita_a_dos_psicologos(): void
    {
        $centro = $this->centro('gratuito');
        $this->usuario('psicologo', $centro);
        $this->assertTrue(LimitesPlan::puedeAgregarPsicologo($centro));

        $this->usuario('psicologo', $centro);
        $this->assertFalse(LimitesPlan::puedeAgregarPsicologo($centro->fresh()));
    }
}
