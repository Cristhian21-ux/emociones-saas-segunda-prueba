<?php

namespace Tests\Feature;

use App\Models\Paciente;
use Tests\TestCase;

class PacienteTest extends TestCase
{
    public function test_recepcion_registra_un_paciente(): void
    {
        $this->comoUsuario('recepcionista');

        $this->postJson('/api/pacientes', [
            'nombres' => 'María', 'apellidos' => 'Torres Ríos', 'dni' => '45678912', 'telefono' => '987654321',
        ])->assertCreated()->assertJsonPath('nombre_completo', 'María Torres Ríos');
    }

    public function test_dni_debe_tener_ocho_digitos(): void
    {
        $this->comoUsuario('recepcionista');

        $this->postJson('/api/pacientes', ['nombres' => 'A', 'apellidos' => 'B', 'dni' => '123'])
            ->assertStatus(422)->assertJsonValidationErrors('dni');
    }

    public function test_dni_duplicado_en_el_mismo_centro_se_rechaza(): void
    {
        $centro = $this->centro();
        $this->paciente($centro, ['dni' => '11112222']);
        $this->comoUsuario('recepcionista', $centro);

        $this->postJson('/api/pacientes', ['nombres' => 'A', 'apellidos' => 'B', 'dni' => '11112222'])
            ->assertStatus(422)->assertJsonValidationErrors('dni');
    }

    public function test_telefono_debe_ser_celular_peruano(): void
    {
        $this->comoUsuario('recepcionista');

        $this->postJson('/api/pacientes', ['nombres' => 'A', 'apellidos' => 'B', 'dni' => '22223333', 'telefono' => '12345'])
            ->assertStatus(422)->assertJsonValidationErrors('telefono');
    }

    public function test_buscar_pacientes_por_apellido(): void
    {
        $centro = $this->centro();
        $this->paciente($centro, ['apellidos' => 'Quispe Mamani']);
        $this->paciente($centro, ['apellidos' => 'García López']);
        $this->comoUsuario('recepcionista', $centro);

        $this->getJson('/api/pacientes?buscar=Quispe')->assertOk()->assertJsonPath('total', 1);
    }

    public function test_actualizar_paciente(): void
    {
        $centro = $this->centro();
        $paciente = $this->paciente($centro);
        $this->comoUsuario('admin', $centro);

        $this->putJson("/api/pacientes/{$paciente->id}", [
            'nombres' => 'Nuevo', 'apellidos' => $paciente->apellidos, 'dni' => $paciente->dni,
        ])->assertOk()->assertJsonPath('nombres', 'Nuevo');
    }

    public function test_eliminar_paciente_es_borrado_logico(): void
    {
        $centro = $this->centro();
        $paciente = $this->paciente($centro);
        $this->comoUsuario('admin', $centro);

        $this->deleteJson("/api/pacientes/{$paciente->id}")->assertNoContent();
        $this->assertSoftDeleted('pacientes', ['id' => $paciente->id]);
    }

    public function test_psicologo_no_puede_registrar_pacientes(): void
    {
        $this->comoUsuario('psicologo');

        $this->postJson('/api/pacientes', ['nombres' => 'A', 'apellidos' => 'B', 'dni' => '33334444'])->assertForbidden();
    }

    public function test_plan_gratuito_limita_el_numero_de_pacientes(): void
    {
        $centro = $this->centro('gratuito');
        Paciente::factory()->count(50)->create(['centro_id' => $centro->id]);
        $this->comoUsuario('recepcionista', $centro);

        $this->postJson('/api/pacientes', ['nombres' => 'A', 'apellidos' => 'B', 'dni' => '44445555'])->assertStatus(402);
    }
}
