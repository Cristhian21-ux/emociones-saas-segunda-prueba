<?php

namespace Tests\Feature;

use Tests\TestCase;

class PersonalTest extends TestCase
{
    private function nuevo(array $extra = []): array
    {
        return $extra + ['name' => 'Ps. Nueva', 'email' => 'nueva@centro.test', 'role' => 'psicologo', 'especialidad' => 'Psicología clínica', 'password' => 'Clave12345'];
    }

    public function test_admin_agrega_un_psicologo(): void
    {
        $this->comoUsuario('admin');

        $this->postJson('/api/personal', $this->nuevo())->assertCreated()->assertJsonPath('role', 'psicologo');
    }

    public function test_psicologo_requiere_especialidad(): void
    {
        $this->comoUsuario('admin');

        $this->postJson('/api/personal', $this->nuevo(['especialidad' => null]))->assertStatus(422)->assertJsonValidationErrors('especialidad');
    }

    public function test_recepcion_no_puede_agregar_personal(): void
    {
        $this->comoUsuario('recepcionista');

        $this->postJson('/api/personal', $this->nuevo())->assertForbidden();
    }

    public function test_limite_de_psicologos_del_plan_gratuito(): void
    {
        $centro = $this->centro('gratuito');
        $this->usuario('psicologo', $centro);
        $this->usuario('psicologo', $centro);
        $this->comoUsuario('admin', $centro);

        $this->postJson('/api/personal', $this->nuevo())->assertStatus(402);
    }

    public function test_desactivar_personal_revoca_sus_tokens(): void
    {
        $centro = $this->centro();
        $this->comoUsuario('admin', $centro);
        $recepcion = $this->usuario('recepcionista', $centro);
        $recepcion->createToken('spa');

        $this->patchJson("/api/personal/{$recepcion->id}/activo")->assertOk()->assertJsonPath('activo', false);
        $this->assertSame(0, $recepcion->tokens()->count());
    }

    public function test_admin_no_se_desactiva_a_si_mismo(): void
    {
        $admin = $this->comoUsuario('admin');

        $this->patchJson("/api/personal/{$admin->id}/activo")->assertStatus(422);
    }

    public function test_no_se_gestiona_personal_de_otro_centro(): void
    {
        $this->comoUsuario('admin');
        $ajeno = $this->usuario('recepcionista');

        $this->patchJson("/api/personal/{$ajeno->id}/activo")->assertNotFound();
    }
}
