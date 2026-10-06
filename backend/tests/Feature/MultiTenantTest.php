<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Aislamiento de datos entre centros (tenants) del SaaS. */
class MultiTenantTest extends TestCase
{
    public function test_un_centro_no_ve_los_pacientes_de_otro(): void
    {
        $centroA = $this->centro();
        $centroB = $this->centro();
        $this->paciente($centroA);
        $this->paciente($centroB);
        $this->paciente($centroB);

        $this->comoUsuario('recepcionista', $centroA);

        $this->getJson('/api/pacientes')->assertOk()->assertJsonPath('total', 1);
    }

    public function test_no_se_puede_abrir_un_paciente_de_otro_centro(): void
    {
        $ajeno = $this->paciente($this->centro());
        $this->comoUsuario('recepcionista');

        $this->getJson("/api/pacientes/{$ajeno->id}")->assertNotFound();
    }

    public function test_no_se_puede_agendar_con_un_psicologo_de_otro_centro(): void
    {
        $centro = $this->centro();
        $this->comoUsuario('recepcionista', $centro);
        $psicologoAjeno = $this->usuario('psicologo');

        $this->postJson('/api/citas', [
            'paciente_id' => $this->paciente($centro)->id,
            'psicologo_id' => $psicologoAjeno->id,
            'fecha' => $this->diaHabil(),
            'hora' => '10:00',
            'motivo' => 'Consulta',
        ])->assertStatus(422)->assertJsonValidationErrors('psicologo_id');
    }

    public function test_el_mismo_dni_puede_existir_en_centros_distintos(): void
    {
        $this->paciente($this->centro(), ['dni' => '12345678']);
        $this->comoUsuario('recepcionista');

        $this->postJson('/api/pacientes', ['nombres' => 'Luis', 'apellidos' => 'Rojas', 'dni' => '12345678'])->assertCreated();
    }

    public function test_un_paciente_nuevo_se_asocia_al_centro_del_usuario(): void
    {
        $centro = $this->centro();
        $this->comoUsuario('recepcionista', $centro);

        $id = $this->postJson('/api/pacientes', ['nombres' => 'Luis', 'apellidos' => 'Rojas', 'dni' => '87654321'])->json('id');

        $this->assertDatabaseHas('pacientes', ['id' => $id, 'centro_id' => $centro->id]);
    }
}
