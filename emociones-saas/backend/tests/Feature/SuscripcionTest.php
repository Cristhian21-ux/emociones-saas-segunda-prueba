<?php

namespace Tests\Feature;

use App\Models\Suscripcion;
use Tests\TestCase;

class SuscripcionTest extends TestCase
{
    public function test_los_planes_son_publicos(): void
    {
        $this->getJson('/api/planes')->assertOk()->assertJsonCount(3);
    }

    public function test_admin_mejora_a_premium_anual(): void
    {
        $centro = $this->centro('gratuito');
        $this->comoUsuario('admin', $centro);

        $this->postJson('/api/suscripcion/cambiar', ['plan' => 'premium', 'ciclo' => 'anual'])
            ->assertOk()->assertJsonPath('plan.codigo', 'premium')->assertJsonPath('monto', '700.00');

        $this->assertSame('premium', $centro->fresh()->plan->codigo);
    }

    public function test_cambiar_de_plan_cancela_la_suscripcion_anterior(): void
    {
        $centro = $this->centro('gratuito');
        $this->comoUsuario('admin', $centro);

        $this->postJson('/api/suscripcion/cambiar', ['plan' => 'vip', 'ciclo' => 'mensual'])->assertOk();
        $this->postJson('/api/suscripcion/cambiar', ['plan' => 'premium', 'ciclo' => 'mensual'])->assertOk();

        $this->assertSame(1, Suscripcion::where('centro_id', $centro->id)->where('estado', 'activa')->count());
    }

    public function test_no_se_baja_de_plan_si_se_superan_los_limites(): void
    {
        $centro = $this->centro('vip');
        foreach (range(1, 3) as $i) {
            $this->usuario('psicologo', $centro);
        }
        $this->comoUsuario('admin', $centro);

        $this->postJson('/api/suscripcion/cambiar', ['plan' => 'gratuito', 'ciclo' => 'mensual'])->assertStatus(422);
    }

    public function test_cancelar_vuelve_al_plan_gratuito(): void
    {
        $centro = $this->centro('premium');
        $this->comoUsuario('admin', $centro);

        $this->postJson('/api/suscripcion/cancelar')->assertOk()->assertJsonPath('plan.codigo', 'gratuito');
    }

    public function test_consultar_uso_del_plan(): void
    {
        $centro = $this->centro('gratuito');
        $this->paciente($centro);
        $this->comoUsuario('admin', $centro);

        $this->getJson('/api/suscripcion')->assertOk()->assertJsonPath('uso.pacientes', 1)->assertJsonPath('uso.max_pacientes', 50);
    }

    public function test_plan_inexistente_se_rechaza(): void
    {
        $this->comoUsuario('admin');

        $this->postJson('/api/suscripcion/cambiar', ['plan' => 'oro', 'ciclo' => 'mensual'])->assertStatus(422);
    }
}
